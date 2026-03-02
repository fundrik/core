<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\UseCases\CreateCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignCreatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign\CreateCampaignHandler;
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

#[CoversClass( CreateCampaignHandler::class )]
#[UsesClass( CampaignCreatedEvent::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Money::class )]
final class CreateCampaignHandlerTest extends MockeryTestCase {

	private CampaignRepositoryPort&MockInterface $repository;
	private ApplicationEventBusPort&MockInterface $event_bus;

	private CreateCampaignHandler $handler;

	protected function setUp(): void {

		parent::setUp();

		$this->repository = Mockery::mock( CampaignRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );

		$this->handler = new CreateCampaignHandler( $this->repository, $this->event_bus );
	}

	#[Test]
	public function handle_inserts_campaign_and_publishes_created_event(): void {

		$campaign = $this->make_campaign();

		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->with( $this->identicalTo( $campaign ) )
			->andReturn( $campaign );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs(
				function ( object $event ) use ( $campaign ): bool {

					$this->assertInstanceOf( CampaignCreatedEvent::class, $event );
					$this->assertSame( $campaign->get_id(), $event->get_campaign_id() );

					return true;
				},
			);

		$result = $this->handler->handle( $campaign );

		$this->assertSame( $campaign, $result );
	}

	#[Test]
	public function handle_propagates_repository_exception_without_publishing(): void {

		$campaign = $this->make_campaign();
		$e = new FakeCampaignRepositoryException();

		$this->repository
			->shouldReceive( 'insert' )
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

		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->with( $this->identicalTo( $campaign ) )
			->andReturn( $campaign );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->andThrow( $e );

		$this->expectException( ApplicationEventBusExceptionInterface::class );

		$this->handler->handle( $campaign );
	}
}
