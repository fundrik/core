<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation;

use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;

/**
 * Provides methods for refunding a donation.
 *
 * @since 0.1.0
 */
interface RefundDonationUseCase {

	/**
	 * Refunds an existing donation.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param UtcDateTime|null $at Optional refund timestamp.
	 *
	 * @return Donation Persisted donation snapshot.
	 *
	 * @throws RefundDonationException When refunding fails.
	 */
	public function handle( EntityId $donation_id, ?UtcDateTime $at = null ): Donation;
}
