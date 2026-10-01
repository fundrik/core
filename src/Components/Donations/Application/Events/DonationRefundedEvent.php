<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Events;

use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Signals that a donation has been refunded.
 *
 * @since 1.0.0
 */
final readonly class DonationRefundedEvent implements DonationApplicationEventInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 */
	public function __construct(
		private EntityId $donation_id,
	) {}

	/**
	 * Returns the donation ID associated with this event.
	 *
	 * @since 1.0.0
	 *
	 * @return EntityId Donation ID.
	 */
	public function get_donation_id(): EntityId {

		return $this->donation_id;
	}
}
