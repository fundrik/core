<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult;

use Fundrik\Core\Components\Donations\Domain\PaymentId;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Represents the result of normalized payment result processing.
 *
 * @since 1.0.0
 */
final readonly class ProcessDonationPaymentResult {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Added the `$payment_id` parameter.
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param PaymentId $payment_id Provider payment ID.
	 * @param DonationPaymentResultType $result_type Normalized payment result type.
	 * @param ProcessDonationPaymentResultStatus $status Processing outcome.
	 */
	public function __construct(
		private EntityId $donation_id,
		private PaymentId $payment_id,
		private DonationPaymentResultType $result_type,
		private ProcessDonationPaymentResultStatus $status,
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
	 * Returns the provider payment ID.
	 *
	 * @since 1.1.0
	 *
	 * @return PaymentId Provider payment ID.
	 */
	public function get_payment_id(): PaymentId {

		return $this->payment_id;
	}

	/**
	 * Returns the normalized payment result type.
	 *
	 * @since 1.0.0
	 *
	 * @return DonationPaymentResultType Normalized payment result type.
	 */
	public function get_result_type(): DonationPaymentResultType {

		return $this->result_type;
	}

	/**
	 * Returns the processing outcome.
	 *
	 * @since 1.0.0
	 *
	 * @return ProcessDonationPaymentResultStatus Processing outcome.
	 */
	public function get_status(): ProcessDonationPaymentResultStatus {

		return $this->status;
	}
}
