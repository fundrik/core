<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\Ports\CampaignRepository;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositorySaveOutcome;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositorySaveResult;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CampaignRepositorySaveOutcome::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Money::class )]
final class CampaignRepositorySaveOutcomeTest extends FundrikTestCase {

	#[Test]
	public function stores_result_and_campaign_snapshot(): void {

		$campaign = $this->make_campaign();

		$outcome = new CampaignRepositorySaveOutcome(
			result: CampaignRepositorySaveResult::Inserted,
			campaign: $campaign,
		);

		$this->assertSame( CampaignRepositorySaveResult::Inserted, $outcome->result );
		$this->assertSame( $campaign, $outcome->campaign );
	}

	#[Test]
	public function stores_updated_result(): void {

		$campaign = $this->make_campaign();

		$outcome = new CampaignRepositorySaveOutcome(
			result: CampaignRepositorySaveResult::Updated,
			campaign: $campaign,
		);

		$this->assertSame( CampaignRepositorySaveResult::Updated, $outcome->result );
		$this->assertSame( $campaign, $outcome->campaign );
	}
}
