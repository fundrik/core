<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Ports\Gateway;

use Fundrik\Core\Components\Shared\Application\Url;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Money;

/**
 * Represents normalized input for creating gateway checkout.
 *
 * @since 0.1.0
 */
final readonly class DonationGatewayCheckoutRequest {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation identifier.
	 * @param EntityId $campaign_id Campaign identifier.
	 * @param Money $money Donation money.
	 * @param Url $success_url Success callback URL.
	 * @param Url $cancel_url Cancellation callback URL.
	 */
	public function __construct(
		private EntityId $donation_id,
		private EntityId $campaign_id,
		private Money $money,
		private Url $success_url,
		private Url $cancel_url,
	) {}

	/**
	 * Returns the donation identifier.
	 *
	 * @since 0.1.0
	 *
	 * @return EntityId Donation identifier.
	 */
	public function get_donation_id(): EntityId {

		return $this->donation_id;
	}

	/**
	 * Returns the campaign identifier.
	 *
	 * @since 0.1.0
	 *
	 * @return EntityId Campaign identifier.
	 */
	public function get_campaign_id(): EntityId {

		return $this->campaign_id;
	}

	/**
	 * Returns the donation money.
	 *
	 * @since 0.1.0
	 *
	 * @return Money Donation money.
	 */
	public function get_money(): Money {

		return $this->money;
	}

	/**
	 * Returns the success callback URL.
	 *
	 * @since 0.1.0
	 *
	 * @return Url Success callback URL.
	 */
	public function get_success_url(): Url {

		return $this->success_url;
	}

	/**
	 * Returns the cancellation callback URL.
	 *
	 * @since 0.1.0
	 *
	 * @return Url Cancellation callback URL.
	 */
	public function get_cancel_url(): Url {

		return $this->cancel_url;
	}
}
