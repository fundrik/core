<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\Events;

use Fundrik\Core\Components\Donations\Application\Events\DonationApplicationEventInterface;
use Fundrik\Core\Components\Donations\Application\Events\DonationCreatedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationPendingEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationRefundedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationRejectedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationSucceededEvent;
use Fundrik\Core\Components\Donations\Domain\PaymentId;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( DonationCreatedEvent::class )]
#[CoversClass( DonationSucceededEvent::class )]
#[CoversClass( DonationRejectedEvent::class )]
#[CoversClass( DonationRefundedEvent::class )]
#[CoversClass( DonationPendingEvent::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( PaymentId::class )]
final class DonationEventsTest extends FundrikTestCase {

	#[Test]
	#[DataProvider( 'event_class_provider' )]
	public function it_exposes_donation_id( string $event_class ): void {

		$id = EntityId::create( '7c1bb0b8-4d8e-4b3a-9a6e-3f1d9b1b6f5b' );
		$event = new $event_class( $id );

		$this->assertInstanceOf( DonationApplicationEventInterface::class, $event );
		$this->assertSame( $id, $event->get_donation_id() );
	}

	public static function event_class_provider(): array {

		return [
			'created' => [ DonationCreatedEvent::class ],
			'succeeded' => [ DonationSucceededEvent::class ],
			'rejected' => [ DonationRejectedEvent::class ],
			'refunded' => [ DonationRefundedEvent::class ],
		];
	}

	#[Test]
	public function payment_attached_event_exposes_donation_and_payment_ids(): void {

		$donation_id = EntityId::create( 5_001 );
		$payment_id = PaymentId::create( 'pay_5001' );
		$event = new DonationPendingEvent( $donation_id, $payment_id );

		$this->assertSame( $donation_id, $event->get_donation_id() );
		$this->assertSame( $payment_id, $event->get_payment_id() );
	}
}
