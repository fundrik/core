<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests;

use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class FundrikTestCase extends PHPUnitTestCase {

	/**
	 * Returns a valid Campaign for use in tests.
	 * Allows overriding fields to simulate variations.
	 */
	protected function make_campaign(
		int|string $id = 1,
		string $title = 'Test Campaign',
		bool $is_active = true,
		bool $is_open = true,
		bool $has_target = true,
		int $target_amount = 100,
	): Campaign {

		return new Campaign(
			id: EntityId::create( $id ),
			version: EntityVersion::initial(),
			title: CampaignTitle::create( $title ),
			is_active: $is_active,
			is_open: $is_open,
			target: CampaignTarget::create( $has_target, Money::create( $target_amount, 'RUB' ) ),
		);
	}

	/**
	 * Returns a valid pending Donation for use in tests.
	 * Allows overriding key fields to simulate variations.
	 */
	protected function make_pending_donation(
		int|string $id = 5_001,
		int|string|EntityId $campaign_id = 901,
		int $amount = 1_000,
		string $currency = 'RUB',
	): Donation {

		$factory = new DonationFactory();

		return $factory->create_pending(
			id: EntityId::create( $id ),
			campaign_id: $campaign_id instanceof EntityId ? $campaign_id : EntityId::create( $campaign_id ),
			money: Money::create( $amount, $currency ),
		);
	}

	/**
	 * Returns a valid captured Donation for use in tests.
	 * Allows overriding key fields to simulate variations.
	 */
	protected function make_captured_donation(
		int|string $id = 5_001,
		int|string|EntityId $campaign_id = 901,
		int $amount = 1_000,
		string $currency = 'RUB',
	): Donation {

		return $this->make_pending_donation(
			id: $id,
			campaign_id: $campaign_id,
			amount: $amount,
			currency: $currency,
		)->capture();
	}
}
