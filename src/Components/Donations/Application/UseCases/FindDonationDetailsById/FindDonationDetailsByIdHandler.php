<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\FindDonationDetailsById;

use Fundrik\Core\Components\Donations\Application\Ports\DonationDetailsRead\DonationDetailsReadExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\DonationDetailsRead\DonationDetailsReadPort;
use Fundrik\Core\Components\Donations\Application\ReadModels\DonationDetails;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Handles retrieving donation details by its ID.
 *
 * @since 0.1.0
 */
final readonly class FindDonationDetailsByIdHandler {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param DonationDetailsReadPort $donation_details_read Retrieves donation details from storage.
	 */
	public function __construct(
		private DonationDetailsReadPort $donation_details_read,
	) {}

	/**
	 * Retrieves donation details by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID to retrieve.
	 *
	 * @return DonationDetails|null Donation details if found, null otherwise.
	 *
	 * @throws FindDonationDetailsByIdException When donation details retrieval fails.
	 */
	public function handle( EntityId $donation_id ): ?DonationDetails {

		try {
			return $this->donation_details_read->find_by_id( $donation_id );
		} catch ( DonationDetailsReadExceptionInterface $e ) {
			throw new FindDonationDetailsByIdException(
				sprintf(
					'Failed to retrieve donation "%s".',
					(string) $donation_id->get_value(),
				),
				previous: $e,
			);
		}
	}
}
