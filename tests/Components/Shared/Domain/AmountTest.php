<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Shared\Domain;

use Fundrik\Core\Components\Shared\Domain\Amount;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidAmountException;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( Amount::class )]
final class AmountTest extends FundrikTestCase {

	#[Test]
	public function create_builds_positive_amount(): void {

		$amount = Amount::create( 500 );

		$this->assertSame( 500, $amount->get_value() );
	}

	#[Test]
	public function create_throws_when_amount_is_not_positive(): void {

		$this->expectException( InvalidAmountException::class );
		$this->expectExceptionMessage( 'Amount must be a positive integer. Given: 0.' );

		Amount::create( 0 );
	}

	#[Test]
	public function equals_returns_true_for_same_value(): void {

		$amount1 = Amount::create( 500 );
		$amount2 = Amount::create( 500 );

		$this->assertTrue( $amount1->equals( $amount2 ) );
	}
}
