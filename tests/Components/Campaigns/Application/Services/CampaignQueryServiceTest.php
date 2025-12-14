<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\Loggers\AbstractCampaignServiceLogger;
use Fundrik\Core\Components\Campaigns\Application\Loggers\CampaignQueryServiceLogger;
use Fundrik\Core\Components\Campaigns\Application\Ports\Out\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\Out\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryService;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Support\TypeCaster;
use Fundrik\Core\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;

#[CoversClass( CampaignQueryService::class )]
#[UsesClass( AbstractCampaignServiceLogger::class )]
#[UsesClass( CampaignQueryServiceLogger::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( TypeCaster::class )]
final class CampaignQueryServiceTest extends MockeryTestCase {

	private CampaignRepositoryPort&MockInterface $repository;
	private LoggerInterface&MockInterface $psr_logger;

	private CampaignQueryService $service;

	protected function setUp(): void {

		parent::setUp();

		$this->repository = Mockery::mock( CampaignRepositoryPort::class );
		$this->psr_logger = Mockery::mock( LoggerInterface::class )->shouldIgnoreMissing();

		$this->service = new CampaignQueryService(
			$this->repository,
			new CampaignQueryServiceLogger( $this->psr_logger ),
		);
	}

	// Find campaign by id.

	#[Test]
	public function find_campaign_by_id_returns_campaign(): void {

		$campaign_id = EntityId::create( 1 );
		$campaign = $this->make_campaign( id: 1 );

		$this->repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( $campaign );

		$result = $this->service->find_campaign_by_id( $campaign_id );

		$this->assertSame( $campaign, $result );
	}

	#[Test]
	public function find_campaign_by_id_returns_null_when_not_found(): void {

		$campaign_id = EntityId::create( 999 );

		$this->repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( null );

		$result = $this->service->find_campaign_by_id( $campaign_id );

		$this->assertNull( $result );
	}

	#[Test]
	public function find_campaign_by_id_propagates_repository_exception(): void {

		$campaign_id = EntityId::create( 123 );
		$e = new FakeCampaignRepositoryException();

		$this->repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andThrow( $e );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Finding campaign by ID failed (repository error).',
				$this->array_has(
					[
						'operation' => 'find_campaign_by_id',
						'id' => $campaign_id->get_value(),
						'exception' => $e,
					],
				),
			);

		$this->expectException( CampaignRepositoryExceptionInterface::class );

		$this->service->find_campaign_by_id( $campaign_id );
	}

	// Find all campaigns.

	#[Test]
	public function find_all_campaigns_returns_list_of_campaigns(): void {

		$campaign1 = $this->make_campaign( id: 1 );
		$campaign2 = $this->make_campaign( id: 2 );

		$this->repository
			->shouldReceive( 'find_all' )
			->once()
			->andReturn( [ $campaign1, $campaign2 ] );

		$result = $this->service->find_all_campaigns();

		$this->assertCount( 2, $result );
		$this->assertSame( $campaign1, $result[0] );
		$this->assertSame( $campaign2, $result[1] );
	}

	#[Test]
	public function find_all_campaigns_returns_empty_array_when_no_campaigns_found(): void {

		$this->repository
			->shouldReceive( 'find_all' )
			->once()
			->andReturn( [] );

		$result = $this->service->find_all_campaigns();

		$this->assertIsArray( $result );
		$this->assertCount( 0, $result );
	}

	#[Test]
	public function find_all_campaigns_propagates_repository_exception(): void {

		$e = new FakeCampaignRepositoryException();

		$this->repository
			->shouldReceive( 'find_all' )
			->once()
			->andThrow( $e );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Finding campaigns failed (repository error).',
				$this->array_has(
					[
						'operation' => 'find_all_campaigns',
						'exception' => $e,
					],
				),
			);

		$this->expectException( CampaignRepositoryExceptionInterface::class );

		$this->service->find_all_campaigns();
	}
}
