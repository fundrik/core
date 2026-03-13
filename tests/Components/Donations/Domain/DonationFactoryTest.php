<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Domain;

use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationFactoryException;
use Fundrik\Core\Components\Donations\Domain\Exceptions\InvalidDonationAmountException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
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
final class DonationFactoryTest extends FundrikTestCase {

	private DonationFactory $factory;

	protected function setUp(): void {

		parent::setUp();

		$this->factory = new DonationFactory();
	}

	#[Test]
	public function create_builds_donation_in_captured_state(): void {

		$donation = $this->factory->create(
			id: EntityId::create( 101 ),
			version: EntityVersion::create( 2 ),
			campaign_id: EntityId::create( 901 ),
			money: Money::create( 1_500, 'RUB' ),
			status: DonationStatus::Captured,
		);

		$this->assertSame( 101, $donation->get_id()->get_value() );
		$this->assertSame( 2, $donation->get_version()->get_value() );
		$this->assertSame( 901, $donation->get_campaign_id()->get_value() );
		$this->assertSame( 1_500, $donation->get_money()->get_amount_minor() );
		$this->assertSame( 'RUB', $donation->get_money()->get_currency() );
		$this->assertSame( DonationStatus::Captured, $donation->get_status() );
	}

	#[Test]
	public function create_pending_builds_valid_pending_donation(): void {

		$donation = $this->factory->create_pending(
			id: EntityId::create( 101 ),
			campaign_id: EntityId::create( 901 ),
			money: Money::create( 1_500, 'RUB' ),
		);

		$this->assertSame( 1, $donation->get_version()->get_value() );
		$this->assertSame( DonationStatus::Pending, $donation->get_status() );
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
		);
	}

	#[Test]
	public function create_from_primitives_builds_donation(): void {

		$donation = $this->factory->create_from_primitives(
			id: 11,
			version: 2,
			campaign_id: 22,
			amount_minor: 3_000,
			currency: 'EUR',
			status: DonationStatus::Captured->value,
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
		);
	}
}
