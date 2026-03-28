<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\UseCases\FindCampaignById;

use Fundrik\Core\Components\Campaigns\Application\Exceptions\CampaignApplicationException;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdHandler;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( FindCampaignByIdHandler::class )]
#[UsesClass( FindCampaignByIdException::class )]
#[UsesClass( CampaignApplicationException::class )]
#[UsesClass( FundrikApplicationException::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( EntityId::class )]
final class FindCampaignByIdHandlerTest extends MockeryTestCase {

	private CampaignRepositoryPort&MockInterface $campaigns;

	private FindCampaignByIdHandler $handler;

	protected function setUp(): void {

		parent::setUp();

		$this->campaigns = Mockery::mock( CampaignRepositoryPort::class );
		$this->handler = new FindCampaignByIdHandler( $this->campaigns );
	}

	#[Test]
	public function handle_returns_campaign_entity(): void {

		$campaign = $this->make_campaign( 1_001 );
		$campaign_id = $campaign->get_id();

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( $campaign );

		$result = $this->handler->handle( $campaign_id );

		$this->assertSame( $campaign, $result );
	}

	#[Test]
	public function handle_returns_null_when_not_found(): void {

		$campaign_id = EntityId::create( 1_001 );

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturnNull();

		$result = $this->handler->handle( $campaign_id );

		$this->assertNull( $result );
	}

	#[Test]
	public function handle_wraps_repository_exception(): void {

		$campaign_id = EntityId::create( 1_001 );
		$e = new FakeCampaignRepositoryException();

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andThrow( $e );

		try {
			$this->handler->handle( $campaign_id );
			$this->fail( 'Expected FindCampaignByIdException to be thrown.' );
		} catch ( FindCampaignByIdException $exception ) {
			$this->assertSame( $e, $exception->getPrevious() );
			$this->assertSame( 'Failed to retrieve campaign "1001".', $exception->getMessage() );
		}
	}
}
