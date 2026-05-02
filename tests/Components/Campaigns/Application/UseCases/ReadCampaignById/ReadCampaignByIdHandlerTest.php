<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\UseCases\ReadCampaignById;

use Fundrik\Core\Components\Campaigns\Application\Exceptions\CampaignApplicationException;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRead\CampaignReadPort;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\Campaign;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ReadCampaignById\ReadCampaignByIdException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ReadCampaignById\ReadCampaignByIdHandler;
use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Tests\Fixtures\FakeCampaignReadException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( ReadCampaignByIdHandler::class )]
#[UsesClass( ReadCampaignByIdException::class )]
#[UsesClass( CampaignApplicationException::class )]
#[UsesClass( FundrikApplicationException::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( EntityId::class )]
final class ReadCampaignByIdHandlerTest extends MockeryTestCase {

	private CampaignReadPort&MockInterface $campaign_read;

	private ReadCampaignByIdHandler $handler;

	protected function setUp(): void {

		parent::setUp();

		$this->campaign_read = Mockery::mock( CampaignReadPort::class );
		$this->handler = new ReadCampaignByIdHandler( $this->campaign_read );
	}

	#[Test]
	public function handle_returns_campaign(): void {

		$campaign = $this->make_campaign_read_model( id: 1_001 );
		$campaign_id = EntityId::create( $campaign->get_id() );

		$this->campaign_read
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

		$this->campaign_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturnNull();

		$result = $this->handler->handle( $campaign_id );

		$this->assertNull( $result );
	}

	#[Test]
	public function handle_wraps_read_exception(): void {

		$campaign_id = EntityId::create( 1_001 );
		$e = new FakeCampaignReadException();

		$this->campaign_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andThrow( $e );

		try {
			$this->handler->handle( $campaign_id );
			$this->fail( 'Expected ReadCampaignByIdException to be thrown.' );
		} catch ( ReadCampaignByIdException $exception ) {
			$this->assertSame( $e, $exception->getPrevious() );
			$this->assertSame( 'Failed to retrieve campaign "1001".', $exception->getMessage() );
		}
	}
}
