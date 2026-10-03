<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult;

use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutationException;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdException;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RejectDonation\RejectDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\SucceedDonation\SucceedDonationHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\PaymentId;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Handles processing donation payment results.
 *
 * @since 1.0.0
 */
final readonly class ProcessDonationPaymentResultHandler {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Replaced `ReadDonationByIdHandler` with `FindDonationByIdHandler`.
	 *
	 * @param FindDonationByIdHandler $find_donation_by_id Reads authoritative donation state.
	 * @param ProcessDonationPaymentResultPolicy $policy Decides how payment results affect donation state.
	 * @param SucceedDonationHandler $succeed_donation Marks donations as succeeded.
	 * @param RejectDonationHandler $reject_donation Marks donations as rejected.
	 * @param RefundDonationHandler $refund_donation Refunds donations.
	 */
	public function __construct(
		private FindDonationByIdHandler $find_donation_by_id,
		private ProcessDonationPaymentResultPolicy $policy,
		private SucceedDonationHandler $succeed_donation,
		private RejectDonationHandler $reject_donation,
		private RefundDonationHandler $refund_donation,
	) {}

	/**
	 * Processes a donation payment result idempotently.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Validates provider payment IDs against authoritative donation state.
	 *
	 * @param DonationPaymentResult $result Normalized payment result.
	 *
	 * @return ProcessDonationPaymentResult Processing result.
	 *
	 * @throws ProcessDonationPaymentResultException When result processing fails.
	 */
	public function handle( DonationPaymentResult $result ): ProcessDonationPaymentResult {

		$donation_id = $result->get_donation_id();
		$payment_id = $result->get_payment_id();
		$result_type = $result->get_type();

		$donation = $this->require_matching_donation( $donation_id, $payment_id );
		$status = $this->policy->determine_status( $donation->get_status(), $result_type );

		if ( $status === ProcessDonationPaymentResultStatus::Applied ) {
			$this->apply_payment_result( $donation_id, $result_type );
		}

		return $this->new_payment_result( $donation_id, $payment_id, $result_type, $status );
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Returns the donation matching the payment result.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param PaymentId $payment_id Provider payment ID.
	 *
	 * @return Donation Matching donation.
	 *
	 * @throws ProcessDonationPaymentResultException When lookup or payment validation fails.
	 */
	private function require_matching_donation( EntityId $donation_id, PaymentId $payment_id ): Donation {

		$donation_id_value = $donation_id->get_value();

		try {
			$donation = $this->find_donation_by_id->handle( $donation_id );
		} catch ( FindDonationByIdException $e ) {
			throw new ProcessDonationPaymentResultException(
				stage: UseCaseFailureStage::Persistence,
				message: sprintf( 'Failed to retrieve donation "%s".', $donation_id_value ),
				previous: $e,
			);
		}

		if ( $donation === null ) {
			throw new ProcessDonationPaymentResultException(
				stage: UseCaseFailureStage::Precondition,
				message: sprintf(
					'Cannot process payment result for donation "%s": donation does not exist.',
					$donation_id_value,
				),
				reason: ProcessDonationPaymentResultPreconditionReason::DonationNotFound,
			);
		}

		$attached_payment_id = $donation->get_payment_id();

		if ( $attached_payment_id === null ) {
			throw new ProcessDonationPaymentResultException(
				stage: UseCaseFailureStage::Precondition,
				message: sprintf(
					'Cannot process payment result for donation "%s": payment is not attached.',
					$donation_id_value,
				),
				reason: ProcessDonationPaymentResultPreconditionReason::PaymentNotAttached,
			);
		}

		if ( ! $attached_payment_id->equals( $payment_id ) ) {
			throw new ProcessDonationPaymentResultException(
				stage: UseCaseFailureStage::Precondition,
				message: sprintf(
					'Cannot process payment result for donation "%s": payment ID does not match.',
					$donation_id_value,
				),
				reason: ProcessDonationPaymentResultPreconditionReason::PaymentIdMismatch,
			);
		}

		return $donation;
	}
	// phpcs:enable

	/**
	 * Applies a donation payment result through donation mutation services.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param DonationPaymentResultType $result_type Normalized payment result type.
	 *
	 * @throws ProcessDonationPaymentResultException When donation mutation fails.
	 */
	private function apply_payment_result( EntityId $donation_id, DonationPaymentResultType $result_type ): void {

		try {
			match ( $result_type ) {
				DonationPaymentResultType::Succeeded => $this->succeed_donation->handle( $donation_id ),
				DonationPaymentResultType::Rejected => $this->reject_donation->handle( $donation_id ),
				DonationPaymentResultType::Refunded => $this->refund_donation->handle( $donation_id ),
			};
		} catch ( DonationMutationException $e ) {
			throw new ProcessDonationPaymentResultException(
				stage: $e->get_stage(),
				message: sprintf(
					'Failed to apply payment result to donation "%s".',
					$donation_id->get_value(),
				),
				previous: $e,
			);
		}
	}

	/**
	 * Creates a donation payment result processing result.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param PaymentId $payment_id Provider payment ID.
	 * @param DonationPaymentResultType $result_type Normalized payment result type.
	 * @param ProcessDonationPaymentResultStatus $status Processing outcome.
	 *
	 * @return ProcessDonationPaymentResult Processing result.
	 */
	private function new_payment_result(
		EntityId $donation_id,
		PaymentId $payment_id,
		DonationPaymentResultType $result_type,
		ProcessDonationPaymentResultStatus $status,
	): ProcessDonationPaymentResult {

		return new ProcessDonationPaymentResult(
			donation_id: $donation_id,
			payment_id: $payment_id,
			result_type: $result_type,
			status: $status,
		);
	}
}
