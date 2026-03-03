<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById;

use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Provides methods for retrieving a donation by ID.
 *
 * @since 0.1.0
 */
interface FindDonationByIdUseCase {

	/**
	 * Retrieves a donation by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id The donation ID to retrieve.
	 *
	 * @return Donation|null The donation if found, null otherwise.
	 *
	 * @throws FindDonationByIdException When donation retrieval fails.
	 */
	public function handle( EntityId $donation_id ): ?Donation;
}
