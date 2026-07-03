<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout;

use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\DonationCreationData;
use Fundrik\Core\Components\Shared\Application\Url;

/**
 * Represents input for creating a donation checkout.
 *
 * @since 0.1.0
 */
final readonly class CreateDonationCheckoutData {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param DonationCreationData $donation_creation_data Donation creation data.
	 * @param Url $success_url Success URL.
	 * @param Url $cancel_url Cancellation URL.
	 */
	public function __construct(
		private DonationCreationData $donation_creation_data,
		private Url $success_url,
		private Url $cancel_url,
	) {}

	/**
	 * Returns the donation creation data.
	 *
	 * @since 0.1.0
	 *
	 * @return DonationCreationData Donation creation data.
	 */
	public function get_donation_creation_data(): DonationCreationData {

		return $this->donation_creation_data;
	}

	/**
	 * Returns the success URL.
	 *
	 * @since 0.1.0
	 *
	 * @return Url Success URL.
	 */
	public function get_success_url(): Url {

		return $this->success_url;
	}

	/**
	 * Returns the cancellation URL.
	 *
	 * @since 0.1.0
	 *
	 * @return Url Cancellation URL.
	 */
	public function get_cancel_url(): Url {

		return $this->cancel_url;
	}
}
