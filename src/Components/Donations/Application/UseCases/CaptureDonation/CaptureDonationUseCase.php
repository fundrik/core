<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CaptureDonation;

use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;

/**
 * Provides methods for capturing a donation.
 *
 * @since 0.1.0
 */
interface CaptureDonationUseCase {

	/**
	 * Captures an existing donation.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param UtcDateTime|null $at Optional capture timestamp.
	 *
	 * @return Donation Persisted donation snapshot.
	 *
	 * @throws CaptureDonationException When capture fails.
	 */
	public function handle( EntityId $donation_id, ?UtcDateTime $at = null ): Donation;
}
