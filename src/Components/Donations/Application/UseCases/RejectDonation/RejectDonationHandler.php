<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\RejectDonation;

use Fundrik\Core\Components\Donations\Application\Events\DonationRejectedEvent;
use Fundrik\Core\Components\Donations\Application\UseCases\AbstractDonationMutationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutation;
use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutationPreconditionReason;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationDomainException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Throwable;

/**
 * Handles rejecting an existing donation.
 *
 * @since 0.1.0
 */
final readonly class RejectDonationHandler extends AbstractDonationMutationHandler {

	/**
	 * Creates the reject-donation exception used when the donation disappears before persistence.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param Throwable $previous Previous repository exception.
	 *
	 * @return RejectDonationException Concrete reject-donation exception.
	 */
	protected function new_not_found_mutation_exception(
		EntityId $donation_id,
		Throwable $previous,
	): RejectDonationException {

		return new RejectDonationNotFoundException(
			$donation_id,
			$previous,
		);
	}

	/**
	 * Creates the reject-donation exception exposed by this use case.
	 *
	 * @since 0.1.0
	 *
	 * @param UseCaseFailureStage $stage Processing stage where failure happened.
	 * @param string $message Exception message.
	 * @param Throwable|null $previous Previous exception.
	 * @param DonationMutationPreconditionReason|null $reason Optional precondition failure reason.
	 *
	 * @return RejectDonationException Concrete reject-donation exception.
	 */
	protected function new_mutation_exception(
		UseCaseFailureStage $stage,
		string $message,
		?Throwable $previous = null,
		?DonationMutationPreconditionReason $reason = null,
	): RejectDonationException {

		return new RejectDonationException( $stage, $message, $previous, $reason );
	}

	/**
	 * Rejects an existing donation.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 *
	 * @return Donation Persisted donation snapshot.
	 */
	public function handle( EntityId $donation_id ): Donation {

		$mutation = DonationMutation::Reject;
		$donation = $this->require_donation( $donation_id, $mutation );

		try {
			$rejected_donation = $donation->reject();
		} catch ( DonationDomainException $e ) {
			$this->reject_mutation( $donation_id, $mutation, $e );
		}

		return $this->persist_donation(
			$rejected_donation,
			$mutation,
			new DonationRejectedEvent( $rejected_donation->get_id() ),
		);
	}
}
