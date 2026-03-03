<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\FindDonationsByCampaignId;

use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Provides methods for retrieving donations by campaign ID.
 *
 * @since 0.1.0
 */
interface FindDonationsByCampaignIdUseCase {

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
	public function handle( EntityId $campaign_id ): array;
}
