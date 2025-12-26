<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\UseCases\CreateCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignCreatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign\CreateCampaignPublishCreatedEventDecorator;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign\CreateCampaignUseCase;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Campaigns\Domain\CampaignVersion;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusExceptionInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Tests\Fixtures\FakeApplicationEventBusException;
use Fundrik\Core\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CreateCampaignPublishCreatedEventDecorator::class )]
#[UsesClass( CampaignCreatedEvent::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( CampaignVersion::class )]
#[UsesClass( EntityId::class )]
final class CreateCampaignPublishCreatedEventDecoratorTest extends MockeryTestCase {

	private CreateCampaignUseCase&MockInterface $inner;
	private ApplicationEventBusPort&MockInterface $event_bus;

	private CreateCampaignPublishCreatedEventDecorator $decorator;

	protected function setUp(): void {

		parent::setUp();

		$this->inner = Mockery::mock( CreateCampaignUseCase::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );

		$this->decorator = new CreateCampaignPublishCreatedEventDecorator( $this->inner, $this->event_bus );
	}

	#[Test]
	public function handle_delegates_and_publishes_created_event(): void {

		$campaign = $this->make_campaign();

		$this->inner
			->shouldReceive( 'handle' )
			->once()
			->with( $this->identicalTo( $campaign ) )
			->andReturn( $campaign );

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

		$result = $this->decorator->handle( $campaign );

		$this->assertSame( $campaign, $result );
	}

	#[Test]
	public function handle_propagates_repository_exception(): void {

		$campaign = $this->make_campaign();
		$e = new FakeCampaignRepositoryException();

		$this->inner
			->shouldReceive( 'handle' )
			->once()
			->andThrow( $e );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		$this->expectException( CampaignRepositoryExceptionInterface::class );

		$this->decorator->handle( $campaign );
	}

	#[Test]
	public function handle_throws_event_bus_exception(): void {

		$campaign = $this->make_campaign();
		$e = new FakeApplicationEventBusException();

		$this->inner
			->shouldReceive( 'handle' )
			->once()
			->andReturn( $campaign );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->andThrow( $e );

		$this->expectException( ApplicationEventBusExceptionInterface::class );

		$this->decorator->handle( $campaign );
	}
}
