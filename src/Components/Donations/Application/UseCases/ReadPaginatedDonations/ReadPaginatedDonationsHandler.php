<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\ReadPaginatedDonations;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRead\DonationReadExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRead\DonationReadPort;
use Fundrik\Core\Components\Donations\Application\ReadModels\PaginatedDonations;

/**
 * Handles retrieving paginated donations.
 *
 * @since 0.1.0
 */
final readonly class ReadPaginatedDonationsHandler {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param DonationReadPort $donation_read Retrieves paginated donations from storage.
	 */
	public function __construct(
		private DonationReadPort $donation_read,
	) {}

	/**
	 * Returns a paginated list of donations.
	 *
	 * @since 0.1.0
	 *
	 * @param int $page Page number.
	 * @param int $per_page Donations per page.
	 *
	 * @return PaginatedDonations Paginated list of donations.
	 *
	 * @throws ReadPaginatedDonationsException When page retrieval fails.
	 */
	public function handle( int $page, int $per_page ): PaginatedDonations {

		if ( $page <= 0 ) {
			throw new ReadPaginatedDonationsException(
				sprintf( 'Page must be a positive integer. Given: %d.', $page ),
			);
		}

		if ( $per_page <= 0 ) {
			throw new ReadPaginatedDonationsException(
				sprintf( 'Items per page must be a positive integer. Given: %d.', $per_page ),
			);
		}

		try {
			return $this->donation_read->paginate( $page, $per_page );
		} catch ( DonationReadExceptionInterface $e ) {
			throw new ReadPaginatedDonationsException( 'Failed to retrieve paginated donations.', previous: $e );
		}
	}
}
