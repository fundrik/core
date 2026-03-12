<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\FailDonation;

use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;

/**
 * Provides methods for failing a donation.
 *
 * @since 0.1.0
 */
interface FailDonationUseCase {

	/**
	 * Marks an existing donation as failed.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param UtcDateTime|null $at Optional failure timestamp.
	 *
	 * @return Donation Persisted donation snapshot.
	 *
	 * @throws FailDonationException When the operation fails.
	 */
	public function handle( EntityId $donation_id, ?UtcDateTime $at = null ): Donation;
}
