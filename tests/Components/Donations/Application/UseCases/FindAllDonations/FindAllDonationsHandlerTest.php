<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\UseCases\FindAllDonations;

use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\UseCases\FindAllDonations\FindAllDonationsException;
use Fundrik\Core\Components\Donations\Application\UseCases\FindAllDonations\FindAllDonationsHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationException;
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

#[CoversClass( FindAllDonationsHandler::class )]
#[UsesClass( FindAllDonationsException::class )]
#[UsesClass( DonationApplicationException::class )]
#[UsesClass( FundrikApplicationException::class )]
#[UsesClass( Donation::class )]
#[UsesClass( DonationFactory::class )]
#[UsesClass( DonationStatus::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Money::class )]
final class FindAllDonationsHandlerTest extends MockeryTestCase {

	private DonationRepositoryPort&MockInterface $repository;

	private FindAllDonationsHandler $handler;

	protected function setUp(): void {

		parent::setUp();

		$this->repository = Mockery::mock( DonationRepositoryPort::class );
		$this->handler = new FindAllDonationsHandler( $this->repository );
	}

	#[Test]
	public function handle_returns_donation_list(): void {

		$donations = [ $this->make_pending_donation(), $this->make_pending_donation( 5_002 ) ];

		$this->repository
			->shouldReceive( 'find_all' )
			->once()
			->andReturn( $donations );

		$result = $this->handler->handle();

		$this->assertSame( $donations, $result );
	}

	#[Test]
	public function handle_wraps_repository_exception(): void {

		$e = new FakeDonationRepositoryException();

		$this->repository
			->shouldReceive( 'find_all' )
			->once()
			->andThrow( $e );

		try {
			$this->handler->handle();
			$this->fail( 'Expected FindAllDonationsException to be thrown.' );
		} catch ( FindAllDonationsException $exception ) {
			$this->assertSame( $e, $exception->getPrevious() );
		}
	}
}
