<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\UseCases\DeleteCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeletedEvent;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignPublishDeletedEventDecorator;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignUseCase;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Campaigns\Domain\CampaignVersion;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\EventBusExceptionInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\EventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\Core\Tests\Fixtures\FakeEventBusException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( DeleteCampaignPublishDeletedEventDecorator::class )]
#[UsesClass( CampaignDeletedEvent::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( CampaignVersion::class )]
#[UsesClass( EntityId::class )]
final class DeleteCampaignPublishDeletedEventDecoratorTest extends MockeryTestCase {

	private DeleteCampaignUseCase&MockInterface $inner;
	private EventBusPort&MockInterface $event_bus;

	private DeleteCampaignPublishDeletedEventDecorator $decorator;

	protected function setUp(): void {

		parent::setUp();

		$this->inner = Mockery::mock( DeleteCampaignUseCase::class );
		$this->event_bus = Mockery::mock( EventBusPort::class );

		$this->decorator = new DeleteCampaignPublishDeletedEventDecorator( $this->inner, $this->event_bus );
	}

	#[Test]
	public function handle_delegates_and_publishes_deleted_event(): void {

		$campaign_id = $this->make_campaign()->get_entity_id();

		$this->inner
			->shouldReceive( 'handle' )
			->once()
			->with( $this->identicalTo( $campaign_id ) );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs(
				function ( object $event ) use ( $campaign_id ): bool {

					$this->assertInstanceOf( CampaignDeletedEvent::class, $event );
					$this->assertSame( $campaign_id, $event->campaign_id );

					return true;
				},
			);

		$this->decorator->handle( $campaign_id );

		$this->assertTrue( true );
	}

	#[Test]
	public function handle_propagates_repository_exception(): void {

		$campaign_id = $this->make_campaign()->get_entity_id();
		$e = new FakeCampaignRepositoryException();

		$this->inner
			->shouldReceive( 'handle' )
			->once()
			->andThrow( $e );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		$this->expectException( CampaignRepositoryExceptionInterface::class );

		$this->decorator->handle( $campaign_id );
	}

	#[Test]
	public function handle_throws_event_bus_exception(): void {

		$campaign_id = $this->make_campaign()->get_entity_id();
		$e = new FakeEventBusException();

		$this->inner
			->shouldReceive( 'handle' )
			->once();

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->andThrow( $e );

		$this->expectException( EventBusExceptionInterface::class );

		$this->decorator->handle( $campaign_id );
	}
}
