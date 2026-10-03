<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout;

use Fundrik\Core\Components\Donations\Application\Events\DonationPendingEvent;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationNotFoundExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayCheckoutRequest;
use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayCheckoutResult;
use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayPort;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\DonationCreationData;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently\CreateDonationIdempotentlyHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationChangeException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusExceptionInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Application\Url;

/**
 * Handles creating donation checkout workflows.
 *
 * @since 1.0.0
 */
final readonly class CreateDonationCheckoutHandler {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Added the `$donations` and `$event_bus` parameters.
	 *
	 * @param CreateDonationIdempotentlyHandler $create_donation Creates or replays donations.
	 * @param DonationGatewayPort $gateway Creates gateway checkouts.
	 * @param DonationRepositoryPort $donations Persists payment associations.
	 * @param ApplicationEventBusPort $event_bus Publishes donation events.
	 */
	public function __construct(
		private CreateDonationIdempotentlyHandler $create_donation,
		private DonationGatewayPort $gateway,
		private DonationRepositoryPort $donations,
		private ApplicationEventBusPort $event_bus,
	) {}

	/**
	 * Creates a donation checkout through the selected gateway.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Persists and returns provider payment IDs.
	 *
	 * @param CreateDonationCheckoutData $data Checkout creation input.
	 *
	 * @return CreateDonationCheckoutResult Checkout creation result.
	 *
	 * @throws CreateDonationCheckoutException When checkout creation fails.
	 */
	public function handle( CreateDonationCheckoutData $data ): CreateDonationCheckoutResult {

		$donation = $this->ensure_donation_is_checkoutable( $data->get_donation_creation_data() );

		$gateway_result = $this->create_gateway_checkout(
			$donation,
			$data->get_payment_description(),
			$data->get_success_url(),
			$data->get_cancel_url(),
		);
		$donation = $this->mark_donation_as_awaiting_payment( $donation, $gateway_result );

		return new CreateDonationCheckoutResult(
			donation_id: $donation->get_id(),
			campaign_id: $donation->get_campaign_id(),
			money: $donation->get_money(),
			payment_id: $gateway_result->get_payment_id(),
			redirect_url: $gateway_result->get_redirect_url(),
		);
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Ensures that a created or pending donation exists for checkout.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationCreationData $data Validated donation creation data.
	 *
	 * @return Donation Created or replayed donation.
	 *
	 * @throws CreateDonationCheckoutException When donation preparation fails or checkout cannot be created.
	 */
	private function ensure_donation_is_checkoutable( DonationCreationData $data ): Donation {

		try {
			$donation = $this->create_donation->handle( $data )->get_donation();
		} catch ( CreateDonationException $e ) {
			throw new CreateDonationCheckoutException(
				stage: $e->get_stage(),
				message: sprintf(
					'Cannot create checkout for donation "%s": donation could not be prepared.',
					$data->get_donation_id()->get_value(),
				),
				previous: $e,
			);
		}

		if ( ! in_array( $donation->get_status(), [ DonationStatus::Created, DonationStatus::Pending ], true ) ) {
			throw new CreateDonationCheckoutException(
				stage: UseCaseFailureStage::Precondition,
				message: sprintf(
					'Cannot create checkout for donation "%s": donation is neither created nor pending.',
					$donation->get_id()->get_value(),
				),
			);
		}

		return $donation;
	}
	// phpcs:enable

	/**
	 * Creates the gateway checkout from normalized donation data.
	 *
	 * @since 1.0.0
	 *
	 * @param Donation $donation Created or replayed donation.
	 * @param string $payment_description Payment description.
	 * @param Url $success_url Success callback URL.
	 * @param Url $cancel_url Cancellation callback URL.
	 *
	 * @return DonationGatewayCheckoutResult Gateway checkout result.
	 *
	 * @throws CreateDonationCheckoutException When gateway checkout creation fails.
	 */
	private function create_gateway_checkout(
		Donation $donation,
		string $payment_description,
		Url $success_url,
		Url $cancel_url,
	): DonationGatewayCheckoutResult {

		$request = new DonationGatewayCheckoutRequest(
			donation_id: $donation->get_id(),
			campaign_id: $donation->get_campaign_id(),
			money: $donation->get_money(),
			payment_description: $payment_description,
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
					$donation->get_id()->get_value(),
				),
				previous: $e,
			);
		}
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Marks the donation as awaiting payment and persists the association.
	 *
	 * @since 1.0.0
	 *
	 * @param Donation $donation Created or pending donation.
	 * @param DonationGatewayCheckoutResult $gateway_result Gateway checkout result.
	 *
	 * @return Donation Persisted donation in pending status.
	 *
	 * @throws CreateDonationCheckoutException When payment attachment fails.
	 */
	private function mark_donation_as_awaiting_payment(
		Donation $donation,
		DonationGatewayCheckoutResult $gateway_result,
	): Donation {

		try {
			$updated_donation = $donation->await_payment( $gateway_result->get_payment_id() );
		} catch ( DonationChangeException $e ) {
			throw new CreateDonationCheckoutException(
				stage: UseCaseFailureStage::Precondition,
				message: sprintf(
					'Cannot create checkout for donation "%s": donation cannot await payment.',
					$donation->get_id()->get_value(),
				),
				previous: $e,
			);
		}

		if ( $updated_donation === $donation ) {
			return $donation;
		}

		try {
			$persisted_donation = $this->donations->update( $updated_donation );
		} catch ( DonationNotFoundExceptionInterface $e ) {
			throw new CreateDonationCheckoutNotFoundException( $donation->get_id(), $e );
		} catch ( DonationRepositoryExceptionInterface $e ) {
			throw new CreateDonationCheckoutException(
				stage: UseCaseFailureStage::Persistence,
				message: sprintf(
					'Failed to attach payment to donation "%s".',
					$donation->get_id()->get_value(),
				),
				previous: $e,
			);
		}

		try {
			$this->event_bus->publish(
				new DonationPendingEvent(
					$persisted_donation->get_id(),
					$gateway_result->get_payment_id(),
				),
			);
		} catch ( ApplicationEventBusExceptionInterface $e ) {
			throw new CreateDonationCheckoutException(
				stage: UseCaseFailureStage::EventPublish,
				message: sprintf(
					'Donation "%s" was moved to pending status, but publishing the pending event failed.',
					$persisted_donation->get_id()->get_value(),
				),
				previous: $e,
			);
		}

		return $persisted_donation;
	}
	// phpcs:enable
}
