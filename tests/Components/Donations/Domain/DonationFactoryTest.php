<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Domain;

use DateTimeImmutable;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationChangeException;
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

		$created_at = new DateTimeImmutable( '2026-02-26T10:00:00+00:00' );
		$captured_at = new DateTimeImmutable( '2026-02-26T10:10:00+00:00' );

		$donation = $this->factory->create(
			id: EntityId::create( 101 ),
			version: EntityVersion::create( 2 ),
			campaign_id: EntityId::create( 901 ),
			money: Money::create( 1_500, 'RUB' ),
			status: DonationStatus::Captured,
			created_at: $created_at,
			captured_at: $captured_at,
			status_changed_at: $captured_at,
		);

		$this->assertSame( 101, $donation->get_id()->get_value() );
		$this->assertSame( 2, $donation->get_version()->get_value() );
		$this->assertSame( 901, $donation->get_campaign_id()->get_value() );
		$this->assertSame( 1_500, $donation->get_money()->get_amount_minor() );
		$this->assertSame( 'RUB', $donation->get_money()->get_currency() );
		$this->assertSame( DonationStatus::Captured, $donation->get_status() );
		$this->assertSame( $created_at, $donation->get_created_at() );
		$this->assertSame( $captured_at, $donation->get_captured_at() );
		$this->assertSame( $captured_at, $donation->get_status_changed_at() );
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
		$this->assertSame( $created_at, $donation->get_created_at() );
		$this->assertNull( $donation->get_captured_at() );
		$this->assertNull( $donation->get_status_changed_at() );
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
			created_at: new DateTimeImmutable( '2026-02-26T10:00:00+00:00' ),
			captured_at: null,
			status_changed_at: new DateTimeImmutable( '2026-02-26T10:01:00+00:00' ),
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
			created_at: new DateTimeImmutable( '2026-02-26T10:00:00+00:00' ),
		);
	}
}
