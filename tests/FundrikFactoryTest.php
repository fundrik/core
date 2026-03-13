<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeletedEvent;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignCommandService;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignCommandServiceFactory;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryService;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryServiceFactory;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindAllCampaigns\FindAllCampaignsHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdHandler;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Donations\Application\Events\DonationUpdatedEvent;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\Services\DonationCommandService;
use Fundrik\Core\Components\Donations\Application\Services\DonationCommandServiceFactory;
use Fundrik\Core\Components\Donations\Application\Services\DonationQueryService;
use Fundrik\Core\Components\Donations\Application\Services\DonationQueryServiceFactory;
use Fundrik\Core\Components\Donations\Application\UseCases\AuthorizeDonation\AuthorizeDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CancelDonation\CancelDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CaptureDonation\CaptureDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FailDonation\FailDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FindAllDonations\FindAllDonationsHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationsByCampaignId\FindDonationsByCampaignIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\UpdateDonation\UpdateDonationHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\Core\Fundrik;
use Fundrik\Core\FundrikFactory;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( FundrikFactory::class )]
#[UsesClass( Fundrik::class )]
#[UsesClass( CampaignQueryService::class )]
#[UsesClass( CampaignCommandService::class )]
#[UsesClass( CampaignQueryServiceFactory::class )]
#[UsesClass( CampaignCommandServiceFactory::class )]
#[UsesClass( DonationQueryService::class )]
#[UsesClass( DonationCommandService::class )]
#[UsesClass( DonationQueryServiceFactory::class )]
#[UsesClass( DonationCommandServiceFactory::class )]
#[UsesClass( FindCampaignByIdHandler::class )]
#[UsesClass( FindAllCampaignsHandler::class )]
#[UsesClass( DeleteCampaignHandler::class )]
#[UsesClass( FindDonationByIdHandler::class )]
#[UsesClass( FindAllDonationsHandler::class )]
#[UsesClass( FindDonationsByCampaignIdHandler::class )]
#[UsesClass( CreateDonationHandler::class )]
#[UsesClass( AuthorizeDonationHandler::class )]
#[UsesClass( CaptureDonationHandler::class )]
#[UsesClass( FailDonationHandler::class )]
#[UsesClass( RefundDonationHandler::class )]
#[UsesClass( CancelDonationHandler::class )]
#[UsesClass( UpdateDonationHandler::class )]
#[UsesClass( CampaignDeletedEvent::class )]
#[UsesClass( DonationUpdatedEvent::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( Donation::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Money::class )]
#[UsesClass( UtcDateTime::class )]
final class FundrikFactoryTest extends MockeryTestCase {

	private CampaignRepositoryPort&MockInterface $campaign_repository;
	private DonationRepositoryPort&MockInterface $donation_repository;
	private ApplicationEventBusPort&MockInterface $event_bus;
	private Fundrik $fundrik;

	protected function setUp(): void {

		parent::setUp();

		$this->campaign_repository = Mockery::mock( CampaignRepositoryPort::class );
		$this->donation_repository = Mockery::mock( DonationRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );

		$this->fundrik = ( new FundrikFactory(
			$this->campaign_repository,
			$this->donation_repository,
			$this->event_bus,
		) )->create();
	}

	#[Test]
	public function create_exposes_campaign_and_donation_services_as_shared_instances(): void {

		$this->assertInstanceOf( CampaignQueryService::class, $this->fundrik->campaign_query() );
		$this->assertInstanceOf( CampaignCommandService::class, $this->fundrik->campaign_command() );
		$this->assertInstanceOf( DonationQueryService::class, $this->fundrik->donation_query() );
		$this->assertInstanceOf( DonationCommandService::class, $this->fundrik->donation_command() );
		$this->assertSame( $this->fundrik->campaign_query(), $this->fundrik->campaign_query() );
		$this->assertSame( $this->fundrik->campaign_command(), $this->fundrik->campaign_command() );
		$this->assertSame( $this->fundrik->donation_query(), $this->fundrik->donation_query() );
		$this->assertSame( $this->fundrik->donation_command(), $this->fundrik->donation_command() );
	}

	#[Test]
	public function create_wires_campaign_query_to_injected_campaign_repository(): void {

		$campaign = $this->make_campaign();
		$campaign_id = $campaign->get_id();

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( $campaign );

		$result = $this->fundrik->campaign_query()->find_by_id( $campaign_id );

		$this->assertSame( $campaign, $result );
	}

	#[Test]
	public function create_wires_campaign_command_to_injected_ports_for_delete(): void {

		$campaign_id = EntityId::create( 1_001 );

		$this->donation_repository
			->shouldReceive( 'find_all_by_campaign_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( [] );

		$this->campaign_repository
			->shouldReceive( 'delete' )
			->once()
			->with( $this->identicalTo( $campaign_id ) );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs(
				function ( object $event ) use ( $campaign_id ): bool {

					$this->assertInstanceOf( CampaignDeletedEvent::class, $event );
					$this->assertSame( $campaign_id, $event->get_campaign_id() );

					return true;
				},
			);

		$this->fundrik->campaign_command()->delete( $campaign_id );
	}

	#[Test]
	public function create_wires_donation_query_to_injected_donation_repository(): void {

		$donation = $this->make_pending_donation( 5_001, 1_001 );
		$donation_id = $donation->get_id();

		$this->donation_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation_id ) )
			->andReturn( $donation );

		$result = $this->fundrik->donation_query()->find_by_id( $donation_id );

		$this->assertSame( $donation, $result );
	}

	#[Test]
	public function create_wires_donation_command_to_injected_ports_for_update(): void {

		$donation = $this->make_pending_donation( 5_001, 1_001 );
		$donation_id = $donation->get_id();

		$this->donation_repository
			->shouldReceive( 'update' )
			->once()
			->with( $this->identicalTo( $donation ) )
			->andReturn( $donation );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs(
				function ( object $event ) use ( $donation_id ): bool {

					$this->assertInstanceOf( DonationUpdatedEvent::class, $event );
					$this->assertSame( $donation_id, $event->get_donation_id() );

					return true;
				},
			);

		$result = $this->fundrik->donation_command()->update( $donation );

		$this->assertSame( $donation, $result );
	}
}
