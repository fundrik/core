<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation;

use Fundrik\Core\Components\Donations\Application\Events\DonationRefundedEvent;
use Fundrik\Core\Components\Donations\Application\UseCases\AbstractDonationMutationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutation;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationDomainException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;

/**
 * Handles refunding an existing donation.
 *
 * @since 0.1.0
 */
final readonly class RefundDonationHandler extends AbstractDonationMutationHandler implements RefundDonationUseCase {

	/**
	 * Returns the exception class exposed by this mutation use case.
	 *
	 * @since 0.1.0
	 *
	 * @return string Mutation exception class.
	 *
	 * @phpstan-return class-string<RefundDonationException>
	 */
	protected function mutation_exception_class(): string {

		return RefundDonationException::class;
	}

	/**
	 * Refunds an existing donation.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param UtcDateTime|null $at Optional refund timestamp.
	 *
	 * @return Donation Persisted donation snapshot.
	 */
	public function handle( EntityId $donation_id, ?UtcDateTime $at = null ): Donation {

		$mutation = DonationMutation::Refund;
		$donation = $this->require_donation( $donation_id, $mutation );

		try {
			$refunded_donation = $donation->refund( $at );
		} catch ( DonationDomainException $e ) {
			$this->reject_mutation( $donation_id, $mutation, $e );
		}

		return $this->persist_donation(
			$refunded_donation,
			$mutation,
			new DonationRefundedEvent( $refunded_donation->get_id() ),
		);
	}
}
