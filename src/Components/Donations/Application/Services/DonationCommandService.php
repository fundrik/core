<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Services;

use Fundrik\Core\Components\Donations\Application\Commands\CreateDonationCommand;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\DonationCreationData;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RejectDonation\RejectDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\RejectDonation\RejectDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\SucceedDonation\SucceedDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\SucceedDonation\SucceedDonationHandler;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Domain\Amount;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidAmountException;
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
	 * @param SucceedDonationHandler $succeed_donation Marks donations as succeeded.
	 * @param RejectDonationHandler $reject_donation Marks donations as rejected.
	 * @param RefundDonationHandler $refund_donation Refunds donations.
	 */
	public function __construct(
		private CreateDonationHandler $create_donation,
		private SucceedDonationHandler $succeed_donation,
		private RejectDonationHandler $reject_donation,
		private RefundDonationHandler $refund_donation,
	) {}

	/**
	 * Creates a new donation.
	 *
	 * @since 0.1.0
	 *
	 * @param CreateDonationCommand $command Public donation creation input.
	 *
	 * @throws CreateDonationException When creation fails.
	 */
	public function create( CreateDonationCommand $command ): void {

		try {
			$donation = new DonationCreationData(
				donation_id: EntityId::create( $command->get_id() ),
				campaign_id: EntityId::create( $command->get_campaign_id() ),
				amount: Amount::create( $command->get_amount() ),
			);
		} catch ( InvalidEntityIdException | InvalidAmountException $e ) {
			throw new CreateDonationException(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
			);
		}

		$this->create_donation->handle( $donation );
	}

	/**
	 * Marks an existing donation as succeeded.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $donation_id Donation ID.
	 *
	 * @throws SucceedDonationException When success marking fails.
	 */
	public function succeed( int|string|EntityId $donation_id ): void {

		try {
			$entity_id = EntityId::create( $donation_id );
		} catch ( InvalidEntityIdException $e ) {
			throw new SucceedDonationException(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
			);
		}

		$this->succeed_donation->handle( $entity_id );
	}

	/**
	 * Marks an existing donation as rejected.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $donation_id Donation ID.
	 *
	 * @throws RejectDonationException When rejection fails.
	 */
	public function reject( int|string|EntityId $donation_id ): void {

		try {
			$entity_id = EntityId::create( $donation_id );
		} catch ( InvalidEntityIdException $e ) {
			throw new RejectDonationException(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
			);
		}

		$this->reject_donation->handle( $entity_id );
	}

	/**
	 * Refunds an existing donation.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $donation_id Donation ID.
	 *
	 * @throws RefundDonationException When refund fails.
	 */
	public function refund( int|string|EntityId $donation_id ): void {

		try {
			$entity_id = EntityId::create( $donation_id );
		} catch ( InvalidEntityIdException $e ) {
			throw new RefundDonationException(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
			);
		}

		$this->refund_donation->handle( $entity_id );
	}
}
