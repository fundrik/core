<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Services;

use Fundrik\Core\Components\Donations\Application\ReadModels\DonationDetails;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationDetailsById\FindDonationDetailsByIdException;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationDetailsById\FindDonationDetailsByIdHandler;
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
	 * @param FindDonationDetailsByIdHandler $find_donation_details_by_id Retrieves donation details by ID.
	 */
	public function __construct(
		private FindDonationDetailsByIdHandler $find_donation_details_by_id,
	) {}

	/**
	 * Retrieves donation details by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $donation_id Donation ID.
	 *
	 * @return DonationDetails|null Donation details if found, null otherwise.
	 *
	 * @throws FindDonationDetailsByIdException When donation details retrieval fails.
	 */
	public function find_by_id( int|string|EntityId $donation_id ): ?DonationDetails {

		try {
			return $this->find_donation_details_by_id->handle( EntityId::create( $donation_id ) );
		} catch ( InvalidEntityIdException $e ) {
			throw new FindDonationDetailsByIdException( $e->getMessage(), previous: $e );
		}
	}
}
