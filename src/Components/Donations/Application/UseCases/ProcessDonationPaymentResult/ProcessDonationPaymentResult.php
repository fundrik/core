<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult;

use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Represents the result of normalized payment result processing.
 *
 * @since 0.1.0
 */
 final readonly class ProcessDonationPaymentResult {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param DonationPaymentResultType $result_type Normalized payment result type.
	 * @param ProcessDonationPaymentResultStatus $status Processing outcome.
	 */
	public function __construct(
		private EntityId $donation_id,
		private DonationPaymentResultType $result_type,
		private ProcessDonationPaymentResultStatus $status,
	) {}

	/**
	 * Returns the donation ID.
	 *
	 * @since 0.1.0
	 *
	 * @return EntityId Donation ID.
	 */
	public function get_donation_id(): EntityId {

		return $this->donation_id;
	}

	/**
	 * Returns the normalized payment result type.
	 *
	 * @since 0.1.0
	 *
	 * @return DonationPaymentResultType Normalized payment result type.
	 */
	public function get_result_type(): DonationPaymentResultType {

		return $this->result_type;
	}

	/**
	 * Returns the processing outcome.
	 *
	 * @since 0.1.0
	 *
	 * @return ProcessDonationPaymentResultStatus Processing outcome.
	 */
	public function get_status(): ProcessDonationPaymentResultStatus {

		return $this->status;
	}
}
