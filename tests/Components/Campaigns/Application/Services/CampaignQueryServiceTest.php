<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignDetailsRead\CampaignDetailsReadPort;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\CampaignDetails;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryService;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignDetailsById\FindCampaignDetailsByIdException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignDetailsById\FindCampaignDetailsByIdHandler;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CampaignQueryService::class )]
#[UsesClass( CampaignDetails::class )]
#[UsesClass( FindCampaignDetailsByIdException::class )]
#[UsesClass( FindCampaignDetailsByIdHandler::class )]
#[UsesClass( EntityId::class )]
final class CampaignQueryServiceTest extends MockeryTestCase {

	private CampaignDetailsReadPort&MockInterface $campaign_details_read;

	private CampaignQueryService $query;

	protected function setUp(): void {

		parent::setUp();

		$this->campaign_details_read = Mockery::mock( CampaignDetailsReadPort::class );
		$this->query = new CampaignQueryService(
			new FindCampaignDetailsByIdHandler( $this->campaign_details_read ),
		);
	}

	#[Test]
	public function find_by_id_uses_injected_campaign_details_read_port(): void {

		$details = $this->make_campaign_details();
		$campaign_id = EntityId::create( $details->get_id() );

		$this->campaign_details_read
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_campaign_id ): bool => $actual_campaign_id->equals( $campaign_id ),
			)
			->andReturn( $details );

		$result = $this->query->find_by_id( $campaign_id->get_value() );

		$this->assertInstanceOf( CampaignDetails::class, $result );
		$this->assertSame( $details->get_id(), $result->get_id() );
		$this->assertSame( $details->get_title(), $result->get_title() );
		$this->assertSame( $details->accepts_donations(), $result->accepts_donations() );
		$this->assertSame( $details->get_currency_code(), $result->get_currency_code() );
		$this->assertSame( $details->get_target_amount(), $result->get_target_amount() );
		$this->assertSame( $details->get_created_at(), $result->get_created_at() );
		$this->assertSame( $details->get_updated_at(), $result->get_updated_at() );
	}

	#[Test]
	public function find_by_id_accepts_entity_id(): void {

		$details = $this->make_campaign_details();
		$campaign_id = EntityId::create( $details->get_id() );

		$this->campaign_details_read
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_campaign_id ): bool => $actual_campaign_id === $campaign_id,
			)
			->andReturn( $details );

		$result = $this->query->find_by_id( $campaign_id );

		$this->assertInstanceOf( CampaignDetails::class, $result );
		$this->assertSame( $campaign_id->get_value(), $result->get_id() );
	}

	#[Test]
	public function find_by_id_wraps_invalid_campaign_id_input(): void {

		$this->campaign_details_read
			->shouldNotReceive( 'find_by_id' );

		try {
			$this->query->find_by_id( 'invalid-id' );
			$this->fail( 'Expected FindCampaignDetailsByIdException to be thrown.' );
		} catch ( FindCampaignDetailsByIdException $exception ) {
			$this->assertSame(
				'ID must be a positive integer or a valid UUID. Given: "invalid-id".',
				$exception->getMessage(),
			);
		}
	}
}
