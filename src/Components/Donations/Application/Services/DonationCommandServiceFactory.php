<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\UseCases\AuthorizeDonation\AuthorizeDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CancelDonation\CancelDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CaptureDonation\CaptureDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FailDonation\FailDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\UpdateDonation\UpdateDonationHandler;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;

/**
 * Provides the default factory for creating the public donation write service.
 *
 * @since 0.1.0
 */
final readonly class DonationCommandServiceFactory {

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
	 * Creates the public donation write service.
	 *
	 * @since 0.1.0
	 *
	 * @return DonationCommandService Donation write service.
	 */
	public function create(): DonationCommandService {

		return new DonationCommandService(
			new CreateDonationHandler( $this->campaign_repository, $this->donation_repository, $this->event_bus ),
			new UpdateDonationHandler( $this->donation_repository, $this->event_bus ),
			new AuthorizeDonationHandler( $this->donation_repository, $this->event_bus ),
			new CaptureDonationHandler( $this->donation_repository, $this->event_bus ),
			new FailDonationHandler( $this->donation_repository, $this->event_bus ),
			new RefundDonationHandler( $this->donation_repository, $this->event_bus ),
			new CancelDonationHandler( $this->donation_repository, $this->event_bus ),
		);
	}
}
