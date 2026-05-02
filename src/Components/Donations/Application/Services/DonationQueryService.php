<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Services;

use Fundrik\Core\Components\Donations\Application\ReadModels\Donation;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadDonationById\ReadDonationByIdException;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadDonationById\ReadDonationByIdHandler;
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
	 */
	public function __construct(
		private ReadDonationByIdHandler $read_donation_by_id,
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
}
