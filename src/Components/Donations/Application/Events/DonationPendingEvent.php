<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Events;

use Fundrik\Core\Components\Donations\Domain\PaymentId;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Represents a donation that is pending payment.
 *
 * @since 1.1.0
 */
final readonly class DonationPendingEvent implements DonationApplicationEventInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param PaymentId $payment_id Provider payment ID.
	 */
	public function __construct(
		private EntityId $donation_id,
		private PaymentId $payment_id,
	) {}

	/**
	 * Returns donation ID.
	 *
	 * @since 1.1.0
	 *
	 * @return EntityId Donation ID.
	 */
	public function get_donation_id(): EntityId {

		return $this->donation_id;
	}

	/**
	 * Returns the provider payment ID.
	 *
	 * @since 1.1.0
	 *
	 * @return PaymentId Provider payment ID.
	 */
	public function get_payment_id(): PaymentId {

		return $this->payment_id;
	}
}
