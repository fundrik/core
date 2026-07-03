<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Commands;

use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Represents the public command for creating a donation checkout.
 *
 * @since 0.1.0
 */
final readonly class CreateDonationCheckoutCommand {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $donation_id Donation identifier.
	 * @param int|string|EntityId $campaign_id Campaign identifier.
	 * @param int $amount Donation amount.
	 * @param string $success_url Success callback URL.
	 * @param string $cancel_url Cancellation callback URL.
	 */
	public function __construct(
		private int|string|EntityId $donation_id,
		private int|string|EntityId $campaign_id,
		private int $amount,
		private string $success_url,
		private string $cancel_url,
	) {}

	/**
	 * Returns the donation identifier.
	 *
	 * @since 0.1.0
	 *
	 * @return int|string|EntityId Donation identifier.
	 */
	public function get_donation_id(): int|string|EntityId {

		return $this->donation_id;
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

	/**
	 * Returns the success callback URL.
	 *
	 * @since 0.1.0
	 *
	 * @return string Success callback URL.
	 */
	public function get_success_url(): string {

		return $this->success_url;
	}

	/**
	 * Returns the cancellation callback URL.
	 *
	 * @since 0.1.0
	 *
	 * @return string Cancellation callback URL.
	 */
	public function get_cancel_url(): string {

		return $this->cancel_url;
	}
}
