<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Services;

use Fundrik\Core\Components\Donations\Application\UseCases\FindAllDonations\FindAllDonationsException;
use Fundrik\Core\Components\Donations\Application\UseCases\FindAllDonations\FindAllDonationsHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdException;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationsByCampaignId\FindDonationsByCampaignIdException;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationsByCampaignId\FindDonationsByCampaignIdHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Provides the public entry point for donation read operations.
 *
 * @since 0.1.0
 */
final readonly class DonationQueryService {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param FindDonationByIdHandler $find_donation_by_id Retrieves a donation by its ID.
	 * @param FindAllDonationsHandler $find_all_donations Retrieves all donations.
	 * @param FindDonationsByCampaignIdHandler $find_donations_by_campaign_id Retrieves donations by campaign ID.
	 */
	public function __construct(
		private FindDonationByIdHandler $find_donation_by_id,
		private FindAllDonationsHandler $find_all_donations,
		private FindDonationsByCampaignIdHandler $find_donations_by_campaign_id,
	) {}

	/**
	 * Retrieves a donation by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 *
	 * @return Donation|null Donation if found, null otherwise.
	 *
	 * @throws FindDonationByIdException When donation retrieval fails.
	 */
	public function find_by_id( EntityId $donation_id ): ?Donation {

		return $this->find_donation_by_id->handle( $donation_id );
	}

	/**
	 * Retrieves all donations.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, Donation> Donation list.
	 *
	 * @phpstan-return list<Donation>
	 *
	 * @throws FindAllDonationsException When donation retrieval fails.
	 */
	public function find_all(): array {

		return $this->find_all_donations->handle();
	}

	/**
	 * Retrieves donations for the given campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 *
	 * @return array<int, Donation> Campaign donation list.
	 *
	 * @phpstan-return list<Donation>
	 *
	 * @throws FindDonationsByCampaignIdException When donation retrieval fails.
	 */
	public function find_by_campaign_id( EntityId $campaign_id ): array {

		return $this->find_donations_by_campaign_id->handle( $campaign_id );
	}
}
