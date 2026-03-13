<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Domain;

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

	#[Test]
	public function create_pending_returns_expected_initial_state(): void {

		$donation = $this->make_pending_donation( id: 501, campaign_id: 901 );
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
	}

	#[Test]
	public function authorize_changes_status(): void {

		$authorized = $this->make_pending_donation()->authorize();

		$this->assertSame( DonationStatus::Authorized, $authorized->get_status() );
	}

	#[Test]
	public function capture_is_allowed_from_pending_and_authorized(): void {

		$capture_from_pending = $this->make_pending_donation()->capture();
		$capture_from_authorized = $this->make_pending_donation()->authorize()->capture();

		$this->assertSame( DonationStatus::Captured, $capture_from_pending->get_status() );
		$this->assertSame( DonationStatus::Captured, $capture_from_authorized->get_status() );
	}

	#[Test]
	public function refund_is_allowed_only_from_captured(): void {

		$refunded = $this->make_pending_donation()->capture()->refund();

		$this->assertSame( DonationStatus::Refunded, $refunded->get_status() );
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

		$refunded = $this->make_pending_donation()->capture()->refund();

		$this->expectException( DonationChangeException::class );
		$this->expectExceptionMessage( 'Cannot capture donation from status "refunded".' );

		$refunded->capture();
	}

	#[Test]
	public function throws_when_authorize_is_called_from_non_pending_status(): void {

		$this->expectException( DonationChangeException::class );
		$this->expectExceptionMessage( 'Cannot authorize donation from status "authorized".' );

		$this->make_pending_donation()->authorize()->authorize();
	}

	#[Test]
	public function throws_when_fail_is_called_from_non_pending_or_non_authorized_status(): void {

		$this->expectException( DonationChangeException::class );
		$this->expectExceptionMessage( 'Cannot fail donation from status "captured".' );

		$this->make_pending_donation()->capture()->fail();
	}

	#[Test]
	public function throws_when_refund_is_called_from_non_captured_status(): void {

		$this->expectException( DonationChangeException::class );
		$this->expectExceptionMessage( 'Cannot refund donation from status "authorized".' );

		$this->make_pending_donation()->authorize()->refund();
	}

	#[Test]
	public function throws_when_cancel_is_called_from_non_pending_or_non_authorized_status(): void {

		$this->expectException( DonationChangeException::class );
		$this->expectExceptionMessage( 'Cannot cancel donation from status "captured".' );

		$this->make_pending_donation()->capture()->cancel();
	}

	#[Test]
	public function throws_when_donation_amount_is_zero(): void {

		$this->expectException( InvalidDonationAmountException::class );
		$this->expectExceptionMessage( 'Donation amount must be a positive integer in minor units. Given: 0.' );

		( new DonationFactory() )->create_pending(
			id: EntityId::create( 1 ),
			campaign_id: EntityId::create( 2 ),
			money: Money::create( 0, 'RUB' ),
		);
	}
}
