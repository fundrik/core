<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\ReadModels;

use Fundrik\Core\Components\Donations\Application\ReadModels\Donation;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( Donation::class )]
#[UsesClass( UtcDateTime::class )]
final class DonationTest extends FundrikTestCase {

	#[Test]
	public function exposes_all_read_values(): void {

		$created_at = $this->make_utc_date_time( '2026-03-01T10:00:00+00:00' );
		$updated_at = $this->make_utc_date_time( '2026-03-02T10:00:00+00:00' );
		$donation = new Donation(
			id: '7c1bb0b8-4d8e-4b3a-9a6e-3f1d9b1b6f5b',
			campaign_id: 901,
			amount: 1_000,
			currency_code: 'RUB',
			status: 'succeeded',
			payment_id: 'pay_5001',
			created_at: $created_at,
			updated_at: $updated_at,
		);

		$this->assertSame( '7c1bb0b8-4d8e-4b3a-9a6e-3f1d9b1b6f5b', $donation->get_id() );
		$this->assertSame( 901, $donation->get_campaign_id() );
		$this->assertSame( 1_000, $donation->get_amount() );
		$this->assertSame( 'RUB', $donation->get_currency_code() );
		$this->assertSame( 'succeeded', $donation->get_status() );
		$this->assertSame( 'pay_5001', $donation->get_payment_id() );
		$this->assertSame( $created_at, $donation->get_created_at() );
		$this->assertSame( $updated_at, $donation->get_updated_at() );
	}

	#[Test]
	public function updated_at_is_nullable(): void {

		$donation = $this->make_donation_read_model( updated_at: null );

		$this->assertNull( $donation->get_updated_at() );
		$this->assertNull( $donation->get_payment_id() );
	}
}
