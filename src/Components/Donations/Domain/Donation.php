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
	 * Marks donation as succeeded.
	 *
	 * Allowed transition: pending -> succeeded.
	 *
	 * @since 0.1.0
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
	 * @since 0.1.0
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
