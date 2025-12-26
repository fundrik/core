<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\UseCases\CreateCampaign;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign\CreateCampaignHandler;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Campaigns\Domain\CampaignVersion;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CreateCampaignHandler::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( CampaignVersion::class )]
#[UsesClass( EntityId::class )]
final class CreateCampaignHandlerTest extends MockeryTestCase {

	private CampaignRepositoryPort&MockInterface $repository;

	private CreateCampaignHandler $handler;

	protected function setUp(): void {

		parent::setUp();

		$this->repository = Mockery::mock( CampaignRepositoryPort::class );

		$this->handler = new CreateCampaignHandler( $this->repository );
	}

	#[Test]
	public function handle_inserts_campaign(): void {

		$campaign = $this->make_campaign();

		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->with( $this->identicalTo( $campaign ) )
			->andReturn( $campaign );

		$result = $this->handler->handle( $campaign );

		$this->assertSame( $campaign, $result );
	}

	#[Test]
	public function handle_propagates_repository_exception(): void {

		$campaign = $this->make_campaign();
		$e = new FakeCampaignRepositoryException();

		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->with( $this->identicalTo( $campaign ) )
			->andThrow( $e );

		$this->expectException( CampaignRepositoryExceptionInterface::class );

		$this->handler->handle( $campaign );
	}
}
