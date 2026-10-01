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
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CampaignCreatedEvent::class )]
#[CoversClass( CampaignDeletedEvent::class )]
#[CoversClass( CampaignRenamedEvent::class )]
#[CoversClass( CampaignDonationsEnabledEvent::class )]
#[CoversClass( CampaignDonationsDisabledEvent::class )]
#[CoversClass( CampaignTargetChangedEvent::class )]
#[CoversClass( CampaignSynchronizedEvent::class )]
#[UsesClass( EntityId::class )]
final class CampaignEventsTest extends FundrikTestCase {

	#[Test]
	#[DataProvider( 'event_class_provider' )]
	public function it_exposes_campaign_id_and_event_classification( string $event_class, bool $is_changed ): void {

		$id = EntityId::create( 123 );
		$event = new $event_class( $id );

		$this->assertInstanceOf( CampaignApplicationEventInterface::class, $event );
		$this->assertSame( $id, $event->get_campaign_id() );

		if ( $is_changed ) {
			$this->assertInstanceOf( CampaignChangedEventInterface::class, $event );
			return;
		}

		$this->assertNotInstanceOf( CampaignChangedEventInterface::class, $event );
	}

	public static function event_class_provider(): array {

		return [
			'created' => [ CampaignCreatedEvent::class, false ],
			'deleted' => [ CampaignDeletedEvent::class, false ],
			'renamed' => [ CampaignRenamedEvent::class, true ],
			'donations_enabled' => [ CampaignDonationsEnabledEvent::class, true ],
			'donations_disabled' => [ CampaignDonationsDisabledEvent::class, true ],
			'target_changed' => [ CampaignTargetChangedEvent::class, true ],
			'synchronized' => [ CampaignSynchronizedEvent::class, true ],
		];
	}
}
