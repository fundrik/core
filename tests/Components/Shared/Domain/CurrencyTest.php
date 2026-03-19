<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Shared\Domain;

use Fundrik\Core\Components\Shared\Domain\Currency;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidCurrencyCodeException;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( Currency::class )]
final class CurrencyTest extends FundrikTestCase {

	#[Test]
	public function create_builds_uppercase_currency_code(): void {

		$currency = Currency::create( ' rub ' );

		$this->assertSame( 'RUB', $currency->get_code() );
	}

	#[Test]
	public function create_throws_when_code_is_invalid(): void {

		$this->expectException( InvalidCurrencyCodeException::class );
		$this->expectExceptionMessage( 'Currency code must be a valid ISO 4217 code. Given: "RUBLE".' );

		Currency::create( 'ruble' );
	}

	#[Test]
	public function equals_returns_true_for_same_code(): void {

		$currency1 = Currency::create( 'RUB' );
		$currency2 = Currency::create( 'rub' );

		$this->assertTrue( $currency1->equals( $currency2 ) );
	}
}
