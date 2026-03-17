<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignActivatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignClosedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignCreatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeactivatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeletedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignOpenedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignRenamedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignTargetChangedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignUpdatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositorySaveOutcome;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositorySaveResult;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignCommandService;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignCommandServiceFactory;
use Fundrik\Core\Components\Campaigns\Application\UseCases\AbstractCampaignMutationHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ActivateCampaign\ActivateCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CloseCampaign\CloseCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign\CreateCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeactivateCampaign\DeactivateCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\OpenCampaign\OpenCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign\RenameCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SaveCampaign\SaveCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SetCampaignTargetAmount\SetCampaignTargetAmountHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\UpdateCampaign\UpdateCampaignHandler;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CampaignCommandService::class )]
#[UsesClass( CampaignCommandServiceFactory::class )]
#[UsesClass( CampaignCreatedEvent::class )]
#[UsesClass( CampaignUpdatedEvent::class )]
#[UsesClass( CampaignRenamedEvent::class )]
#[UsesClass( CampaignActivatedEvent::class )]
#[UsesClass( CampaignDeactivatedEvent::class )]
#[UsesClass( CampaignOpenedEvent::class )]
#[UsesClass( CampaignClosedEvent::class )]
#[UsesClass( CampaignTargetChangedEvent::class )]
#[UsesClass( CampaignDeletedEvent::class )]
#[UsesClass( CampaignRepositorySaveOutcome::class )]
#[UsesClass( CampaignRepositorySaveResult::class )]
#[UsesClass( AbstractCampaignMutationHandler::class )]
#[UsesClass( CreateCampaignHandler::class )]
#[UsesClass( SaveCampaignHandler::class )]
#[UsesClass( UpdateCampaignHandler::class )]
#[UsesClass( RenameCampaignHandler::class )]
#[UsesClass( ActivateCampaignHandler::class )]
#[UsesClass( DeactivateCampaignHandler::class )]
#[UsesClass( OpenCampaignHandler::class )]
#[UsesClass( CloseCampaignHandler::class )]
#[UsesClass( SetCampaignTargetAmountHandler::class )]
#[UsesClass( DeleteCampaignHandler::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Money::class )]
final class CampaignCommandServiceTest extends MockeryTestCase {

	private CampaignRepositoryPort&MockInterface $campaign_repository;
	private DonationRepositoryPort&MockInterface $donation_repository;
	private ApplicationEventBusPort&MockInterface $event_bus;

	private CampaignCommandService $command;

	protected function setUp(): void {

		parent::setUp();

		$this->campaign_repository = Mockery::mock( CampaignRepositoryPort::class );
		$this->donation_repository = Mockery::mock( DonationRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );

		$this->command = ( new CampaignCommandServiceFactory(
			$this->campaign_repository,
			$this->donation_repository,
			$this->event_bus,
		) )->create();
	}

	#[Test]
	public function create_uses_injected_ports(): void {

		$campaign = $this->make_campaign();

		$this->campaign_repository
			->shouldReceive( 'insert' )
			->once()
			->with( $this->identicalTo( $campaign ) )
			->andReturn( $campaign );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( CampaignCreatedEvent::class, $campaign->get_id() ) );

		$result = $this->command->create( $campaign );

		$this->assertSame( $campaign, $result );
	}

	#[Test]
	public function save_uses_injected_ports(): void {

		$campaign = $this->make_campaign();
		$outcome = new CampaignRepositorySaveOutcome(
			result: CampaignRepositorySaveResult::Updated,
			campaign: $campaign,
		);

		$this->campaign_repository
			->shouldReceive( 'save' )
			->once()
			->with( $this->identicalTo( $campaign ) )
			->andReturn( $outcome );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( CampaignUpdatedEvent::class, $campaign->get_id() ) );

		$result = $this->command->save( $campaign );

		$this->assertSame( $outcome, $result );
	}

	#[Test]
	public function update_uses_injected_ports(): void {

		$campaign = $this->make_campaign();

		$this->campaign_repository
			->shouldReceive( 'update' )
			->once()
			->with( $this->identicalTo( $campaign ) )
			->andReturn( $campaign );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( CampaignUpdatedEvent::class, $campaign->get_id() ) );

		$result = $this->command->update( $campaign );

		$this->assertSame( $campaign, $result );
	}

	#[Test]
	public function rename_uses_injected_ports(): void {

		$campaign = $this->make_campaign( title: 'Old title' );
		$campaign_id = $campaign->get_id();
		$new_title = 'Renamed campaign';

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( $campaign );

		$this->campaign_repository
			->shouldReceive( 'update' )
			->once()
			->withArgs(
				function ( Campaign $updated_campaign ) use ( $new_title ): bool {

					$this->assertSame( $new_title, $updated_campaign->get_title() );

					return true;
				},
			)
			->andReturnUsing( static fn ( Campaign $updated_campaign ): Campaign => $updated_campaign );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( CampaignRenamedEvent::class, $campaign_id ) );

		$result = $this->command->rename( $campaign_id, $new_title );

		$this->assertSame( $new_title, $result->get_title() );
	}

	#[Test]
	public function activate_uses_injected_ports(): void {

		$campaign = $this->make_campaign( is_active: false );
		$campaign_id = $campaign->get_id();

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( $campaign );

		$this->campaign_repository
			->shouldReceive( 'update' )
			->once()
			->withArgs( static fn ( Campaign $updated_campaign ): bool => $updated_campaign->is_active() )
			->andReturnUsing( static fn ( Campaign $updated_campaign ): Campaign => $updated_campaign );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( CampaignActivatedEvent::class, $campaign_id ) );

		$result = $this->command->activate( $campaign_id );

		$this->assertTrue( $result->is_active() );
	}

	#[Test]
	public function deactivate_uses_injected_ports(): void {

		$campaign = $this->make_campaign( is_active: true );
		$campaign_id = $campaign->get_id();

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( $campaign );

		$this->campaign_repository
			->shouldReceive( 'update' )
			->once()
			->withArgs( static fn ( Campaign $updated_campaign ): bool => ! $updated_campaign->is_active() )
			->andReturnUsing( static fn ( Campaign $updated_campaign ): Campaign => $updated_campaign );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( CampaignDeactivatedEvent::class, $campaign_id ) );

		$result = $this->command->deactivate( $campaign_id );

		$this->assertFalse( $result->is_active() );
	}

	#[Test]
	public function open_uses_injected_ports(): void {

		$campaign = $this->make_campaign( is_open: false );
		$campaign_id = $campaign->get_id();

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( $campaign );

		$this->campaign_repository
			->shouldReceive( 'update' )
			->once()
			->withArgs( static fn ( Campaign $updated_campaign ): bool => $updated_campaign->is_open() )
			->andReturnUsing( static fn ( Campaign $updated_campaign ): Campaign => $updated_campaign );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( CampaignOpenedEvent::class, $campaign_id ) );

		$result = $this->command->open( $campaign_id );

		$this->assertTrue( $result->is_open() );
	}

	#[Test]
	public function close_uses_injected_ports(): void {

		$campaign = $this->make_campaign( is_open: true );
		$campaign_id = $campaign->get_id();

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( $campaign );

		$this->campaign_repository
			->shouldReceive( 'update' )
			->once()
			->withArgs( static fn ( Campaign $updated_campaign ): bool => ! $updated_campaign->is_open() )
			->andReturnUsing( static fn ( Campaign $updated_campaign ): Campaign => $updated_campaign );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( CampaignClosedEvent::class, $campaign_id ) );

		$result = $this->command->close( $campaign_id );

		$this->assertFalse( $result->is_open() );
	}

	#[Test]
	public function set_target_amount_uses_injected_ports(): void {

		$campaign = $this->make_campaign( has_target: true, target_amount: 100 );
		$campaign_id = $campaign->get_id();
		$amount = 50_000;

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( $campaign );

		$this->campaign_repository
			->shouldReceive( 'update' )
			->once()
			->withArgs(
				static fn ( Campaign $updated_campaign ): bool => $updated_campaign->get_target_money()->get_amount_minor() === 50_000,
			)
			->andReturnUsing( static fn ( Campaign $updated_campaign ): Campaign => $updated_campaign );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( CampaignTargetChangedEvent::class, $campaign_id ) );

		$result = $this->command->set_target_amount( $campaign_id, $amount );

		$this->assertSame( $amount, $result->get_target_money()->get_amount_minor() );
	}

	#[Test]
	public function delete_uses_injected_ports(): void {

		$campaign_id = $this->make_campaign()->get_id();

		$this->donation_repository
			->shouldReceive( 'exists_by_campaign_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( false );

		$this->campaign_repository
			->shouldReceive( 'delete' )
			->once()
			->with( $this->identicalTo( $campaign_id ) );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( CampaignDeletedEvent::class, $campaign_id ) );

		$this->command->delete( $campaign_id );

		$this->assertTrue( true );
	}

	private function event_of_type( string $event_class, EntityId $campaign_id ): callable {

		return function ( object $event ) use ( $event_class, $campaign_id ): bool {

			$this->assertInstanceOf( $event_class, $event );
			$this->assertTrue( $event->get_campaign_id()->equals( $campaign_id ) );

			return true;
		};
	}
}
