<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\UseCases\FindDonationsByCampaignId;

use DateTimeImmutable;
use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationsByCampaignId\FindDonationsByCampaignIdException;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationsByCampaignId\FindDonationsByCampaignIdHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\Core\Tests\Fixtures\FakeDonationRepositoryException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( FindDonationsByCampaignIdHandler::class )]
#[UsesClass( FindDonationsByCampaignIdException::class )]
#[UsesClass( DonationApplicationException::class )]
#[UsesClass( FundrikApplicationException::class )]
#[UsesClass( Donation::class )]
#[UsesClass( DonationFactory::class )]
#[UsesClass( DonationStatus::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Money::class )]
#[UsesClass( UtcDateTime::class )]
final class FindDonationsByCampaignIdHandlerTest extends MockeryTestCase {

	private DonationRepositoryPort&MockInterface $repository;

	private FindDonationsByCampaignIdHandler $handler;

	protected function setUp(): void {

		parent::setUp();

		$this->repository = Mockery::mock( DonationRepositoryPort::class );
		$this->handler = new FindDonationsByCampaignIdHandler( $this->repository );
	}

	#[Test]
	public function handle_returns_campaign_donation_list(): void {

		$campaign_id = EntityId::create( 901 );
		$donations = [
			$this->make_pending_donation( 5_001, $campaign_id ),
			$this->make_pending_donation( 5_002, $campaign_id ),
		];

		$this->repository
			->shouldReceive( 'find_all_by_campaign_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( $donations );

		$result = $this->handler->handle( $campaign_id );

		$this->assertSame( $donations, $result );
	}

	#[Test]
	public function handle_wraps_repository_exception(): void {

		$campaign_id = EntityId::create( 901 );
		$e = new FakeDonationRepositoryException();

		$this->repository
			->shouldReceive( 'find_all_by_campaign_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andThrow( $e );

		try {
			$this->handler->handle( $campaign_id );
			$this->fail( 'Expected FindDonationsByCampaignIdException to be thrown.' );
		} catch ( FindDonationsByCampaignIdException $exception ) {
			$this->assertSame( $e, $exception->getPrevious() );
		}
	}

	private function make_pending_donation( int $id, EntityId $campaign_id ): Donation {

		$factory = new DonationFactory();

		return $factory->create_pending(
			id: EntityId::create( $id ),
			campaign_id: $campaign_id,
			money: Money::create( 1_000, 'RUB' ),
			created_at: new DateTimeImmutable( '2026-03-01T10:00:00+00:00' ),
		);
	}
}
