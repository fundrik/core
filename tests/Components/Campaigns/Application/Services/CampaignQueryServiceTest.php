<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryService;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryServiceFactory;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindAllCampaigns\FindAllCampaignsHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdHandler;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CampaignQueryService::class )]
#[UsesClass( CampaignQueryServiceFactory::class )]
#[UsesClass( FindCampaignByIdHandler::class )]
#[UsesClass( FindAllCampaignsHandler::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Money::class )]
final class CampaignQueryServiceTest extends MockeryTestCase {

	private CampaignRepositoryPort&MockInterface $campaign_repository;

	private CampaignQueryService $query;

	protected function setUp(): void {

		parent::setUp();

		$this->campaign_repository = Mockery::mock( CampaignRepositoryPort::class );
		$this->query = ( new CampaignQueryServiceFactory( $this->campaign_repository ) )->create();
	}

	#[Test]
	public function find_by_id_uses_injected_campaign_repository(): void {

		$campaign = $this->make_campaign();
		$campaign_id = $campaign->get_id();

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( $campaign );

		$result = $this->query->find_by_id( $campaign_id );

		$this->assertSame( $campaign, $result );
	}

	#[Test]
	public function find_all_uses_injected_campaign_repository(): void {

		$campaigns = [
			$this->make_campaign( 1, 'Campaign 1' ),
			$this->make_campaign( 2, 'Campaign 2' ),
		];

		$this->campaign_repository
			->shouldReceive( 'find_all' )
			->once()
			->andReturn( $campaigns );

		$result = $this->query->find_all();

		$this->assertSame( $campaigns, $result );
	}
}
