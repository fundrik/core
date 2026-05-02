<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\ReadDonationById;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRead\DonationReadExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRead\DonationReadPort;
use Fundrik\Core\Components\Donations\Application\ReadModels\Donation;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Handles retrieving a donation read model by its ID.
 *
 * @since 0.1.0
 */
final readonly class ReadDonationByIdHandler {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param DonationReadPort $donation_read Retrieves donations from storage.
	 */
	public function __construct(
		private DonationReadPort $donation_read,
	) {}

	/**
	 * Retrieves a donation by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID to retrieve.
	 *
	 * @return Donation|null Donation read model if found, null otherwise.
	 *
	 * @throws ReadDonationByIdException When donation retrieval fails.
	 */
	public function handle( EntityId $donation_id ): ?Donation {

		try {
			return $this->donation_read->find_by_id( $donation_id );
		} catch ( DonationReadExceptionInterface $e ) {
			throw new ReadDonationByIdException(
				sprintf(
					'Failed to retrieve donation "%s".',
					(string) $donation_id->get_value(),
				),
				previous: $e,
			);
		}
	}
}
