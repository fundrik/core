<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\UseCases\FindDonationDetailsById;

use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;
use Fundrik\Core\Components\Donations\Application\Ports\DonationDetailsRead\DonationDetailsReadPort;
use Fundrik\Core\Components\Donations\Application\ReadModels\DonationDetails;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationDetailsById\FindDonationDetailsByIdException;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationDetailsById\FindDonationDetailsByIdHandler;
use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Tests\Fixtures\FakeDonationDetailsReadException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( FindDonationDetailsByIdHandler::class )]
#[UsesClass( FindDonationDetailsByIdException::class )]
#[UsesClass( DonationApplicationException::class )]
#[UsesClass( FundrikApplicationException::class )]
#[UsesClass( DonationDetails::class )]
#[UsesClass( EntityId::class )]
final class FindDonationDetailsByIdHandlerTest extends MockeryTestCase {

	private DonationDetailsReadPort&MockInterface $donation_details_read;

	private FindDonationDetailsByIdHandler $handler;

	protected function setUp(): void {

		parent::setUp();

		$this->donation_details_read = Mockery::mock( DonationDetailsReadPort::class );
		$this->handler = new FindDonationDetailsByIdHandler( $this->donation_details_read );
	}

	#[Test]
	public function handle_returns_donation_details(): void {

		$details = $this->make_donation_details();
		$donation_id = EntityId::create( $details->get_id() );

		$this->donation_details_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation_id ) )
			->andReturn( $details );

		$result = $this->handler->handle( $donation_id );

		$this->assertSame( $details, $result );
	}

	#[Test]
	public function handle_returns_null_when_not_found(): void {

		$donation_id = EntityId::create( 5_001 );

		$this->donation_details_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation_id ) )
			->andReturnNull();

		$result = $this->handler->handle( $donation_id );

		$this->assertNull( $result );
	}

	#[Test]
	public function handle_wraps_repository_exception(): void {

		$donation_id = EntityId::create( 5_001 );
		$e = new FakeDonationDetailsReadException();

		$this->donation_details_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation_id ) )
			->andThrow( $e );

		try {
			$this->handler->handle( $donation_id );
			$this->fail( 'Expected FindDonationDetailsByIdException to be thrown.' );
		} catch ( FindDonationDetailsByIdException $exception ) {
			$this->assertSame( $e, $exception->getPrevious() );
			$this->assertSame( 'Failed to retrieve donation "5001".', $exception->getMessage() );
		}
	}
}
