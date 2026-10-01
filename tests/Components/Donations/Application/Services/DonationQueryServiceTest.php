<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\Services;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRead\DonationReadPort;
use Fundrik\Core\Components\Donations\Application\ReadModels\Donation;
use Fundrik\Core\Components\Donations\Application\ReadModels\PaginatedDonations;
use Fundrik\Core\Components\Donations\Application\Services\DonationQueryService;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadDonationById\ReadDonationByIdException;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadDonationById\ReadDonationByIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadPaginatedDonations\ReadPaginatedDonationsException;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadPaginatedDonations\ReadPaginatedDonationsHandler;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( DonationQueryService::class )]
#[UsesClass( Donation::class )]
#[UsesClass( PaginatedDonations::class )]
#[UsesClass( ReadDonationByIdHandler::class )]
#[UsesClass( ReadPaginatedDonationsHandler::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( UtcDateTime::class )]
final class DonationQueryServiceTest extends MockeryTestCase {

	private DonationReadPort&MockInterface $donation_read;

	private DonationQueryService $query;

	protected function setUp(): void {

		parent::setUp();

		$this->donation_read = Mockery::mock( DonationReadPort::class );
		$this->query = new DonationQueryService(
			new ReadDonationByIdHandler( $this->donation_read ),
			new ReadPaginatedDonationsHandler( $this->donation_read ),
		);
	}

	#[Test]
	public function find_by_id_uses_injected_donation_read_port(): void {

		$donation = $this->make_donation_read_model(
			id: 5_001,
			campaign_id: 901,
			status: 'pending',
			updated_at: $this->make_utc_date_time( '2026-03-02T10:00:00+00:00' ),
		);
		$donation_id = EntityId::create( $donation->get_id() );

		$this->donation_read
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_donation_id ): bool => $actual_donation_id->equals( $donation_id ),
			)
			->andReturn( $donation );

		$result = $this->query->find_by_id( $donation_id->get_value() );

		$this->assertSame( $donation, $result );
	}

	#[Test]
	public function find_by_id_accepts_entity_id(): void {

		$donation = $this->make_donation_read_model();
		$donation_id = EntityId::create( $donation->get_id() );

		$this->donation_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation_id ) )
			->andReturn( $donation );

		$this->assertSame( $donation, $this->query->find_by_id( $donation_id ) );
	}

	#[Test]
	public function find_by_id_returns_null_when_donation_is_missing(): void {

		$this->donation_read
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturnNull();

		$this->assertNull( $this->query->find_by_id( 5_001 ) );
	}

	#[Test]
	public function find_by_id_throws_when_donation_id_is_invalid(): void {

		$this->donation_read->shouldNotReceive( 'find_by_id' );

		try {
			$this->query->find_by_id( -1 );
			$this->fail( 'Expected ReadDonationByIdException to be thrown.' );
		} catch ( ReadDonationByIdException $exception ) {
			$this->assertSame( 'ID must be a positive integer or a valid UUID. Given: "-1".', $exception->getMessage() );
			$this->assertInstanceOf( InvalidEntityIdException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function paginate_uses_injected_donation_read_port(): void {

		$donation1 = $this->make_donation_read_model( id: 1 );
		$donation2 = $this->make_donation_read_model( id: 2 );
		$page = new PaginatedDonations(
			items: [ $donation1, $donation2 ],
			page: 2,
			per_page: 25,
			total: 51,
		);

		$this->donation_read
			->shouldReceive( 'paginate' )
			->once()
			->with( 2, 25 )
			->andReturn( $page );

		$result = $this->query->paginate( 2, 25 );

		$this->assertSame( $page, $result );
	}

	#[Test]
	public function paginate_throws_when_page_is_invalid(): void {

		$this->expectException( ReadPaginatedDonationsException::class );
		$this->expectExceptionMessage( 'Page must be a positive integer. Given: 0.' );

		$this->query->paginate( 0, 25 );
	}
}
