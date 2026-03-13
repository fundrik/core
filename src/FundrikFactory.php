<?php

declare(strict_types=1);

namespace Fundrik\Core;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignCommandServiceFactory;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryServiceFactory;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\Services\DonationCommandServiceFactory;
use Fundrik\Core\Components\Donations\Application\Services\DonationQueryServiceFactory;
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
	 * Creates the Fundrik entry point.
	 *
	 * @since 0.1.0
	 *
	 * @return Fundrik Fundrik entry point.
	 */
	public function create(): Fundrik {

		return new Fundrik(
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
}
