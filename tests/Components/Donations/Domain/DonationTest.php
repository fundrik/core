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

#[CoversClass( Donation::class )]
#[UsesClass( DonationStatus::class )]
#[UsesClass( DonationFactory::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Money::class )]
#[UsesClass( EntityVersion::class )]
final class DonationTest extends FundrikTestCase {

	private DonationFactory $factory;

	protected function setUp(): void {

		parent::setUp();

		$this->factory = new DonationFactory();
	}

	#[Test]
	public function create_pending_returns_expected_initial_state(): void {

		$created_at = new DateTimeImmutable( '2026-02-26T10:00:00+00:00' );

		$donation = $this->make_pending_donation( created_at: $created_at );
		$entity_id = EntityId::create( 501 );
		$campaign_entity_id = EntityId::create( 901 );

		$this->assertSame( 501, $donation->get_id()->get_value() );
		$this->assertTrue( $entity_id->equals( $donation->get_id() ) );
		$this->assertSame( 1, $donation->get_version()->get_value() );
		$this->assertSame( 901, $donation->get_campaign_id()->get_value() );
		$this->assertTrue( $campaign_entity_id->equals( $donation->get_campaign_id() ) );
		$this->assertSame( 1_000, $donation->get_money()->get_amount_minor() );
		$this->assertSame( 'RUB', $donation->get_money()->get_currency() );
		$this->assertSame( DonationStatus::Pending, $donation->get_status() );
		$this->assertSame( $created_at, $donation->get_created_at() );
		$this->assertNull( $donation->get_captured_at() );
		$this->assertNull( $donation->get_status_changed_at() );
	}

	#[Test]
	public function authorize_changes_status_and_sets_timestamp(): void {

		$donation = $this->make_pending_donation();
		$authorized_at = new DateTimeImmutable( '2026-02-26T10:10:00+00:00' );

		$authorized = $donation->authorize( $authorized_at );

		$this->assertSame( DonationStatus::Authorized, $authorized->get_status() );
		$this->assertSame( $authorized_at, $authorized->get_status_changed_at() );
		$this->assertNull( $authorized->get_captured_at() );
	}

	#[Test]
	public function capture_is_allowed_from_pending_and_authorized(): void {

		$capture_from_pending = $this->make_pending_donation()
			->capture( new DateTimeImmutable( '2026-02-26T10:15:00+00:00' ) );

		$capture_from_authorized = $this->make_pending_donation()
			->authorize( new DateTimeImmutable( '2026-02-26T10:10:00+00:00' ) )
			->capture( new DateTimeImmutable( '2026-02-26T10:20:00+00:00' ) );

		$this->assertSame( DonationStatus::Captured, $capture_from_pending->get_status() );
		$this->assertSame( DonationStatus::Captured, $capture_from_authorized->get_status() );
		$this->assertNotNull( $capture_from_pending->get_captured_at() );
		$this->assertNotNull( $capture_from_authorized->get_captured_at() );
	}

	#[Test]
	public function refund_is_allowed_only_from_captured(): void {

		$captured = $this->make_pending_donation()
			->capture( new DateTimeImmutable( '2026-02-26T10:15:00+00:00' ) );

		$refunded = $captured->refund( new DateTimeImmutable( '2026-02-26T10:20:00+00:00' ) );

		$this->assertSame( DonationStatus::Refunded, $refunded->get_status() );
		$this->assertNotNull( $refunded->get_status_changed_at() );
	}

	#[Test]
	public function cancel_is_allowed_from_pending_and_authorized(): void {

		$canceled_from_pending = $this->make_pending_donation()->cancel();
		$canceled_from_authorized = $this->make_pending_donation()->authorize()->cancel();

		$this->assertSame( DonationStatus::Canceled, $canceled_from_pending->get_status() );
		$this->assertSame( DonationStatus::Canceled, $canceled_from_authorized->get_status() );
	}

	#[Test]
	public function fail_is_allowed_from_pending_and_authorized(): void {

		$failed_from_pending = $this->make_pending_donation()->fail();
		$failed_from_authorized = $this->make_pending_donation()->authorize()->fail();

		$this->assertSame( DonationStatus::Failed, $failed_from_pending->get_status() );
		$this->assertSame( DonationStatus::Failed, $failed_from_authorized->get_status() );
	}

	#[Test]
	public function throws_when_transition_is_not_allowed(): void {

		$refunded = $this->make_pending_donation()
			->capture( new DateTimeImmutable( '2026-02-26T10:15:00+00:00' ) )
			->refund( new DateTimeImmutable( '2026-02-26T10:20:00+00:00' ) );

		$this->expectException( DonationChangeException::class );
		$this->expectExceptionMessage( 'Cannot capture donation from status "refunded".' );

		$refunded->capture();
	}

	#[Test]
	public function throws_when_refund_timestamp_is_earlier_than_capture_timestamp(): void {

		$this->expectException( DonationChangeException::class );
		$this->expectExceptionMessage( 'status_changed_at must not be earlier than captured_at for refunded donation.' );

		$this->make_pending_donation()
			->capture( new DateTimeImmutable( '2026-02-26T10:15:00+00:00' ) )
			->refund( new DateTimeImmutable( '2026-02-26T10:10:00+00:00' ) );
	}

	#[Test]
	public function throws_when_donation_amount_is_zero(): void {

		$this->expectException( InvalidDonationAmountException::class );
		$this->expectExceptionMessage( 'Donation amount must be a positive integer in minor units. Given: 0.' );

		$this->factory->create_pending(
			id: EntityId::create( 1 ),
			campaign_id: EntityId::create( 2 ),
			money: Money::create( 0, 'RUB' ),
			created_at: new DateTimeImmutable( '2026-02-26T10:00:00+00:00' ),
		);
	}

	/**
	 * Builds a valid pending donation for tests.
	 */
	private function make_pending_donation( ?DateTimeImmutable $created_at = null ): Donation {

		return $this->factory->create_pending(
			id: EntityId::create( 501 ),
			campaign_id: EntityId::create( 901 ),
			money: Money::create( 1_000, 'RUB' ),
			created_at: $created_at ?? new DateTimeImmutable( '2026-02-26T10:00:00+00:00' ),
		);
	}
}
