<?php

declare(strict_types=1);

namespace Fundrik\Core;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignDetailsRead\CampaignDetailsReadPort;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignCommandService;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryService;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ChangeCampaignTarget\ChangeCampaignTargetHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign\CreateCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DisableCampaignDonations\DisableCampaignDonationsHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\EnableCampaignDonations\EnableCampaignDonationsHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignDetailsById\FindCampaignDetailsByIdHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign\RenameCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot\SyncCampaignFromSnapshotHandler;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Donations\Application\Ports\DonationDetailsRead\DonationDetailsReadPort;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\Services\DonationCommandService;
use Fundrik\Core\Components\Donations\Application\Services\DonationQueryService;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationDetailsById\FindDonationDetailsByIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RejectDonation\RejectDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\SucceedDonation\SucceedDonationHandler;
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
			new FindCampaignDetailsByIdHandler( $this->campaign_details_read ),
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
			new EnableCampaignDonationsHandler( $this->campaign_repository, $this->event_bus ),
			new DisableCampaignDonationsHandler( $this->campaign_repository, $this->event_bus ),
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
			new FindDonationDetailsByIdHandler( $this->donation_details_read ),
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

		$create_donation = new CreateDonationHandler(
			$this->campaign_repository,
			new DonationFactory(),
			$this->donation_repository,
			$this->event_bus,
		);

		return new DonationCommandService(
			$create_donation,
			new SucceedDonationHandler( $this->donation_repository, $this->event_bus ),
			new RejectDonationHandler( $this->donation_repository, $this->event_bus ),
			new RefundDonationHandler( $this->donation_repository, $this->event_bus ),
		);
	}
}
