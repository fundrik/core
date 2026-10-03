<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Domain;

use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationChangeException;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationConstructionException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;

/**
 * Represents a fundraising donation.
 *
 * @since 1.0.0
 */
final readonly class Donation {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Added the `$payment_id` parameter.
	 *
	 * @param EntityId $id Donation ID.
	 * @param EntityVersion $version Donation version.
	 * @param EntityId $campaign_id Campaign ID.
	 * @param Money $money Donation money.
	 * @param DonationStatus $status Donation status.
	 * @param PaymentId|null $payment_id Provider payment ID, if attached.
	 *
	 * @throws DonationConstructionException When status and payment ID are inconsistent.
	 */
	public function __construct(
		private EntityId $id,
		private EntityVersion $version,
		private EntityId $campaign_id,
		private Money $money,
		private DonationStatus $status,
		private ?PaymentId $payment_id,
	) {

		if (
			( $this->status === DonationStatus::Created && $this->payment_id !== null )
			|| ( $this->status !== DonationStatus::Created && $this->payment_id === null )
		) {
			throw new DonationConstructionException(
				sprintf(
					'Cannot create donation "%s": status "%s" and payment ID are inconsistent.',
					$this->id->get_value(),
					$this->status->value,
				),
			);
		}
	}

	/**
	 * Returns donation ID value object.
	 *
	 * @since 1.0.0
	 *
	 * @return EntityId Donation ID value object.
	 */
	public function get_id(): EntityId {

		return $this->id;
	}

	/**
	 * Returns donation version value object.
	 *
	 * @since 1.0.0
	 *
	 * @return EntityVersion Donation version value object.
	 */
	public function get_version(): EntityVersion {

		return $this->version;
	}

	/**
	 * Returns campaign ID value object.
	 *
	 * @since 1.0.0
	 *
	 * @return EntityId Campaign ID value object.
	 */
	public function get_campaign_id(): EntityId {

		return $this->campaign_id;
	}

	/**
	 * Returns donation money value object.
	 *
	 * @since 1.0.0
	 *
	 * @return Money Donation money.
	 */
	public function get_money(): Money {

		return $this->money;
	}

	/**
	 * Returns donation status.
	 *
	 * @since 1.0.0
	 *
	 * @return DonationStatus Donation status.
	 */
	public function get_status(): DonationStatus {

		return $this->status;
	}

	/**
	 * Returns the provider payment ID.
	 *
	 * @since 1.1.0
	 *
	 * @return PaymentId|null Provider payment ID, if attached.
	 */
	public function get_payment_id(): ?PaymentId {

		return $this->payment_id;
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Marks the donation as awaiting a provider payment.
	 *
	 * Awaiting the same payment again while pending is idempotent.
	 *
	 * @since 1.1.0
	 *
	 * @param PaymentId $payment_id Expected provider payment ID.
	 *
	 * @return self Donation in pending status.
	 *
	 * @throws DonationChangeException When the donation cannot await this payment.
	 */
	public function await_payment( PaymentId $payment_id ): self {

		if ( $this->status === DonationStatus::Pending ) {

			// phpcs:ignore Generic.Commenting.DocComment.MissingShort
			/** @var PaymentId $attached_payment_id */
			$attached_payment_id = $this->payment_id;

			if ( $attached_payment_id->equals( $payment_id ) ) {
				return $this;
			}

			throw new DonationChangeException(
				sprintf(
					'Cannot await payment for donation "%s": another payment is already registered.',
					$this->id->get_value(),
				),
			);
		}

		if ( $this->status !== DonationStatus::Created ) {
			throw new DonationChangeException(
				sprintf(
					'Cannot await payment for donation "%s": donation is not created.',
					$this->id->get_value(),
				),
			);
		}

		return new self(
			id: $this->id,
			version: $this->version,
			campaign_id: $this->campaign_id,
			money: $this->money,
			status: $this->status->await_payment(),
			payment_id: $payment_id,
		);
	}
	// phpcs:enable

	/**
	 * Marks donation as succeeded.
	 *
	 * Allowed transition: pending -> succeeded.
	 *
	 * @since 1.0.0
	 *
	 * @return self Donation in succeeded status.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	public function succeed(): self {

		return $this->with_status( $this->status->succeed() );
	}

	/**
	 * Marks donation as rejected.
	 *
	 * Allowed transition: pending -> rejected.
	 *
	 * @since 1.0.0
	 *
	 * @return self Donation in rejected status.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	public function reject(): self {

		return $this->with_status( $this->status->reject() );
	}

	/**
	 * Refunds donation.
	 *
	 * Allowed transition: succeeded -> refunded.
	 *
	 * @since 1.0.0
	 *
	 * @return self Donation in refunded status.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	public function refund(): self {

		return $this->with_status( $this->status->refund() );
	}

	/**
	 * Creates a new immutable donation with a different status.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationStatus $status New donation status.
	 *
	 * @return self Updated immutable donation.
	 */
	private function with_status( DonationStatus $status ): self {

		return new self(
			id: $this->id,
			version: $this->version,
			campaign_id: $this->campaign_id,
			money: $this->money,
			status: $status,
			payment_id: $this->payment_id,
		);
	}
}
