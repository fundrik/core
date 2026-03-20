<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Services;

use Fundrik\Core\Components\Donations\Application\ReadModels\DonationDetails;
use Fundrik\Core\Components\Donations\Application\ReadModels\DonationDetailsMapper;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdException;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdHandler;
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
	 * @param FindDonationByIdHandler $find_donation_by_id Retrieves a donation by its ID.
	 * @param DonationDetailsMapper $donation_details_mapper Maps domain donations to public details.
	 */
	public function __construct(
		private FindDonationByIdHandler $find_donation_by_id,
		private DonationDetailsMapper $donation_details_mapper,
	) {}

	/**
	 * Retrieves a donation by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $donation_id Donation ID.
	 *
	 * @return DonationDetails|null Donation details if found, null otherwise.
	 *
	 * @throws FindDonationByIdException When donation retrieval fails.
	 */
	public function find_by_id( int|string|EntityId $donation_id ): ?DonationDetails {

		try {
			$donation = $this->find_donation_by_id->handle( EntityId::create( $donation_id ) );
		} catch ( InvalidEntityIdException $e ) {
			throw new FindDonationByIdException( $e->getMessage(), previous: $e );
		}

		if ( $donation === null ) {
			return null;
		}

		return $this->donation_details_mapper->map( $donation );
	}
}
