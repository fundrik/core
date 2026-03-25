<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Commands;

use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Represents the public command for creating a donation.
 *
 * @since 0.1.0
 */
final readonly class CreateDonationCommand {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $id Donation identifier.
	 * @param int|string|EntityId $campaign_id Campaign identifier.
	 * @param int $amount Donation amount.
	 */
	public function __construct(
		private int|string|EntityId $id,
		private int|string|EntityId $campaign_id,
		private int $amount,
	) {}

	/**
	 * Returns the donation identifier.
	 *
	 * @since 0.1.0
	 *
	 * @return int|string|EntityId Donation identifier.
	 */
	public function get_id(): int|string|EntityId {

		return $this->id;
	}

	/**
	 * Returns the campaign identifier.
	 *
	 * @since 0.1.0
	 *
	 * @return int|string|EntityId Campaign identifier.
	 */
	public function get_campaign_id(): int|string|EntityId {

		return $this->campaign_id;
	}

	/**
	 * Returns the donation amount.
	 *
	 * @since 0.1.0
	 *
	 * @return int Donation amount.
	 */
	public function get_amount(): int {

		return $this->amount;
	}
}
