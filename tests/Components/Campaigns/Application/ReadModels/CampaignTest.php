<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\ReadModels;

use Fundrik\Core\Components\Campaigns\Application\ReadModels\Campaign;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( Campaign::class )]
#[UsesClass( UtcDateTime::class )]
final class CampaignTest extends FundrikTestCase {

	#[Test]
	public function returns_all_values_with_configured_target_and_updated_timestamp(): void {

		$created_at = $this->make_utc_date_time( '2026-03-01T10:00:00+00:00' );
		$updated_at = $this->make_utc_date_time( '2026-03-02T11:30:00+00:00' );
		$campaign = new Campaign(
			id: 'campaign-uuid',
			title: 'Save the rainforest',
			accepts_donations: true,
			currency_code: 'RUB',
			target_amount: 5_000,
			collected_amount: 1_250,
			donations_count: 12,
			created_at: $created_at,
			updated_at: $updated_at,
		);

		$this->assertSame( 'campaign-uuid', $campaign->get_id() );
		$this->assertSame( 'Save the rainforest', $campaign->get_title() );
		$this->assertTrue( $campaign->accepts_donations() );
		$this->assertSame( 'RUB', $campaign->get_currency_code() );
		$this->assertTrue( $campaign->has_target() );
		$this->assertSame( 5_000, $campaign->get_target_amount() );
		$this->assertSame( 1_250, $campaign->get_collected_amount() );
		$this->assertSame( 12, $campaign->get_donations_count() );
		$this->assertSame( $created_at, $campaign->get_created_at() );
		$this->assertSame( $updated_at, $campaign->get_updated_at() );
	}

	#[Test]
	public function returns_numeric_id_and_null_optional_values(): void {

		$created_at = $this->make_utc_date_time( '2026-03-01T10:00:00+00:00' );
		$campaign = new Campaign(
			id: 10,
			title: 'Save the cats',
			accepts_donations: false,
			currency_code: 'USD',
			target_amount: null,
			collected_amount: 0,
			donations_count: 0,
			created_at: $created_at,
		);

		$this->assertSame( 10, $campaign->get_id() );
		$this->assertFalse( $campaign->accepts_donations() );
		$this->assertFalse( $campaign->has_target() );
		$this->assertNull( $campaign->get_target_amount() );
		$this->assertSame( 0, $campaign->get_collected_amount() );
		$this->assertSame( 0, $campaign->get_donations_count() );
		$this->assertSame( $created_at, $campaign->get_created_at() );
		$this->assertNull( $campaign->get_updated_at() );
	}
}
