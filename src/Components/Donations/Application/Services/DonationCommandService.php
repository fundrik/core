<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Services;

use Fundrik\Core\Components\Donations\Application\Commands\CreateDonationCommand;
use Fundrik\Core\Components\Donations\Application\ReadModels\DonationDetails;
use Fundrik\Core\Components\Donations\Application\ReadModels\DonationDetailsMapper;
use Fundrik\Core\Components\Donations\Application\UseCases\AuthorizeDonation\AuthorizeDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\AuthorizeDonation\AuthorizeDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CancelDonation\CancelDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\CancelDonation\CancelDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CaptureDonation\CaptureDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\CaptureDonation\CaptureDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FailDonation\FailDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\FailDonation\FailDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationHandler;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationFactoryException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;

/**
 * Provides the public entry point for donation write operations.
 *
 * @since 0.1.0
 */
final readonly class DonationCommandService {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CreateDonationHandler $create_donation Creates new donations.
	 * @param DonationFactory $donation_factory Creates donations from public input.
	 * @param DonationDetailsMapper $donation_details_mapper Maps domain donations to public details.
	 * @param AuthorizeDonationHandler $authorize_donation Authorizes donations.
	 * @param CaptureDonationHandler $capture_donation Captures donations.
	 * @param FailDonationHandler $fail_donation Marks donations as failed.
	 * @param RefundDonationHandler $refund_donation Refunds donations.
	 * @param CancelDonationHandler $cancel_donation Cancels donations.
	 */
	public function __construct(
		private CreateDonationHandler $create_donation,
		private DonationFactory $donation_factory,
		private DonationDetailsMapper $donation_details_mapper,
		private AuthorizeDonationHandler $authorize_donation,
		private CaptureDonationHandler $capture_donation,
		private FailDonationHandler $fail_donation,
		private RefundDonationHandler $refund_donation,
		private CancelDonationHandler $cancel_donation,
	) {}

	/**
	 * Creates a new donation.
	 *
	 * @since 0.1.0
	 *
	 * @param CreateDonationCommand $command Public donation creation input.
	 *
	 * @return DonationDetails Persisted donation details.
	 *
	 * @throws CreateDonationException When creation fails.
	 */
	public function create( CreateDonationCommand $command ): DonationDetails {

		try {
			$donation = $this->donation_factory->create_pending_from_primitives(
				id: $command->get_id(),
				campaign_id: $command->get_campaign_id(),
				amount: $command->get_amount(),
				currency_code: $command->get_currency_code(),
			);
		} catch ( DonationFactoryException $e ) {
			throw new CreateDonationException(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
			);
		}

		return $this->donation_details_mapper->map( $this->create_donation->handle( $donation ) );
	}

	/**
	 * Authorizes an existing donation.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $donation_id Donation ID.
	 *
	 * @return DonationDetails Persisted donation details.
	 *
	 * @throws AuthorizeDonationException When authorization fails.
	 */
	public function authorize( int|string|EntityId $donation_id ): DonationDetails {

		try {
			$entity_id = EntityId::create( $donation_id );
		} catch ( InvalidEntityIdException $e ) {
			throw new AuthorizeDonationException(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
			);
		}

		return $this->donation_details_mapper->map(
			$this->authorize_donation->handle( $entity_id ),
		);
	}

	/**
	 * Captures an existing donation.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $donation_id Donation ID.
	 *
	 * @return DonationDetails Persisted donation details.
	 *
	 * @throws CaptureDonationException When capture fails.
	 */
	public function capture( int|string|EntityId $donation_id ): DonationDetails {

		try {
			$entity_id = EntityId::create( $donation_id );
		} catch ( InvalidEntityIdException $e ) {
			throw new CaptureDonationException(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
			);
		}

		return $this->donation_details_mapper->map(
			$this->capture_donation->handle( $entity_id ),
		);
	}

	/**
	 * Marks an existing donation as failed.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $donation_id Donation ID.
	 *
	 * @return DonationDetails Persisted donation details.
	 *
	 * @throws FailDonationException When failure marking fails.
	 */
	public function fail( int|string|EntityId $donation_id ): DonationDetails {

		try {
			$entity_id = EntityId::create( $donation_id );
		} catch ( InvalidEntityIdException $e ) {
			throw new FailDonationException(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
			);
		}

		return $this->donation_details_mapper->map(
			$this->fail_donation->handle( $entity_id ),
		);
	}

	/**
	 * Refunds an existing donation.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $donation_id Donation ID.
	 *
	 * @return DonationDetails Persisted donation details.
	 *
	 * @throws RefundDonationException When refund fails.
	 */
	public function refund( int|string|EntityId $donation_id ): DonationDetails {

		try {
			$entity_id = EntityId::create( $donation_id );
		} catch ( InvalidEntityIdException $e ) {
			throw new RefundDonationException(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
			);
		}

		return $this->donation_details_mapper->map(
			$this->refund_donation->handle( $entity_id ),
		);
	}

	/**
	 * Cancels an existing donation.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $donation_id Donation ID.
	 *
	 * @return DonationDetails Persisted donation details.
	 *
	 * @throws CancelDonationException When cancellation fails.
	 */
	public function cancel( int|string|EntityId $donation_id ): DonationDetails {

		try {
			$entity_id = EntityId::create( $donation_id );
		} catch ( InvalidEntityIdException $e ) {
			throw new CancelDonationException(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
			);
		}

		return $this->donation_details_mapper->map(
			$this->cancel_donation->handle( $entity_id ),
		);
	}
}
