<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignCreatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeletedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignUpdatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Loggers\AbstractCampaignServiceLogger;
use Fundrik\Core\Components\Campaigns\Application\Loggers\CampaignCommandServiceLogger;
use Fundrik\Core\Components\Campaigns\Application\Ports\Out\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\Out\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\Ports\Out\CampaignRepositorySaveResult;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignCommandService;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Shared\Application\Ports\Out\EventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use RuntimeException;

#[CoversClass( CampaignCommandService::class )]
#[UsesClass( CampaignCreatedEvent::class )]
#[UsesClass( CampaignUpdatedEvent::class )]
#[UsesClass( CampaignDeletedEvent::class )]
#[UsesClass( AbstractCampaignServiceLogger::class )]
#[UsesClass( CampaignCommandServiceLogger::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( EntityId::class )]
final class CampaignCommandServiceTest extends MockeryTestCase {

	private CampaignRepositoryPort&MockInterface $repository;
	private LoggerInterface&MockInterface $psr_logger;
	private EventBusPort&MockInterface $event_bus;

	private CampaignCommandService $service;

	protected function setUp(): void {

		parent::setUp();

		$this->repository = Mockery::mock( CampaignRepositoryPort::class );
		$this->psr_logger = Mockery::mock( LoggerInterface::class )->shouldIgnoreMissing();
		$this->event_bus = Mockery::mock( EventBusPort::class );

		$this->service = new CampaignCommandService(
			$this->repository,
			new CampaignCommandServiceLogger( $this->psr_logger ),
			$this->event_bus,
		);
	}

	// Create campaign.

	#[Test]
	public function create_campaign_inserts_and_publishes_created_event(): void {

		$campaign = $this->make_campaign();

		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->with( $this->identicalTo( $campaign ) );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->with(
				Mockery::on(
					static fn ( $event ) => $event instanceof CampaignCreatedEvent
					&& $event->campaign_id->equals( $campaign->get_entity_id() ),
				),
			);

		$this->psr_logger
			->shouldReceive( 'info' )
			->once()
			->with(
				'Saving campaign succeeded.',
				$this->array_has(
					[
						'operation' => 'save_campaign',
						'id' => $campaign->get_id(),
						'action' => 'create',
					],
				),
			);

		$this->service->create_campaign( $campaign );

		$this->assertTrue( true );
	}

	#[Test]
	public function create_campaign_propagates_repository_exception(): void {

		$campaign = $this->make_campaign();
		$e = new FakeCampaignRepositoryException();

		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->with( $this->identicalTo( $campaign ) )
			->andThrow( $e );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Saving campaign failed (repository error).',
				$this->array_has(
					[
						'operation' => 'save_campaign',
						'id' => $campaign->get_id(),
						'action' => 'create',
						'exception' => $e,
					],
				),
			);

		$this->expectException( CampaignRepositoryExceptionInterface::class );

		$this->service->create_campaign( $campaign );
	}

	#[Test]
	public function create_campaign_logs_warning_when_event_bus_fails_but_method_succeeds(): void {

		$campaign = $this->make_campaign();
		$e = new RuntimeException();

		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->with( $this->identicalTo( $campaign ) );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->with( Mockery::type( CampaignCreatedEvent::class ) )
			->andThrow( $e );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Publishing CampaignCreatedEvent failed (event bus error).',
				$this->array_has(
					[
						'operation' => 'save_campaign',
						'id' => $campaign->get_id(),
						'action' => 'create',
						'exception' => $e,
					],
				),
			);

		$this->psr_logger->shouldReceive( 'info' )->once();

		$this->service->create_campaign( $campaign );

		$this->assertTrue( true );
	}

	// Create campaign without id.

	#[Test]
	public function create_campaign_without_id_returns_campaign_and_publishes_created_event(): void {

		$assigned_id = EntityId::create( 777 );

		$title = CampaignTitle::create( 'Test Campaign' );
		$target = CampaignTarget::create( is_enabled: true, amount: 100 );

		$this->repository
			->shouldReceive( 'insert_without_id' )
			->once()
			->with(
				$this->identicalTo( $title ),
				true,
				false,
				$this->identicalTo( $target ),
			)
			->andReturn( $assigned_id );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->with(
				Mockery::on(
					static fn ( $event ) => $event instanceof CampaignCreatedEvent
					&& $event->campaign_id->equals( $assigned_id ),
				),
			);

		$this->psr_logger
			->shouldReceive( 'info' )
			->once()
			->with(
				'Saving campaign succeeded.',
				$this->array_has(
					[
						'operation' => 'save_campaign',
						'id' => $assigned_id->get_value(),
						'action' => 'create',
					],
				),
			);

		$result = $this->service->create_campaign_without_id(
			title: $title,
			is_active: true,
			is_open: false,
			target: $target,
		);

		$this->assertInstanceOf( Campaign::class, $result );
	}

	#[Test]
	public function create_campaign_without_id_propagates_repository_exception(): void {

		$title = CampaignTitle::create( 'Test Campaign' );
		$target = CampaignTarget::create( is_enabled: true, amount: 100 );
		$e = new FakeCampaignRepositoryException();

		$this->repository
			->shouldReceive( 'insert_without_id' )
			->once()
			->andThrow( $e );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Saving campaign failed (repository error).',
				$this->array_has(
					[
						'operation' => 'save_campaign',
						'id' => '[new]',
						'action' => 'create',
						'exception' => $e,
					],
				),
			);

		$this->expectException( CampaignRepositoryExceptionInterface::class );

		$this->service->create_campaign_without_id( title: $title, is_active: false, is_open: true, target: $target );
	}

	#[Test]
	public function create_campaign_without_id_logs_warning_when_event_bus_fails_but_method_succeeds(): void {

		$assigned_id = EntityId::create( 777 );
		$e = new RuntimeException();

		$title = CampaignTitle::create( 'Test Campaign' );
		$target = CampaignTarget::create( is_enabled: true, amount: 100 );

		$this->repository
			->shouldReceive( 'insert_without_id' )
			->once()
			->andReturn( $assigned_id );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->with( Mockery::type( CampaignCreatedEvent::class ) )
			->andThrow( $e );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Publishing CampaignCreatedEvent failed (event bus error).',
				$this->array_has(
					[
						'operation' => 'save_campaign',
						'id' => $assigned_id->get_value(),
						'action' => 'create',
						'exception' => $e,
					],
				),
			);

		$this->psr_logger->shouldReceive( 'info' )->once();

		$result = $this->service->create_campaign_without_id(
			title: $title,
			is_active: true,
			is_open: false,
			target: $target,
		);

		$this->assertInstanceOf( Campaign::class, $result );
	}

	// Update campaign.

	#[Test]
	public function update_campaign_updates_and_publishes_updated_event(): void {

		$campaign = $this->make_campaign();

		$this->repository
			->shouldReceive( 'update' )
			->once()
			->with( $this->identicalTo( $campaign ) );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->with(
				Mockery::on(
					static fn ( $event ) => $event instanceof CampaignUpdatedEvent
					&& $event->campaign_id->equals( $campaign->get_entity_id() ),
				),
			);

		$this->psr_logger
			->shouldReceive( 'info' )
			->once()
			->with(
				'Saving campaign succeeded.',
				$this->array_has(
					[
						'operation' => 'save_campaign',
						'id' => $campaign->get_id(),
						'action' => 'update',
					],
				),
			);

		$this->service->update_campaign( $campaign );

		$this->assertTrue( true );
	}

	#[Test]
	public function update_campaign_propagates_repository_exception(): void {

		$campaign = $this->make_campaign();
		$e = new FakeCampaignRepositoryException();

		$this->repository
			->shouldReceive( 'update' )
			->once()
			->with( $this->identicalTo( $campaign ) )
			->andThrow( $e );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Saving campaign failed (repository error).',
				$this->array_has(
					[
						'operation' => 'save_campaign',
						'id' => $campaign->get_id(),
						'action' => 'update',
						'exception' => $e,
					],
				),
			);

		$this->expectException( CampaignRepositoryExceptionInterface::class );

		$this->service->update_campaign( $campaign );
	}

	#[Test]
	public function update_campaign_logs_warning_when_event_bus_fails_but_method_succeeds(): void {

		$campaign = $this->make_campaign();
		$e = new RuntimeException();

		$this->repository
			->shouldReceive( 'update' )
			->once()
			->with( $this->identicalTo( $campaign ) );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->with( Mockery::type( CampaignUpdatedEvent::class ) )
			->andThrow( $e );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Publishing CampaignUpdatedEvent failed (event bus error).',
				$this->array_has(
					[
						'operation' => 'save_campaign',
						'id' => $campaign->get_id(),
						'action' => 'update',
						'exception' => $e,
					],
				),
			);

		$this->psr_logger->shouldReceive( 'info' )->once();

		$this->service->update_campaign( $campaign );

		$this->assertTrue( true );
	}

	// Save campaign.

	#[Test]
	public function save_campaign_inserts_and_publishes_created_event_when_repo_returns_inserted(): void {

		$campaign = $this->make_campaign();

		$this->repository
			->shouldReceive( 'save' )
			->once()
			->with( $this->identicalTo( $campaign ) )
			->andReturn( CampaignRepositorySaveResult::Inserted );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->with(
				Mockery::on(
					static fn ( $event ) => $event instanceof CampaignCreatedEvent
						&& $event->campaign_id->equals( $campaign->get_entity_id() ),
				),
			);

		$this->psr_logger
			->shouldReceive( 'info' )
			->once()
			->with(
				'Saving campaign succeeded.',
				$this->array_has(
					[
						'operation' => 'save_campaign',
						'id' => $campaign->get_id(),
						'action' => 'create',
					],
				),
			);

		$this->service->save_campaign( $campaign );

		$this->assertTrue( true );
	}

	#[Test]
	public function save_campaign_updates_and_publishes_updated_event_when_repo_returns_updated(): void {

		$campaign = $this->make_campaign();

		$this->repository
			->shouldReceive( 'save' )
			->once()
			->with( $this->identicalTo( $campaign ) )
			->andReturn( CampaignRepositorySaveResult::Updated );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->with(
				Mockery::on(
					static fn ( $event ) => $event instanceof CampaignUpdatedEvent
						&& $event->campaign_id->equals( $campaign->get_entity_id() ),
				),
			);

		$this->psr_logger
			->shouldReceive( 'info' )
			->once()
			->with(
				'Saving campaign succeeded.',
				$this->array_has(
					[
						'operation' => 'save_campaign',
						'id' => $campaign->get_id(),
						'action' => 'update',
					],
				),
			);

		$this->service->save_campaign( $campaign );

		$this->assertTrue( true );
	}

	#[Test]
	public function save_campaign_propagates_repository_exception(): void {

		$campaign = $this->make_campaign();
		$e = new FakeCampaignRepositoryException();

		$this->repository
			->shouldReceive( 'save' )
			->once()
			->with( $this->identicalTo( $campaign ) )
			->andThrow( $e );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Saving campaign failed (repository error).',
				$this->array_has(
					[
						'operation' => 'save_campaign',
						'id' => $campaign->get_id(),
						'action' => 'save',
						'exception' => $e,
					],
				),
			);

		$this->expectException( CampaignRepositoryExceptionInterface::class );

		$this->service->save_campaign( $campaign );
	}

	#[Test]
	public function save_campaign_logs_warning_when_event_bus_fails_after_inserted(): void {

		$campaign = $this->make_campaign();
		$e = new RuntimeException();

		$this->repository
			->shouldReceive( 'save' )
			->once()
			->with( $this->identicalTo( $campaign ) )
			->andReturn( CampaignRepositorySaveResult::Inserted );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->with( Mockery::type( CampaignCreatedEvent::class ) )
			->andThrow( $e );

			$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Publishing CampaignCreatedEvent failed (event bus error).',
				$this->array_has(
					[
						'operation' => 'save_campaign',
						'id' => $campaign->get_id(),
						'action' => 'create',
						'exception' => $e,
					],
				),
			);

		$this->psr_logger->shouldReceive( 'info' )->once();

		$this->service->save_campaign( $campaign );

		$this->assertTrue( true );
	}

	#[Test]
	public function save_campaign_logs_warning_when_event_bus_fails_after_updated(): void {

		$campaign = $this->make_campaign();
		$e = new RuntimeException();

		$this->repository
			->shouldReceive( 'save' )
			->once()
			->with( $this->identicalTo( $campaign ) )
			->andReturn( CampaignRepositorySaveResult::Updated );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->with( Mockery::type( CampaignUpdatedEvent::class ) )
			->andThrow( $e );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Publishing CampaignUpdatedEvent failed (event bus error).',
				$this->array_has(
					[
						'operation' => 'save_campaign',
						'id' => $campaign->get_id(),
						'action' => 'update',
						'exception' => $e,
					],
				),
			);

		$this->psr_logger->shouldReceive( 'info' )->once();

		$this->service->save_campaign( $campaign );

		$this->assertTrue( true );
	}

	// Delete campaign.

	#[Test]
	public function delete_campaign_calls_repository_and_publishes_event(): void {

		$id = EntityId::create( 42 );

		$this->repository
			->shouldReceive( 'delete' )
			->once()
			->with( $this->identicalTo( $id ) );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->with(
				Mockery::on(
					static fn ( $event ) => $event instanceof CampaignDeletedEvent
					&& $event->campaign_id->equals( $id ),
				),
			);

		$this->psr_logger
			->shouldReceive( 'info' )
			->once()
			->with(
				'Deleting campaign succeeded.',
				$this->array_has(
					[
						'operation' => 'delete_campaign',
						'id' => $id->get_value(),
					],
				),
			);

		$this->service->delete_campaign( $id );

		$this->assertTrue( true );
	}

	#[Test]
	public function delete_campaign_propagates_repository_exception(): void {

		$id = EntityId::create( 42 );
		$e = new FakeCampaignRepositoryException();

		$this->repository
			->shouldReceive( 'delete' )
			->once()
			->with( $this->identicalTo( $id ) )
			->andThrow( $e );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Deleting campaign failed (repository error).',
				$this->array_has(
					[
						'operation' => 'delete_campaign',
						'id' => $id->get_value(),
						'exception' => $e,
					],
				),
			);

		$this->expectException( CampaignRepositoryExceptionInterface::class );

		$this->service->delete_campaign( $id );
	}

	#[Test]
	public function delete_campaign_logs_warning_when_event_bus_fails_and_still_succeeds(): void {

		$id = EntityId::create( 42 );
		$e = new RuntimeException();

		$this->repository
			->shouldReceive( 'delete' )
			->once()
			->with( $this->identicalTo( $id ) );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->with( Mockery::type( CampaignDeletedEvent::class ) )
			->andThrow( $e );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Publishing CampaignDeletedEvent failed (event bus error).',
				$this->array_has(
					[
						'operation' => 'delete_campaign',
						'id' => $id->get_value(),
						'exception' => $e,
					],
				),
			);

		$this->psr_logger->shouldReceive( 'info' )->once();

		$this->service->delete_campaign( $id );

		$this->assertTrue( true );
	}
}
