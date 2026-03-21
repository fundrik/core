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
			accepts_donations: false,
			target: CampaignTarget::create( 'RUB', 123 ),
		);

		$this->assertInstanceOf( Campaign::class, $campaign );
		$this->assertSame( 1, $campaign->get_id()->get_value() );
		$this->assertSame( 3, $campaign->get_version()->get_value() );
		$this->assertSame( 'Save the cats', $campaign->get_title() );
		$this->assertFalse( $campaign->accepts_donations() );
		$this->assertSame( 'RUB', $campaign->get_target()->get_currency()->get_code() );
		$this->assertTrue( $campaign->has_target() );
		$this->assertSame( 123, $campaign->get_target()->get_amount()?->get_value() );
	}

	#[Test]
	public function create_accepts_uuid_entity_id(): void {

		$campaign = $this->factory->create(
			id: EntityId::create( 'c6f2a6d1-2b2a-4b33-9c9d-8a3e5d9c1b22' ),
			version: EntityVersion::create( 2 ),
			title: CampaignTitle::create( 'Save the dogs' ),
			accepts_donations: true,
			target: CampaignTarget::create( 'USD', null ),
		);

		$this->assertSame( 'c6f2a6d1-2b2a-4b33-9c9d-8a3e5d9c1b22', $campaign->get_id()->get_value() );
		$this->assertSame( 'USD', $campaign->get_target()->get_currency()->get_code() );
		$this->assertFalse( $campaign->has_target() );
		$this->assertNull( $campaign->get_target()->get_amount() );
	}

	#[Test]
	public function create_new_builds_campaign_with_initial_version(): void {

		$campaign = $this->factory->create_new(
			id: EntityId::create( 1 ),
			title: CampaignTitle::create( 'New campaign' ),
			accepts_donations: true,
			target: CampaignTarget::create( 'RUB', null ),
		);

		$this->assertSame( 1, $campaign->get_version()->get_value() );
	}

	#[Test]
	public function create_from_primitives_builds_campaign(): void {

		$campaign = $this->factory->create_from_primitives(
			id: 10,
			version: 2,
			title: 'From primitives',
			accepts_donations: true,
			currency_code: 'EUR',
			target_amount: 500,
		);

		$this->assertSame( 10, $campaign->get_id()->get_value() );
		$this->assertSame( 2, $campaign->get_version()->get_value() );
		$this->assertSame( 'From primitives', $campaign->get_title() );
		$this->assertSame( 'EUR', $campaign->get_target()->get_currency()->get_code() );
		$this->assertSame( 500, $campaign->get_target()->get_amount()?->get_value() );
	}

	#[Test]
	public function create_from_primitives_builds_campaign_without_target(): void {

		$campaign = $this->factory->create_from_primitives(
			id: 10,
			version: 2,
			title: 'Without target',
			accepts_donations: true,
			currency_code: 'RUB',
			target_amount: null,
		);

		$this->assertFalse( $campaign->has_target() );
		$this->assertNull( $campaign->get_target()->get_amount() );
	}

	#[Test]
	public function create_from_primitives_wraps_exceptions_into_factory_exception(): void {

		try {
			$this->factory->create_from_primitives(
				id: -1,
				version: 1,
				title: 'Invalid id',
				accepts_donations: true,
				currency_code: 'RUB',
				target_amount: null,
			);
			$this->fail( 'Expected CampaignFactoryException to be thrown.' );
		} catch ( CampaignFactoryException $exception ) {
			$this->assertSame(
				'ID must be a positive integer or a valid UUID. Given: -1.',
				$exception->getMessage(),
			);
		}
	}

	#[Test]
	public function create_from_primitives_wraps_invalid_version_exception(): void {

		$this->expectException( CampaignFactoryException::class );

		$this->factory->create_from_primitives(
			id: 1,
			version: 0,
			title: 'Invalid version',
			accepts_donations: true,
			currency_code: 'RUB',
			target_amount: null,
		);
	}

	#[Test]
	public function create_from_primitives_wraps_invalid_title_exception(): void {

		$this->expectException( CampaignFactoryException::class );

		$this->factory->create_from_primitives(
			id: 1,
			version: 1,
			title: '   ',
			accepts_donations: true,
			currency_code: 'RUB',
			target_amount: null,
		);
	}

	#[Test]
	public function create_from_primitives_wraps_invalid_target_exception(): void {

		$this->expectException( CampaignFactoryException::class );

		$this->factory->create_from_primitives(
			id: 1,
			version: 1,
			title: 'Invalid target',
			accepts_donations: true,
			currency_code: 'RUB',
			target_amount: 0,
		);
	}

	#[Test]
	public function create_from_primitives_wraps_invalid_negative_target_amount_exception(): void {

		$this->expectException( CampaignFactoryException::class );

		$this->factory->create_from_primitives(
			id: 1,
			version: 1,
			title: 'Invalid amount',
			accepts_donations: true,
			currency_code: 'RUB',
			target_amount: -1,
		);
	}

	#[Test]
	public function create_from_primitives_wraps_invalid_campaign_currency_exception(): void {

		$this->expectException( CampaignFactoryException::class );

		$this->factory->create_from_primitives(
			id: 1,
			version: 1,
			title: 'Invalid currency',
			accepts_donations: true,
			currency_code: 'EURO',
			target_amount: 100,
		);
	}

	#[Test]
	public function create_from_primitives_throws_specific_campaign_currency_message(): void {

		try {
			$this->factory->create_from_primitives(
				id: 1,
				version: 1,
				title: 'Invalid currency',
				accepts_donations: true,
				currency_code: 'EURO',
				target_amount: null,
			);
			$this->fail( 'Expected CampaignFactoryException to be thrown.' );
		} catch ( CampaignFactoryException $exception ) {
			$this->assertSame(
				'Campaign currency code must be a valid ISO 4217 code. Given: "EURO".',
				$exception->getMessage(),
			);
		}
	}

	#[Test]
	public function create_new_from_primitives_builds_campaign_with_initial_version(): void {

		$campaign = $this->factory->create_new_from_primitives(
			id: 20,
			title: 'New from primitives',
			accepts_donations: false,
			currency_code: 'USD',
			target_amount: null,
		);

		$this->assertSame( 20, $campaign->get_id()->get_value() );
		$this->assertSame( 1, $campaign->get_version()->get_value() );
		$this->assertSame( 'New from primitives', $campaign->get_title() );
		$this->assertSame( 'USD', $campaign->get_target()->get_currency()->get_code() );
		$this->assertFalse( $campaign->has_target() );
	}
}
