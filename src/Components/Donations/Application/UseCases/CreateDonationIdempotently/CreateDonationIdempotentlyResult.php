<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently;

use Fundrik\Core\Components\Donations\Domain\Donation;

/**
 * Represents the result of idempotent donation creation.
 *
 * @since 0.1.0
 */
final readonly class CreateDonationIdempotentlyResult {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param Donation $donation Created or replayed donation.
	 * @param CreateDonationIdempotentlyStatus $status Idempotent creation status.
	 */
	public function __construct(
		private Donation $donation,
		private CreateDonationIdempotentlyStatus $status,
	) {}

	/**
	 * Returns the created or replayed donation.
	 *
	 * @since 0.1.0
	 *
	 * @return Donation Created or replayed donation.
	 */
	public function get_donation(): Donation {

		return $this->donation;
	}

	/**
	 * Returns the idempotent creation status.
	 *
	 * @since 0.1.0
	 *
	 * @return CreateDonationIdempotentlyStatus Idempotent creation status.
	 */
	public function get_status(): CreateDonationIdempotentlyStatus {

		return $this->status;
	}
}
