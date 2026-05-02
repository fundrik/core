<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\UseCases\ReadDonationById;

use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRead\DonationReadPort;
use Fundrik\Core\Components\Donations\Application\ReadModels\Donation;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadDonationById\ReadDonationByIdException;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadDonationById\ReadDonationByIdHandler;
use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Tests\Fixtures\FakeDonationReadException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( ReadDonationByIdHandler::class )]
#[UsesClass( ReadDonationByIdException::class )]
#[UsesClass( DonationApplicationException::class )]
#[UsesClass( FundrikApplicationException::class )]
#[UsesClass( Donation::class )]
#[UsesClass( EntityId::class )]
final class ReadDonationByIdHandlerTest extends MockeryTestCase {

	private DonationReadPort&MockInterface $donation_read;

	private ReadDonationByIdHandler $handler;

	protected function setUp(): void {

		parent::setUp();

		$this->donation_read = Mockery::mock( DonationReadPort::class );
		$this->handler = new ReadDonationByIdHandler( $this->donation_read );
	}

	#[Test]
	public function handle_returns_donation(): void {

		$donation = $this->make_donation_read_model();
		$donation_id = EntityId::create( $donation->get_id() );

		$this->donation_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation_id ) )
			->andReturn( $donation );

		$result = $this->handler->handle( $donation_id );

		$this->assertSame( $donation, $result );
	}

	#[Test]
	public function handle_returns_null_when_not_found(): void {

		$donation_id = EntityId::create( 5_001 );

		$this->donation_read
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
		$e = new FakeDonationReadException();

		$this->donation_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation_id ) )
			->andThrow( $e );

		try {
			$this->handler->handle( $donation_id );
			$this->fail( 'Expected ReadDonationByIdException to be thrown.' );
		} catch ( ReadDonationByIdException $exception ) {
			$this->assertSame( $e, $exception->getPrevious() );
			$this->assertSame( 'Failed to retrieve donation "5001".', $exception->getMessage() );
		}
	}
}
