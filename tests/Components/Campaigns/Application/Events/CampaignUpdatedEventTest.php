<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\Events;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignUpdatedEvent;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass( CampaignUpdatedEvent::class )]
#[UsesClass( EntityId::class )]
final class CampaignUpdatedEventTest extends TestCase {

	#[Test]
	public function it_exposes_campaign_id(): void {

		$id = EntityId::create( 123 );
		$event = new CampaignUpdatedEvent( $id );

		$this->assertSame( $id, $event->campaign_id );
		$this->assertTrue( $event->campaign_id->equals( $id ) );
	}

	#[Test]
	public function it_accepts_uuid_ids(): void {

		$uuid = '7c1bb0b8-4d8e-4b3a-9a6e-3f1d9b1b6f5b';
		$id = EntityId::create( $uuid );
		$event = new CampaignUpdatedEvent( $id );

		$this->assertSame( $id, $event->campaign_id );
		$this->assertTrue( $event->campaign_id->equals( $id ) );
	}
}
