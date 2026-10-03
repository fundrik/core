<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Domain;

use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationConstructionException;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationFactoryException;
use Fundrik\Core\Components\Donations\Domain\Exceptions\InvalidPaymentIdException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidAmountException;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidCurrencyCodeException;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityVersionException;
use Fundrik\Core\Components\Shared\Domain\Money;
use ValueError;

/**
 * Creates Donation entities.
 *
 * @since 1.0.0
 */
final readonly class DonationFactory {

	/**
	 * Creates a donation in any valid state.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Added the `$payment_id` parameter.
	 *
	 * @param EntityId $id Donation ID.
	 * @param EntityVersion $version Donation version.
	 * @param EntityId $campaign_id Campaign ID.
	 * @param Money $money Donation amount and currency.
	 * @param DonationStatus $status Donation status.
	 * @param PaymentId|null $payment_id Provider payment ID, if attached.
	 *
	 * @return Donation Donation entity.
	 *
	 * @throws DonationConstructionException When status and payment ID are inconsistent.
	 */
	public function create(
		EntityId $id,
		EntityVersion $version,
		EntityId $campaign_id,
		Money $money,
		DonationStatus $status,
		?PaymentId $payment_id,
	): Donation {

		return new Donation(
			id: $id,
			version: $version,
			campaign_id: $campaign_id,
			money: $money,
			status: $status,
			payment_id: $payment_id,
		);
	}

	/**
	 * Creates a donation from primitive values.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Added the `$payment_id` parameter.
	 *
	 * @param int|string|EntityId $id Donation ID.
	 * @param int $version Donation version.
	 * @param int|string|EntityId $campaign_id Campaign ID.
	 * @param int $amount Donation amount in minor units.
	 * @param string $currency_code Three-letter donation currency code.
	 * @param string $status Donation status value.
	 * @param string|null $payment_id Provider payment ID, if attached.
	 *
	 * @return Donation Donation entity.
	 *
	 * @throws DonationFactoryException When creating donation from primitives fails.
	 */
	public function create_from_primitives(
		int|string|EntityId $id,
		int $version,
		int|string|EntityId $campaign_id,
		int $amount,
		string $currency_code,
		string $status,
		?string $payment_id,
	): Donation {

		try {

			return $this->create(
				id: EntityId::create( $id ),
				version: EntityVersion::create( $version ),
				campaign_id: EntityId::create( $campaign_id ),
				money: Money::create( $amount, $currency_code ),
				status: $this->create_status( $status ),
				payment_id: $payment_id === null ? null : PaymentId::create( $payment_id ),
			);

		} catch (
			InvalidEntityIdException
			| InvalidEntityVersionException
			| InvalidAmountException
			| InvalidCurrencyCodeException
			| InvalidPaymentIdException
			| DonationConstructionException $e
		) {

			throw new DonationFactoryException( $e->getMessage(), previous: $e );
		}
	}

	/**
	 * Creates a validated donation status.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status Donation status value.
	 *
	 * @return DonationStatus Donation status.
	 *
	 * @throws DonationFactoryException When the donation status is invalid.
	 */
	private function create_status( string $status ): DonationStatus {

		try {
			return DonationStatus::from( $status );
		} catch ( ValueError $e ) {
			throw new DonationFactoryException(
				sprintf( 'Donation status must be valid. Given: "%s".', $status ),
				previous: $e,
			);
		}
	}

	/**
	 * Creates a new donation in created status with initial version.
	 *
	 * @since 1.1.0
	 *
	 * @param EntityId $id Donation ID.
	 * @param EntityId $campaign_id Campaign ID.
	 * @param Money $money Donation amount and currency.
	 *
	 * @return Donation Created donation.
	 */
	public function create_created( EntityId $id, EntityId $campaign_id, Money $money ): Donation {

		return $this->create(
			id: $id,
			version: EntityVersion::initial(),
			campaign_id: $campaign_id,
			money: $money,
			status: DonationStatus::Created,
			payment_id: null,
		);
	}

	/**
	 * Creates a new donation in created status with initial version from primitive values.
	 *
	 * @since 1.1.0
	 *
	 * @param int|string|EntityId $id Donation ID.
	 * @param int|string|EntityId $campaign_id Campaign ID.
	 * @param int $amount Donation amount in minor units.
	 * @param string $currency_code Three-letter donation currency code.
	 *
	 * @return Donation Created donation.
	 *
	 * @throws DonationFactoryException When creating donation from primitives fails.
	 */
	public function create_created_from_primitives(
		int|string|EntityId $id,
		int|string|EntityId $campaign_id,
		int $amount,
		string $currency_code,
	): Donation {

		try {

			return $this->create_created(
				id: EntityId::create( $id ),
				campaign_id: EntityId::create( $campaign_id ),
				money: Money::create( $amount, $currency_code ),
			);

		} catch (
			InvalidEntityIdException
			| InvalidAmountException
			| InvalidCurrencyCodeException $e
		) {

			throw new DonationFactoryException( $e->getMessage(), previous: $e );
		}
	}
}
