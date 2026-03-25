<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignDetailsRead\CampaignDetailsReadPort;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignCommandService;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryService;
use Fundrik\Core\Components\Campaigns\Application\UseCases\AbstractCampaignMutationHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ChangeCampaignTarget\ChangeCampaignTargetHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign\CreateCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DisableCampaignDonations\DisableCampaignDonationsHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\EnableCampaignDonations\EnableCampaignDonationsHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign\RenameCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot\SyncCampaignFromSnapshotHandler;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Donations\Application\Ports\DonationDetailsRead\DonationDetailsReadPort;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\Services\DonationCommandService;
use Fundrik\Core\Components\Donations\Application\Services\DonationQueryService;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RejectDonation\RejectDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\SucceedDonation\SucceedDonationHandler;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Fundrik;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( Fundrik::class )]
#[UsesClass( CampaignQueryService::class )]
#[UsesClass( CampaignCommandService::class )]
#[UsesClass( AbstractCampaignMutationHandler::class )]
#[UsesClass( DonationQueryService::class )]
#[UsesClass( DonationCommandService::class )]
#[UsesClass( FindCampaignByIdHandler::class )]
#[UsesClass( CreateCampaignHandler::class )]
#[UsesClass( SyncCampaignFromSnapshotHandler::class )]
#[UsesClass( RenameCampaignHandler::class )]
#[UsesClass( EnableCampaignDonationsHandler::class )]
#[UsesClass( DisableCampaignDonationsHandler::class )]
#[UsesClass( ChangeCampaignTargetHandler::class )]
#[UsesClass( DeleteCampaignHandler::class )]
#[UsesClass( FindDonationByIdHandler::class )]
#[UsesClass( CreateDonationHandler::class )]
#[UsesClass( SucceedDonationHandler::class )]
#[UsesClass( RejectDonationHandler::class )]
#[UsesClass( RefundDonationHandler::class )]
#[UsesClass( CampaignFactory::class )]
#[UsesClass( DonationFactory::class )]
final class FundrikTest extends MockeryTestCase {

	private CampaignDetailsReadPort&MockInterface $campaign_details_read;
	private CampaignRepositoryPort&MockInterface $campaign_repository;
	private DonationDetailsReadPort&MockInterface $donation_details_read;
	private DonationRepositoryPort&MockInterface $donation_repository;
	private ApplicationEventBusPort&MockInterface $event_bus;
	private Fundrik $fundrik;

	protected function setUp(): void {

		parent::setUp();

		$this->campaign_details_read = Mockery::mock( CampaignDetailsReadPort::class );
		$this->campaign_repository = Mockery::mock( CampaignRepositoryPort::class );
		$this->donation_details_read = Mockery::mock( DonationDetailsReadPort::class );
		$this->donation_repository = Mockery::mock( DonationRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );

		$this->fundrik = new Fundrik(
			new CampaignQueryService(
				new FindCampaignByIdHandler( $this->campaign_details_read ),
			),
			new CampaignCommandService(
				new CreateCampaignHandler( $this->campaign_repository, $this->event_bus ),
				new CampaignFactory(),
				new SyncCampaignFromSnapshotHandler( $this->campaign_repository, $this->event_bus ),
				new RenameCampaignHandler( $this->campaign_repository, $this->event_bus ),
				new EnableCampaignDonationsHandler( $this->campaign_repository, $this->event_bus ),
				new DisableCampaignDonationsHandler( $this->campaign_repository, $this->event_bus ),
				new ChangeCampaignTargetHandler( $this->campaign_repository, $this->event_bus ),
				new DeleteCampaignHandler( $this->campaign_repository, $this->donation_repository, $this->event_bus ),
			),
			new DonationQueryService(
				new FindDonationByIdHandler( $this->donation_details_read ),
			),
			new DonationCommandService(
				new CreateDonationHandler(
					$this->campaign_repository,
					new DonationFactory(),
					$this->donation_repository,
					$this->event_bus,
				),
				new SucceedDonationHandler(
					$this->donation_repository,
					$this->event_bus,
				),
				new RejectDonationHandler(
					$this->donation_repository,
					$this->event_bus,
				),
				new RefundDonationHandler(
					$this->donation_repository,
					$this->event_bus,
				),
			),
		);
	}

	#[Test]
	public function campaign_services_are_exposed_as_shared_instances(): void {

		$this->assertInstanceOf( CampaignQueryService::class, $this->fundrik->campaign_query() );
		$this->assertInstanceOf( CampaignCommandService::class, $this->fundrik->campaign_command() );
		$this->assertInstanceOf( DonationQueryService::class, $this->fundrik->donation_query() );
		$this->assertInstanceOf( DonationCommandService::class, $this->fundrik->donation_command() );
		$this->assertSame( $this->fundrik->campaign_query(), $this->fundrik->campaign_query() );
		$this->assertSame( $this->fundrik->campaign_command(), $this->fundrik->campaign_command() );
		$this->assertSame( $this->fundrik->donation_query(), $this->fundrik->donation_query() );
		$this->assertSame( $this->fundrik->donation_command(), $this->fundrik->donation_command() );
	}
}
