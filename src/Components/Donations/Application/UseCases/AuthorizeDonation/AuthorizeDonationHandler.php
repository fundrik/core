<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\AuthorizeDonation;

use Fundrik\Core\Components\Donations\Application\Events\DonationAuthorizedEvent;
use Fundrik\Core\Components\Donations\Application\UseCases\AbstractDonationMutationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutation;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationDomainException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;

// phpcs:disable SlevomatCodingStandard.Files.LineLength.LineTooLong
/**
 * Handles authorizing an existing donation.
 *
 * @since 0.1.0
 */
final readonly class AuthorizeDonationHandler extends AbstractDonationMutationHandler implements AuthorizeDonationUseCase {

	/**
	 * Returns the exception class exposed by this mutation use case.
	 *
	 * @since 0.1.0
	 *
	 * @return string Mutation exception class.
	 *
	 * @phpstan-return class-string<AuthorizeDonationException>
	 */
	protected function mutation_exception_class(): string {

		return AuthorizeDonationException::class;
	}

	/**
	 * Authorizes an existing donation.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param UtcDateTime|null $at Optional authorization timestamp.
	 *
	 * @return Donation Persisted donation snapshot.
	 */
	public function handle( EntityId $donation_id, ?UtcDateTime $at = null ): Donation {

		$mutation = DonationMutation::Authorize;
		$donation = $this->require_donation( $donation_id, $mutation );

		try {
			$authorized_donation = $donation->authorize( $at );
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
// phpcs:enable
