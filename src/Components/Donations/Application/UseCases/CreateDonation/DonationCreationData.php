<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation;

use Fundrik\Core\Components\Shared\Domain\Amount;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Represents validated input for creating a donation.
 *
 * @since 0.1.0
 */
final readonly class DonationCreationData {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param EntityId $campaign_id Campaign ID.
	 * @param Amount $amount Donation amount.
	 */
	public function __construct(
		private EntityId $donation_id,
		private EntityId $campaign_id,
		private Amount $amount,
	) {}

	/**
	 * Returns the donation ID.
	 *
	 * @since 0.1.0
	 *
	 * @return EntityId Donation ID.
	 */
	public function get_donation_id(): EntityId {

		return $this->donation_id;
	}

	/**
	 * Returns the campaign ID.
	 *
	 * @since 0.1.0
	 *
	 * @return EntityId Campaign ID.
	 */
	public function get_campaign_id(): EntityId {

		return $this->campaign_id;
	}

	/**
	 * Returns the donation amount.
	 *
	 * @since 0.1.0
	 *
	 * @return Amount Donation amount.
	 */
	public function get_amount(): Amount {

		return $this->amount;
	}
}
