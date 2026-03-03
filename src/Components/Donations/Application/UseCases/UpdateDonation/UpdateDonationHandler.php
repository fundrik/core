<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\UpdateDonation;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;

/**
 * Handles updating an existing donation.
 *
 * @since 0.1.0
 */
final readonly class UpdateDonationHandler implements UpdateDonationUseCase {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param DonationRepositoryPort $repository Updates donations in storage.
	 */
	public function __construct(
		private DonationRepositoryPort $repository,
	) {}

	/**
	 * Updates an existing donation.
	 *
	 * @since 0.1.0
	 *
	 * @param Donation $donation The donation to update.
	 *
	 * @return Donation The persisted donation snapshot.
	 *
	 * @throws UpdateDonationException When donation update fails.
	 */
	public function handle( Donation $donation ): Donation {

		try {
			return $this->repository->update( $donation );
		} catch ( DonationRepositoryExceptionInterface $e ) {
			throw new UpdateDonationException(
				stage: UseCaseFailureStage::Persistence,
				message: sprintf(
					'Failed to update donation "%s".',
					(string) $donation->get_id()->get_value(),
				),
				previous: $e,
			);
		}
	}
}
