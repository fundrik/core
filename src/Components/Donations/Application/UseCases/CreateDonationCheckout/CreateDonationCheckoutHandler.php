<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout;

use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayCheckoutRequest;
use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayCheckoutResult;
use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayPort;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\DonationCreationData;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently\CreateDonationIdempotentlyHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;

/**
 * Handles creating donation checkout workflows.
 *
 * @since 0.1.0
 */
final readonly class CreateDonationCheckoutHandler {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CreateDonationIdempotentlyHandler $create_donation Creates or replays donations.
	 * @param DonationGatewayPort $gateway Creates gateway checkouts.
	 */
	public function __construct(
		private CreateDonationIdempotentlyHandler $create_donation,
		private DonationGatewayPort $gateway,
	) {}

	/**
	 * Creates a donation checkout through the selected gateway.
	 *
	 * @since 0.1.0
	 *
	 * @param CreateDonationCheckoutData $data Checkout creation input.
	 *
	 * @return CreateDonationCheckoutResult Checkout creation result.
	 *
	 * @throws CreateDonationCheckoutException When checkout creation fails.
	 */
	public function handle( CreateDonationCheckoutData $data ): CreateDonationCheckoutResult {

		$donation = $this->ensure_donation( $data->get_donation_creation_data() );

		$gateway_result = $this->create_gateway_checkout(
			$donation,
			$data->get_success_url()->get_value(),
			$data->get_cancel_url()->get_value(),
		);

		return new CreateDonationCheckoutResult(
			donation_id: $donation->get_id()->get_value(),
			campaign_id: $donation->get_campaign_id()->get_value(),
			amount: $donation->get_money()->get_amount()->get_value(),
			currency_code: $donation->get_money()->get_currency()->get_code(),
			redirect_url: $gateway_result->get_redirect_url(),
		);
	}

	/**
	 * Ensures a donation exists for checkout.
	 *
	 * @since 0.1.0
	 *
	 * @param DonationCreationData $data Validated donation creation data.
	 *
	 * @return Donation Created or replayed donation.
	 *
	 * @throws CreateDonationCheckoutException When donation preparation fails.
	 */
	private function ensure_donation( DonationCreationData $data ): Donation {

		try {
			return $this->create_donation->handle( $data )->get_donation();
		} catch ( CreateDonationException $e ) {
			throw new CreateDonationCheckoutException(
				stage: $e->get_stage(),
				message: sprintf(
					'Cannot create checkout for donation "%s": donation could not be prepared.',
					(string) $data->get_donation_id()->get_value(),
				),
				previous: $e,
			);
		}
	}

	/**
	 * Creates the gateway checkout from normalized donation data.
	 *
	 * @since 0.1.0
	 *
	 * @param Donation $donation Created or replayed donation.
	 * @param string $success_url Success callback URL.
	 * @param string $cancel_url Cancellation callback URL.
	 *
	 * @return DonationGatewayCheckoutResult Gateway checkout result.
	 *
	 * @throws CreateDonationCheckoutException When gateway checkout creation fails.
	 */
	private function create_gateway_checkout(
		Donation $donation,
		string $success_url,
		string $cancel_url,
	): DonationGatewayCheckoutResult {

		$request = new DonationGatewayCheckoutRequest(
			donation_id: $donation->get_id()->get_value(),
			campaign_id: $donation->get_campaign_id()->get_value(),
			amount: $donation->get_money()->get_amount()->get_value(),
			currency_code: $donation->get_money()->get_currency()->get_code(),
			success_url: $success_url,
			cancel_url: $cancel_url,
		);

		try {
			return $this->gateway->create_checkout( $request );
		} catch ( DonationGatewayExceptionInterface $e ) {
			throw new CreateDonationCheckoutException(
				stage: UseCaseFailureStage::External,
				message: sprintf(
					'Failed to create checkout for donation "%s".',
					(string) $donation->get_id()->get_value(),
				),
				previous: $e,
			);
		}
	}
}
