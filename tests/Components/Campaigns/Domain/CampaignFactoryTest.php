<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Domain;

use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignFactoryException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CampaignFactory::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( CampaignFactoryException::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Money::class )]
final class CampaignFactoryTest extends FundrikTestCase {

	private CampaignFactory $factory;

	protected function setUp(): void {

		parent::setUp();

		$this->factory = new CampaignFactory();
	}

	#[Test]
	public function create_builds_campaign_from_value_objects(): void {

		$campaign = $this->factory->create(
			id: EntityId::create( 1 ),
			version: EntityVersion::create( 3 ),
			title: CampaignTitle::create( 'Save the cats' ),
			is_active: true,
			is_open: false,
			target: CampaignTarget::create( true, Money::create( 123, 'RUB' ) ),
		);

		$this->assertInstanceOf( Campaign::class, $campaign );
		$this->assertSame( 1, $campaign->get_id()->get_value() );
		$this->assertSame( 3, $campaign->get_version()->get_value() );
		$this->assertSame( 'Save the cats', $campaign->get_title() );
		$this->assertTrue( $campaign->is_active() );
		$this->assertFalse( $campaign->is_open() );
		$this->assertTrue( $campaign->has_target() );
		$this->assertSame( 123, $campaign->get_target_money()->get_amount_minor() );
		$this->assertSame( 'RUB', $campaign->get_target_money()->get_currency() );
	}

	#[Test]
	public function create_accepts_uuid_entity_id(): void {

		$campaign = $this->factory->create(
			id: EntityId::create( 'c6f2a6d1-2b2a-4b33-9c9d-8a3e5d9c1b22' ),
			version: EntityVersion::create( 2 ),
			title: CampaignTitle::create( 'Save the dogs' ),
			is_active: false,
			is_open: true,
			target: CampaignTarget::create( false, Money::create( 0, 'RUB' ) ),
		);

		$this->assertSame( 'c6f2a6d1-2b2a-4b33-9c9d-8a3e5d9c1b22', $campaign->get_id()->get_value() );
		$this->assertSame( 0, $campaign->get_target_money()->get_amount_minor() );
		$this->assertFalse( $campaign->has_target() );
	}

	#[Test]
	public function create_new_builds_campaign_with_initial_version(): void {

		$campaign = $this->factory->create_new(
			id: EntityId::create( 1 ),
			title: CampaignTitle::create( 'New campaign' ),
			is_active: true,
			is_open: true,
			target: CampaignTarget::create( false, Money::create( 0, 'RUB' ) ),
		);

		$this->assertSame( 1, $campaign->get_version()->get_value() );
	}

	#[Test]
	public function create_from_primitives_builds_campaign(): void {

		$campaign = $this->factory->create_from_primitives(
			id: 10,
			version: 2,
			title: 'From primitives',
			is_active: true,
			is_open: true,
			has_target: true,
			target_amount: 500,
			target_currency: 'EUR',
		);

		$this->assertSame( 10, $campaign->get_id()->get_value() );
		$this->assertSame( 2, $campaign->get_version()->get_value() );
		$this->assertSame( 'From primitives', $campaign->get_title() );
		$this->assertSame( 500, $campaign->get_target_money()->get_amount_minor() );
		$this->assertSame( 'EUR', $campaign->get_target_money()->get_currency() );
	}

	#[Test]
	public function create_from_primitives_wraps_exceptions_into_factory_exception(): void {

		$this->expectException( CampaignFactoryException::class );

		$this->factory->create_from_primitives(
			id: -1,
			version: 1,
			title: 'Invalid id',
			is_active: true,
			is_open: true,
			has_target: false,
			target_amount: 0,
			target_currency: 'RUB',
		);
	}

	#[Test]
	public function create_from_primitives_wraps_invalid_version_exception(): void {

		$this->expectException( CampaignFactoryException::class );

		$this->factory->create_from_primitives(
			id: 1,
			version: 0,
			title: 'Invalid version',
			is_active: true,
			is_open: true,
			has_target: false,
			target_amount: 0,
			target_currency: 'RUB',
		);
	}

		#[Test]
	public function create_from_primitives_wraps_invalid_title_exception(): void {

			$this->expectException( CampaignFactoryException::class );

		$this->factory->create_from_primitives(
			id: 1,
			version: 1,
			title: '   ',
			is_active: true,
			is_open: true,
			has_target: false,
			target_amount: 0,
			target_currency: 'RUB',
		);
	}

		#[Test]
	public function create_from_primitives_wraps_invalid_target_exception(): void {

		$this->expectException( CampaignFactoryException::class );

		$this->factory->create_from_primitives(
			id: 1,
			version: 1,
			title: 'Invalid target',
			is_active: true,
			is_open: true,
			has_target: true,
			target_amount: 0,
			target_currency: 'RUB',
		);
	}

		#[Test]
	public function create_from_primitives_wraps_invalid_money_amount_exception(): void {

		$this->expectException( CampaignFactoryException::class );

		$this->factory->create_from_primitives(
			id: 1,
			version: 1,
			title: 'Invalid amount',
			is_active: true,
			is_open: true,
			has_target: true,
			target_amount: -1,
			target_currency: 'RUB',
		);
	}

		#[Test]
	public function create_from_primitives_wraps_invalid_money_currency_exception(): void {

				$this->expectException( CampaignFactoryException::class );

		$this->factory->create_from_primitives(
			id: 1,
			version: 1,
			title: 'Invalid currency',
			is_active: true,
			is_open: true,
			has_target: false,
			target_amount: 0,
			target_currency: 'EURO',
		);
	}

				#[Test]
	public function create_new_from_primitives_builds_campaign_with_initial_version(): void {

					$campaign = $this->factory->create_new_from_primitives(
						id: 20,
						title: 'New from primitives',
						is_active: true,
						is_open: false,
						has_target: false,
						target_amount: 0,
						target_currency: 'RUB',
					);

					$this->assertSame( 20, $campaign->get_id()->get_value() );
					$this->assertSame( 1, $campaign->get_version()->get_value() );
					$this->assertSame( 'New from primitives', $campaign->get_title() );
	}
}
