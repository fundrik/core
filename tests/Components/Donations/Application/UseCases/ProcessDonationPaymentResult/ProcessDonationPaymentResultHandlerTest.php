<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\UseCases\ProcessDonationPaymentResult;

use Fundrik\Core\Components\Donations\Application\Events\DonationRefundedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationRejectedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationSucceededEvent;
use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutation;
use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutationException;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\DonationPaymentResult;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\DonationPaymentResultType;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResult;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResultException;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResultHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResultPolicy;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResultPreconditionReason;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResultStatus;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RejectDonation\RejectDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\SucceedDonation\SucceedDonationHandler;
use Fundrik\Core\Components\Donations\Domain\Donation as DonationEntity;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Donations\Domain\PaymentId;
use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( ProcessDonationPaymentResultHandler::class )]
#[CoversClass( DonationPaymentResult::class )]
#[CoversClass( ProcessDonationPaymentResult::class )]
#[CoversClass( ProcessDonationPaymentResultException::class )]
#[CoversClass( DonationPaymentResultType::class )]
#[CoversClass( ProcessDonationPaymentResultStatus::class )]
#[UsesClass( DonationApplicationException::class )]
#[UsesClass( DonationMutationException::class )]
#[UsesClass( DonationMutation::class )]
#[UsesClass( DonationEntity::class )]
#[UsesClass( DonationFactory::class )]
#[UsesClass( DonationRejectedEvent::class )]
#[UsesClass( DonationRefundedEvent::class )]
#[UsesClass( DonationSucceededEvent::class )]
#[UsesClass( DonationStatus::class )]
#[UsesClass( FundrikApplicationException::class )]
#[UsesClass( FindDonationByIdHandler::class )]
#[UsesClass( ProcessDonationPaymentResultPolicy::class )]
#[UsesClass( RejectDonationHandler::class )]
#[UsesClass( RefundDonationHandler::class )]
#[UsesClass( SucceedDonationHandler::class )]
#[UsesClass( UseCaseFailureStage::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Amount::class )]
#[UsesClass( Currency::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( Money::class )]
#[UsesClass( PaymentId::class )]
final class ProcessDonationPaymentResultHandlerTest extends MockeryTestCase {

	private DonationRepositoryPort&MockInterface $donation_repository;
	private ApplicationEventBusPort&MockInterface $event_bus;
	private FindDonationByIdHandler $find_donation_by_id;
	private ProcessDonationPaymentResultPolicy $policy;
	private SucceedDonationHandler $succeed_donation;
	private RejectDonationHandler $reject_donation;
	private RefundDonationHandler $refund_donation;

	private ProcessDonationPaymentResultHandler $handler;

	protected function setUp(): void {

		parent::setUp();

		$this->donation_repository = Mockery::mock( DonationRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );
		$this->policy = new ProcessDonationPaymentResultPolicy();
		$this->succeed_donation = new SucceedDonationHandler( $this->donation_repository, $this->event_bus );
		$this->reject_donation = new RejectDonationHandler( $this->donation_repository, $this->event_bus );
		$this->refund_donation = new RefundDonationHandler( $this->donation_repository, $this->event_bus );
		$this->find_donation_by_id = new FindDonationByIdHandler( $this->donation_repository );

		$this->handler = new ProcessDonationPaymentResultHandler(
			$this->find_donation_by_id,
			$this->policy,
			$this->succeed_donation,
			$this->reject_donation,
			$this->refund_donation,
		);
	}

	#[Test]
	public function handle_applies_success_result_for_pending_donation(): void {

		$donation_id = EntityId::create( 5_001 );
		$donation = $this->make_pending_donation( 5_001, 901, payment_id: 'pay_5001' );

		$this->donation_repository
			->shouldReceive( 'find_by_id' )
			->twice()
			->withArgs(
				static fn ( EntityId $actual ): bool => $actual->equals( $donation_id ),
			)
			->andReturn( $donation );

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

		$result = $this->handler->handle(
			new DonationPaymentResult(
				$donation_id,
				PaymentId::create( 'pay_5001' ),
				DonationPaymentResultType::Succeeded,
			),
		);

		$this->assertSame( $donation_id, $result->get_donation_id() );
		$this->assertSame( 'pay_5001', $result->get_payment_id()->get_value() );
		$this->assertSame( DonationPaymentResultType::Succeeded, $result->get_result_type() );
		$this->assertSame( ProcessDonationPaymentResultStatus::Applied, $result->get_status() );
	}

	#[Test]
	#[DataProvider( 'applied_result_provider' )]
	public function handle_applies_rejected_and_refunded_results(
		DonationPaymentResultType $result_type,
		DonationStatus $current_status,
		DonationStatus $expected_status,
		string $event_class,
	): void {

		$donation_id = EntityId::create( 5_001 );
		$donation = $current_status === DonationStatus::Succeeded
			? $this->make_succeeded_donation( 5_001, 901, payment_id: 'pay_5001' )
			: $this->make_pending_donation( 5_001, 901, payment_id: 'pay_5001' );

		$this->donation_repository
			->shouldReceive( 'find_by_id' )
			->twice()
			->andReturn( $donation );

		$this->donation_repository
			->shouldReceive( 'update' )
			->once()
			->withArgs( static fn ( DonationEntity $updated ): bool => $updated->get_status() === $expected_status )
			->andReturnUsing( static fn ( DonationEntity $updated ): DonationEntity => $updated );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( $event_class, $donation_id ) );

		$result = $this->handler->handle(
			new DonationPaymentResult( $donation_id, PaymentId::create( 'pay_5001' ), $result_type ),
		);

		$this->assertSame( $donation_id, $result->get_donation_id() );
		$this->assertSame( ProcessDonationPaymentResultStatus::Applied, $result->get_status() );
	}

	#[Test]
	public function handle_replays_success_result_for_already_succeeded_donation(): void {

		$donation_id = EntityId::create( 5_001 );

		$this->donation_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( $this->make_succeeded_donation( 5_001, 901, payment_id: 'pay_5001' ) );

		$this->donation_repository->shouldNotReceive( 'update' );
		$this->event_bus->shouldNotReceive( 'publish' );

		$result = $this->handler->handle(
			new DonationPaymentResult(
				$donation_id,
				PaymentId::create( 'pay_5001' ),
				DonationPaymentResultType::Succeeded,
			),
		);

		$this->assertSame( $donation_id, $result->get_donation_id() );
		$this->assertSame( ProcessDonationPaymentResultStatus::Replayed, $result->get_status() );
	}

	#[Test]
	public function handle_ignores_stale_success_result_for_refunded_donation(): void {

		$donation_id = EntityId::create( 5_001 );

		$this->donation_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn(
				$this->make_succeeded_donation( 5_001, 901, payment_id: 'pay_5001' )->refund(),
			);

		$this->donation_repository->shouldNotReceive( 'update' );
		$this->event_bus->shouldNotReceive( 'publish' );

		$result = $this->handler->handle(
			new DonationPaymentResult(
				$donation_id,
				PaymentId::create( 'pay_5001' ),
				DonationPaymentResultType::Succeeded,
			),
		);

		$this->assertSame( $donation_id, $result->get_donation_id() );
		$this->assertSame( ProcessDonationPaymentResultStatus::Ignored, $result->get_status() );
	}

	#[Test]
	public function handle_throws_when_donation_is_missing(): void {

		$donation_id = EntityId::create( 5_001 );

		$this->donation_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturnNull();

		try {
			$this->handler->handle(
				new DonationPaymentResult(
					$donation_id,
					PaymentId::create( 'pay_5001' ),
					DonationPaymentResultType::Succeeded,
				),
			);
			$this->fail( 'Expected ProcessDonationPaymentResultException to be thrown.' );
		} catch ( ProcessDonationPaymentResultException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame( 0, $exception->getCode() );
			$this->assertSame(
				'Cannot process payment result for donation "5001": donation does not exist.',
				$exception->getMessage(),
			);
			$this->assertSame(
				ProcessDonationPaymentResultPreconditionReason::DonationNotFound,
				$exception->get_reason(),
			);
		}
	}

	#[Test]
	public function handle_throws_when_payment_is_not_attached(): void {

		$donation_id = EntityId::create( 5_001 );

		$this->donation_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( $this->make_created_donation( 5_001, 901 ) );

		try {
			$this->handler->handle(
				new DonationPaymentResult(
					$donation_id,
					PaymentId::create( 'pay_5001' ),
					DonationPaymentResultType::Succeeded,
				),
			);
			$this->fail( 'Expected ProcessDonationPaymentResultException to be thrown.' );
		} catch ( ProcessDonationPaymentResultException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame(
				'Cannot process payment result for donation "5001": payment is not attached.',
				$exception->getMessage(),
			);
			$this->assertSame(
				ProcessDonationPaymentResultPreconditionReason::PaymentNotAttached,
				$exception->get_reason(),
			);
		}
	}

	#[Test]
	public function handle_throws_when_payment_id_does_not_match(): void {

		$donation_id = EntityId::create( 5_001 );

		$this->donation_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( $this->make_pending_donation( 5_001, 901, payment_id: 'pay_5001' ) );

		try {
			$this->handler->handle(
				new DonationPaymentResult(
					$donation_id,
					PaymentId::create( 'pay_other' ),
					DonationPaymentResultType::Succeeded,
				),
			);
			$this->fail( 'Expected ProcessDonationPaymentResultException to be thrown.' );
		} catch ( ProcessDonationPaymentResultException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame(
				'Cannot process payment result for donation "5001": payment ID does not match.',
				$exception->getMessage(),
			);
			$this->assertSame(
				ProcessDonationPaymentResultPreconditionReason::PaymentIdMismatch,
				$exception->get_reason(),
			);
		}
	}

	#[Test]
	public function handle_wraps_donation_mutation_failure(): void {

		$donation_id = EntityId::create( 5_001 );
		$repository_exception = new FakeDonationRepositoryException();

		$this->donation_repository
			->shouldReceive( 'find_by_id' )
			->twice()
			->andReturn( $this->make_pending_donation( 5_001, 901, payment_id: 'pay_5001' ) );
		$this->donation_repository
			->shouldReceive( 'update' )
			->once()
			->andThrow( $repository_exception );
		$this->event_bus->shouldNotReceive( 'publish' );

		try {
			$this->handler->handle(
				new DonationPaymentResult(
					$donation_id,
					PaymentId::create( 'pay_5001' ),
					DonationPaymentResultType::Succeeded,
				),
			);
			$this->fail( 'Expected ProcessDonationPaymentResultException to be thrown.' );
		} catch ( ProcessDonationPaymentResultException $exception ) {
			$this->assertSame( UseCaseFailureStage::Persistence, $exception->get_stage() );
			$this->assertSame( 'Failed to apply payment result to donation "5001".', $exception->getMessage() );
			$this->assertInstanceOf( DonationMutationException::class, $exception->getPrevious() );
			$this->assertSame( $repository_exception, $exception->getPrevious()->getPrevious() );
		}
	}

	#[Test]
	public function handle_wraps_donation_lookup_failure(): void {

		$donation_id = EntityId::create( 5_001 );

		$this->donation_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->andThrow( new FakeDonationRepositoryException() );

		try {
			$this->handler->handle(
				new DonationPaymentResult(
					$donation_id,
					PaymentId::create( 'pay_5001' ),
					DonationPaymentResultType::Succeeded,
				),
			);
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

	public static function applied_result_provider(): array {

		return [
			'rejected result' => [ DonationPaymentResultType::Rejected, DonationStatus::Pending, DonationStatus::Rejected, DonationRejectedEvent::class ],
			'refunded result' => [ DonationPaymentResultType::Refunded, DonationStatus::Succeeded, DonationStatus::Refunded, DonationRefundedEvent::class ],
		];
	}
}
