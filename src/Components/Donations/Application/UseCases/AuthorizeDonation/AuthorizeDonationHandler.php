<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\AuthorizeDonation;

use Fundrik\Core\Components\Donations\Application\Events\DonationAuthorizedEvent;
use Fundrik\Core\Components\Donations\Application\UseCases\AbstractDonationMutationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutation;
use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutationPreconditionReason;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationDomainException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Throwable;

/**
 * Handles authorizing an existing donation.
 *
 * @since 0.1.0
 */
final readonly class AuthorizeDonationHandler extends AbstractDonationMutationHandler {

	/**
	 * Creates the authorize-donation exception used when the donation disappears before persistence.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param Throwable $previous Previous repository exception.
	 *
	 * @return AuthorizeDonationException Concrete authorize-donation exception.
	 */
	protected function new_not_found_mutation_exception(
		EntityId $donation_id,
		Throwable $previous,
	): AuthorizeDonationException {

		return new AuthorizeDonationNotFoundException(
			(string) $donation_id->get_value(),
			$previous,
		);
	}

	/**
	 * Creates the authorize-donation exception exposed by this use case.
	 *
	 * @since 0.1.0
	 *
	 * @param UseCaseFailureStage $stage Processing stage where failure happened.
	 * @param string $message Exception message.
	 * @param Throwable|null $previous Previous exception.
	 * @param DonationMutationPreconditionReason|null $reason Optional precondition failure reason.
	 *
	 * @return AuthorizeDonationException Concrete authorize-donation exception.
	 */
	protected function new_mutation_exception(
		UseCaseFailureStage $stage,
		string $message,
		?Throwable $previous = null,
		?DonationMutationPreconditionReason $reason = null,
	): AuthorizeDonationException {

		return new AuthorizeDonationException( $stage, $message, $previous, $reason );
	}

	/**
	 * Authorizes an existing donation.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 *
	 * @return Donation Persisted donation snapshot.
	 */
	public function handle( EntityId $donation_id ): Donation {

		$mutation = DonationMutation::Authorize;
		$donation = $this->require_donation( $donation_id, $mutation );

		try {
			$authorized_donation = $donation->authorize();
		} catch ( DonationDomainException $e ) {
			$this->reject_mutation( $donation_id, $mutation, $e );
		}

		return $this->persist_donation(
			$authorized_donation,
			$mutation,
			new DonationAuthorizedEvent( $authorized_donation->get_id() ),
		);
	}
}
