<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation;

use Fundrik\Core\Components\Donations\Domain\Donation;

/**
 * Provides methods for creating a new donation.
 *
 * @since 0.1.0
 */
interface CreateDonationUseCase {

	/**
	 * Creates a new donation.
	 *
	 * @since 0.1.0
	 *
	 * @param Donation $donation The donation to create.
	 *
	 * @return Donation The persisted donation snapshot.
	 *
	 * @throws CreateDonationException When donation creation fails.
	 */
	public function handle( Donation $donation ): Donation;
}
