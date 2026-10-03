<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Domain;

use Fundrik\Core\Components\Donations\Domain\Exceptions\InvalidPaymentIdException;
use Fundrik\Core\Components\Donations\Domain\PaymentId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass( PaymentId::class )]
final class PaymentIdTest extends TestCase {

	#[Test]
	public function create_returns_payment_id_with_opaque_value(): void {

		$payment_id = PaymentId::create( 'provider:payment/5001' );

		$this->assertSame( 'provider:payment/5001', $payment_id->get_value() );
		$this->assertSame( $payment_id, PaymentId::create( $payment_id ) );
	}

	#[Test]
	public function equals_compares_payment_id_values(): void {

		$this->assertTrue( PaymentId::create( 'pay_5001' )->equals( PaymentId::create( 'pay_5001' ) ) );
		$this->assertFalse( PaymentId::create( 'pay_5001' )->equals( PaymentId::create( 'pay_5002' ) ) );
	}

	#[Test]
	public function create_rejects_empty_value(): void {

		$this->expectException( InvalidPaymentIdException::class );
		$this->expectExceptionMessage( 'Payment ID must be a non-empty string. Given: "".' );

		PaymentId::create( '' );
	}
}
