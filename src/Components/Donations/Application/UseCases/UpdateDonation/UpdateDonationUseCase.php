<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\UpdateDonation;

use Fundrik\Core\Components\Donations\Domain\Donation;

/**
 * Provides methods for updating an existing donation.
 *
 * @since 0.1.0
 */
interface UpdateDonationUseCase {

	/**
	 * Updates an existing donation.
	 *
	 * @since 0.1.0
	 *
	 * @param Donation $donation The donation to update.
	 *
	 * @return Donation The persisted donation snapshot.
	 *
	 * @throws UpdateDonationException When donation update fails.
	 */
	public function handle( Donation $donation ): Donation;
}
