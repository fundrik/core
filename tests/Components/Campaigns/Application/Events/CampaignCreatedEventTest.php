<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\Events;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignCreatedEvent;
use Fundrik\Core\Components\Shared\Application\Events\ApplicationEventInterface;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass( CampaignCreatedEvent::class )]
#[UsesClass( EntityId::class )]
final class CampaignCreatedEventTest extends TestCase {

	#[Test]
	public function it_exposes_campaign_id(): void {

		$id = EntityId::create( 123 );
		$event = new CampaignCreatedEvent( $id );

		$this->assertInstanceOf( ApplicationEventInterface::class, $event );
		$this->assertTrue( $event->campaign_id->equals( $id ) );
	}

	#[Test]
	public function it_accepts_uuid_ids(): void {

		$uuid = '7c1bb0b8-4d8e-4b3a-9a6e-3f1d9b1b6f5b';
		$id = EntityId::create( $uuid );
		$event = new CampaignCreatedEvent( $id );

		$this->assertInstanceOf( ApplicationEventInterface::class, $event );
		$this->assertTrue( $event->campaign_id->equals( $id ) );
	}
}
