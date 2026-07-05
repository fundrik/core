<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout;

use Fundrik\Core\Components\Shared\Application\Url;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Money;

/**
 * Represents the result of creating a donation checkout.
 *
 * @since 0.1.0
 */
final readonly class CreateDonationCheckoutResult {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation identifier.
	 * @param EntityId $campaign_id Campaign identifier.
	 * @param Money $money Donation money.
	 * @param Url $redirect_url Checkout redirect URL.
	 */
	public function __construct(
		private EntityId $donation_id,
		private EntityId $campaign_id,
		private Money $money,
		private Url $redirect_url,
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
	 * Returns the donation amount.
	 *
	 * @since 0.1.0
	 *
	 * @return Money Donation money.
	 */
	public function get_money(): Money {

		return $this->money;
	}

	/**
	 * Returns the checkout redirect URL.
	 *
	 * @since 0.1.0
	 *
	 * @return Url Checkout redirect URL.
	 */
	public function get_redirect_url(): Url {

		return $this->redirect_url;
	}

	/**
	 * Returns the donation amount.
	 *
	 * @since 0.1.0
	 *
	 * @return int Donation amount.
	 */
	public function get_amount(): int {

		return $this->money->get_amount()->get_value();
	}

	/**
	 * Returns the donation currency code.
	 *
	 * @since 0.1.0
	 *
	 * @return string Donation currency code.
	 */
	public function get_currency_code(): string {

		return $this->money->get_currency()->get_code();
	}
}
