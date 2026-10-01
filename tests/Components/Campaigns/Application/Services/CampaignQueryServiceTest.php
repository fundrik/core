<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRead\CampaignReadPort;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\Campaign;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryService;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ReadCampaignById\ReadCampaignByIdException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ReadCampaignById\ReadCampaignByIdHandler;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
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
#[UsesClass( UtcDateTime::class )]
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

		$this->assertSame( $campaign, $result );
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

		$this->assertSame( $campaign, $result );
	}

	#[Test]
	public function find_by_id_returns_null_when_campaign_is_missing(): void {

		$this->campaign_read
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturnNull();

		$this->assertNull( $this->query->find_by_id( 123 ) );
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
			$this->assertInstanceOf( InvalidEntityIdException::class, $exception->getPrevious() );
		}
	}
}
