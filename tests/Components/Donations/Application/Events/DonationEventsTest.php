<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\Events;

use Fundrik\Core\Components\Donations\Application\Events\DonationApplicationEventInterface;
use Fundrik\Core\Components\Donations\Application\Events\DonationCreatedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationRejectedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationRefundedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationSucceededEvent;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass( DonationCreatedEvent::class )]
#[CoversClass( DonationSucceededEvent::class )]
#[CoversClass( DonationRejectedEvent::class )]
#[CoversClass( DonationRefundedEvent::class )]
#[UsesClass( EntityId::class )]
final class DonationEventsTest extends TestCase {

	#[Test]
	#[DataProvider( 'event_class_provider' )]
	public function it_exposes_donation_id( string $event_class ): void {

		$id = EntityId::create( 123 );
		$event = new $event_class( $id );

		$this->assertInstanceOf( DonationApplicationEventInterface::class, $event );
		$this->assertTrue( $event->get_donation_id()->equals( $id ) );
	}

	#[Test]
	#[DataProvider( 'event_class_provider' )]
	public function it_accepts_uuid_ids( string $event_class ): void {

		$uuid = '7c1bb0b8-4d8e-4b3a-9a6e-3f1d9b1b6f5b';
		$id = EntityId::create( $uuid );
		$event = new $event_class( $id );

		$this->assertInstanceOf( DonationApplicationEventInterface::class, $event );
		$this->assertTrue( $event->get_donation_id()->equals( $id ) );
	}

	public static function event_class_provider(): array {

		return [
			'created' => [ DonationCreatedEvent::class ],
			'succeeded' => [ DonationSucceededEvent::class ],
			'rejected' => [ DonationRejectedEvent::class ],
			'refunded' => [ DonationRefundedEvent::class ],
		];
	}
}
