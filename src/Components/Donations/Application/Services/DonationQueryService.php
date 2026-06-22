<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Services;

use Fundrik\Core\Components\Donations\Application\ReadModels\Donation;
use Fundrik\Core\Components\Donations\Application\ReadModels\PaginatedDonations;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadDonationById\ReadDonationByIdException;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadDonationById\ReadDonationByIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadPaginatedDonations\ReadPaginatedDonationsException;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadPaginatedDonations\ReadPaginatedDonationsHandler;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;

/**
 * Provides the public entry point for donation read operations.
 *
 * @since 0.1.0
 */
final readonly class DonationQueryService {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param ReadDonationByIdHandler $read_donation_by_id Retrieves donations by ID.
	 * @param ReadPaginatedDonationsHandler $read_donations_page Retrieves paginated donations.
	 */
	public function __construct(
		private ReadDonationByIdHandler $read_donation_by_id,
		private ReadPaginatedDonationsHandler $read_donations_page,
	) {}

	/**
	 * Retrieves a donation by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $donation_id Donation ID.
	 *
	 * @return Donation|null Donation read model if found, null otherwise.
	 *
	 * @throws ReadDonationByIdException When donation retrieval fails.
	 */
	public function find_by_id( int|string|EntityId $donation_id ): ?Donation {

		try {
			return $this->read_donation_by_id->handle( EntityId::create( $donation_id ) );
		} catch ( InvalidEntityIdException $e ) {
			throw new ReadDonationByIdException( $e->getMessage(), previous: $e );
		}
	}

	/**
	 * Returns a paginated list of donations.
	 *
	 * @since 0.1.0
	 *
	 * @param int $page Page number.
	 * @param int $per_page Donations per page.
	 *
	 * @return PaginatedDonations Paginated list of donation read models.
	 *
	 * @throws ReadPaginatedDonationsException When paginated donations retrieval fails.
	 */
	public function paginate( int $page, int $per_page ): PaginatedDonations {

		return $this->read_donations_page->handle( $page, $per_page );
	}
}
