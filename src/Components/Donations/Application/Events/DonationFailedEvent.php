<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Events;

use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Signals that a donation has failed.
 *
 * @since 0.1.0
 */
final readonly class DonationFailedEvent implements DonationApplicationEventInterface {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 */
	public function __construct(
		private EntityId $donation_id,
	) {}

	/**
	 * Returns the donation ID associated with this event.
	 *
	 * @since 0.1.0
	 *
	 * @return EntityId Donation ID.
	 */
	public function get_donation_id(): EntityId {

		return $this->donation_id;
	}
}
