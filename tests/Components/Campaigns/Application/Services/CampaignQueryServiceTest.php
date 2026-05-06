<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRead\CampaignReadPort;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\Campaign;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryService;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ReadCampaignById\ReadCampaignByIdException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ReadCampaignById\ReadCampaignByIdHandler;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CampaignQueryService::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( ReadCampaignByIdException::class )]
#[UsesClass( ReadCampaignByIdHandler::class )]
#[UsesClass( EntityId::class )]
final class CampaignQueryServiceTest extends MockeryTestCase {

	private CampaignReadPort&MockInterface $campaign_read;

	private CampaignQueryService $query;

	protected function setUp(): void {

		parent::setUp();

		$this->campaign_read = Mockery::mock( CampaignReadPort::class );
		$this->query = new CampaignQueryService(
			new ReadCampaignByIdHandler( $this->campaign_read ),
		);
	}

	#[Test]
	public function find_by_id_uses_injected_campaign_read_port(): void {

		$campaign = $this->make_campaign_read_model();
		$campaign_id = EntityId::create( $campaign->get_id() );

		$this->campaign_read
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_campaign_id ): bool => $actual_campaign_id->equals( $campaign_id ),
			)
			->andReturn( $campaign );

		$result = $this->query->find_by_id( $campaign_id->get_value() );

		$this->assertInstanceOf( Campaign::class, $result );
		$this->assertSame( $campaign->get_id(), $result->get_id() );
		$this->assertSame( $campaign->get_title(), $result->get_title() );
		$this->assertSame( $campaign->accepts_donations(), $result->accepts_donations() );
		$this->assertSame( $campaign->get_currency_code(), $result->get_currency_code() );
		$this->assertSame( $campaign->get_target_amount(), $result->get_target_amount() );
		$this->assertSame( $campaign->get_collected_amount(), $result->get_collected_amount() );
		$this->assertSame( $campaign->get_donations_count(), $result->get_donations_count() );
		$this->assertSame( $campaign->get_created_at(), $result->get_created_at() );
		$this->assertSame( $campaign->get_updated_at(), $result->get_updated_at() );
	}

	#[Test]
	public function find_by_id_accepts_entity_id(): void {

		$campaign = $this->make_campaign_read_model();
		$campaign_id = EntityId::create( $campaign->get_id() );

		$this->campaign_read
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_campaign_id ): bool => $actual_campaign_id === $campaign_id,
			)
			->andReturn( $campaign );

		$result = $this->query->find_by_id( $campaign_id );

		$this->assertInstanceOf( Campaign::class, $result );
		$this->assertSame( $campaign_id->get_value(), $result->get_id() );
	}

	#[Test]
	public function find_by_id_wraps_invalid_campaign_id_input(): void {

		$this->campaign_read
			->shouldNotReceive( 'find_by_id' );

		try {
			$this->query->find_by_id( 'invalid-id' );
			$this->fail( 'Expected ReadCampaignByIdException to be thrown.' );
		} catch ( ReadCampaignByIdException $exception ) {
			$this->assertSame(
				'ID must be a positive integer or a valid UUID. Given: "invalid-id".',
				$exception->getMessage(),
			);
		}
	}
}
