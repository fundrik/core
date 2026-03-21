<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Domain;

use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationChangeException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidAmountException;
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
		$this->assertSame( 1_000, $donation->get_money()->get_amount()->get_value() );
		$this->assertSame( 'RUB', $donation->get_money()->get_currency()->get_code() );
		$this->assertSame( DonationStatus::Pending, $donation->get_status() );
	}

	#[Test]
	public function succeed_changes_status(): void {

		$succeeded = $this->make_pending_donation()->succeed();

		$this->assertSame( DonationStatus::Succeeded, $succeeded->get_status() );
	}

	#[Test]
	public function reject_changes_status(): void {

		$rejected = $this->make_pending_donation()->reject();

		$this->assertSame( DonationStatus::Rejected, $rejected->get_status() );
	}

	#[Test]
	public function refund_is_allowed_only_from_succeeded(): void {

		$refunded = $this->make_pending_donation()->succeed()->refund();

		$this->assertSame( DonationStatus::Refunded, $refunded->get_status() );
	}

	#[Test]
	public function throws_when_transition_is_not_allowed(): void {

		$refunded = $this->make_pending_donation()->succeed()->refund();

		$this->expectException( DonationChangeException::class );
		$this->expectExceptionMessage( 'Cannot succeed donation from status "refunded".' );

		$refunded->succeed();
	}

	#[Test]
	public function throws_when_succeed_is_called_from_non_pending_status(): void {

		$this->expectException( DonationChangeException::class );
		$this->expectExceptionMessage( 'Cannot succeed donation from status "succeeded".' );

		$this->make_pending_donation()->succeed()->succeed();
	}

	#[Test]
	public function throws_when_reject_is_called_from_non_pending_status(): void {

		$this->expectException( DonationChangeException::class );
		$this->expectExceptionMessage( 'Cannot reject donation from status "succeeded".' );

		$this->make_pending_donation()->succeed()->reject();
	}

	#[Test]
	public function throws_when_refund_is_called_from_non_captured_status(): void {

		$this->expectException( DonationChangeException::class );
		$this->expectExceptionMessage( 'Cannot refund donation from status "pending".' );

		$this->make_pending_donation()->refund();
	}

	#[Test]
	public function throws_when_reject_is_called_from_refunded_status(): void {

		$this->expectException( DonationChangeException::class );
		$this->expectExceptionMessage( 'Cannot reject donation from status "refunded".' );

		$this->make_pending_donation()->succeed()->refund()->reject();
	}

	#[Test]
	public function throws_when_money_amount_is_not_positive(): void {

		$this->expectException( InvalidAmountException::class );
		$this->expectExceptionMessage( 'Amount must be a positive integer. Given: 0.' );

		( new DonationFactory() )->create_pending(
			id: EntityId::create( 1 ),
			campaign_id: EntityId::create( 2 ),
			money: Money::create( 0, 'RUB' ),
		);
	}
}
