<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\FindDonationsByCampaignId;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Handles retrieving donations by campaign ID.
 *
 * @since 0.1.0
 */
final readonly class FindDonationsByCampaignIdHandler implements FindDonationsByCampaignIdUseCase {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param DonationRepositoryPort $repository Retrieves donations from storage.
	 */
	public function __construct(
		private DonationRepositoryPort $repository,
	) {}

	/**
	 * Retrieves donations for the given campaign ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id The campaign ID to filter by.
	 *
	 * @return array<int, Donation> The list of campaign donations.
	 *
	 * @phpstan-return list<Donation>
	 *
	 * @throws FindDonationsByCampaignIdException When donation retrieval fails.
	 */
	public function handle( EntityId $campaign_id ): array {

		try {
			return $this->repository->find_all_by_campaign_id( $campaign_id );
		} catch ( DonationRepositoryExceptionInterface $e ) {
			throw new FindDonationsByCampaignIdException(
				sprintf(
					'Failed to retrieve donations for campaign "%s".',
					(string) $campaign_id->get_value(),
				),
				previous: $e,
			);
		}
	}
}
