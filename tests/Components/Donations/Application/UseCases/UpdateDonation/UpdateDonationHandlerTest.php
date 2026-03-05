<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\UseCases\UpdateDonation;

use DateTimeImmutable;
use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\UseCases\UpdateDonation\UpdateDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\UpdateDonation\UpdateDonationHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
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

#[CoversClass( UpdateDonationHandler::class )]
#[UsesClass( UpdateDonationException::class )]
#[UsesClass( UseCaseFailureStage::class )]
#[UsesClass( DonationApplicationException::class )]
#[UsesClass( FundrikApplicationException::class )]
#[UsesClass( Donation::class )]
#[UsesClass( DonationFactory::class )]
#[UsesClass( DonationStatus::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Money::class )]
final class UpdateDonationHandlerTest extends MockeryTestCase {

	private DonationRepositoryPort&MockInterface $repository;

	private UpdateDonationHandler $handler;

	protected function setUp(): void {

		parent::setUp();

		$this->repository = Mockery::mock( DonationRepositoryPort::class );
		$this->handler = new UpdateDonationHandler( $this->repository );
	}

	#[Test]
	public function handle_updates_donation(): void {

		$donation = $this->make_captured_donation();

		$this->repository
			->shouldReceive( 'update' )
			->once()
			->with( $this->identicalTo( $donation ) )
			->andReturn( $donation );

		$result = $this->handler->handle( $donation );

		$this->assertSame( $donation, $result );
	}

	#[Test]
	public function handle_wraps_repository_exception(): void {

		$donation = $this->make_captured_donation();
		$e = new FakeDonationRepositoryException();

		$this->repository
			->shouldReceive( 'update' )
			->once()
			->with( $this->identicalTo( $donation ) )
			->andThrow( $e );

		try {
			$this->handler->handle( $donation );
			$this->fail( 'Expected UpdateDonationException to be thrown.' );
		} catch ( UpdateDonationException $exception ) {
			$this->assertSame( UseCaseFailureStage::Persistence, $exception->get_stage() );
			$this->assertSame( $e, $exception->getPrevious() );
		}
	}

	private function make_captured_donation(): Donation {

		$factory = new DonationFactory();
		$created_at = new DateTimeImmutable( '2026-03-01T10:00:00+00:00' );
		$captured_at = new DateTimeImmutable( '2026-03-01T10:10:00+00:00' );

		return $factory->create(
			id: EntityId::create( 5_001 ),
			version: EntityVersion::initial(),
			campaign_id: EntityId::create( 901 ),
			money: Money::create( 1_000, 'RUB' ),
			status: DonationStatus::Captured,
			created_at: UtcDateTime::create( $created_at ),
			captured_at: UtcDateTime::create( $captured_at ),
			status_changed_at: UtcDateTime::create( $captured_at ),
		);
	}
}
