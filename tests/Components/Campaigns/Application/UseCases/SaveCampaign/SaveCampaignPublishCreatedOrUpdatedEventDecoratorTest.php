<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\UseCases\SaveCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignCreatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignUpdatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositorySaveOutcome;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositorySaveResult;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SaveCampaign\SaveCampaignPublishCreatedOrUpdatedEventDecorator;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SaveCampaign\SaveCampaignUseCase;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusExceptionInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Tests\Fixtures\FakeApplicationEventBusException;
use Fundrik\Core\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( SaveCampaignPublishCreatedOrUpdatedEventDecorator::class )]
#[UsesClass( CampaignCreatedEvent::class )]
#[UsesClass( CampaignUpdatedEvent::class )]
#[UsesClass( CampaignRepositorySaveOutcome::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
final class SaveCampaignPublishCreatedOrUpdatedEventDecoratorTest extends MockeryTestCase {

	private SaveCampaignUseCase&MockInterface $inner;
	private ApplicationEventBusPort&MockInterface $event_bus;

	private SaveCampaignPublishCreatedOrUpdatedEventDecorator $decorator;

	protected function setUp(): void {

		parent::setUp();

		$this->inner = Mockery::mock( SaveCampaignUseCase::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );

		$this->decorator = new SaveCampaignPublishCreatedOrUpdatedEventDecorator( $this->inner, $this->event_bus );
	}

	#[Test]
	public function handle_publishes_created_event_when_inserted(): void {

		$campaign = $this->make_campaign();

		$outcome = new CampaignRepositorySaveOutcome(
			campaign: $campaign,
			result: CampaignRepositorySaveResult::Inserted,
		);

		$this->inner
			->shouldReceive( 'handle' )
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

		$result = $this->decorator->handle( $campaign );

		$this->assertSame( $outcome, $result );
	}

	#[Test]
	public function handle_publishes_updated_event_when_updated(): void {

		$campaign = $this->make_campaign();

		$outcome = new CampaignRepositorySaveOutcome(
			campaign: $campaign,
			result: CampaignRepositorySaveResult::Updated,
		);

		$this->inner
			->shouldReceive( 'handle' )
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

		$result = $this->decorator->handle( $campaign );

		$this->assertSame( $outcome, $result );
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

		$outcome = new CampaignRepositorySaveOutcome(
			campaign: $campaign,
			result: CampaignRepositorySaveResult::Updated,
		);

		$e = new FakeApplicationEventBusException();

		$this->inner
			->shouldReceive( 'handle' )
			->once()
			->andReturn( $outcome );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->andThrow( $e );

		$this->expectException( ApplicationEventBusExceptionInterface::class );

		$this->decorator->handle( $campaign );
	}
}
