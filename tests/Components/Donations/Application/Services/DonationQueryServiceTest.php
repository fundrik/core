<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\Services;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\ReadModels\DonationDetails;
use Fundrik\Core\Components\Donations\Application\ReadModels\DonationDetailsMapper;
use Fundrik\Core\Components\Donations\Application\Services\DonationQueryService;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdException;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdHandler;
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
#[UsesClass( DonationDetails::class )]
#[UsesClass( DonationDetailsMapper::class )]
#[UsesClass( FindDonationByIdHandler::class )]
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
		$this->query = new DonationQueryService(
			new FindDonationByIdHandler( $this->donation_repository ),
			new DonationDetailsMapper(),
		);
	}

	#[Test]
	public function find_by_id_uses_injected_donation_repository(): void {

		$donation = $this->make_pending_donation( 5_001, 901 );
		$donation_id = $donation->get_id();

		$this->donation_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_donation_id ): bool => $actual_donation_id->equals( $donation_id ),
			)
			->andReturn( $donation );

		$result = $this->query->find_by_id( $donation_id->get_value() );

		$this->assertInstanceOf( DonationDetails::class, $result );
		$this->assertSame( $donation_id->get_value(), $result->get_id() );
		$this->assertSame( $donation->get_campaign_id()->get_value(), $result->get_campaign_id() );
		$this->assertSame( $donation->get_money()->get_amount()->get_value(), $result->get_amount() );
		$this->assertSame( $donation->get_money()->get_currency()->get_code(), $result->get_currency_code() );
		$this->assertSame( $donation->get_status()->value, $result->get_status() );
	}

	#[Test]
	public function find_by_id_throws_when_donation_id_is_invalid(): void {

		$this->expectException( FindDonationByIdException::class );
		$this->expectExceptionMessage( 'ID must be a positive integer or a valid UUID. Given: -1.' );

		$this->query->find_by_id( -1 );
	}
}
