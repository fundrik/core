<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Domain;

use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationFactoryException;
use Fundrik\Core\Components\Donations\Domain\Exceptions\InvalidDonationAmountException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityVersionException;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidMoneyAmountException;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidMoneyCurrencyException;
use Fundrik\Core\Components\Shared\Domain\Money;
use ValueError;

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
	 *
	 * @return Donation Donation entity.
	 */
	public function create(
		EntityId $id,
		EntityVersion $version,
		EntityId $campaign_id,
		Money $money,
		DonationStatus $status,
	): Donation {

		return new Donation( id: $id, version: $version, campaign_id: $campaign_id, money: $money, status: $status );
	}

	/**
	 * Creates a donation from primitive values.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id Donation ID.
	 * @param int $version Donation version.
	 * @param int|string $campaign_id Campaign ID.
	 * @param int $amount_minor Donation amount in minor units.
	 * @param string $currency Donation currency (ISO 4217).
	 * @param string $status Donation status value.
	 *
	 * @return Donation Donation entity.
	 *
	 * @throws DonationFactoryException When creating donation from primitives fails.
	 */
	public function create_from_primitives(
		int|string $id,
		int $version,
		int|string $campaign_id,
		int $amount_minor,
		string $currency,
		string $status,
	): Donation {

		try {

			return $this->create(
				id: EntityId::create( $id ),
				version: EntityVersion::create( $version ),
				campaign_id: EntityId::create( $campaign_id ),
				money: Money::create( $amount_minor, $currency ),
				status: DonationStatus::from( $status ),
			);

		} catch (
			InvalidEntityIdException
			| InvalidEntityVersionException
			| InvalidMoneyAmountException
			| InvalidMoneyCurrencyException
			| InvalidDonationAmountException
			| ValueError $e
		) {

			throw new DonationFactoryException( 'Failed to create donation from primitives.', previous: $e );
		}
	}

	/**
	 * Creates a new pending donation with initial version.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id Donation ID.
	 * @param EntityId $campaign_id Campaign ID.
	 * @param Money $money Donation amount and currency.
	 *
	 * @return Donation Pending donation.
	 */
	public function create_pending( EntityId $id, EntityId $campaign_id, Money $money, ): Donation {

		return $this->create(
			id: $id,
			version: EntityVersion::initial(),
			campaign_id: $campaign_id,
			money: $money,
			status: DonationStatus::Pending,
		);
	}

	/**
	 * Creates a new pending donation with initial version from primitive values.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id Donation ID.
	 * @param int|string $campaign_id Campaign ID.
	 * @param int $amount_minor Donation amount in minor units.
	 * @param string $currency Donation currency (ISO 4217).
	 *
	 * @return Donation Pending donation.
	 *
	 * @throws DonationFactoryException When creating donation from primitives fails.
	 */
	public function create_pending_from_primitives(
		int|string $id,
		int|string $campaign_id,
		int $amount_minor,
		string $currency,
	): Donation {

		try {

			return $this->create_pending(
				id: EntityId::create( $id ),
				campaign_id: EntityId::create( $campaign_id ),
				money: Money::create( $amount_minor, $currency ),
			);

		} catch (
			InvalidEntityIdException
			| InvalidMoneyAmountException
			| InvalidMoneyCurrencyException
			| InvalidDonationAmountException $e
		) {

			throw new DonationFactoryException( 'Failed to create pending donation from primitives.', previous: $e );
		}
	}
}
