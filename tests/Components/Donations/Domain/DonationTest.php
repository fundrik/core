<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Domain;

use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationChangeException;
use Fundrik\Core\Components\Shared\Domain\Amount;
use Fundrik\Core\Components\Shared\Domain\Currency;
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
#[UsesClass( Amount::class )]
#[UsesClass( Currency::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Money::class )]
#[UsesClass( EntityVersion::class )]
final class DonationTest extends FundrikTestCase {

	#[Test]
	public function create_pending_returns_expected_initial_state(): void {

		$donation = $this->make_pending_donation( id: 501, campaign_id: 901 );
		$this->assertSame( 501, $donation->get_id()->get_value() );
		$this->assertSame( 1, $donation->get_version()->get_value() );
		$this->assertSame( 901, $donation->get_campaign_id()->get_value() );
		$this->assertSame( 1_000, $donation->get_money()->get_amount()->get_value() );
		$this->assertSame( 'RUB', $donation->get_money()->get_currency()->get_code() );
		$this->assertSame( DonationStatus::Pending, $donation->get_status() );
	}

	#[Test]
	public function succeed_changes_status(): void {

		$succeeded = $this->make_pending_donation()->succeed();

		$this->assertSame(
			DonationStatus::Succeeded,
			$succeeded->get_status(),
		);
	}

	#[Test]
	public function reject_changes_status(): void {

		$rejected = $this->make_pending_donation()->reject();

		$this->assertSame(
			DonationStatus::Rejected,
			$rejected->get_status(),
		);
	}

	#[Test]
	public function refund_is_allowed_only_from_succeeded(): void {

		$refunded = $this->make_pending_donation()->succeed()->refund();

		$this->assertSame(
			DonationStatus::Refunded,
			$refunded->get_status(),
		);
	}

	#[Test]
	public function throws_when_status_transition_is_not_allowed(): void {

		$this->expectException( DonationChangeException::class );
		$this->expectExceptionMessage( 'Cannot succeed donation from status "refunded".' );

		$this->make_pending_donation()->succeed()->refund()->succeed();
	}

	#[Test]
	public function status_changes_return_new_donation_and_preserve_immutable_data(): void {

		$pending = $this->make_pending_donation( id: 501, campaign_id: 901, amount: 1_500, currency: 'EUR' );
		$succeeded = $pending->succeed();

		$this->assertNotSame( $pending, $succeeded );
		$this->assertSame( DonationStatus::Pending, $pending->get_status() );
		$this->assertSame( DonationStatus::Succeeded, $succeeded->get_status() );
		$this->assertSame( $pending->get_id(), $succeeded->get_id() );
		$this->assertSame( $pending->get_version(), $succeeded->get_version() );
		$this->assertSame( $pending->get_campaign_id(), $succeeded->get_campaign_id() );
		$this->assertSame( $pending->get_money(), $succeeded->get_money() );
	}
}
