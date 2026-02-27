<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Domain;

use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignChangeException;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\InvalidCampaignTargetException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Money::class )]
final class CampaignTest extends FundrikTestCase {

	#[Test]
	public function campaign_returns_all_expected_values(): void {

		$campaign = $this->make_campaign();
		$entity_id = EntityId::create( 1 );
		$this->assertSame( 1, $campaign->get_id()->get_value() );
		$this->assertSame( 1, $campaign->get_version()->get_value() );
		$this->assertSame( 'Test Campaign', $campaign->get_title() );
		$this->assertTrue( $entity_id->equals( $campaign->get_id() ) );
		$this->assertTrue( $campaign->is_active() );
		$this->assertTrue( $campaign->is_open() );
		$this->assertTrue(
			$campaign->has_target(),
		);
		$this->assertSame(
			100,
			$campaign->get_target_money()->get_amount_minor(),
		);
		$this->assertSame(
			'RUB',
			$campaign->get_target_money()->get_currency(),
		);
	}

	#[Test]
	public function campaign_allows_uuid_as_id(): void {

		$uuid = '7f2c8a19-8b3a-42e0-8573-5e672c7e4f01';

		$campaign = $this->make_campaign( id: $uuid );

		$this->assertSame( $uuid, $campaign->get_id()->get_value() );
	}

	#[Test]
	public function campaign_without_enabled_target(): void {

		$campaign = $this->make_campaign( has_target: false, target_amount: 0 );
		$this->assertFalse(
			$campaign->has_target(),
		);
		$this->assertSame(
			0,
			$campaign->get_target_money()->get_amount_minor(),
		);
		$this->assertSame(
			1,
			$campaign->get_id()->get_value(),
		);
	}

	#[Test]
	public function rename_changes_title_and_returns_new_instance(): void {

		$campaign1 = $this->make_campaign( title: 'Old' );
		$campaign2 = $campaign1->rename( 'New' );

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
		$campaign->rename( 'Same' );
	}

	#[Test]
	public function activate_turns_campaign_active(): void {

		$inactive = $this->make_campaign( is_active: false );

		$active = $inactive->activate();

		$this->assertTrue( $active->is_active() );
	}

	#[Test]
	public function activate_throws_when_already_active(): void {

		$active = $this->make_campaign( is_active: true );

		$this->expectException( CampaignChangeException::class );
		$this->expectExceptionMessage( 'Cannot activate campaign: already active.' );

		$active->activate();
	}

	#[Test]
	public function deactivate_turns_campaign_inactive(): void {

		$active = $this->make_campaign( is_active: true );

		$inactive = $active->deactivate();

		$this->assertFalse( $inactive->is_active() );
	}

	#[Test]
	public function deactivate_throws_when_already_inactive(): void {

		$inactive = $this->make_campaign( is_active: false );

		$this->expectException( CampaignChangeException::class );
		$this->expectExceptionMessage( 'Cannot deactivate campaign: already inactive.' );

		$inactive->deactivate();
	}

	#[Test]
	public function open_turns_campaign_open(): void {

		$closed = $this->make_campaign( is_open: false );

		$open = $closed->open();

		$this->assertTrue( $open->is_open() );
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

		$this->assertFalse( $closed->is_open() );
	}

	#[Test]
	public function close_throws_when_already_closed(): void {

		$closed = $this->make_campaign( is_open: false );

		$this->expectException( CampaignChangeException::class );
		$this->expectExceptionMessage( 'Cannot close campaign: already closed.' );

		$closed->close();
	}

	#[Test]
	public function enable_target_changes_amount_and_disable_target_turns_off(): void {

		$campaign1 = $this->make_campaign( has_target: false, target_amount: 0 );

		$campaign2 = $campaign1->enable_target( 500 );
		$this->assertTrue( $campaign2->has_target() );
		$this->assertSame( 500, $campaign2->get_target_money()->get_amount_minor() );
		$campaign3 = $campaign2->disable_target();
		$this->assertFalse(
			$campaign3->has_target(),
		);
		$this->assertSame(
			0,
			$campaign3->get_target_money()->get_amount_minor(),
		);
	}

	#[Test]
	public function enable_target_accepts_campaign_target(): void {

		$campaign = $this->make_campaign( has_target: false, target_amount: 0 );
		$updated = $campaign->enable_target( CampaignTarget::create( true, Money::create( 750, 'RUB' ) ), );
		$this->assertTrue(
			$updated->has_target(),
		);
		$this->assertSame(
			750,
			$updated->get_target_money()->get_amount_minor(),
		);
	}

		#[Test]
	public function enable_target_throws_when_campaign_target_is_disabled(): void {

		$campaign = $this->make_campaign( has_target: false, target_amount: 0 );
		$this->expectException( InvalidCampaignTargetException::class );
		$this->expectExceptionMessage( 'Target must be enabled in enable_target().' );

		$campaign->enable_target(
			CampaignTarget::create( false, Money::create( 0, 'RUB' ) ),
		);
	}

		#[Test]
	public function enable_target_same_amount_throws(): void {

		$campaign = $this->make_campaign( has_target: true, target_amount: 100 );

		$this->expectException( CampaignChangeException::class );
		$this->expectExceptionMessage( 'Target amount must be different from the current one. Given: 100.' );

		$campaign->enable_target( 100 );
	}

			#[Test]
	public function disable_target_already_disabled_throws(): void {

		$campaign = $this->make_campaign( has_target: false, target_amount: 0 );

		$this->expectException( CampaignChangeException::class );
		$this->expectExceptionMessage( 'Cannot disable target: already disabled.' );

		$campaign->disable_target();
	}

			#[Test]
	public function set_target_amount_zero_disables_and_positive_enables(): void {

		$campaign1 = $this->make_campaign( has_target: false, target_amount: 0 );

		$campaign2 = $campaign1->set_target_amount( 250 );
		$this->assertTrue( $campaign2->has_target() );
		$this->assertSame( 250, $campaign2->get_target_money()->get_amount_minor() );
		$campaign3 = $campaign2->set_target_amount( 0 );
		$this->assertFalse(
			$campaign3->has_target(),
		);
		$this->assertSame(
			0,
			$campaign3->get_target_money()->get_amount_minor(),
		);
	}

		#[Test]
	public function set_target_amount_accepts_campaign_target(): void {

		$campaign1 = $this->make_campaign( has_target: false, target_amount: 0 );
		$campaign2 = $campaign1->set_target_amount( CampaignTarget::create( true, Money::create( 300, 'RUB' ) ), );
		$this->assertTrue( $campaign2->has_target() );
		$this->assertSame( 300, $campaign2->get_target_money()->get_amount_minor() );
		$campaign3 = $campaign2->set_target_amount(
			CampaignTarget::create( false, Money::create( 0, 'RUB' ) ),
		);
		$this->assertFalse(
			$campaign3->has_target(),
		);
		$this->assertSame(
			0,
			$campaign3->get_target_money()->get_amount_minor(),
		);
	}
}
