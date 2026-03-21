<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\Events;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignApplicationEventInterface;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignChangedEventInterface;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignCreatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeletedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDonationsDisabledEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDonationsEnabledEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignRenamedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignSynchronizedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignTargetChangedEvent;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass( CampaignCreatedEvent::class )]
#[CoversClass( CampaignDeletedEvent::class )]
#[CoversClass( CampaignRenamedEvent::class )]
#[CoversClass( CampaignDonationsEnabledEvent::class )]
#[CoversClass( CampaignDonationsDisabledEvent::class )]
#[CoversClass( CampaignTargetChangedEvent::class )]
#[CoversClass( CampaignSynchronizedEvent::class )]
#[UsesClass( EntityId::class )]
final class CampaignEventsTest extends TestCase {

	#[Test]
	#[DataProvider( 'event_class_provider' )]
	public function it_exposes_campaign_id( string $event_class ): void {

		$id = EntityId::create( 123 );
		$event = new $event_class( $id );

		$this->assertInstanceOf( CampaignApplicationEventInterface::class, $event );
		$this->assertTrue( $event->get_campaign_id()->equals( $id ) );
	}

	#[Test]
	#[DataProvider( 'event_class_provider' )]
	public function it_accepts_uuid_ids( string $event_class ): void {

		$uuid = '7c1bb0b8-4d8e-4b3a-9a6e-3f1d9b1b6f5b';
		$id = EntityId::create( $uuid );
		$event = new $event_class( $id );

		$this->assertInstanceOf( CampaignApplicationEventInterface::class, $event );
		$this->assertTrue( $event->get_campaign_id()->equals( $id ) );
	}

	#[Test]
	#[DataProvider( 'changed_event_class_provider' )]
	public function it_marks_changed_events( string $event_class ): void {

		$id = EntityId::create( 321 );
		$event = new $event_class( $id );

		$this->assertInstanceOf( CampaignChangedEventInterface::class, $event );
		$this->assertTrue( $event->get_campaign_id()->equals( $id ) );
	}

	public static function event_class_provider(): array {

		return [
			'created' => [ CampaignCreatedEvent::class ],
			'deleted' => [ CampaignDeletedEvent::class ],
			'renamed' => [ CampaignRenamedEvent::class ],
			'donations_enabled' => [ CampaignDonationsEnabledEvent::class ],
			'donations_disabled' => [ CampaignDonationsDisabledEvent::class ],
			'target_changed' => [ CampaignTargetChangedEvent::class ],
			'synchronized' => [ CampaignSynchronizedEvent::class ],
		];
	}

	public static function changed_event_class_provider(): array {

		return [
			'renamed' => [ CampaignRenamedEvent::class ],
			'donations_enabled' => [ CampaignDonationsEnabledEvent::class ],
			'donations_disabled' => [ CampaignDonationsDisabledEvent::class ],
			'target_changed' => [ CampaignTargetChangedEvent::class ],
			'synchronized' => [ CampaignSynchronizedEvent::class ],
		];
	}
}
