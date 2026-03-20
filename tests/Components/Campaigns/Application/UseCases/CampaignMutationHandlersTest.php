<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\UseCases;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignClosedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignOpenedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignRenamedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignTargetChangedEvent;
use Fundrik\Core\Components\Campaigns\Application\Exceptions\CampaignApplicationException;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\UseCases\AbstractCampaignMutationHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutation;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationPreconditionReason;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ChangeCampaignTarget\ChangeCampaignTargetException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ChangeCampaignTarget\ChangeCampaignTargetHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ChangeCampaignTarget\ChangeCampaignTargetNotFoundException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CloseCampaign\CloseCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CloseCampaign\CloseCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CloseCampaign\CloseCampaignNotFoundException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\OpenCampaign\OpenCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\OpenCampaign\OpenCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\OpenCampaign\OpenCampaignNotFoundException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign\RenameCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign\RenameCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign\RenameCampaignNotFoundException;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\Amount;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Tests\Fixtures\FakeApplicationEventBusException;
use Fundrik\Core\Tests\Fixtures\FakeCampaignNotFoundException;
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
#[CoversClass( OpenCampaignHandler::class )]
#[CoversClass( CloseCampaignHandler::class )]
#[CoversClass( ChangeCampaignTargetHandler::class )]
#[UsesClass( CampaignMutationException::class )]
#[UsesClass( RenameCampaignException::class )]
#[UsesClass( RenameCampaignNotFoundException::class )]
#[UsesClass( OpenCampaignException::class )]
#[UsesClass( OpenCampaignNotFoundException::class )]
#[UsesClass( CloseCampaignException::class )]
#[UsesClass( CloseCampaignNotFoundException::class )]
#[UsesClass( ChangeCampaignTargetException::class )]
#[UsesClass( ChangeCampaignTargetNotFoundException::class )]
#[UsesClass( CampaignMutationPreconditionReason::class )]
#[UsesClass( CampaignMutation::class )]
#[UsesClass( CampaignApplicationException::class )]
#[UsesClass( FundrikApplicationException::class )]
#[UsesClass( CampaignRenamedEvent::class )]
#[UsesClass( CampaignOpenedEvent::class )]
#[UsesClass( CampaignClosedEvent::class )]
#[UsesClass( CampaignTargetChangedEvent::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( Amount::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
final class CampaignMutationHandlersTest extends MockeryTestCase {

	private CampaignRepositoryPort&MockInterface $campaigns;
	private ApplicationEventBusPort&MockInterface $event_bus;

	protected function setUp(): void {

		parent::setUp();

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

		$this->campaigns
			->shouldReceive( 'find_by_id' )
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
		$e = new FakeCampaignRepositoryException();

		$this->campaigns
			->shouldReceive( 'find_by_id' )
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
			$this->assertInstanceOf( RenameCampaignException::class, $exception );
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame( CampaignMutationPreconditionReason::CampaignLookupFailed, $exception->get_reason() );
			$this->assertSame( 'Failed to retrieve campaign "1".', $exception->getMessage() );
			$this->assertSame( $e, $exception->getPrevious() );
		}
	}

	#[Test]
	#[DataProvider( 'action_phrase_provider' )]
	public function handle_throws_when_campaign_does_not_exist(
		string $action,
		string $phrase,
		string $exception_class,
	): void {

		$campaign_id = EntityId::create( 1 );
		$handler = $this->make_handler( $action );

		$this->campaigns
			->shouldReceive( 'find_by_id' )
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
			$this->assertInstanceOf( $exception_class, $exception );
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
	public function handle_throws_when_campaign_action_is_rejected(
		string $action,
		string $phrase,
		string $exception_class,
	): void {

		$campaign_id = EntityId::create( 1 );
		$campaign = $this->make_rejected_campaign_for_action( $action );
		$handler = $this->make_handler( $action );

		$this->campaigns
			->shouldReceive( 'find_by_id' )
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
			$this->assertInstanceOf( $exception_class, $exception );
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame( CampaignMutationPreconditionReason::CampaignMutationRejected, $exception->get_reason() );
			$this->assertSame(
				$action === 'change_target'
					? 'Target amount must be different from the current one.'
					: sprintf( 'Cannot %s campaign "1": change was rejected.', $phrase ),
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

		$this->campaigns
			->shouldReceive( 'find_by_id' )
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
			$this->assertInstanceOf( RenameCampaignException::class, $exception );
			$this->assertSame( UseCaseFailureStage::Persistence, $exception->get_stage() );
			$this->assertNull( $exception->get_reason() );
			$this->assertSame( 'Failed to rename campaign "1".', $exception->getMessage() );
			$this->assertSame( $e, $exception->getPrevious() );
		}
	}

	#[Test]
	#[DataProvider( 'persistence_not_found_provider' )]
	public function handle_throws_when_campaign_disappears_before_persist(
		string $action,
		string $phrase,
		string $exception_class,
	): void {

		$campaign_id = EntityId::create( 1 );
		$campaign = $this->make_campaign_for_action( $action );
		$handler = $this->make_handler( $action );
		$e = new FakeCampaignNotFoundException();

		$this->campaigns
			->shouldReceive( 'find_by_id' )
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
			->andThrow( $e );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		try {
			$this->invoke_handler( $handler, $action, $campaign_id );
			$this->fail( 'Expected CampaignMutationException to be thrown.' );
		} catch ( CampaignMutationException $exception ) {
			$this->assertInstanceOf( $exception_class, $exception );
			$this->assertSame( UseCaseFailureStage::Persistence, $exception->get_stage() );
			$this->assertNull( $exception->get_reason() );
			$this->assertSame(
				sprintf( 'Cannot %s campaign "1": campaign does not exist.', $phrase ),
				$exception->getMessage(),
			);
			$this->assertSame( $e, $exception->getPrevious() );
		}
	}

	#[Test]
	#[DataProvider( 'event_publish_provider' )]
	public function handle_wraps_event_publish_failure(
		string $action,
		string $event_label,
		string $past_participle,
		string $exception_class,
	): void {

		$campaign_id = EntityId::create( 1 );
		$campaign = $this->make_campaign_for_action( $action );
		$handler = $this->make_handler( $action );
		$e = new FakeApplicationEventBusException();

		$this->campaigns
			->shouldReceive( 'find_by_id' )
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
			$this->assertInstanceOf( $exception_class, $exception );
			$this->assertSame( UseCaseFailureStage::EventPublish, $exception->get_stage() );
			$this->assertNull( $exception->get_reason() );
			$this->assertSame(
				sprintf(
					'Campaign "%s" was %s, but publishing the %s event failed.',
					(string) $campaign_id->get_value(),
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
			'open' => [ 'open', CampaignOpenedEvent::class ],
			'close' => [ 'close', CampaignClosedEvent::class ],
			'change_target' => [ 'change_target', CampaignTargetChangedEvent::class ],
		];
	}

	public static function action_phrase_provider(): array {

		return [
			'rename' => [ 'rename', 'rename', RenameCampaignException::class ],
			'open' => [ 'open', 'open', OpenCampaignException::class ],
			'close' => [ 'close', 'close', CloseCampaignException::class ],
			'change_target' => [ 'change_target', 'change target for', ChangeCampaignTargetException::class ],
		];
	}

	public static function event_publish_provider(): array {

		return [
			'rename' => [ 'rename', 'renamed', 'renamed', RenameCampaignException::class ],
			'open' => [ 'open', 'opened', 'opened', OpenCampaignException::class ],
			'close' => [ 'close', 'closed', 'closed', CloseCampaignException::class ],
			'change_target' => [ 'change_target', 'target changed', 'updated', ChangeCampaignTargetException::class ],
		];
	}

	public static function persistence_not_found_provider(): array {

		return [
			'rename' => [ 'rename', 'rename', RenameCampaignNotFoundException::class ],
			'open' => [ 'open', 'open', OpenCampaignNotFoundException::class ],
			'close' => [ 'close', 'close', CloseCampaignNotFoundException::class ],
			'change_target' => [ 'change_target', 'change target for', ChangeCampaignTargetNotFoundException::class ],
		];
	}

	private function make_handler( string $action ): object {

		return match ( $action ) {
			'rename' => new RenameCampaignHandler( $this->campaigns, $this->event_bus ),
			'open' => new OpenCampaignHandler( $this->campaigns, $this->event_bus ),
			'close' => new CloseCampaignHandler( $this->campaigns, $this->event_bus ),
			'change_target' => new ChangeCampaignTargetHandler( $this->campaigns, $this->event_bus ),
		};
	}

	private function make_campaign_for_action( string $action ): Campaign {

		return match ( $action ) {
			'rename' => $this->make_campaign( title: 'Old Title' ),
			'open' => $this->make_campaign( is_open: false ),
			'close' => $this->make_campaign( is_open: true ),
			'change_target' => $this->make_campaign( target_amount: 100 ),
		};
	}

	private function make_rejected_campaign_for_action( string $action ): Campaign {

		return match ( $action ) {
			'rename' => $this->make_campaign( title: 'Test Campaign' ),
			'open' => $this->make_campaign( is_open: true ),
			'close' => $this->make_campaign( is_open: false ),
			'change_target' => $this->make_campaign( target_amount: 100 ),
		};
	}

	private function invoke_handler( object $handler, string $action, EntityId $campaign_id ): Campaign {

		return match ( $action ) {
			'rename' => $handler->handle( $campaign_id, CampaignTitle::create( 'Renamed Campaign' ) ),
			'open', 'close' => $handler->handle( $campaign_id ),
			'change_target' => $handler->handle( $campaign_id, Amount::create( 250 ) ),
		};
	}

	private function invoke_rejected_handler( object $handler, string $action, EntityId $campaign_id ): Campaign {

		return match ( $action ) {
			'rename' => $handler->handle( $campaign_id, CampaignTitle::create( 'Test Campaign' ) ),
			'open', 'close' => $handler->handle( $campaign_id ),
			'change_target' => $handler->handle( $campaign_id, Amount::create( 100 ) ),
		};
	}

	private function assert_campaign_action_result( string $action, Campaign $campaign ): void {

		$this->assertSame( 1, $campaign->get_id()->get_value() );

		match ( $action ) {
			'rename' => $this->assertSame( 'Renamed Campaign', $campaign->get_title() ),
			'open' => $this->assertTrue( $campaign->can_receive_donations() ),
			'close' => $this->assertFalse( $campaign->can_receive_donations() ),
			'change_target' => $this->assertSame( 250, $campaign->get_target()->get_amount()?->get_value() ),
		};
	}
}
