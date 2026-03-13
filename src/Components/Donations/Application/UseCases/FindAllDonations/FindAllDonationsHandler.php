<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\FindAllDonations;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Domain\Donation;

/**
 * Handles retrieving all donations.
 *
 * @since 0.1.0
 */
final readonly class FindAllDonationsHandler {

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
	 * Retrieves all donations.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, Donation> The list of donations.
	 *
	 * @phpstan-return list<Donation>
	 *
	 * @throws FindAllDonationsException When donation retrieval fails.
	 */
	public function handle(): array {

		try {
			return $this->repository->find_all();
		} catch ( DonationRepositoryExceptionInterface $e ) {
			throw new FindAllDonationsException( 'Failed to retrieve donations.', previous: $e );
		}
	}
}
