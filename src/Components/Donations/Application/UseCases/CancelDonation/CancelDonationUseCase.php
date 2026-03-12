<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CancelDonation;

use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;

/**
 * Provides methods for canceling a donation.
 *
 * @since 0.1.0
 */
interface CancelDonationUseCase {

	/**
	 * Cancels an existing donation.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param UtcDateTime|null $at Optional cancellation timestamp.
	 *
	 * @return Donation Persisted donation snapshot.
	 *
	 * @throws CancelDonationException When cancellation fails.
	 */
	public function handle( EntityId $donation_id, ?UtcDateTime $at = null ): Donation;
}
