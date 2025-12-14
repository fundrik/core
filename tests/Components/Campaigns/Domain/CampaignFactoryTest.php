<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Domain;

use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignFactoryException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Support\TypeCaster;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CampaignFactory::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( TypeCaster::class )]
final class CampaignFactoryTest extends FundrikTestCase {

	private CampaignFactory $factory;

	protected function setUp(): void {

		parent::setUp();

		$this->factory = new CampaignFactory();
	}

	#[Test]
	public function create_builds_campaign_from_int_id_and_string_title(): void {

		$campaign = $this->factory->create(
			id: 1,
			title: 'Save the cats',
			is_active: true,
			is_open: false,
			has_target: true,
			target_amount: 123,
		);

		$this->assertInstanceOf( Campaign::class, $campaign );

		$this->assertSame( 1, $campaign->get_id() );
		$this->assertSame( 'Save the cats', $campaign->get_title() );
		$this->assertSame( true, $campaign->is_active() );
		$this->assertSame( false, $campaign->is_open() );
		$this->assertSame( true, $campaign->has_target() );
		$this->assertSame( 123, $campaign->get_target_amount() );
	}

	#[Test]
	public function create_builds_campaign_from_string_id(): void {

		$campaign = $this->factory->create(
			id: 'c6f2a6d1-2b2a-4b33-9c9d-8a3e5d9c1b22',
			title: 'Save the dogs',
			is_active: false,
			is_open: true,
			has_target: false,
			target_amount: 0,
		);

		$this->assertInstanceOf( Campaign::class, $campaign );

		$this->assertSame( 'c6f2a6d1-2b2a-4b33-9c9d-8a3e5d9c1b22', $campaign->get_id() );
		$this->assertSame( 'Save the dogs', $campaign->get_title() );
		$this->assertSame( false, $campaign->is_active() );
		$this->assertSame( true, $campaign->is_open() );
		$this->assertSame( false, $campaign->has_target() );
		$this->assertSame( 0, $campaign->get_target_amount() );
	}

	#[Test]
	public function create_builds_campaign_from_value_objects(): void {

		$id = EntityId::create( 77 );
		$title = CampaignTitle::create( 'Help the whales' );

		$campaign = $this->factory->create(
			id: $id,
			title: $title,
			is_active: true,
			is_open: true,
			has_target: true,
			target_amount: 500,
		);

		$this->assertInstanceOf( Campaign::class, $campaign );

		$this->assertSame( 77, $campaign->get_id() );
		$this->assertSame( 'Help the whales', $campaign->get_title() );
		$this->assertSame( true, $campaign->is_active() );
		$this->assertSame( true, $campaign->is_open() );
		$this->assertSame( true, $campaign->has_target() );
		$this->assertSame( 500, $campaign->get_target_amount() );
	}

	#[Test]
	public function create_throws_factory_exception_when_id_is_invalid(): void {

		$this->expectException( CampaignFactoryException::class );

		$this->factory->create(
			id: -1,
			title: 'Anything',
			is_active: true,
			is_open: true,
			has_target: false,
			target_amount: 0,
		);
	}

	#[Test]
	public function create_throws_factory_exception_when_title_is_invalid(): void {

		$this->expectException( CampaignFactoryException::class );

		$this->factory->create( id: 1, title: '', is_active: true, is_open: true, has_target: false, target_amount: 0 );
	}

	#[Test]
	public function create_throws_factory_exception_when_target_amount_is_invalid(): void {

		$this->expectException( CampaignFactoryException::class );

		$this->factory->create(
			id: 1,
			title: 'Bad target',
			is_active: true,
			is_open: true,
			has_target: false,
			target_amount: 100,
		);
	}
}
