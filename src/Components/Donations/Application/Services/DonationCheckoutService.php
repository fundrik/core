<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Services;

use Fundrik\Core\Components\Donations\Application\Commands\CreateDonationCheckoutCommand;
use Fundrik\Core\Components\Donations\Application\Commands\DonationPaymentEvent;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\DonationCreationData;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutData;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutResult;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentEvent\ProcessDonationPaymentEventException;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentEvent\ProcessDonationPaymentEventHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentEvent\ProcessDonationPaymentEventResult;
use Fundrik\Core\Components\Shared\Application\Exceptions\InvalidUrlException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Url;
use Fundrik\Core\Components\Shared\Domain\Amount;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidAmountException;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;

/**
 * Provides the public entry point for donation checkout operations.
 *
 * @since 0.1.0
 */
final readonly class DonationCheckoutService {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CreateDonationCheckoutHandler $create_checkout_handler Creates donation checkouts.
	 * @param ProcessDonationPaymentEventHandler $process_payment_event_handler Processes normalized payment events.
	 */
	public function __construct(
		private CreateDonationCheckoutHandler $create_checkout_handler,
		private ProcessDonationPaymentEventHandler $process_payment_event_handler,
	) {}

	/**
	 * Creates a donation checkout through the selected gateway.
	 *
	 * @since 0.1.0
	 *
	 * @param CreateDonationCheckoutCommand $command Public checkout creation input.
	 *
	 * @return CreateDonationCheckoutResult Public checkout creation result.
	 *
	 * @throws CreateDonationCheckoutException When checkout creation fails.
	 */
	public function create_checkout( CreateDonationCheckoutCommand $command ): CreateDonationCheckoutResult {

		try {
			$data = new CreateDonationCheckoutData(
				donation_creation_data: new DonationCreationData(
					donation_id: EntityId::create( $command->get_donation_id() ),
					campaign_id: EntityId::create( $command->get_campaign_id() ),
					amount: Amount::create( $command->get_amount() ),
				),
				success_url: Url::create( $command->get_success_url() ),
				cancel_url: Url::create( $command->get_cancel_url() ),
			);
		} catch ( InvalidEntityIdException | InvalidAmountException | InvalidUrlException $e ) {
			throw new CreateDonationCheckoutException(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
			);
		}

		return $this->create_checkout_handler->handle( $data );
	}

	/**
	 * Processes a normalized payment event idempotently.
	 *
	 * @since 0.1.0
	 *
	 * @param DonationPaymentEvent $event Normalized payment event.
	 *
	 * @return ProcessDonationPaymentEventResult Event processing result.
	 *
	 * @throws ProcessDonationPaymentEventException When event processing fails.
	 */
	public function process_payment_event( DonationPaymentEvent $event ): ProcessDonationPaymentEventResult {

		return $this->process_payment_event_handler->handle( $event );
	}
}
