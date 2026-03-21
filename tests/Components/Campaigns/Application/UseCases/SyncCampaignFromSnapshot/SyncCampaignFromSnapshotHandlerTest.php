<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignSynchronizedEvent;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot\SyncCampaignFromSnapshotException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot\SyncCampaignFromSnapshotHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot\SyncCampaignFromSnapshotNotFoundException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot\SyncCampaignFromSnapshotPreconditionReason;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Tests\Fixtures\FakeApplicationEventBusException;
use Fundrik\Core\Tests\Fixtures\FakeCampaignNotFoundException;
use Fundrik\Core\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( SyncCampaignFromSnapshotHandler::class )]
#[UsesClass( SyncCampaignFromSnapshotException::class )]
#[UsesClass( SyncCampaignFromSnapshotNotFoundException::class )]
#[UsesClass( SyncCampaignFromSnapshotPreconditionReason::class )]
#[UsesClass( CampaignSynchronizedEvent::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( EntityVersion::class )]
final class SyncCampaignFromSnapshotHandlerTest extends MockeryTestCase {

	private CampaignRepositoryPort&MockInterface $campaigns;
	private ApplicationEventBusPort&MockInterface $event_bus;

	private SyncCampaignFromSnapshotHandler $handler;

	protected function setUp(): void {

		parent::setUp();

		$this->campaigns = Mockery::mock( CampaignRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );

		$this->handler = new SyncCampaignFromSnapshotHandler( $this->campaigns, $this->event_bus );
	}

	#[Test]
	public function handle_updates_existing_campaign_with_single_persistence_write(): void {

		$persisted = $this->make_campaign( title: 'Old Campaign', accepts_donations: false, target_amount: 1_000 );
		$snapshot = $this->make_campaign(
			id: $persisted->get_id()->get_value(),
			title: 'Synced Campaign',
			accepts_donations: true,
			target_amount: 5_000,
		);
		$campaign_id = $persisted->get_id();

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_campaign_id ): bool => $actual_campaign_id->equals( $campaign_id ),
			)
			->andReturn( $persisted );

		$this->campaigns
			->shouldReceive( 'update' )
			->once()
			->with( $this->identicalTo( $snapshot ) )
			->andReturn( $snapshot );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs(
				function ( object $event ) use ( $campaign_id ): bool {

					$this->assertInstanceOf( CampaignSynchronizedEvent::class, $event );
					$this->assertTrue( $event->get_campaign_id()->equals( $campaign_id ) );

					return true;
				},
			);

		$this->handler->handle( $snapshot );
	}

	#[Test]
	public function handle_returns_without_persisting_when_snapshot_matches_campaign_state(): void {

		$persisted = $this->make_campaign();
		$snapshot = new Campaign(
			id: $persisted->get_id(),
			version: EntityVersion::create( 2 ),
			title: CampaignTitle::create( 'Test Campaign' ),
			accepts_donations: true,
			target: CampaignTarget::create( 'RUB', 100 ),
		);

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $persisted->get_id() ) )
			->andReturn( $persisted );

		$this->campaigns
			->shouldNotReceive( 'update' );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		$this->handler->handle( $snapshot );

		$this->assertTrue( true );
	}

	#[Test]
	public function handle_throws_when_campaign_lookup_fails(): void {

		$snapshot = $this->make_campaign( id: 1, title: 'Synced Campaign', target_amount: 500 );
		$campaign_id = $snapshot->get_id();
		$e = new FakeCampaignRepositoryException();

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andThrow( $e );

		$this->campaigns
			->shouldNotReceive( 'update' );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		try {
			$this->handler->handle( $snapshot );
			$this->fail( 'Expected SyncCampaignFromSnapshotException to be thrown.' );
		} catch ( SyncCampaignFromSnapshotException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame(
				SyncCampaignFromSnapshotPreconditionReason::CampaignLookupFailed,
				$exception->get_reason(),
			);
			$this->assertSame( 'Failed to retrieve campaign "1".', $exception->getMessage() );
			$this->assertSame( $e, $exception->getPrevious() );
		}
	}

	#[Test]
	public function handle_throws_when_campaign_does_not_exist(): void {

		$snapshot = $this->make_campaign( id: 1, title: 'Synced Campaign', target_amount: 500 );
		$campaign_id = $snapshot->get_id();

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( null );

		$this->campaigns
			->shouldNotReceive( 'update' );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		try {
			$this->handler->handle( $snapshot );
			$this->fail( 'Expected SyncCampaignFromSnapshotException to be thrown.' );
		} catch ( SyncCampaignFromSnapshotException $exception ) {
			$this->assertInstanceOf( SyncCampaignFromSnapshotNotFoundException::class, $exception );
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame( SyncCampaignFromSnapshotPreconditionReason::CampaignNotFound, $exception->get_reason() );
			$this->assertSame( 'Cannot sync campaign "1": campaign does not exist.', $exception->getMessage() );
		}
	}

	#[Test]
	public function handle_throws_when_currency_change_is_requested(): void {

		$persisted = $this->make_campaign( currency_code: 'RUB' );
		$snapshot = $this->make_campaign(
			id: $persisted->get_id()->get_value(),
			title: 'Synced Campaign',
			currency_code: 'EUR',
			target_amount: 500,
		);
		$campaign_id = $persisted->get_id();

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_campaign_id ): bool => $actual_campaign_id->equals( $campaign_id ),
			)
			->andReturn( $persisted );

		$this->campaigns
			->shouldNotReceive( 'update' );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		try {
			$this->handler->handle( $snapshot );
			$this->fail( 'Expected SyncCampaignFromSnapshotException to be thrown.' );
		} catch ( SyncCampaignFromSnapshotException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame(
				SyncCampaignFromSnapshotPreconditionReason::CurrencyChangeRejected,
				$exception->get_reason(),
			);
			$this->assertSame( 'Cannot sync campaign "1": currency change is not supported.', $exception->getMessage() );
		}
	}

	#[Test]
	public function handle_wraps_campaign_persistence_failure(): void {

		$persisted = $this->make_campaign( title: 'Old Campaign' );
		$snapshot = $this->make_campaign(
			id: $persisted->get_id()->get_value(),
			title: 'Synced Campaign',
		);
		$campaign_id = $persisted->get_id();
		$e = new FakeCampaignRepositoryException();

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_campaign_id ): bool => $actual_campaign_id->equals( $campaign_id ),
			)
			->andReturn( $persisted );

		$this->campaigns
			->shouldReceive( 'update' )
			->once()
			->with( $this->identicalTo( $snapshot ) )
			->andThrow( $e );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		try {
			$this->handler->handle( $snapshot );
			$this->fail( 'Expected SyncCampaignFromSnapshotException to be thrown.' );
		} catch ( SyncCampaignFromSnapshotException $exception ) {
			$this->assertSame( UseCaseFailureStage::Persistence, $exception->get_stage() );
			$this->assertNull( $exception->get_reason() );
			$this->assertSame( 'Failed to sync campaign "1".', $exception->getMessage() );
			$this->assertSame( $e, $exception->getPrevious() );
		}
	}

	#[Test]
	public function handle_throws_when_campaign_disappears_before_persist(): void {

		$persisted = $this->make_campaign( title: 'Old Campaign' );
		$snapshot = $this->make_campaign(
			id: $persisted->get_id()->get_value(),
			title: 'Synced Campaign',
		);
		$campaign_id = $persisted->get_id();
		$e = new FakeCampaignNotFoundException();

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_campaign_id ): bool => $actual_campaign_id->equals( $campaign_id ),
			)
			->andReturn( $persisted );

		$this->campaigns
			->shouldReceive( 'update' )
			->once()
			->with( $this->identicalTo( $snapshot ) )
			->andThrow( $e );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		try {
			$this->handler->handle( $snapshot );
			$this->fail( 'Expected SyncCampaignFromSnapshotException to be thrown.' );
		} catch ( SyncCampaignFromSnapshotException $exception ) {
			$this->assertInstanceOf( SyncCampaignFromSnapshotNotFoundException::class, $exception );
			$this->assertSame( UseCaseFailureStage::Persistence, $exception->get_stage() );
			$this->assertNull( $exception->get_reason() );
			$this->assertSame( 'Cannot sync campaign "1": campaign does not exist.', $exception->getMessage() );
			$this->assertSame( $e, $exception->getPrevious() );
		}
	}

	#[Test]
	public function handle_wraps_event_publish_failure(): void {

		$persisted = $this->make_campaign( title: 'Old Campaign' );
		$snapshot = $this->make_campaign(
			id: $persisted->get_id()->get_value(),
			title: 'Synced Campaign',
		);
		$campaign_id = $persisted->get_id();
		$e = new FakeApplicationEventBusException();

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_campaign_id ): bool => $actual_campaign_id->equals( $campaign_id ),
			)
			->andReturn( $persisted );

		$this->campaigns
			->shouldReceive( 'update' )
			->once()
			->with( $this->identicalTo( $snapshot ) )
			->andReturn( $snapshot );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->andThrow( $e );

		try {
			$this->handler->handle( $snapshot );
			$this->fail( 'Expected SyncCampaignFromSnapshotException to be thrown.' );
		} catch ( SyncCampaignFromSnapshotException $exception ) {
			$this->assertSame( UseCaseFailureStage::EventPublish, $exception->get_stage() );
			$this->assertNull( $exception->get_reason() );
			$this->assertSame(
				'Campaign "1" was synchronized, but publishing the synchronized event failed.',
				$exception->getMessage(),
			);
			$this->assertSame( $e, $exception->getPrevious() );
		}
	}
}
