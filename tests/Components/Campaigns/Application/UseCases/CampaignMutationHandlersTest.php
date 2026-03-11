<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\UseCases;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignActivatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignClosedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeactivatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignOpenedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignRenamedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignTargetChangedEvent;
use Fundrik\Core\Components\Campaigns\Application\Exceptions\CampaignApplicationException;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\UseCases\AbstractCampaignMutationHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ActivateCampaign\ActivateCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutation;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationPreconditionReason;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CloseCampaign\CloseCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeactivateCampaign\DeactivateCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdUseCase;
use Fundrik\Core\Components\Campaigns\Application\UseCases\OpenCampaign\OpenCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign\RenameCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SetCampaignTargetAmount\SetCampaignTargetAmountHandler;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Tests\Fixtures\FakeApplicationEventBusException;
use Fundrik\Core\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( AbstractCampaignMutationHandler::class )]
#[CoversClass( RenameCampaignHandler::class )]
#[CoversClass( ActivateCampaignHandler::class )]
#[CoversClass( DeactivateCampaignHandler::class )]
#[CoversClass( OpenCampaignHandler::class )]
#[CoversClass( CloseCampaignHandler::class )]
#[CoversClass( SetCampaignTargetAmountHandler::class )]
#[UsesClass( CampaignMutationException::class )]
#[UsesClass( CampaignMutationPreconditionReason::class )]
#[UsesClass( CampaignMutation::class )]
#[UsesClass( FindCampaignByIdException::class )]
#[UsesClass( CampaignApplicationException::class )]
#[UsesClass( FundrikApplicationException::class )]
#[UsesClass( CampaignRenamedEvent::class )]
#[UsesClass( CampaignActivatedEvent::class )]
#[UsesClass( CampaignDeactivatedEvent::class )]
#[UsesClass( CampaignOpenedEvent::class )]
#[UsesClass( CampaignClosedEvent::class )]
#[UsesClass( CampaignTargetChangedEvent::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Money::class )]
final class CampaignMutationHandlersTest extends MockeryTestCase {

	private FindCampaignByIdUseCase&MockInterface $find_campaign_by_id;
	private CampaignRepositoryPort&MockInterface $campaigns;
	private ApplicationEventBusPort&MockInterface $event_bus;

	protected function setUp(): void {

		parent::setUp();

		$this->find_campaign_by_id = Mockery::mock( FindCampaignByIdUseCase::class );
		$this->campaigns = Mockery::mock( CampaignRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );
	}

	#[Test]
	#[DataProvider( 'successful_action_provider' )]
	public function handle_applies_campaign_action_persists_result_and_publishes_event(
		string $action,
		string $event_class,
	): void {

		$campaign_id = EntityId::create( 1 );
		$campaign = $this->make_campaign_for_action( $action );
		$handler = $this->make_handler( $action );

		$this->find_campaign_by_id
			->shouldReceive( 'handle' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( $campaign );

		$this->campaigns
			->shouldReceive( 'update' )
			->once()
			->withArgs(
				function ( Campaign $updated_campaign ) use ( $action ): bool {

					$this->assert_campaign_action_result( $action, $updated_campaign );

					return true;
				},
			)
			->andReturnUsing( static fn ( Campaign $updated_campaign ): Campaign => $updated_campaign );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs(
				function ( object $event ) use ( $campaign_id, $event_class ): bool {

					$this->assertInstanceOf( $event_class, $event );
					$this->assertTrue( $event->get_campaign_id()->equals( $campaign_id ) );

					return true;
				},
			);

		$result = $this->invoke_handler( $handler, $action, $campaign_id );

		$this->assert_campaign_action_result( $action, $result );
	}

	#[Test]
	public function handle_throws_when_campaign_lookup_fails(): void {

		$campaign_id = EntityId::create( 1 );
		$handler = $this->make_handler( 'rename' );
		$e = new FindCampaignByIdException(
			'Failed to retrieve campaign "1" by ID.',
			previous: new FakeCampaignRepositoryException(),
		);

		$this->find_campaign_by_id
			->shouldReceive( 'handle' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andThrow( $e );

		$this->campaigns
			->shouldNotReceive( 'update' );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		try {
			$this->invoke_handler( $handler, 'rename', $campaign_id );
			$this->fail( 'Expected CampaignMutationException to be thrown.' );
		} catch ( CampaignMutationException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame( CampaignMutationPreconditionReason::CampaignLookupFailed, $exception->get_reason() );
			$this->assertSame( 'Failed to retrieve campaign "1".', $exception->getMessage() );
			$this->assertSame( $e, $exception->getPrevious() );
		}
	}

	#[Test]
	#[DataProvider( 'action_phrase_provider' )]
	public function handle_throws_when_campaign_does_not_exist( string $action, string $phrase ): void {

		$campaign_id = EntityId::create( 1 );
		$handler = $this->make_handler( $action );

		$this->find_campaign_by_id
			->shouldReceive( 'handle' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( null );

		$this->campaigns
			->shouldNotReceive( 'update' );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		try {
			$this->invoke_handler( $handler, $action, $campaign_id );
			$this->fail( 'Expected CampaignMutationException to be thrown.' );
		} catch ( CampaignMutationException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame( CampaignMutationPreconditionReason::CampaignNotFound, $exception->get_reason() );
			$this->assertSame(
				sprintf( 'Cannot %s campaign "1": campaign does not exist.', $phrase ),
				$exception->getMessage(),
			);
		}
	}

	#[Test]
	#[DataProvider( 'action_phrase_provider' )]
	public function handle_throws_when_campaign_action_is_rejected( string $action, string $phrase ): void {

		$campaign_id = EntityId::create( 1 );
		$campaign = $this->make_rejected_campaign_for_action( $action );
		$handler = $this->make_handler( $action );

		$this->find_campaign_by_id
			->shouldReceive( 'handle' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( $campaign );

		$this->campaigns
			->shouldNotReceive( 'update' );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		try {
			$this->invoke_rejected_handler( $handler, $action, $campaign_id );
			$this->fail( 'Expected CampaignMutationException to be thrown.' );
		} catch ( CampaignMutationException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame( CampaignMutationPreconditionReason::CampaignMutationRejected, $exception->get_reason() );
			$this->assertSame(
				sprintf( 'Cannot %s campaign "1": change was rejected.', $phrase ),
				$exception->getMessage(),
			);
			$this->assertNotNull( $exception->getPrevious() );
		}
	}

	#[Test]
	public function handle_wraps_campaign_persistence_failure(): void {

		$campaign_id = EntityId::create( 1 );
		$campaign = $this->make_campaign( title: 'Old Title' );
		$handler = $this->make_handler( 'rename' );
		$e = new FakeCampaignRepositoryException();

		$this->find_campaign_by_id
			->shouldReceive( 'handle' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( $campaign );

		$this->campaigns
			->shouldReceive( 'update' )
			->once()
			->withArgs(
				function ( Campaign $updated_campaign ): bool {

					$this->assertSame( 'Renamed Campaign', $updated_campaign->get_title() );

					return true;
				},
			)
			->andThrow( $e );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		try {
			$this->invoke_handler( $handler, 'rename', $campaign_id );
			$this->fail( 'Expected CampaignMutationException to be thrown.' );
		} catch ( CampaignMutationException $exception ) {
			$this->assertSame( UseCaseFailureStage::Persistence, $exception->get_stage() );
			$this->assertNull( $exception->get_reason() );
			$this->assertSame( 'Failed to rename campaign "1".', $exception->getMessage() );
			$this->assertSame( $e, $exception->getPrevious() );
		}
	}

	#[Test]
	#[DataProvider( 'event_publish_provider' )]
	public function handle_wraps_event_publish_failure(
		string $action,
		string $event_label,
		string $past_participle,
	): void {

		$campaign_id = EntityId::create( 1 );
		$campaign = $this->make_campaign_for_action( $action );
		$handler = $this->make_handler( $action );
		$e = new FakeApplicationEventBusException();

		$this->find_campaign_by_id
			->shouldReceive( 'handle' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( $campaign );

		$this->campaigns
			->shouldReceive( 'update' )
			->once()
			->withArgs(
				function ( Campaign $updated_campaign ) use ( $action ): bool {

					$this->assert_campaign_action_result( $action, $updated_campaign );

					return true;
				},
			)
			->andReturnUsing( static fn ( Campaign $updated_campaign ): Campaign => $updated_campaign );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->andThrow( $e );

		try {
			$this->invoke_handler( $handler, $action, $campaign_id );
			$this->fail( 'Expected CampaignMutationException to be thrown.' );
		} catch ( CampaignMutationException $exception ) {
			$this->assertSame( UseCaseFailureStage::EventPublish, $exception->get_stage() );
			$this->assertNull( $exception->get_reason() );
			$this->assertSame(
				sprintf(
					'Campaign "1" was %s, but publishing the %s event failed.',
					$past_participle,
					$event_label,
				),
				$exception->getMessage(),
			);
			$this->assertSame( $e, $exception->getPrevious() );
		}
	}

	public static function successful_action_provider(): array {

		return [
			'rename' => [ 'rename', CampaignRenamedEvent::class ],
			'activate' => [ 'activate', CampaignActivatedEvent::class ],
			'deactivate' => [ 'deactivate', CampaignDeactivatedEvent::class ],
			'open' => [ 'open', CampaignOpenedEvent::class ],
			'close' => [ 'close', CampaignClosedEvent::class ],
			'set_target_amount' => [ 'set_target_amount', CampaignTargetChangedEvent::class ],
		];
	}

	public static function action_phrase_provider(): array {

		return [
			'rename' => [ 'rename', 'rename' ],
			'activate' => [ 'activate', 'activate' ],
			'deactivate' => [ 'deactivate', 'deactivate' ],
			'open' => [ 'open', 'open' ],
			'close' => [ 'close', 'close' ],
			'set_target_amount' => [ 'set_target_amount', 'set target amount for' ],
		];
	}

	public static function event_publish_provider(): array {

		return [
			'rename' => [ 'rename', 'renamed', 'renamed' ],
			'activate' => [ 'activate', 'activated', 'activated' ],
			'deactivate' => [ 'deactivate', 'deactivated', 'deactivated' ],
			'open' => [ 'open', 'opened', 'opened' ],
			'close' => [ 'close', 'closed', 'closed' ],
			'set_target_amount' => [ 'set_target_amount', 'target changed', 'updated' ],
		];
	}

	private function make_handler( string $action ): object {

		// phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
		return match ( $action ) {
			'rename' => new RenameCampaignHandler( $this->find_campaign_by_id, $this->campaigns, $this->event_bus ),
			'activate' => new ActivateCampaignHandler( $this->find_campaign_by_id, $this->campaigns, $this->event_bus ),
			'deactivate' => new DeactivateCampaignHandler( $this->find_campaign_by_id, $this->campaigns, $this->event_bus ),
			'open' => new OpenCampaignHandler( $this->find_campaign_by_id, $this->campaigns, $this->event_bus ),
			'close' => new CloseCampaignHandler( $this->find_campaign_by_id, $this->campaigns, $this->event_bus ),
			'set_target_amount' => new SetCampaignTargetAmountHandler( $this->find_campaign_by_id, $this->campaigns, $this->event_bus ),
		};
		// phpcs:enable
	}

	private function make_campaign_for_action( string $action ): Campaign {

		return match ( $action ) {
			'rename' => $this->make_campaign( title: 'Old Title' ),
			'activate' => $this->make_campaign( is_active: false ),
			'deactivate' => $this->make_campaign( is_active: true ),
			'open' => $this->make_campaign( is_open: false ),
			'close' => $this->make_campaign( is_open: true ),
			'set_target_amount' => $this->make_campaign( has_target: true, target_amount: 100 ),
		};
	}

	private function make_rejected_campaign_for_action( string $action ): Campaign {

		return match ( $action ) {
			'rename' => $this->make_campaign( title: 'Test Campaign' ),
			'activate' => $this->make_campaign( is_active: true ),
			'deactivate' => $this->make_campaign( is_active: false ),
			'open' => $this->make_campaign( is_open: true ),
			'close' => $this->make_campaign( is_open: false ),
			'set_target_amount' => $this->make_campaign( has_target: true, target_amount: 100 ),
		};
	}

	private function invoke_handler( object $handler, string $action, EntityId $campaign_id ): Campaign {

		return match ( $action ) {
			'rename' => $handler->handle( $campaign_id, 'Renamed Campaign' ),
			'activate', 'deactivate', 'open', 'close' => $handler->handle( $campaign_id ),
			'set_target_amount' => $handler->handle( $campaign_id, 250 ),
		};
	}

	private function invoke_rejected_handler( object $handler, string $action, EntityId $campaign_id ): Campaign {

		return match ( $action ) {
			'rename' => $handler->handle( $campaign_id, 'Test Campaign' ),
			'activate', 'deactivate', 'open', 'close' => $handler->handle( $campaign_id ),
			'set_target_amount' => $handler->handle( $campaign_id, 100 ),
		};
	}

	private function assert_campaign_action_result( string $action, Campaign $campaign ): void {

		$this->assertSame( 1, $campaign->get_id()->get_value() );

		match ( $action ) {
			'rename' => $this->assertSame( 'Renamed Campaign', $campaign->get_title() ),
			'activate' => $this->assertTrue( $campaign->is_active() ),
			'deactivate' => $this->assertFalse( $campaign->is_active() ),
			'open' => $this->assertTrue( $campaign->is_open() ),
			'close' => $this->assertFalse( $campaign->is_open() ),
			'set_target_amount' => $this->assertSame( 250, $campaign->get_target_money()->get_amount_minor() ),
		};
	}
}
