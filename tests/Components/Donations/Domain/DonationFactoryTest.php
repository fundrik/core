<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Domain;

use DateTimeImmutable;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationChangeException;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationFactoryException;
use Fundrik\Core\Components\Donations\Domain\Exceptions\InvalidDonationAmountException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidUtcDateTimeException;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( DonationFactory::class )]
#[UsesClass( Donation::class )]
#[UsesClass( DonationStatus::class )]
#[UsesClass( DonationFactoryException::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( Money::class )]
#[UsesClass( UtcDateTime::class )]
#[UsesClass( InvalidUtcDateTimeException::class )]
final class DonationFactoryTest extends FundrikTestCase {

	private DonationFactory $factory;

	protected function setUp(): void {

		parent::setUp();

		$this->factory = new DonationFactory();
	}

	#[Test]
	public function create_builds_donation_in_captured_state(): void {

		$created_at = new DateTimeImmutable( '2026-02-26T10:00:00+00:00' );
		$captured_at = new DateTimeImmutable( '2026-02-26T10:10:00+00:00' );

		$donation = $this->factory->create(
			id: EntityId::create( 101 ),
			version: EntityVersion::create( 2 ),
			campaign_id: EntityId::create( 901 ),
			money: Money::create( 1_500, 'RUB' ),
			status: DonationStatus::Captured,
			created_at: UtcDateTime::create( $created_at ),
			captured_at: UtcDateTime::create( $captured_at ),
			status_changed_at: UtcDateTime::create( $captured_at ),
		);

		$this->assertSame( 101, $donation->get_id()->get_value() );
		$this->assertSame( 2, $donation->get_version()->get_value() );
		$this->assertSame( 901, $donation->get_campaign_id()->get_value() );
		$this->assertSame( 1_500, $donation->get_money()->get_amount_minor() );
		$this->assertSame( 'RUB', $donation->get_money()->get_currency() );
		$this->assertSame( DonationStatus::Captured, $donation->get_status() );
		$this->assertSame( 'UTC', $donation->get_created_at()->get_value()->getTimezone()->getName() );
		$this->assertSame( 'UTC', $donation->get_captured_at()?->get_value()->getTimezone()->getName() );
		$this->assertSame( 'UTC', $donation->get_status_changed_at()?->get_value()->getTimezone()->getName() );
		$this->assertSame( $created_at->getTimestamp(), $donation->get_created_at()->get_value()->getTimestamp() );
		$this->assertSame( $captured_at->getTimestamp(), $donation->get_captured_at()?->get_value()->getTimestamp() );
		$this->assertSame(
			$captured_at->getTimestamp(),
			$donation->get_status_changed_at()?->get_value()->getTimestamp(),
		);
	}

	#[Test]
	public function create_throws_when_provided_timestamps_are_not_utc(): void {

		$this->expectException( InvalidUtcDateTimeException::class );
		$this->expectExceptionMessage( 'Timestamp must use UTC timezone offset. Given: "+03:00".' );

		$this->factory->create(
			id: EntityId::create( 201 ),
			version: EntityVersion::create( 2 ),
			campaign_id: EntityId::create( 901 ),
			money: Money::create( 1_500, 'RUB' ),
			status: DonationStatus::Captured,
			created_at: UtcDateTime::create( new DateTimeImmutable( '2026-02-26T13:00:00+03:00' ) ),
			captured_at: UtcDateTime::create( new DateTimeImmutable( '2026-02-26T13:10:00+03:00' ) ),
			status_changed_at: UtcDateTime::create( new DateTimeImmutable( '2026-02-26T13:10:00+03:00' ) ),
		);
	}

	#[Test]
	public function create_pending_builds_valid_pending_donation(): void {

		$created_at = new DateTimeImmutable( '2026-02-26T10:00:00+00:00' );

		$donation = $this->factory->create_pending(
			id: EntityId::create( 101 ),
			campaign_id: EntityId::create( 901 ),
			money: Money::create( 1_500, 'RUB' ),
			created_at: $created_at,
		);

		$this->assertSame( 1, $donation->get_version()->get_value() );
		$this->assertSame( DonationStatus::Pending, $donation->get_status() );
		$this->assertSame( 'UTC', $donation->get_created_at()->get_value()->getTimezone()->getName() );
		$this->assertSame( $created_at->getTimestamp(), $donation->get_created_at()->get_value()->getTimestamp() );
		$this->assertNull( $donation->get_captured_at() );
		$this->assertNull( $donation->get_status_changed_at() );
	}

	#[Test]
	public function create_pending_throws_when_created_at_is_not_utc(): void {

		$this->expectException( InvalidUtcDateTimeException::class );
		$this->expectExceptionMessage( 'Timestamp must use UTC timezone offset. Given: "+03:00".' );

		$this->factory->create_pending(
			id: EntityId::create( 101 ),
			campaign_id: EntityId::create( 901 ),
			money: Money::create( 1_500, 'RUB' ),
			created_at: new DateTimeImmutable( '2026-02-26T13:00:00+03:00' ),
		);
	}

	#[Test]
	public function create_throws_when_state_is_inconsistent(): void {

		$this->expectException( DonationChangeException::class );
		$this->expectExceptionMessage( 'Pending donation must not have status timestamps.' );

		$this->factory->create(
			id: EntityId::create( 101 ),
			version: EntityVersion::create( 1 ),
			campaign_id: EntityId::create( 901 ),
			money: Money::create( 1_500, 'RUB' ),
			status: DonationStatus::Pending,
			created_at: UtcDateTime::create( new DateTimeImmutable( '2026-02-26T10:00:00+00:00' ) ),
			captured_at: null,
			status_changed_at: UtcDateTime::create( new DateTimeImmutable( '2026-02-26T10:01:00+00:00' ) ),
		);
	}

	#[Test]
	public function create_throws_when_amount_is_zero(): void {

		$this->expectException( InvalidDonationAmountException::class );
		$this->expectExceptionMessage( 'Donation amount must be a positive integer in minor units. Given: 0.' );

		$this->factory->create(
			id: EntityId::create( 101 ),
			version: EntityVersion::create( 1 ),
			campaign_id: EntityId::create( 901 ),
			money: Money::create( 0, 'RUB' ),
			status: DonationStatus::Pending,
			created_at: UtcDateTime::create( new DateTimeImmutable( '2026-02-26T10:00:00+00:00' ) ),
		);
	}

	#[Test]
	public function create_from_primitives_builds_donation(): void {

		$created_at = new DateTimeImmutable( '2026-02-26T10:00:00+00:00' );
		$captured_at = new DateTimeImmutable( '2026-02-26T10:10:00+00:00' );

		$donation = $this->factory->create_from_primitives(
			id: 11,
			version: 2,
			campaign_id: 22,
			amount_minor: 3_000,
			currency: 'EUR',
			status: DonationStatus::Captured->value,
			created_at: $created_at,
			captured_at: $captured_at,
			status_changed_at: $captured_at,
		);

		$this->assertSame( 11, $donation->get_id()->get_value() );
		$this->assertSame( 22, $donation->get_campaign_id()->get_value() );
		$this->assertSame( 2, $donation->get_version()->get_value() );
		$this->assertSame( 3_000, $donation->get_money()->get_amount_minor() );
		$this->assertSame( 'EUR', $donation->get_money()->get_currency() );
		$this->assertSame( DonationStatus::Captured, $donation->get_status() );
	}

	#[Test]
	public function create_pending_from_primitives_builds_pending_donation_with_initial_version(): void {

		$donation = $this->factory->create_pending_from_primitives(
			id: 11,
			campaign_id: 22,
			amount_minor: 3_000,
			currency: 'EUR',
		);

		$this->assertSame( 11, $donation->get_id()->get_value() );
		$this->assertSame( 22, $donation->get_campaign_id()->get_value() );
		$this->assertSame( 1, $donation->get_version()->get_value() );
		$this->assertSame( DonationStatus::Pending, $donation->get_status() );
		$this->assertSame( 'UTC', $donation->get_created_at()->get_value()->getTimezone()->getName() );
	}

	#[Test]
	public function create_pending_from_primitives_wraps_exceptions_into_factory_exception(): void {

		$this->expectException( DonationFactoryException::class );
		$this->expectExceptionMessage( 'Failed to create pending donation from primitives.' );

		$this->factory->create_pending_from_primitives( id: 11, campaign_id: 22, amount_minor: 0, currency: 'EUR' );
	}

	#[Test]
	public function create_pending_from_primitives_wraps_invalid_entity_id_exception(): void {

		$this->expectException( DonationFactoryException::class );

		$this->factory->create_pending_from_primitives( id: -11, campaign_id: 22, amount_minor: 100, currency: 'EUR' );
	}

	#[Test]
	public function create_pending_from_primitives_wraps_invalid_utc_date_time_exception(): void {

		$this->expectException( DonationFactoryException::class );

		$this->factory->create_pending_from_primitives(
			id: 11,
			campaign_id: 22,
			amount_minor: 100,
			currency: 'EUR',
			created_at: new DateTimeImmutable( '2026-02-26T13:00:00+03:00' ),
		);
	}

		#[Test]
	public function create_pending_from_primitives_wraps_invalid_money_amount_exception(): void {

		$this->expectException( DonationFactoryException::class );

		$this->factory->create_pending_from_primitives( id: 11, campaign_id: 22, amount_minor: -1, currency: 'EUR' );
	}

		#[Test]
	public function create_pending_from_primitives_wraps_invalid_money_currency_exception(): void {

			$this->expectException( DonationFactoryException::class );

		$this->factory->create_pending_from_primitives( id: 11, campaign_id: 22, amount_minor: 100, currency: 'EURO' );
	}

	#[Test]
	public function create_from_primitives_wraps_exceptions_into_factory_exception(): void {

		$this->expectException( DonationFactoryException::class );

		$this->factory->create_from_primitives(
			id: 11,
			version: 1,
			campaign_id: 22,
			amount_minor: 100,
			currency: 'EUR',
			status: 'invalid-status',
			created_at: new DateTimeImmutable( '2026-02-26T10:00:00+00:00' ),
		);
	}

	#[Test]
	public function create_from_primitives_wraps_invalid_utc_date_time_exception(): void {

		$this->expectException( DonationFactoryException::class );

		$this->factory->create_from_primitives(
			id: 11,
			version: 1,
			campaign_id: 22,
			amount_minor: 100,
			currency: 'EUR',
			status: DonationStatus::Pending->value,
			created_at: new DateTimeImmutable( '2026-02-26T13:00:00+03:00' ),
		);
	}

		#[Test]
	public function create_from_primitives_wraps_invalid_entity_id_exception(): void {

		$this->expectException( DonationFactoryException::class );

		$this->factory->create_from_primitives(
			id: -11,
			version: 1,
			campaign_id: 22,
			amount_minor: 100,
			currency: 'EUR',
			status: DonationStatus::Pending->value,
			created_at: new DateTimeImmutable( '2026-02-26T10:00:00+00:00' ),
		);
	}

			#[Test]
	public function create_from_primitives_wraps_invalid_entity_version_exception(): void {

		$this->expectException( DonationFactoryException::class );

		$this->factory->create_from_primitives(
			id: 11,
			version: 0,
			campaign_id: 22,
			amount_minor: 100,
			currency: 'EUR',
			status: DonationStatus::Pending->value,
			created_at: new DateTimeImmutable( '2026-02-26T10:00:00+00:00' ),
		);
	}

		#[Test]
	public function create_from_primitives_wraps_invalid_money_amount_exception(): void {

			$this->expectException( DonationFactoryException::class );

		$this->factory->create_from_primitives(
			id: 11,
			version: 1,
			campaign_id: 22,
			amount_minor: -1,
			currency: 'EUR',
			status: DonationStatus::Pending->value,
			created_at: new DateTimeImmutable( '2026-02-26T10:00:00+00:00' ),
		);
	}

		#[Test]
	public function create_from_primitives_wraps_invalid_money_currency_exception(): void {

			$this->expectException( DonationFactoryException::class );

		$this->factory->create_from_primitives(
			id: 11,
			version: 1,
			campaign_id: 22,
			amount_minor: 100,
			currency: 'EURO',
			status: DonationStatus::Pending->value,
			created_at: new DateTimeImmutable( '2026-02-26T10:00:00+00:00' ),
		);
	}

	#[Test]
	public function create_from_primitives_wraps_invalid_donation_amount_exception(): void {

			$this->expectException( DonationFactoryException::class );

		$this->factory->create_from_primitives(
			id: 11,
			version: 1,
			campaign_id: 22,
			amount_minor: 0,
			currency: 'EUR',
			status: DonationStatus::Pending->value,
			created_at: new DateTimeImmutable( '2026-02-26T10:00:00+00:00' ),
		);
	}

		#[Test]
	public function create_from_primitives_wraps_donation_change_exception(): void {

				$this->expectException( DonationFactoryException::class );

		$this->factory->create_from_primitives(
			id: 11,
			version: 1,
			campaign_id: 22,
			amount_minor: 100,
			currency: 'EUR',
			status: DonationStatus::Pending->value,
			created_at: new DateTimeImmutable( '2026-02-26T10:00:00+00:00' ),
			status_changed_at: new DateTimeImmutable( '2026-02-26T10:01:00+00:00' ),
		);
	}
}
