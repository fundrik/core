<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\UseCases\FindDonationByPaymentId;

use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationByPaymentId\FindDonationByPaymentIdException;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationByPaymentId\FindDonationByPaymentIdHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
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

#[CoversClass( FindDonationByPaymentIdHandler::class )]
#[CoversClass( FindDonationByPaymentIdException::class )]
#[UsesClass( DonationApplicationException::class )]
#[UsesClass( FundrikApplicationException::class )]
#[UsesClass( Donation::class )]
#[UsesClass( DonationStatus::class )]
#[UsesClass( PaymentId::class )]
#[UsesClass( Amount::class )]
#[UsesClass( Currency::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( Money::class )]
final class FindDonationByPaymentIdHandlerTest extends MockeryTestCase {

	private DonationRepositoryPort&MockInterface $donations;

	private FindDonationByPaymentIdHandler $handler;

	protected function setUp(): void {

		parent::setUp();

		$this->donations = Mockery::mock( DonationRepositoryPort::class );
		$this->handler = new FindDonationByPaymentIdHandler( $this->donations );
	}

	#[Test]
	public function handle_returns_donation_for_matching_payment_id(): void {

		$donation = $this->make_pending_donation();
		$payment_id = $donation->get_payment_id();
		$this->assertNotNull( $payment_id );

		$this->donations
			->shouldReceive( 'find_by_payment_id' )
			->once()
			->with( $this->identicalTo( $payment_id ) )
			->andReturn( $donation );

		$this->assertSame( $donation, $this->handler->handle( $payment_id ) );
	}

	#[Test]
	public function handle_returns_null_when_payment_is_not_found(): void {

		$payment_id = PaymentId::create( 'pay_missing' );

		$this->donations
			->shouldReceive( 'find_by_payment_id' )
			->once()
			->with( $this->identicalTo( $payment_id ) )
			->andReturnNull();

		$this->assertNull( $this->handler->handle( $payment_id ) );
	}

	#[Test]
	public function handle_wraps_repository_failure(): void {

		$payment_id = PaymentId::create( 'pay_5001' );
		$repository_exception = new FakeDonationRepositoryException();

		$this->donations
			->shouldReceive( 'find_by_payment_id' )
			->once()
			->with( $this->identicalTo( $payment_id ) )
			->andThrow( $repository_exception );

		try {
			$this->handler->handle( $payment_id );
			$this->fail( 'Expected FindDonationByPaymentIdException to be thrown.' );
		} catch ( FindDonationByPaymentIdException $exception ) {
			$this->assertSame( 'Failed to retrieve donation for payment "pay_5001".', $exception->getMessage() );
			$this->assertSame( $repository_exception, $exception->getPrevious() );
		}
	}
}
