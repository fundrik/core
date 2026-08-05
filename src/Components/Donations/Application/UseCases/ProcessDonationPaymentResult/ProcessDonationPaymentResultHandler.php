<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult;

use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutationException;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadDonationById\ReadDonationByIdException;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadDonationById\ReadDonationByIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RejectDonation\RejectDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\SucceedDonation\SucceedDonationHandler;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use ValueError;

/**
 * Handles processing donation payment results.
 *
 * @since 0.1.0
 */
final readonly class ProcessDonationPaymentResultHandler {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param ReadDonationByIdHandler $read_donation_by_id Reads donation state for idempotent result processing.
	 * @param ProcessDonationPaymentResultPolicy $policy Decides how payment results affect donation state.
	 * @param SucceedDonationHandler $succeed_donation Marks donations as succeeded.
	 * @param RejectDonationHandler $reject_donation Marks donations as rejected.
	 * @param RefundDonationHandler $refund_donation Refunds donations.
	 */
	public function __construct(
		private ReadDonationByIdHandler $read_donation_by_id,
		private ProcessDonationPaymentResultPolicy $policy,
		private SucceedDonationHandler $succeed_donation,
		private RejectDonationHandler $reject_donation,
		private RefundDonationHandler $refund_donation,
	) {}

	/**
	 * Processes a donation payment result idempotently.
	 *
	 * @since 0.1.0
	 *
	 * @param DonationPaymentResult $result Normalized payment result.
	 *
	 * @return ProcessDonationPaymentResult Processing result.
	 *
	 * @throws ProcessDonationPaymentResultException When result processing fails.
	 */
	public function handle( DonationPaymentResult $result ): ProcessDonationPaymentResult {

		$donation_id = $result->get_donation_id();
		$result_type = $result->get_type();

		$current_status = $this->require_donation_status( $donation_id );
		$status = $this->policy->determine_status( $current_status, $result_type );

		if ( $status === ProcessDonationPaymentResultStatus::Applied ) {
			$this->apply_payment_result( $donation_id, $result_type );
		}

		return $this->new_payment_result( $donation_id, $result_type, $status );
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Returns the current donation status required for payment result processing.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 *
	 * @return DonationStatus Current donation status.
	 *
	 * @throws ProcessDonationPaymentResultException When donation lookup fails, donation does not exist,
	 *                                               or donation status is invalid.
	 */
	private function require_donation_status( EntityId $donation_id ): DonationStatus {

		$donation_id_value = (string) $donation_id->get_value();

		try {
			$donation = $this->read_donation_by_id->handle( $donation_id );
		} catch ( ReadDonationByIdException $e ) {
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
			);
		}

		try {
			return DonationStatus::from( $donation->get_status() );
		} catch ( ValueError $e ) {
			throw new ProcessDonationPaymentResultException(
				stage: UseCaseFailureStage::Persistence,
				message: sprintf( 'Failed to resolve donation status for donation "%s".', $donation_id_value ),
				previous: $e,
			);
		}
	}
	// phpcs:enable

	/**
	 * Applies a donation payment result through donation mutation services.
	 *
	 * @since 0.1.0
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
					(string) $donation_id->get_value(),
				),
				previous: $e,
			);
		}
	}

	/**
	 * Creates a donation payment result processing result.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param DonationPaymentResultType $result_type Normalized payment result type.
	 * @param ProcessDonationPaymentResultStatus $status Processing outcome.
	 *
	 * @return ProcessDonationPaymentResult Processing result.
	 */
	private function new_payment_result(
		EntityId $donation_id,
		DonationPaymentResultType $result_type,
		ProcessDonationPaymentResultStatus $status,
	): ProcessDonationPaymentResult {

		return new ProcessDonationPaymentResult(
			donation_id: $donation_id,
			result_type: $result_type,
			status: $status,
		);
	}
}
