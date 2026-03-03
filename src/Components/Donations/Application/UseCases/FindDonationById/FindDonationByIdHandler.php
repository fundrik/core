<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Handles retrieving a donation by its ID.
 *
 * @since 0.1.0
 */
final readonly class FindDonationByIdHandler implements FindDonationByIdUseCase {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param DonationRepositoryPort $repository Retrieves donations from storage.
	 */
	public function __construct(
		private DonationRepositoryPort $repository,
	) {}

	/**
	 * Retrieves a donation by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id The donation ID to retrieve.
	 *
	 * @return Donation|null The donation if found, null otherwise.
	 *
	 * @throws FindDonationByIdException When donation retrieval fails.
	 */
	public function handle( EntityId $donation_id ): ?Donation {

		try {
			return $this->repository->find_by_id( $donation_id );
		} catch ( DonationRepositoryExceptionInterface $e ) {
			throw new FindDonationByIdException(
				sprintf(
					'Failed to retrieve donation "%s".',
					(string) $donation_id->get_value(),
				),
				previous: $e,
			);
		}
	}
}
