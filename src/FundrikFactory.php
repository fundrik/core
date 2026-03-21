<?php

declare(strict_types=1);

namespace Fundrik\Core;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignDetailsRead\CampaignDetailsReadPort;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignCommandService;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryService;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ChangeCampaignTarget\ChangeCampaignTargetHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CloseCampaign\CloseCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign\CreateCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\OpenCampaign\OpenCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign\RenameCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot\SyncCampaignFromSnapshotHandler;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Donations\Application\Ports\DonationDetailsRead\DonationDetailsReadPort;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\Services\DonationCommandService;
use Fundrik\Core\Components\Donations\Application\Services\DonationQueryService;
use Fundrik\Core\Components\Donations\Application\UseCases\AuthorizeDonation\AuthorizeDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CancelDonation\CancelDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CaptureDonation\CaptureDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FailDonation\FailDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationHandler;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;

/**
 * Provides the default factory for creating the Fundrik entry point from ports.
 *
 * @since 0.1.0
 */
final readonly class FundrikFactory {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignDetailsReadPort $campaign_details_read Campaign details read port.
	 * @param DonationDetailsReadPort $donation_details_read Donation details read port.
	 * @param CampaignRepositoryPort $campaign_repository Provides campaign persistence.
	 * @param DonationRepositoryPort $donation_repository Provides donation persistence.
	 * @param ApplicationEventBusPort $event_bus Publishes application events.
	 */
	public function __construct(
		private CampaignDetailsReadPort $campaign_details_read,
		private DonationDetailsReadPort $donation_details_read,
		private CampaignRepositoryPort $campaign_repository,
		private DonationRepositoryPort $donation_repository,
		private ApplicationEventBusPort $event_bus,
	) {}

	/**
	 * Creates the Fundrik entry point.
	 *
	 * @since 0.1.0
	 *
	 * @return Fundrik Fundrik entry point.
	 */
	public function create(): Fundrik {

		return new Fundrik(
			$this->create_campaign_query_service(),
			$this->create_campaign_command_service(),
			$this->create_donation_query_service(),
			$this->create_donation_command_service(),
		);
	}

	/**
	 * Creates the public campaign read service.
	 *
	 * @since 0.1.0
	 *
	 * @return CampaignQueryService Campaign read service.
	 */
	private function create_campaign_query_service(): CampaignQueryService {

		return new CampaignQueryService(
			new FindCampaignByIdHandler( $this->campaign_details_read ),
		);
	}

	/**
	 * Creates the public campaign write service.
	 *
	 * @since 0.1.0
	 *
	 * @return CampaignCommandService Campaign write service.
	 */
	private function create_campaign_command_service(): CampaignCommandService {

		return new CampaignCommandService(
			new CreateCampaignHandler( $this->campaign_repository, $this->event_bus ),
			new CampaignFactory(),
			new SyncCampaignFromSnapshotHandler( $this->campaign_repository, $this->event_bus ),
			new RenameCampaignHandler( $this->campaign_repository, $this->event_bus ),
			new OpenCampaignHandler( $this->campaign_repository, $this->event_bus ),
			new CloseCampaignHandler( $this->campaign_repository, $this->event_bus ),
			new ChangeCampaignTargetHandler( $this->campaign_repository, $this->event_bus ),
			new DeleteCampaignHandler( $this->campaign_repository, $this->donation_repository, $this->event_bus ),
		);
	}

	/**
	 * Creates the public donation read service.
	 *
	 * @since 0.1.0
	 *
	 * @return DonationQueryService Donation read service.
	 */
	private function create_donation_query_service(): DonationQueryService {

		return new DonationQueryService(
			new FindDonationByIdHandler( $this->donation_details_read ),
		);
	}

	/**
	 * Creates the public donation write service.
	 *
	 * @since 0.1.0
	 *
	 * @return DonationCommandService Donation write service.
	 */
	private function create_donation_command_service(): DonationCommandService {

		return new DonationCommandService(
			new CreateDonationHandler(
				$this->campaign_repository,
				$this->donation_repository,
				$this->event_bus,
			),
			new DonationFactory(),
			new AuthorizeDonationHandler( $this->donation_repository, $this->event_bus ),
			new CaptureDonationHandler( $this->donation_repository, $this->event_bus ),
			new FailDonationHandler( $this->donation_repository, $this->event_bus ),
			new RefundDonationHandler( $this->donation_repository, $this->event_bus ),
			new CancelDonationHandler( $this->donation_repository, $this->event_bus ),
		);
	}
}
