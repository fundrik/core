<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Shared\Domain;

use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidMoneyAmountException;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidMoneyCurrencyException;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( Money::class )]
final class MoneyTest extends FundrikTestCase {

	#[Test]
	public function creates_money_with_non_negative_amount_and_currency(): void {

		$money = Money::create( 1_000, 'rub' );

		$this->assertSame( 1_000, $money->get_amount_minor() );
		$this->assertSame( 'RUB', $money->get_currency() );
	}

	#[Test]
	public function creates_zero_money(): void {

		$money = Money::create( 0, 'RUB' );

		$this->assertSame( 0, $money->get_amount_minor() );
		$this->assertSame( 'RUB', $money->get_currency() );
	}

	#[Test]
	public function throws_when_amount_is_negative(): void {

		$this->expectException( InvalidMoneyAmountException::class );
		$this->expectExceptionMessage( 'Money amount must be zero or positive integer in minor units. Given: -100.' );

		Money::create( -100, 'RUB' );
	}

	#[Test]
	public function throws_when_currency_is_invalid(): void {

		$this->expectException( InvalidMoneyCurrencyException::class );
		$this->expectExceptionMessage( 'Money currency must be a valid ISO 4217 code. Given: "RUBLE".' );

		Money::create( 1_000, 'ruble' );
	}

	#[Test]
	public function equals_compares_amount_and_currency(): void {

		$money1 = Money::create( 1_000, 'RUB' );
		$money2 = Money::create( 1_000, 'RUB' );
		$money3 = Money::create( 1_001, 'RUB' );

		$this->assertTrue( $money1->equals( $money2 ) );
		$this->assertFalse( $money1->equals( $money3 ) );
	}
}
