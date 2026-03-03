<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;

/**
 * Handles creating a new donation.
 *
 * @since 0.1.0
 */
final readonly class CreateDonationHandler implements CreateDonationUseCase {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param DonationRepositoryPort $repository Adds donations to storage.
	 */
	public function __construct(
		private DonationRepositoryPort $repository,
	) {}

	/**
	 * Creates a new donation.
	 *
	 * @since 0.1.0
	 *
	 * @param Donation $donation The donation to create.
	 *
	 * @return Donation The persisted donation snapshot.
	 *
	 * @throws CreateDonationException When donation creation fails.
	 */
	public function handle( Donation $donation ): Donation {

		try {
			return $this->repository->insert( $donation );
		} catch ( DonationRepositoryExceptionInterface $e ) {
			throw new CreateDonationException(
				stage: UseCaseFailureStage::Persistence,
				message: sprintf(
					'Failed to create donation "%s".',
					(string) $donation->get_id()->get_value(),
				),
				previous: $e,
			);
		}
	}
}
