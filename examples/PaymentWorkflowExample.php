<?php

declare(strict_types=1);

namespace Fundrik\Core\Examples;

use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\DonationCreationData;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutData;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutResult;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\DonationPaymentResult;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\DonationPaymentResultType;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResult;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResultHandler;
use Fundrik\Core\Components\Shared\Application\Url;
use Fundrik\Core\Components\Shared\Domain\Amount;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Demonstrates checkout creation and payment-result processing.
 *
 * @since 1.0.0
 */
final readonly class PaymentWorkflowExample {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param CreateDonationCheckoutHandler $create_checkout Creates donation checkouts.
	 * @param ProcessDonationPaymentResultHandler $process_result Processes payment results.
	 */
	public function __construct(
		private CreateDonationCheckoutHandler $create_checkout,
		private ProcessDonationPaymentResultHandler $process_result,
	) {}

	/**
	 * Creates an idempotent checkout for an existing campaign.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 *
	 * @return CreateDonationCheckoutResult Checkout details.
	 */
	public function create_checkout( EntityId $campaign_id ): CreateDonationCheckoutResult {

		$donation = new DonationCreationData(
			donation_id: EntityId::uuid7(),
			campaign_id: $campaign_id,
			amount: Amount::create( 2_500 ),
		);

		return $this->create_checkout->handle(
			new CreateDonationCheckoutData(
				donation_creation_data: $donation,
				payment_description: 'Donation to the community library',
				success_url: Url::create( 'https://example.com/donations/success' ),
				cancel_url: Url::create( 'https://example.com/donations/cancel' ),
			),
		);
	}

	/**
	 * Processes a successful provider callback.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $donation_id Donation ID from trusted callback metadata.
	 *
	 * @return ProcessDonationPaymentResult Processing result.
	 */
	public function process_success( EntityId $donation_id ): ProcessDonationPaymentResult {

		return $this->process_result->handle(
			new DonationPaymentResult(
				donation_id: $donation_id,
				type: DonationPaymentResultType::Succeeded,
			),
		);
	}
}
