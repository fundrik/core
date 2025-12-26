<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests;

use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Campaigns\Domain\CampaignVersion;
use Fundrik\Core\Components\Shared\Domain\EntityId;
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
			version: CampaignVersion::initial(),
			title: CampaignTitle::create( $title ),
			is_active: $is_active,
			is_open: $is_open,
			target: CampaignTarget::create( $has_target, $target_amount ),
		);
	}
}
