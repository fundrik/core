<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Domain;

use DateTimeImmutable;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;

/**
 * Creates Donation entities.
 *
 * @since 0.1.0
 */
final readonly class DonationFactory {

	/**
	 * Creates a donation in any valid state.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id Donation ID.
	 * @param EntityVersion $version Donation version.
	 * @param EntityId $campaign_id Campaign ID.
	 * @param Money $money Donation amount and currency.
	 * @param DonationStatus $status Donation status.
	 * @param DateTimeImmutable $created_at Creation timestamp.
	 * @param DateTimeImmutable|null $captured_at Capture timestamp.
	 * @param DateTimeImmutable|null $status_changed_at Status change timestamp.
	 *
	 * @return Donation Donation entity.
	 */
	public function create(
		EntityId $id,
		EntityVersion $version,
		EntityId $campaign_id,
		Money $money,
		DonationStatus $status,
		DateTimeImmutable $created_at,
		?DateTimeImmutable $captured_at = null,
		?DateTimeImmutable $status_changed_at = null,
	): Donation {

		return new Donation(
			id: $id,
			version: $version,
			campaign_id: $campaign_id,
			money: $money,
			status: $status,
			created_at: $created_at,
			captured_at: $captured_at,
			status_changed_at: $status_changed_at,
		);
	}

	/**
	 * Creates a new pending donation with initial version.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id Donation ID.
	 * @param EntityId $campaign_id Campaign ID.
	 * @param Money $money Donation amount and currency.
	 * @param DateTimeImmutable|null $created_at Optional creation timestamp.
	 *
	 * @return Donation Pending donation.
	 */
	public function create_pending(
		EntityId $id,
		EntityId $campaign_id,
		Money $money,
		?DateTimeImmutable $created_at = null,
	): Donation {

		return $this->create(
			id: $id,
			version: EntityVersion::initial(),
			campaign_id: $campaign_id,
			money: $money,
			status: DonationStatus::Pending,
			created_at: $created_at ?? new DateTimeImmutable(),
		);
	}
}
