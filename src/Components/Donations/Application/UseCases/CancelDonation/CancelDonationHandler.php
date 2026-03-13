<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CancelDonation;

use Fundrik\Core\Components\Donations\Application\Events\DonationCanceledEvent;
use Fundrik\Core\Components\Donations\Application\UseCases\AbstractDonationMutationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutation;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationDomainException;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Handles canceling an existing donation.
 *
 * @since 0.1.0
 */
final readonly class CancelDonationHandler extends AbstractDonationMutationHandler {

	/**
	 * Returns the exception class exposed by this mutation use case.
	 *
	 * @since 0.1.0
	 *
	 * @return string Mutation exception class.
	 *
	 * @phpstan-return class-string<CancelDonationException>
	 */
	protected function mutation_exception_class(): string {

		return CancelDonationException::class;
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
