<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult;

use Fundrik\Core\Components\Donations\Domain\DonationStatus;

/**
 * Determines whether a donation payment result should be applied, replayed, or ignored.
 *
 * @since 0.1.0
 */
final readonly class ProcessDonationPaymentResultPolicy {

	/**
	 * Returns the processing outcome for a donation payment result.
	 *
	 * @since 0.1.0
	 *
	 * @param DonationStatus $current_status Current donation status.
	 * @param DonationPaymentResultType $result_type Payment result type.
	 *
	 * @return ProcessDonationPaymentResultStatus Processing outcome.
	 */
	public function determine_status(
		DonationStatus $current_status,
		DonationPaymentResultType $result_type,
	): ProcessDonationPaymentResultStatus {

		if ( $current_status === $this->target_status( $result_type ) ) {
			return ProcessDonationPaymentResultStatus::Replayed;
		}

		if ( $current_status !== $this->source_status( $result_type ) ) {
			return ProcessDonationPaymentResultStatus::Ignored;
		}

		return ProcessDonationPaymentResultStatus::Applied;
	}

	/**
	 * Returns the donation status produced by a payment result.
	 *
	 * @since 0.1.0
	 *
	 * @param DonationPaymentResultType $result_type Payment result type.
	 *
	 * @return DonationStatus Target donation status.
	 */
	private function target_status( DonationPaymentResultType $result_type ): DonationStatus {

		return match ( $result_type ) {
			DonationPaymentResultType::Succeeded => DonationStatus::Succeeded,
			DonationPaymentResultType::Rejected => DonationStatus::Rejected,
			DonationPaymentResultType::Refunded => DonationStatus::Refunded,
		};
	}

	/**
	 * Returns the donation status required by a payment result.
	 *
	 * @since 0.1.0
	 *
	 * @param DonationPaymentResultType $result_type Payment result type.
	 *
	 * @return DonationStatus Source donation status.
	 */
	private function source_status( DonationPaymentResultType $result_type ): DonationStatus {

		return match ( $result_type ) {
			DonationPaymentResultType::Succeeded => DonationStatus::Pending,
			DonationPaymentResultType::Rejected => DonationStatus::Pending,
			DonationPaymentResultType::Refunded => DonationStatus::Succeeded,
		};
	}
}
