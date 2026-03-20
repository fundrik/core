<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Domain;

use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\InvalidCampaignTargetException;
use Fundrik\Core\Components\Shared\Domain\Amount;
use Fundrik\Core\Components\Shared\Domain\Currency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass( CampaignTarget::class )]
#[CoversClass( Currency::class )]
#[CoversClass( Amount::class )]
final class CampaignTargetTest extends TestCase {

	#[Test]
	public function create_builds_target_with_currency_code_and_amount(): void {

		$target = CampaignTarget::create( 'rub', 500 );

		$this->assertSame( 'RUB', $target->get_currency()->get_code() );
		$this->assertTrue( $target->has_amount() );
		$this->assertSame( 500, $target->get_amount()?->get_value() );
	}

	#[Test]
	public function create_builds_target_without_amount(): void {

		$target = CampaignTarget::create( 'USD', null );

		$this->assertSame( 'USD', $target->get_currency()->get_code() );
		$this->assertFalse( $target->has_amount() );
		$this->assertNull( $target->get_amount() );
	}

	#[Test]
	public function create_throws_when_currency_code_is_invalid(): void {

		$this->expectException( InvalidCampaignTargetException::class );
		$this->expectExceptionMessage( 'Campaign currency code must be a valid ISO 4217 code. Given: "EURO".' );

		CampaignTarget::create( 'EURO', null );
	}

	#[Test]
	public function create_throws_when_amount_is_not_positive(): void {

		$this->expectException( InvalidCampaignTargetException::class );
		$this->expectExceptionMessage( 'Target amount must be positive. Given: 0.' );

		CampaignTarget::create( 'RUB', 0 );
	}

	#[Test]
	public function with_amount_preserves_currency_code(): void {

		$updated = CampaignTarget::create( 'EUR', 100 )->with_amount( Amount::create( 250 ) );

		$this->assertSame( 'EUR', $updated->get_currency()->get_code() );
		$this->assertSame( 250, $updated->get_amount()?->get_value() );
	}

	#[Test]
	public function equals_returns_true_for_same_currency_code_and_amount(): void {

		$target1 = CampaignTarget::create( 'RUB', 100 );
		$target2 = CampaignTarget::create( 'RUB', 100 );

		$this->assertTrue( $target1->equals( $target2 ) );
	}
}
