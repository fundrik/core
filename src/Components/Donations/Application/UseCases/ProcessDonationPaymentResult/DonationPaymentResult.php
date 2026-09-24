<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult;

use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Represents a normalized donation payment result.
 *
 * @since 1.0.0
 */
final readonly class DonationPaymentResult {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param DonationPaymentResultType $type Normalized payment result type.
	 */
	public function __construct(
		private EntityId $donation_id,
		private DonationPaymentResultType $type,
	) {}

	/**
	 * Returns the donation ID.
	 *
	 * @since 1.0.0
	 *
	 * @return EntityId Donation ID.
	 */
	public function get_donation_id(): EntityId {

		return $this->donation_id;
	}

	/**
	 * Returns the normalized payment result type.
	 *
	 * @since 1.0.0
	 *
	 * @return DonationPaymentResultType Normalized payment result type.
	 */
	public function get_type(): DonationPaymentResultType {

		return $this->type;
	}
}
