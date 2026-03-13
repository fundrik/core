<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Services;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\UseCases\FindAllDonations\FindAllDonationsHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationsByCampaignId\FindDonationsByCampaignIdHandler;

/**
 * Provides the default factory for creating the public donation read service.
 *
 * @since 0.1.0
 */
final readonly class DonationQueryServiceFactory {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param DonationRepositoryPort $donation_repository Provides donation persistence.
	 */
	public function __construct(
		private DonationRepositoryPort $donation_repository,
	) {}

	/**
	 * Creates the public donation read service.
	 *
	 * @since 0.1.0
	 *
	 * @return DonationQueryService Donation read service.
	 */
	public function create(): DonationQueryService {

		return new DonationQueryService(
			new FindDonationByIdHandler( $this->donation_repository ),
			new FindAllDonationsHandler( $this->donation_repository ),
			new FindDonationsByCampaignIdHandler( $this->donation_repository ),
		);
	}
}
