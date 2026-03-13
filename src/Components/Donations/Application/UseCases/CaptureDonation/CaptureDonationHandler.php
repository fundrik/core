<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CaptureDonation;

use Fundrik\Core\Components\Donations\Application\Events\DonationCapturedEvent;
use Fundrik\Core\Components\Donations\Application\UseCases\AbstractDonationMutationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutation;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationDomainException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;

/**
 * Handles capturing an existing donation.
 *
 * @since 0.1.0
 */
final readonly class CaptureDonationHandler extends AbstractDonationMutationHandler {

	/**
	 * Returns the exception class exposed by this mutation use case.
	 *
	 * @since 0.1.0
	 *
	 * @return string Mutation exception class.
	 *
	 * @phpstan-return class-string<CaptureDonationException>
	 */
	protected function mutation_exception_class(): string {

		return CaptureDonationException::class;
	}

	/**
	 * Captures an existing donation.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param UtcDateTime|null $at Optional capture timestamp.
	 *
	 * @return Donation Persisted donation snapshot.
	 */
	public function handle( EntityId $donation_id, ?UtcDateTime $at = null ): Donation {

		$mutation = DonationMutation::Capture;
		$donation = $this->require_donation( $donation_id, $mutation );

		try {
			$captured_donation = $donation->capture( $at );
		} catch ( DonationDomainException $e ) {
			$this->reject_mutation( $donation_id, $mutation, $e );
		}

		return $this->persist_donation(
			$captured_donation,
			$mutation,
			new DonationCapturedEvent( $captured_donation->get_id() ),
		);
	}
}
