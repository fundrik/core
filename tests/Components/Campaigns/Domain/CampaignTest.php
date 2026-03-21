<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Domain;

use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignChangeException;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\InvalidCampaignTargetException;
use Fundrik\Core\Components\Shared\Domain\Amount;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( Amount::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
final class CampaignTest extends FundrikTestCase {

	#[Test]
	public function campaign_returns_all_expected_values(): void {

		$campaign = $this->make_campaign();
		$entity_id = EntityId::create( 1 );
		$this->assertSame( 1, $campaign->get_id()->get_value() );
		$this->assertSame( 1, $campaign->get_version()->get_value() );
		$this->assertSame( 'Test Campaign', $campaign->get_title() );
		$this->assertTrue( $entity_id->equals( $campaign->get_id() ) );
		$this->assertTrue( $campaign->accepts_donations() );
		$this->assertSame( 'RUB', $campaign->get_target()->get_currency()->get_code() );
		$this->assertTrue( $campaign->has_target() );
		$this->assertSame( 100, $campaign->get_target()->get_amount()?->get_value() );
	}

	#[Test]
	public function campaign_allows_uuid_as_id(): void {

		$uuid = '7f2c8a19-8b3a-42e0-8573-5e672c7e4f01';

		$campaign = $this->make_campaign( id: $uuid );

		$this->assertSame( $uuid, $campaign->get_id()->get_value() );
	}

	#[Test]
	public function campaign_without_target_returns_null_target(): void {

		$campaign = $this->make_campaign( target_amount: null );

		$this->assertFalse( $campaign->has_target() );
		$this->assertNull( $campaign->get_target()->get_amount() );
		$this->assertSame( 1, $campaign->get_id()->get_value() );
	}

	#[Test]
	public function campaign_throws_when_currency_is_invalid(): void {

		$this->expectException( InvalidCampaignTargetException::class );
		$this->expectExceptionMessage( 'Campaign currency code must be a valid ISO 4217 code. Given: "EURO".' );

		$this->make_campaign( currency_code: 'EURO' );
	}

	#[Test]
	public function rename_changes_title_and_returns_new_instance(): void {

		$campaign1 = $this->make_campaign( title: 'Old' );
		$campaign2 = $campaign1->rename( CampaignTitle::create( 'New' ) );

		$this->assertNotSame( $campaign1, $campaign2 );
		$this->assertSame(
			'New',
			$campaign2->get_title(),
		);
		$this->assertTrue(
			$campaign1->get_id()->equals( $campaign2->get_id() ),
		);
	}

	#[Test]
	public function rename_throws_when_same_title(): void {

		$campaign = $this->make_campaign( title: 'Same' );

		$this->expectException( CampaignChangeException::class );
		$this->expectExceptionMessage( 'Campaign title must be different from the current one. Given: "Same".' );
		$campaign->rename( CampaignTitle::create( 'Same' ) );
	}

	#[Test]
	public function enable_donations_turns_campaign_into_accepting_donations(): void {

		$campaign = $this->make_campaign( accepts_donations: false );

		$updated = $campaign->enable_donations();

		$this->assertTrue( $updated->accepts_donations() );
	}

	#[Test]
	public function enable_donations_throws_when_already_enabled(): void {

		$campaign = $this->make_campaign( accepts_donations: true );

		$this->expectException( CampaignChangeException::class );
		$this->expectExceptionMessage( 'Cannot enable donations for campaign: already enabled.' );

		$campaign->enable_donations();
	}

	#[Test]
	public function disable_donations_turns_campaign_into_not_accepting_donations(): void {

		$campaign = $this->make_campaign( accepts_donations: true );

		$updated = $campaign->disable_donations();

		$this->assertFalse( $updated->accepts_donations() );
	}

	#[Test]
	public function disable_donations_throws_when_already_disabled(): void {

		$campaign = $this->make_campaign( accepts_donations: false );

		$this->expectException( CampaignChangeException::class );
		$this->expectExceptionMessage( 'Cannot disable donations for campaign: already disabled.' );

		$campaign->disable_donations();
	}

	#[Test]
	public function accepts_donations_reflects_campaign_state(): void {

		$enabled = $this->make_campaign( accepts_donations: true );
		$disabled = $this->make_campaign( accepts_donations: false );

		$this->assertTrue( $enabled->accepts_donations() );
		$this->assertFalse( $disabled->accepts_donations() );
	}

	#[Test]
	public function change_target_sets_amount_and_returns_new_instance(): void {

		$campaign1 = $this->make_campaign( target_amount: null );

		$campaign2 = $campaign1->change_target_amount( Amount::create( 500 ) );

		$this->assertNotSame( $campaign1, $campaign2 );
		$this->assertTrue( $campaign2->has_target() );
		$this->assertSame( 500, $campaign2->get_target()->get_amount()?->get_value() );
	}

	#[Test]
	public function change_target_same_value_throws(): void {

		$campaign = $this->make_campaign( target_amount: 100 );

		$this->expectException( CampaignChangeException::class );
		$this->expectExceptionMessage( 'Target amount must be different from the current one.' );

		$campaign->change_target_amount( Amount::create( 100 ) );
	}

	#[Test]
	public function change_target_clears_existing_target_when_null_is_provided(): void {

		$campaign = $this->make_campaign( target_amount: 500 );
		$updated = $campaign->change_target_amount( null );

		$this->assertFalse( $updated->has_target() );
		$this->assertNull( $updated->get_target()->get_amount() );
	}

	#[Test]
	public function change_target_without_existing_target_throws_when_target_would_not_change(): void {

		$campaign = $this->make_campaign( target_amount: null );

		$this->expectException( CampaignChangeException::class );
		$this->expectExceptionMessage( 'Target amount must be different from the current one.' );

		$campaign->change_target_amount( null );
	}
}
