<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\FindAllDonations;

use Fundrik\Core\Components\Donations\Domain\Donation;

/**
 * Provides methods for retrieving all donations.
 *
 * @since 0.1.0
 */
interface FindAllDonationsUseCase {

	/**
	 * Retrieves all donations.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, Donation> The list of donations.
	 *
	 * @phpstan-return list<Donation>
	 *
	 * @throws FindAllDonationsException When donation retrieval fails.
	 */
	public function handle(): array;
}
