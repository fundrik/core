<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Domain;

use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\InvalidCampaignTargetException;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CampaignTarget::class )]
#[UsesClass( Money::class )]
final class CampaignTargetTest extends FundrikTestCase {

	#[Test]
	public function creates_enabled_target_with_positive_amount(): void {

		$target = CampaignTarget::create( true, Money::create( 100, 'RUB' ) );

		$this->assertTrue(
			$target->is_enabled(),
		);
		$this->assertSame(
			100,
			$target->get_money()->get_amount_minor(),
		);
	}

	#[Test]
	public function creates_target_from_money_value_object(): void {

		$target = CampaignTarget::create( true, Money::create( 200, 'USD' ) );
		$this->assertTrue(
			$target->is_enabled(),
		);
		$this->assertSame(
			200,
			$target->get_money()->get_amount_minor(),
		);
		$this->assertSame(
			'USD',
			$target->get_money()->get_currency(),
		);
	}

	#[Test]
	public function throws_when_enabled_but_zero_amount(): void {

		$this->expectException( InvalidCampaignTargetException::class );
		$this->expectExceptionMessage( 'Target amount must be positive when targeting is enabled. Given: 0.' );

		CampaignTarget::create( true, Money::create( 0, 'RUB' ) );
	}

	#[Test]
	public function creates_disabled_target_with_zero_amount(): void {

		$target = CampaignTarget::create( false, Money::create( 0, 'RUB' ) );
		$this->assertFalse(
			$target->is_enabled(),
		);
		$this->assertSame(
			0,
			$target->get_money()->get_amount_minor(),
		);
	}

	#[Test]
	public function throws_when_disabled_but_positive_amount(): void {

		$this->expectException( InvalidCampaignTargetException::class );
		$this->expectExceptionMessage( 'Target amount must be zero when targeting is disabled. Given: 100.' );

		CampaignTarget::create( false, Money::create( 100, 'RUB' ) );
	}

	#[Test]
	public function equals_returns_true_for_identical_targets(): void {

		$t1 = CampaignTarget::create( true, Money::create( 100, 'RUB' ) );
		$t2 = CampaignTarget::create( true, Money::create( 100, 'RUB' ) );

		$this->assertTrue( $t1->equals( $t2 ) );
	}

	#[Test]
	public function equals_returns_false_for_different_targets(): void {

		$t1 = CampaignTarget::create( true, Money::create( 100, 'RUB' ) );
		$t2 = CampaignTarget::create( true, Money::create( 200, 'RUB' ) );

		$this->assertFalse( $t1->equals( $t2 ) );
	}
}
