<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\UseCases\FindAllCampaigns;

use Fundrik\Core\Components\Campaigns\Application\Exceptions\CampaignApplicationException;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindAllCampaigns\FindAllCampaignsException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindAllCampaigns\FindAllCampaignsHandler;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( FindAllCampaignsHandler::class )]
#[UsesClass( FindAllCampaignsException::class )]
#[UsesClass( CampaignApplicationException::class )]
#[UsesClass( FundrikApplicationException::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Money::class )]
final class FindAllCampaignsHandlerTest extends MockeryTestCase {

	private CampaignRepositoryPort&MockInterface $repository;

	private FindAllCampaignsHandler $handler;

	protected function setUp(): void {

		parent::setUp();

		$this->repository = Mockery::mock( CampaignRepositoryPort::class );

		$this->handler = new FindAllCampaignsHandler( $this->repository );
	}

	#[Test]
	public function handle_returns_campaigns(): void {

		$campaigns = [ $this->make_campaign(), $this->make_campaign() ];

		$this->repository
			->shouldReceive( 'find_all' )
			->once()
			->andReturn( $campaigns );

		$result = $this->handler->handle();

		$this->assertSame( $campaigns, $result );
	}

	#[Test]
	public function handle_throws_repository_exception(): void {

		$e = new FakeCampaignRepositoryException();

		$this->repository
			->shouldReceive( 'find_all' )
			->once()
			->andThrow( $e );

		try {
			$this->handler->handle();
			$this->fail( 'Expected FindAllCampaignsException to be thrown.' );
		} catch ( FindAllCampaignsException $exception ) {
			$this->assertSame( $e, $exception->getPrevious() );
		}
	}
}
