<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\CampaignDetails;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\CampaignDetailsMapper;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryService;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdHandler;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CampaignQueryService::class )]
#[UsesClass( CampaignDetails::class )]
#[UsesClass( CampaignDetailsMapper::class )]
#[UsesClass( FindCampaignByIdException::class )]
#[UsesClass( FindCampaignByIdHandler::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
final class CampaignQueryServiceTest extends MockeryTestCase {

	private CampaignRepositoryPort&MockInterface $campaign_repository;

	private CampaignQueryService $query;

	protected function setUp(): void {

		parent::setUp();

		$this->campaign_repository = Mockery::mock( CampaignRepositoryPort::class );
		$this->query = new CampaignQueryService(
			new FindCampaignByIdHandler( $this->campaign_repository ),
			new CampaignDetailsMapper(),
		);
	}

	#[Test]
	public function find_by_id_uses_injected_campaign_repository(): void {

		$campaign = $this->make_campaign();
		$campaign_id = $campaign->get_id();

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_campaign_id ): bool => $actual_campaign_id->equals( $campaign_id ),
			)
			->andReturn( $campaign );

		$result = $this->query->find_by_id( $campaign_id->get_value() );

		$this->assertInstanceOf( CampaignDetails::class, $result );
		$this->assertSame( $campaign_id->get_value(), $result->get_id() );
		$this->assertSame( $campaign->get_title(), $result->get_title() );
		$this->assertSame( $campaign->can_receive_donations(), $result->can_receive_donations() );
		$this->assertSame( $campaign->get_target()->get_currency()->get_code(), $result->get_currency_code() );
		$this->assertSame( $campaign->get_target()->get_amount()?->get_value(), $result->get_target_amount() );
	}

	#[Test]
	public function find_by_id_accepts_entity_id(): void {

		$campaign = $this->make_campaign();
		$campaign_id = $campaign->get_id();

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_campaign_id ): bool => $actual_campaign_id === $campaign_id,
			)
			->andReturn( $campaign );

		$result = $this->query->find_by_id( $campaign_id );

		$this->assertInstanceOf( CampaignDetails::class, $result );
		$this->assertSame( $campaign_id->get_value(), $result->get_id() );
	}

	#[Test]
	public function find_by_id_wraps_invalid_campaign_id_input(): void {

		$this->campaign_repository
			->shouldNotReceive( 'find_by_id' );

		try {
			$this->query->find_by_id( 'invalid-id' );
			$this->fail( 'Expected FindCampaignByIdException to be thrown.' );
		} catch ( FindCampaignByIdException $exception ) {
			$this->assertSame(
				'ID must be a positive integer or a valid UUID. Given: "invalid-id".',
				$exception->getMessage(),
			);
		}
	}
}
