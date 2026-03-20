<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Domain;

use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationChangeException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;

/**
 * Represents a fundraising donation.
 *
 * @since 0.1.0
 */
final readonly class Donation {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id Donation ID.
	 * @param EntityVersion $version Donation version.
	 * @param EntityId $campaign_id Campaign ID.
	 * @param Money $money Donation money.
	 * @param DonationStatus $status Donation status.
	 */
	public function __construct(
		private EntityId $id,
		private EntityVersion $version,
		private EntityId $campaign_id,
		private Money $money,
		private DonationStatus $status,
	) {}

	/**
	 * Returns donation ID value object.
	 *
	 * @since 0.1.0
	 *
	 * @return EntityId Donation ID value object.
	 */
	public function get_id(): EntityId {

		return $this->id;
	}

	/**
	 * Returns donation version value object.
	 *
	 * @since 0.1.0
	 *
	 * @return EntityVersion Donation version value object.
	 */
	public function get_version(): EntityVersion {

		return $this->version;
	}

	/**
	 * Returns campaign ID value object.
	 *
	 * @since 0.1.0
	 *
	 * @return EntityId Campaign ID value object.
	 */
	public function get_campaign_id(): EntityId {

		return $this->campaign_id;
	}

	/**
	 * Returns donation money value object.
	 *
	 * @since 0.1.0
	 *
	 * @return Money Donation money.
	 */
	public function get_money(): Money {

		return $this->money;
	}

	/**
	 * Returns donation status.
	 *
	 * @since 0.1.0
	 *
	 * @return DonationStatus Donation status.
	 */
	public function get_status(): DonationStatus {

		return $this->status;
	}

	/**
	 * Authorizes donation.
	 *
	 * Allowed transition: pending -> authorized.
	 *
	 * @since 0.1.0
	 *
	 * @return self Donation in authorized status.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	public function authorize(): self {

		return $this->with_status( $this->status->authorize() );
	}

	/**
	 * Captures donation.
	 *
	 * Allowed transitions: pending|authorized -> captured.
	 *
	 * @since 0.1.0
	 *
	 * @return self Donation in captured status.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	public function capture(): self {

		return $this->with_status( $this->status->capture() );
	}

	/**
	 * Marks donation as failed.
	 *
	 * Allowed transitions: pending|authorized -> failed.
	 *
	 * @since 0.1.0
	 *
	 * @return self Donation in failed status.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	public function fail(): self {

		return $this->with_status( $this->status->fail() );
	}

	/**
	 * Refunds donation.
	 *
	 * Allowed transition: captured -> refunded.
	 *
	 * @since 0.1.0
	 *
	 * @return self Donation in refunded status.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	public function refund(): self {

		return $this->with_status( $this->status->refund() );
	}

	/**
	 * Cancels donation.
	 *
	 * Allowed transitions: pending|authorized -> canceled.
	 *
	 * @since 0.1.0
	 *
	 * @return self Donation in canceled status.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	public function cancel(): self {

		return $this->with_status( $this->status->cancel() );
	}

	/**
	 * Creates a new immutable donation with a different status.
	 *
	 * @since 0.1.0
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
		);
	}
}
