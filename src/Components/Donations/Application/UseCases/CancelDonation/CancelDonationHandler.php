<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CancelDonation;

use Fundrik\Core\Components\Donations\Application\Events\DonationCanceledEvent;
use Fundrik\Core\Components\Donations\Application\UseCases\AbstractDonationMutationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutation;
use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutationPreconditionReason;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationDomainException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Throwable;

/**
 * Handles canceling an existing donation.
 *
 * @since 0.1.0
 */
final readonly class CancelDonationHandler extends AbstractDonationMutationHandler {

	/**
	 * Creates the cancel-donation exception used when the donation disappears before persistence.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param Throwable $previous Previous repository exception.
	 *
	 * @return CancelDonationException Concrete cancel-donation exception.
	 */
	protected function new_not_found_mutation_exception(
		EntityId $donation_id,
		Throwable $previous,
	): CancelDonationException {

		return new CancelDonationNotFoundException(
			(string) $donation_id->get_value(),
			$previous,
		);
	}

	/**
	 * Creates the cancel-donation exception exposed by this use case.
	 *
	 * @since 0.1.0
	 *
	 * @param UseCaseFailureStage $stage Processing stage where failure happened.
	 * @param string $message Exception message.
	 * @param Throwable|null $previous Previous exception.
	 * @param DonationMutationPreconditionReason|null $reason Optional precondition failure reason.
	 *
	 * @return CancelDonationException Concrete cancel-donation exception.
	 */
	protected function new_mutation_exception(
		UseCaseFailureStage $stage,
		string $message,
		?Throwable $previous = null,
		?DonationMutationPreconditionReason $reason = null,
	): CancelDonationException {

		return new CancelDonationException( $stage, $message, $previous, $reason );
	}

	/**
	 * Cancels an existing donation.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 *
	 * @return Donation Persisted donation snapshot.
	 */
	public function handle( EntityId $donation_id ): Donation {

		$mutation = DonationMutation::Cancel;
		$donation = $this->require_donation( $donation_id, $mutation );

		try {
			$canceled_donation = $donation->cancel();
		} catch ( DonationDomainException $e ) {
			$this->reject_mutation( $donation_id, $mutation, $e );
		}

		return $this->persist_donation(
			$canceled_donation,
			$mutation,
			new DonationCanceledEvent( $canceled_donation->get_id() ),
		);
	}
}
