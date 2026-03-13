<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignCommandService;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignCommandServiceFactory;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryService;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryServiceFactory;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindAllCampaigns\FindAllCampaignsHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdHandler;
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
#[UsesClass( CampaignQueryServiceFactory::class )]
#[UsesClass( CampaignCommandServiceFactory::class )]
#[UsesClass( DonationQueryService::class )]
#[UsesClass( DonationCommandService::class )]
#[UsesClass( DonationQueryServiceFactory::class )]
#[UsesClass( DonationCommandServiceFactory::class )]
#[UsesClass( FindCampaignByIdHandler::class )]
#[UsesClass( FindAllCampaignsHandler::class )]
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
final class FundrikTest extends MockeryTestCase {

	private CampaignRepositoryPort&MockInterface $campaign_repository;
	private DonationRepositoryPort&MockInterface $donation_repository;
	private ApplicationEventBusPort&MockInterface $event_bus;
	private Fundrik $fundrik;

	protected function setUp(): void {

		parent::setUp();

		$this->campaign_repository = Mockery::mock( CampaignRepositoryPort::class );
		$this->donation_repository = Mockery::mock( DonationRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );

		$this->fundrik = new Fundrik(
			campaign_query: ( new CampaignQueryServiceFactory( $this->campaign_repository ) )->create(),
			campaign_command: ( new CampaignCommandServiceFactory(
				$this->campaign_repository,
				$this->donation_repository,
				$this->event_bus,
			) )->create(),
			donation_query: ( new DonationQueryServiceFactory( $this->donation_repository ) )->create(),
			donation_command: ( new DonationCommandServiceFactory(
				$this->campaign_repository,
				$this->donation_repository,
				$this->event_bus,
			) )->create(),
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
