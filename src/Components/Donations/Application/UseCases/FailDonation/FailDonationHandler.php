<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\FailDonation;

use Fundrik\Core\Components\Donations\Application\Events\DonationFailedEvent;
use Fundrik\Core\Components\Donations\Application\UseCases\AbstractDonationMutationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutation;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationDomainException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;

/**
 * Handles marking an existing donation as failed.
 *
 * @since 0.1.0
 */
final readonly class FailDonationHandler extends AbstractDonationMutationHandler implements FailDonationUseCase {

	/**
	 * Returns the exception class exposed by this mutation use case.
	 *
	 * @since 0.1.0
	 *
	 * @return string Mutation exception class.
	 *
	 * @phpstan-return class-string<FailDonationException>
	 */
	protected function mutation_exception_class(): string {

		return FailDonationException::class;
	}

	/**
	 * Marks an existing donation as failed.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param UtcDateTime|null $at Optional failure timestamp.
	 *
	 * @return Donation Persisted donation snapshot.
	 */
	public function handle( EntityId $donation_id, ?UtcDateTime $at = null ): Donation {

		$mutation = DonationMutation::Fail;
		$donation = $this->require_donation( $donation_id, $mutation );

		try {
			$failed_donation = $donation->fail( $at );
		} catch ( DonationDomainException $e ) {
			$this->reject_mutation( $donation_id, $mutation, $e );
		}

		return $this->persist_donation(
			$failed_donation,
			$mutation,
			new DonationFailedEvent( $failed_donation->get_id() ),
		);
	}
}
