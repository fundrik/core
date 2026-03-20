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
		$this->assertTrue( $campaign->can_receive_donations() );
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
	public function open_turns_campaign_open(): void {

		$closed = $this->make_campaign( is_open: false );

		$open = $closed->open();

		$this->assertTrue( $open->can_receive_donations() );
	}

	#[Test]
	public function open_throws_when_already_open(): void {

		$open = $this->make_campaign( is_open: true );

		$this->expectException( CampaignChangeException::class );
		$this->expectExceptionMessage( 'Cannot open campaign: already open.' );

		$open->open();
	}

	#[Test]
	public function close_turns_campaign_closed(): void {

		$open = $this->make_campaign( is_open: true );

		$closed = $open->close();

		$this->assertFalse( $closed->can_receive_donations() );
	}

	#[Test]
	public function close_throws_when_already_closed(): void {

		$closed = $this->make_campaign( is_open: false );

		$this->expectException( CampaignChangeException::class );
		$this->expectExceptionMessage( 'Cannot close campaign: already closed.' );

		$closed->close();
	}

	#[Test]
	public function can_receive_donations_requires_campaign_to_be_open(): void {

		$open = $this->make_campaign( is_open: true );
		$closed = $this->make_campaign( is_open: false );

		$this->assertTrue( $open->can_receive_donations() );
		$this->assertFalse( $closed->can_receive_donations() );
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
