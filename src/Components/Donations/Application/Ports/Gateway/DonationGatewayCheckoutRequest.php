<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Ports\Gateway;

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
	 * @param int|string $donation_id Donation identifier.
	 * @param int|string $campaign_id Campaign identifier.
	 * @param int $amount Donation amount.
	 * @param string $currency_code Donation currency code.
	 * @param string $success_url Success callback URL.
	 * @param string $cancel_url Cancellation callback URL.
	 */
	public function __construct(
		private int|string $donation_id,
		private int|string $campaign_id,
		private int $amount,
		private string $currency_code,
		private string $success_url,
		private string $cancel_url,
	) {}

	/**
	 * Returns the donation identifier.
	 *
	 * @since 0.1.0
	 *
	 * @return int|string Donation identifier.
	 */
	public function get_donation_id(): int|string {

		return $this->donation_id;
	}

	/**
	 * Returns the campaign identifier.
	 *
	 * @since 0.1.0
	 *
	 * @return int|string Campaign identifier.
	 */
	public function get_campaign_id(): int|string {

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
	 * Returns the donation currency code.
	 *
	 * @since 0.1.0
	 *
	 * @return string Donation currency code.
	 */
	public function get_currency_code(): string {

		return $this->currency_code;
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
