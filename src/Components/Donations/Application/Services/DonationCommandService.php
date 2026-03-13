<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Services;

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
use Fundrik\Core\Components\Donations\Application\UseCases\UpdateDonation\UpdateDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\UpdateDonation\UpdateDonationHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;

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
	 * @param UpdateDonationHandler $update_donation Updates existing donation snapshots.
	 * @param AuthorizeDonationHandler $authorize_donation Authorizes donations.
	 * @param CaptureDonationHandler $capture_donation Captures donations.
	 * @param FailDonationHandler $fail_donation Marks donations as failed.
	 * @param RefundDonationHandler $refund_donation Refunds donations.
	 * @param CancelDonationHandler $cancel_donation Cancels donations.
	 */
	public function __construct(
		private CreateDonationHandler $create_donation,
		private UpdateDonationHandler $update_donation,
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
	 * @param Donation $donation Donation to create.
	 *
	 * @return Donation Persisted donation snapshot.
	 *
	 * @throws CreateDonationException When creation fails.
	 */
	public function create( Donation $donation ): Donation {

		return $this->create_donation->handle( $donation );
	}

	/**
	 * Updates an existing donation snapshot.
	 *
	 * @since 0.1.0
	 *
	 * @param Donation $donation Donation to update.
	 *
	 * @return Donation Persisted donation snapshot.
	 *
	 * @throws UpdateDonationException When update fails.
	 */
	public function update( Donation $donation ): Donation {

		return $this->update_donation->handle( $donation );
	}

	/**
	 * Authorizes an existing donation.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param UtcDateTime|null $at Optional authorization timestamp.
	 *
	 * @return Donation Persisted donation snapshot.
	 *
	 * @throws AuthorizeDonationException When authorization fails.
	 */
	public function authorize( EntityId $donation_id, ?UtcDateTime $at = null ): Donation {

		return $this->authorize_donation->handle( $donation_id, $at );
	}

	/**
	 * Captures an existing donation.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param UtcDateTime|null $at Optional capture timestamp.
	 *
	 * @return Donation Persisted donation snapshot.
	 *
	 * @throws CaptureDonationException When capture fails.
	 */
	public function capture( EntityId $donation_id, ?UtcDateTime $at = null ): Donation {

		return $this->capture_donation->handle( $donation_id, $at );
	}

	/**
	 * Marks an existing donation as failed.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param UtcDateTime|null $at Optional failure timestamp.
	 *
	 * @return Donation Persisted donation snapshot.
	 *
	 * @throws FailDonationException When failure marking fails.
	 */
	public function fail( EntityId $donation_id, ?UtcDateTime $at = null ): Donation {

		return $this->fail_donation->handle( $donation_id, $at );
	}

	/**
	 * Refunds an existing donation.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param UtcDateTime|null $at Optional refund timestamp.
	 *
	 * @return Donation Persisted donation snapshot.
	 *
	 * @throws RefundDonationException When refund fails.
	 */
	public function refund( EntityId $donation_id, ?UtcDateTime $at = null ): Donation {

		return $this->refund_donation->handle( $donation_id, $at );
	}

	/**
	 * Cancels an existing donation.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param UtcDateTime|null $at Optional cancellation timestamp.
	 *
	 * @return Donation Persisted donation snapshot.
	 *
	 * @throws CancelDonationException When cancellation fails.
	 */
	public function cancel( EntityId $donation_id, ?UtcDateTime $at = null ): Donation {

		return $this->cancel_donation->handle( $donation_id, $at );
	}
}
