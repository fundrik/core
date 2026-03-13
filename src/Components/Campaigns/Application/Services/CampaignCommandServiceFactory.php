<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
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
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;

/**
 * Provides the default factory for creating the public campaign write service.
 *
 * @since 0.1.0
 */
final readonly class CampaignCommandServiceFactory {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignRepositoryPort $campaign_repository Provides campaign persistence.
	 * @param DonationRepositoryPort $donation_repository Provides donation persistence.
	 * @param ApplicationEventBusPort $event_bus Publishes application events.
	 */
	public function __construct(
		private CampaignRepositoryPort $campaign_repository,
		private DonationRepositoryPort $donation_repository,
		private ApplicationEventBusPort $event_bus,
	) {}

	/**
	 * Creates the public campaign write service.
	 *
	 * @since 0.1.0
	 *
	 * @return CampaignCommandService Campaign write service.
	 */
	public function create(): CampaignCommandService {

		return new CampaignCommandService(
			new CreateCampaignHandler( $this->campaign_repository, $this->event_bus ),
			new SaveCampaignHandler( $this->campaign_repository, $this->event_bus ),
			new UpdateCampaignHandler( $this->campaign_repository, $this->event_bus ),
			new RenameCampaignHandler( $this->campaign_repository, $this->event_bus ),
			new ActivateCampaignHandler( $this->campaign_repository, $this->event_bus ),
			new DeactivateCampaignHandler( $this->campaign_repository, $this->event_bus ),
			new OpenCampaignHandler( $this->campaign_repository, $this->event_bus ),
			new CloseCampaignHandler( $this->campaign_repository, $this->event_bus ),
			new SetCampaignTargetAmountHandler( $this->campaign_repository, $this->event_bus ),
			new DeleteCampaignHandler( $this->campaign_repository, $this->donation_repository, $this->event_bus ),
		);
	}
}
