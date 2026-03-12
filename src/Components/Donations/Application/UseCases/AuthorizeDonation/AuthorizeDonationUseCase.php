<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\AuthorizeDonation;

use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;

/**
 * Provides methods for authorizing a donation.
 *
 * @since 0.1.0
 */
interface AuthorizeDonationUseCase {

	/**
	 * Authorizes an existing donation.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param UtcDateTime|null $at Optional authorization timestamp.
	 *
	 * @return Donation Persisted donation snapshot.
	 *
	 * @throws AuthorizeDonationException When authorization fails.
	 */
	public function handle( EntityId $donation_id, ?UtcDateTime $at = null ): Donation;
}
