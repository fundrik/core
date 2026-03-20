<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\UseCases\FindCampaignById;

use Fundrik\Core\Components\Campaigns\Application\Exceptions\CampaignApplicationException;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignDetailsRead\CampaignDetailsReadPort;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\CampaignDetails;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdHandler;
use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Tests\Fixtures\FakeCampaignDetailsReadException;
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
#[UsesClass( CampaignDetails::class )]
#[UsesClass( EntityId::class )]
final class FindCampaignByIdHandlerTest extends MockeryTestCase {

	private CampaignDetailsReadPort&MockInterface $campaign_details_read;

	private FindCampaignByIdHandler $handler;

	protected function setUp(): void {

		parent::setUp();

		$this->campaign_details_read = Mockery::mock( CampaignDetailsReadPort::class );

		$this->handler = new FindCampaignByIdHandler( $this->campaign_details_read );
	}

	#[Test]
	public function handle_returns_campaign_details(): void {

		$details = $this->make_campaign_details();
		$campaign_id = EntityId::create( $details->get_id() );

		$this->campaign_details_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( $details );

		$result = $this->handler->handle( $campaign_id );

		$this->assertSame( $details, $result );
	}

	#[Test]
	public function handle_returns_null_when_not_found(): void {

		$campaign_id = $this->make_campaign()->get_id();

		$this->campaign_details_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturnNull();

		$result = $this->handler->handle( $campaign_id );

		$this->assertNull( $result );
	}

	#[Test]
	public function handle_throws_view_exception(): void {

		$campaign_id = $this->make_campaign()->get_id();
		$e = new FakeCampaignDetailsReadException();

		$this->campaign_details_read
			->shouldReceive( 'find_by_id' )
			->once()
			->andThrow( $e );

		try {
			$this->handler->handle( $campaign_id );
			$this->fail( 'Expected FindCampaignByIdException to be thrown.' );
		} catch ( FindCampaignByIdException $exception ) {
			$this->assertSame( $e, $exception->getPrevious() );
			$this->assertSame( 'Failed to retrieve campaign "1".', $exception->getMessage() );
		}
	}
}
