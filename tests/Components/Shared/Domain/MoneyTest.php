<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Shared\Domain;

use Fundrik\Core\Components\Shared\Domain\Amount;
use Fundrik\Core\Components\Shared\Domain\Currency;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidAmountException;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidCurrencyCodeException;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( Money::class )]
#[CoversClass( Amount::class )]
#[CoversClass( Currency::class )]
final class MoneyTest extends FundrikTestCase {

	#[Test]
	public function creates_money_with_positive_amount_and_currency(): void {

		$money = Money::create( 1_000, 'rub' );

		$this->assertSame( 1_000, $money->get_amount()->get_value() );
		$this->assertSame( 'RUB', $money->get_currency()->get_code() );
	}

	#[Test]
	public function trims_currency_before_validation(): void {

		$money = Money::create( 1_000, ' rub ' );

		$this->assertSame( 'RUB', $money->get_currency()->get_code() );
	}

	#[Test]
	public function throws_when_amount_is_not_positive(): void {

		$this->expectException( InvalidAmountException::class );
		$this->expectExceptionMessage( 'Amount must be a positive integer. Given: 0.' );

		Money::create( 0, 'RUB' );
	}

	#[Test]
	public function throws_when_currency_is_invalid(): void {

		$this->expectException( InvalidCurrencyCodeException::class );
		$this->expectExceptionMessage( 'Currency code must be a valid ISO 4217 code. Given: "RUBLE".' );

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
