<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\Services;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\Services\DonationQueryService;
use Fundrik\Core\Components\Donations\Application\Services\DonationQueryServiceFactory;
use Fundrik\Core\Components\Donations\Application\UseCases\FindAllDonations\FindAllDonationsHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationsByCampaignId\FindDonationsByCampaignIdHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( DonationQueryService::class )]
#[UsesClass( DonationQueryServiceFactory::class )]
#[UsesClass( FindDonationByIdHandler::class )]
#[UsesClass( FindAllDonationsHandler::class )]
#[UsesClass( FindDonationsByCampaignIdHandler::class )]
#[UsesClass( Donation::class )]
#[UsesClass( DonationFactory::class )]
#[UsesClass( DonationStatus::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Money::class )]
final class DonationQueryServiceTest extends MockeryTestCase {

	private DonationRepositoryPort&MockInterface $donation_repository;

	private DonationQueryService $query;

	protected function setUp(): void {

		parent::setUp();

		$this->donation_repository = Mockery::mock( DonationRepositoryPort::class );
		$this->query = ( new DonationQueryServiceFactory( $this->donation_repository ) )->create();
	}

	#[Test]
	public function find_by_id_uses_injected_donation_repository(): void {

		$donation = $this->make_pending_donation( 5_001, 901 );
		$donation_id = $donation->get_id();

		$this->donation_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation_id ) )
			->andReturn( $donation );

		$result = $this->query->find_by_id( $donation_id );

		$this->assertSame( $donation, $result );
	}

	#[Test]
	public function find_all_uses_injected_donation_repository(): void {

		$donations = [
			$this->make_pending_donation( 5_001, 901 ),
			$this->make_pending_donation( 5_002, 902 ),
		];

		$this->donation_repository
			->shouldReceive( 'find_all' )
			->once()
			->andReturn( $donations );

		$result = $this->query->find_all();

		$this->assertSame( $donations, $result );
	}

	#[Test]
	public function find_by_campaign_id_uses_injected_donation_repository(): void {

		$campaign_id = EntityId::create( 901 );
		$donations = [
			$this->make_pending_donation( 5_001, 901 ),
			$this->make_pending_donation( 5_002, 901 ),
		];

		$this->donation_repository
			->shouldReceive( 'find_all_by_campaign_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( $donations );

		$result = $this->query->find_by_campaign_id( $campaign_id );

		$this->assertSame( $donations, $result );
	}
}
