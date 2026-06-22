<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\UseCases\ReadPaginatedDonations;

use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRead\DonationReadPort;
use Fundrik\Core\Components\Donations\Application\ReadModels\Donation;
use Fundrik\Core\Components\Donations\Application\ReadModels\PaginatedDonations;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadPaginatedDonations\ReadPaginatedDonationsException;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadPaginatedDonations\ReadPaginatedDonationsHandler;
use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationException;
use Fundrik\Core\Tests\Fixtures\FakeDonationReadException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( ReadPaginatedDonationsHandler::class )]
#[UsesClass( ReadPaginatedDonationsException::class )]
#[UsesClass( DonationApplicationException::class )]
#[UsesClass( FundrikApplicationException::class )]
#[UsesClass( Donation::class )]
#[UsesClass( PaginatedDonations::class )]
final class ReadPaginatedDonationsHandlerTest extends MockeryTestCase {

	private DonationReadPort&MockInterface $donation_read;

	private ReadPaginatedDonationsHandler $handler;

	protected function setUp(): void {

		parent::setUp();

		$this->donation_read = Mockery::mock( DonationReadPort::class );
		$this->handler = new ReadPaginatedDonationsHandler( $this->donation_read );
	}

	#[Test]
	public function handle_returns_donation_page(): void {

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

		$result = $this->handler->handle( 2, 25 );

		$this->assertSame( $page, $result );
	}

	#[Test]
	public function handle_throws_when_page_is_invalid(): void {

		$this->donation_read
			->shouldNotReceive( 'paginate' );

		try {
			$this->handler->handle( 0, 25 );
			$this->fail( 'Expected ReadPaginatedDonationsException to be thrown.' );
		} catch ( ReadPaginatedDonationsException $exception ) {
			$this->assertSame( 'Page must be a positive integer. Given: 0.', $exception->getMessage() );
		}
	}

	#[Test]
	public function handle_throws_when_per_page_is_invalid(): void {

		$this->donation_read
			->shouldNotReceive( 'paginate' );

		try {
			$this->handler->handle( 1, 0 );
			$this->fail( 'Expected ReadPaginatedDonationsException to be thrown.' );
		} catch ( ReadPaginatedDonationsException $exception ) {
			$this->assertSame( 'Items per page must be a positive integer. Given: 0.', $exception->getMessage() );
		}
	}

	#[Test]
	public function handle_wraps_repository_exception(): void {

		$e = new FakeDonationReadException();

		$this->donation_read
			->shouldReceive( 'paginate' )
			->once()
			->with( 1, 25 )
			->andThrow( $e );

		try {
			$this->handler->handle( 1, 25 );
			$this->fail( 'Expected ReadPaginatedDonationsException to be thrown.' );
		} catch ( ReadPaginatedDonationsException $exception ) {
			$this->assertSame( $e, $exception->getPrevious() );
			$this->assertSame( 'Failed to retrieve paginated donations.', $exception->getMessage() );
		}
	}
}
