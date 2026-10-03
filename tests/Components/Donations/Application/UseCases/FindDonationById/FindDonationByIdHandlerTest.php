<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\UseCases\FindDonationById;

use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdException;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Donations\Domain\PaymentId;
use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationException;
use Fundrik\Core\Components\Shared\Domain\Amount;
use Fundrik\Core\Components\Shared\Domain\Currency;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Tests\Fixtures\FakeDonationRepositoryException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( FindDonationByIdHandler::class )]
#[CoversClass( FindDonationByIdException::class )]
#[UsesClass( DonationApplicationException::class )]
#[UsesClass( FundrikApplicationException::class )]
#[UsesClass( Donation::class )]
#[UsesClass( DonationFactory::class )]
#[UsesClass( DonationStatus::class )]
#[UsesClass( PaymentId::class )]
#[UsesClass( Amount::class )]
#[UsesClass( Currency::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( Money::class )]
final class FindDonationByIdHandlerTest extends MockeryTestCase {

	private DonationRepositoryPort&MockInterface $donations;

	private FindDonationByIdHandler $handler;

	protected function setUp(): void {

		parent::setUp();

		$this->donations = Mockery::mock( DonationRepositoryPort::class );
		$this->handler = new FindDonationByIdHandler( $this->donations );
	}

	#[Test]
	public function handle_returns_donation_entity(): void {

		$donation = $this->make_pending_donation();
		$donation_id = $donation->get_id();

		$this->donations
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

		$this->donations
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
		$e = new FakeDonationRepositoryException();

		$this->donations
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation_id ) )
			->andThrow( $e );

		try {
			$this->handler->handle( $donation_id );
			$this->fail( 'Expected FindDonationByIdException to be thrown.' );
		} catch ( FindDonationByIdException $exception ) {
			$this->assertSame( $e, $exception->getPrevious() );
			$this->assertSame( 'Failed to retrieve donation "5001".', $exception->getMessage() );
		}
	}
}
