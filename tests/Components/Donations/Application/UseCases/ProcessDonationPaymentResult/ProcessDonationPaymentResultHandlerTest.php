<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\UseCases\ProcessDonationPaymentResult;

use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;
use Fundrik\Core\Components\Donations\Application\Events\DonationRejectedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationRefundedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationSucceededEvent;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRead\DonationReadPort;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\ReadModels\Donation;
use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutationException;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\DonationPaymentResult;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\DonationPaymentResultType;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResultException;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResultHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResultPolicy;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResult;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResultStatus;
use Fundrik\Core\Components\Donations\Application\UseCases\RejectDonation\RejectDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadDonationById\ReadDonationByIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\SucceedDonation\SucceedDonationHandler;
use Fundrik\Core\Components\Donations\Domain\Donation as DonationEntity;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Tests\Fixtures\FakeDonationReadException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
#[CoversClass( ProcessDonationPaymentResultHandler::class )]
#[UsesClass( DonationPaymentResult::class )]
#[UsesClass( DonationPaymentResultType::class )]
#[UsesClass( ProcessDonationPaymentResult::class )]
#[UsesClass( ProcessDonationPaymentResultPolicy::class )]
#[UsesClass( ProcessDonationPaymentResultStatus::class )]
#[UsesClass( DonationApplicationException::class )]
#[UsesClass( DonationMutationException::class )]
#[UsesClass( DonationReadPort::class )]
#[UsesClass( DonationRepositoryPort::class )]
#[UsesClass( Donation::class )]
#[UsesClass( DonationEntity::class )]
#[UsesClass( DonationRejectedEvent::class )]
#[UsesClass( DonationRefundedEvent::class )]
#[UsesClass( DonationSucceededEvent::class )]
#[UsesClass( DonationStatus::class )]
#[UsesClass( FundrikApplicationException::class )]
#[UsesClass( ReadDonationByIdHandler::class )]
#[UsesClass( RejectDonationHandler::class )]
#[UsesClass( RefundDonationHandler::class )]
#[UsesClass( SucceedDonationHandler::class )]
#[UsesClass( UseCaseFailureStage::class )]
#[UsesClass( EntityId::class )]
final class ProcessDonationPaymentResultHandlerTest extends MockeryTestCase {

	private DonationReadPort&MockInterface $donation_read;
	private DonationRepositoryPort&MockInterface $donation_repository;
	private ApplicationEventBusPort&MockInterface $event_bus;
	private ReadDonationByIdHandler $read_donation_by_id;
	private ProcessDonationPaymentResultPolicy $policy;
	private SucceedDonationHandler $succeed_donation;
	private RejectDonationHandler $reject_donation;
	private RefundDonationHandler $refund_donation;

	private ProcessDonationPaymentResultHandler $handler;

	protected function setUp(): void {

		parent::setUp();

		$this->donation_read = Mockery::mock( DonationReadPort::class );
		$this->donation_repository = Mockery::mock( DonationRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );
		$this->policy = new ProcessDonationPaymentResultPolicy();

		$this->succeed_donation = new SucceedDonationHandler( $this->donation_repository, $this->event_bus );
		$this->reject_donation = new RejectDonationHandler( $this->donation_repository, $this->event_bus );
		$this->refund_donation = new RefundDonationHandler( $this->donation_repository, $this->event_bus );
		$this->read_donation_by_id = new ReadDonationByIdHandler( $this->donation_read );

		$this->handler = new ProcessDonationPaymentResultHandler(
			$this->read_donation_by_id,
			$this->policy,
			$this->succeed_donation,
			$this->reject_donation,
			$this->refund_donation,
		);
	}

	#[Test]
	public function handle_applies_success_result_for_pending_donation(): void {

		$donation_id = EntityId::create( 5_001 );
		$this->donation_read
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual ): bool => $actual->equals( $donation_id ),
			)
			->andReturn( $this->make_donation_read_model( id: 5_001, status: 'pending' ) );

		$this->donation_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual ): bool => $actual->equals( $donation_id ),
			)
			->andReturn( $this->make_pending_donation( 5_001, 901 ) );

		$this->donation_repository
			->shouldReceive( 'update' )
			->once()
			->withArgs(
				static fn ( DonationEntity $donation ): bool => $donation->get_status() === DonationStatus::Succeeded,
			)
			->andReturnUsing( static fn ( DonationEntity $donation ): DonationEntity => $donation );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( DonationSucceededEvent::class, $donation_id ) );

		$result = $this->handler->handle( new DonationPaymentResult( $donation_id, DonationPaymentResultType::Succeeded ) );

		$this->assertSame( $donation_id, $result->get_donation_id() );
		$this->assertSame( ProcessDonationPaymentResultStatus::Applied, $result->get_status() );
	}

	#[Test]
	public function handle_replays_success_result_for_already_succeeded_donation(): void {

		$donation_id = EntityId::create( 5_001 );

		$this->donation_read
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( $this->make_donation_read_model( id: 5_001, status: 'succeeded' ) );

		$this->donation_repository->shouldNotReceive( 'find_by_id' );
		$this->donation_repository->shouldNotReceive( 'update' );
		$this->event_bus->shouldNotReceive( 'publish' );

		$result = $this->handler->handle( new DonationPaymentResult( $donation_id, DonationPaymentResultType::Succeeded ) );

		$this->assertSame( $donation_id, $result->get_donation_id() );
		$this->assertSame( ProcessDonationPaymentResultStatus::Replayed, $result->get_status() );
	}

	#[Test]
	public function handle_ignores_stale_success_result_for_refunded_donation(): void {

		$donation_id = EntityId::create( 5_001 );

		$this->donation_read
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( $this->make_donation_read_model( id: 5_001, status: 'refunded' ) );

		$this->donation_repository->shouldNotReceive( 'find_by_id' );
		$this->donation_repository->shouldNotReceive( 'update' );
		$this->event_bus->shouldNotReceive( 'publish' );

		$result = $this->handler->handle( new DonationPaymentResult( $donation_id, DonationPaymentResultType::Succeeded ) );

		$this->assertSame( $donation_id, $result->get_donation_id() );
		$this->assertSame( ProcessDonationPaymentResultStatus::Ignored, $result->get_status() );
	}

	#[Test]
	public function handle_wraps_donation_lookup_failure(): void {

		$donation_id = EntityId::create( 5_001 );

		$this->donation_read
			->shouldReceive( 'find_by_id' )
			->once()
			->andThrow( new FakeDonationReadException() );

		try {
			$this->handler->handle( new DonationPaymentResult( $donation_id, DonationPaymentResultType::Succeeded ) );
			$this->fail( 'Expected ProcessDonationPaymentResultException to be thrown.' );
		} catch ( ProcessDonationPaymentResultException $exception ) {
			$this->assertSame( UseCaseFailureStage::Persistence, $exception->get_stage() );
			$this->assertSame( 'Failed to retrieve donation "5001".', $exception->getMessage() );
		}
	}

	private function event_of_type( string $event_class, EntityId $donation_id ): callable {

		return function ( object $event ) use ( $event_class, $donation_id ): bool {

			$this->assertInstanceOf( $event_class, $event );
			$this->assertTrue( $event->get_donation_id()->equals( $donation_id ) );

			return true;
		};
	}
}
