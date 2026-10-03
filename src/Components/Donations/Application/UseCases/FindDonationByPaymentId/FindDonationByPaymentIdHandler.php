<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\FindDonationByPaymentId;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\PaymentId;

/**
 * Provides authoritative donation lookup by provider payment ID.
 *
 * @since 1.1.0
 */
final readonly class FindDonationByPaymentIdHandler {

	/**
	 * Constructor.
	 *
	 * @since 1.1.0
	 *
	 * @param DonationRepositoryPort $donations Retrieves donation entities from storage.
	 */
	public function __construct(
		private DonationRepositoryPort $donations,
	) {}

	/**
	 * Returns a donation by its provider payment ID.
	 *
	 * @since 1.1.0
	 *
	 * @param PaymentId $payment_id Provider payment ID.
	 *
	 * @return Donation|null Donation entity if found, null otherwise.
	 *
	 * @throws FindDonationByPaymentIdException When donation retrieval fails.
	 */
	public function handle( PaymentId $payment_id ): ?Donation {

		try {
			return $this->donations->find_by_payment_id( $payment_id );
		} catch ( DonationRepositoryExceptionInterface $e ) {
			throw new FindDonationByPaymentIdException(
				sprintf( 'Failed to retrieve donation for payment "%s".', $payment_id->get_value() ),
				previous: $e,
			);
		}
	}
}
