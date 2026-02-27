<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\UseCases\SaveCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignCreatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignUpdatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositorySaveOutcome;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositorySaveResult;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SaveCampaign\SaveCampaignHandler;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusExceptionInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Tests\Fixtures\FakeApplicationEventBusException;
use Fundrik\Core\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( SaveCampaignHandler::class )]
#[UsesClass( CampaignCreatedEvent::class )]
#[UsesClass( CampaignUpdatedEvent::class )]
#[UsesClass( CampaignRepositorySaveOutcome::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Money::class )]
final class SaveCampaignHandlerTest extends MockeryTestCase {

	private CampaignRepositoryPort&MockInterface $repository;
	private ApplicationEventBusPort&MockInterface $event_bus;

	private SaveCampaignHandler $handler;

	protected function setUp(): void {

		parent::setUp();

		$this->repository = Mockery::mock( CampaignRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );

		$this->handler = new SaveCampaignHandler( $this->repository, $this->event_bus );
	}

	#[Test]
	public function handle_saves_campaign_and_publishes_created_event_when_inserted(): void {

		$campaign = $this->make_campaign();

		$outcome = new CampaignRepositorySaveOutcome(
			campaign: $campaign,
			result: CampaignRepositorySaveResult::Inserted,
		);

		$this->repository
			->shouldReceive( 'save' )
			->once()
			->with( $this->identicalTo( $campaign ) )
			->andReturn( $outcome );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs(
				function ( object $event ) use ( $campaign ): bool {

					$this->assertInstanceOf( CampaignCreatedEvent::class, $event );
					$this->assertSame( $campaign->get_entity_id(), $event->campaign_id );

					return true;
				},
			);

		$result = $this->handler->handle( $campaign );

		$this->assertSame( $outcome, $result );
	}

	#[Test]
	public function handle_saves_campaign_and_publishes_updated_event_when_updated(): void {

		$campaign = $this->make_campaign();

		$outcome = new CampaignRepositorySaveOutcome(
			campaign: $campaign,
			result: CampaignRepositorySaveResult::Updated,
		);

		$this->repository
			->shouldReceive( 'save' )
			->once()
			->with( $this->identicalTo( $campaign ) )
			->andReturn( $outcome );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs(
				function ( object $event ) use ( $campaign ): bool {

					$this->assertInstanceOf( CampaignUpdatedEvent::class, $event );
					$this->assertSame( $campaign->get_entity_id(), $event->campaign_id );

					return true;
				},
			);

		$result = $this->handler->handle( $campaign );

		$this->assertSame( $outcome, $result );
	}

	#[Test]
	public function handle_throws_repository_exception_without_publishing(): void {

		$campaign = $this->make_campaign();
		$e = new FakeCampaignRepositoryException();

		$this->repository
			->shouldReceive( 'save' )
			->once()
			->with( $this->identicalTo( $campaign ) )
			->andThrow( $e );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		$this->expectException( CampaignRepositoryExceptionInterface::class );

		$this->handler->handle( $campaign );
	}

	#[Test]
	public function handle_throws_event_bus_exception_when_publishing_fails(): void {

		$campaign = $this->make_campaign();
		$e = new FakeApplicationEventBusException();

		$outcome = new CampaignRepositorySaveOutcome(
			campaign: $campaign,
			result: CampaignRepositorySaveResult::Updated,
		);

		$this->repository
			->shouldReceive( 'save' )
			->once()
			->with( $this->identicalTo( $campaign ) )
			->andReturn( $outcome );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->andThrow( $e );

		$this->expectException( ApplicationEventBusExceptionInterface::class );

		$this->handler->handle( $campaign );
	}
}
