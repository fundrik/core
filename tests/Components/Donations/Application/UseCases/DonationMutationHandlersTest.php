<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\UseCases;

use Fundrik\Core\Components\Donations\Application\Events\DonationAuthorizedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationCanceledEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationCapturedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationFailedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationRefundedEvent;
use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\UseCases\AbstractDonationMutationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\AuthorizeDonation\AuthorizeDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\AuthorizeDonation\AuthorizeDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\AuthorizeDonation\AuthorizeDonationNotFoundException;
use Fundrik\Core\Components\Donations\Application\UseCases\CancelDonation\CancelDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\CancelDonation\CancelDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CancelDonation\CancelDonationNotFoundException;
use Fundrik\Core\Components\Donations\Application\UseCases\CaptureDonation\CaptureDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\CaptureDonation\CaptureDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CaptureDonation\CaptureDonationNotFoundException;
use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutation;
use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutationException;
use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutationPreconditionReason;
use Fundrik\Core\Components\Donations\Application\UseCases\FailDonation\FailDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\FailDonation\FailDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FailDonation\FailDonationNotFoundException;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationNotFoundException;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Tests\Fixtures\FakeApplicationEventBusException;
use Fundrik\Core\Tests\Fixtures\FakeDonationNotFoundException;
use Fundrik\Core\Tests\Fixtures\FakeDonationRepositoryException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( AbstractDonationMutationHandler::class )]
#[CoversClass( AuthorizeDonationHandler::class )]
#[CoversClass( CaptureDonationHandler::class )]
#[CoversClass( FailDonationHandler::class )]
#[CoversClass( RefundDonationHandler::class )]
#[CoversClass( CancelDonationHandler::class )]
#[UsesClass( DonationMutationException::class )]
#[UsesClass( AuthorizeDonationException::class )]
#[UsesClass( AuthorizeDonationNotFoundException::class )]
#[UsesClass( CaptureDonationException::class )]
#[UsesClass( CaptureDonationNotFoundException::class )]
#[UsesClass( FailDonationException::class )]
#[UsesClass( FailDonationNotFoundException::class )]
#[UsesClass( RefundDonationException::class )]
#[UsesClass( RefundDonationNotFoundException::class )]
#[UsesClass( CancelDonationException::class )]
#[UsesClass( CancelDonationNotFoundException::class )]
#[UsesClass( DonationMutationPreconditionReason::class )]
#[UsesClass( DonationMutation::class )]
#[UsesClass( DonationApplicationException::class )]
#[UsesClass( FundrikApplicationException::class )]
#[UsesClass( DonationAuthorizedEvent::class )]
#[UsesClass( DonationCapturedEvent::class )]
#[UsesClass( DonationFailedEvent::class )]
#[UsesClass( DonationRefundedEvent::class )]
#[UsesClass( DonationCanceledEvent::class )]
#[UsesClass( Donation::class )]
#[UsesClass( DonationFactory::class )]
#[UsesClass( DonationStatus::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Money::class )]
final class DonationMutationHandlersTest extends MockeryTestCase {

	private DonationRepositoryPort&MockInterface $donations;
	private ApplicationEventBusPort&MockInterface $event_bus;

	protected function setUp(): void {

		parent::setUp();

		$this->donations = Mockery::mock( DonationRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );
	}

	#[Test]
	#[DataProvider( 'successful_action_provider' )]
	public function handle_applies_donation_action_persists_result_and_publishes_event(
		string $action,
		string $event_class,
	): void {

		$donation_id = EntityId::create( 5_001 );
		$donation = $this->make_donation_for_action( $action );
		$handler = $this->make_handler( $action );

		$this->donations
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation_id ) )
			->andReturn( $donation );

		$this->donations
			->shouldReceive( 'update' )
			->once()
			->withArgs(
				function ( Donation $updated_donation ) use ( $action ): bool {

					$this->assert_donation_action_result( $action, $updated_donation );

					return true;
				},
			)
			->andReturnUsing( static fn ( Donation $updated_donation ): Donation => $updated_donation );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs(
				function ( object $event ) use ( $event_class, $donation_id ): bool {

					$this->assertInstanceOf( $event_class, $event );
					$this->assertTrue( $event->get_donation_id()->equals( $donation_id ) );

					return true;
				},
			);

		$result = $this->invoke_handler( $handler, $action, $donation_id );

		$this->assert_donation_action_result( $action, $result );
	}

	#[Test]
	public function handle_throws_when_donation_lookup_fails(): void {

		$donation_id = EntityId::create( 5_001 );
		$handler = $this->make_handler( 'authorize' );
		$e = new FakeDonationRepositoryException();

		$this->donations
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation_id ) )
			->andThrow( $e );

		$this->donations
			->shouldNotReceive( 'update' );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		try {
			$this->invoke_handler( $handler, 'authorize', $donation_id );
			$this->fail( 'Expected DonationMutationException to be thrown.' );
		} catch ( DonationMutationException $exception ) {
			$this->assertInstanceOf( AuthorizeDonationException::class, $exception );
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame( DonationMutationPreconditionReason::DonationLookupFailed, $exception->get_reason() );
			$this->assertSame( 'Failed to retrieve donation "5001".', $exception->getMessage() );
			$this->assertSame( $e, $exception->getPrevious() );
		}
	}

	#[Test]
	#[DataProvider( 'action_phrase_provider' )]
	public function handle_throws_when_donation_does_not_exist(
		string $action,
		string $phrase,
		string $exception_class,
	): void {

		$donation_id = EntityId::create( 5_001 );
		$handler = $this->make_handler( $action );

		$this->donations
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation_id ) )
			->andReturn( null );

		$this->donations
			->shouldNotReceive( 'update' );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		try {
			$this->invoke_handler( $handler, $action, $donation_id );
			$this->fail( 'Expected DonationMutationException to be thrown.' );
		} catch ( DonationMutationException $exception ) {
			$this->assertInstanceOf( $exception_class, $exception );
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame( DonationMutationPreconditionReason::DonationNotFound, $exception->get_reason() );
			$this->assertSame(
				sprintf( 'Cannot %s donation "5001": donation does not exist.', $phrase ),
				$exception->getMessage(),
			);
		}
	}

	#[Test]
	#[DataProvider( 'action_phrase_provider' )]
	public function handle_throws_when_donation_action_is_rejected(
		string $action,
		string $phrase,
		string $exception_class,
	): void {

		$donation_id = EntityId::create( 5_001 );
		$donation = $this->make_rejected_donation_for_action( $action );
		$handler = $this->make_handler( $action );

		$this->donations
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation_id ) )
			->andReturn( $donation );

		$this->donations
			->shouldNotReceive( 'update' );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		try {
			$this->invoke_handler( $handler, $action, $donation_id );
			$this->fail( 'Expected DonationMutationException to be thrown.' );
		} catch ( DonationMutationException $exception ) {
			$this->assertInstanceOf( $exception_class, $exception );
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame( DonationMutationPreconditionReason::DonationMutationRejected, $exception->get_reason() );
			$this->assertSame(
				sprintf( 'Cannot %s donation "5001": change was rejected.', $phrase ),
				$exception->getMessage(),
			);
			$this->assertNotNull( $exception->getPrevious() );
		}
	}

	#[Test]
	public function handle_wraps_donation_persistence_failure(): void {

		$donation_id = EntityId::create( 5_001 );
		$donation = $this->make_pending_donation();
		$handler = $this->make_handler( 'authorize' );
		$e = new FakeDonationRepositoryException();

		$this->donations
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation_id ) )
			->andReturn( $donation );

		$this->donations
			->shouldReceive( 'update' )
			->once()
			->withArgs(
				function ( Donation $updated_donation ): bool {

					$this->assertSame( DonationStatus::Authorized, $updated_donation->get_status() );

					return true;
				},
			)
			->andThrow( $e );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		try {
			$this->invoke_handler( $handler, 'authorize', $donation_id );
			$this->fail( 'Expected DonationMutationException to be thrown.' );
		} catch ( DonationMutationException $exception ) {
			$this->assertInstanceOf( AuthorizeDonationException::class, $exception );
			$this->assertSame( UseCaseFailureStage::Persistence, $exception->get_stage() );
			$this->assertNull( $exception->get_reason() );
			$this->assertSame( 'Failed to authorize donation "5001".', $exception->getMessage() );
			$this->assertSame( $e, $exception->getPrevious() );
		}
	}

	#[Test]
	#[DataProvider( 'persistence_not_found_provider' )]
	public function handle_throws_when_donation_disappears_before_persist(
		string $action,
		string $phrase,
		string $exception_class,
	): void {

		$donation_id = EntityId::create( 5_001 );
		$donation = $this->make_donation_for_action( $action );
		$handler = $this->make_handler( $action );
		$e = new FakeDonationNotFoundException();

		$this->donations
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation_id ) )
			->andReturn( $donation );

		$this->donations
			->shouldReceive( 'update' )
			->once()
			->withArgs(
				function ( Donation $updated_donation ) use ( $action ): bool {

					$this->assert_donation_action_result( $action, $updated_donation );

					return true;
				},
			)
			->andThrow( $e );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		try {
			$this->invoke_handler( $handler, $action, $donation_id );
			$this->fail( 'Expected DonationMutationException to be thrown.' );
		} catch ( DonationMutationException $exception ) {
			$this->assertInstanceOf( $exception_class, $exception );
			$this->assertSame( UseCaseFailureStage::Persistence, $exception->get_stage() );
			$this->assertNull( $exception->get_reason() );
			$this->assertSame(
				sprintf( 'Cannot %s donation "5001": donation does not exist.', $phrase ),
				$exception->getMessage(),
			);
			$this->assertSame( $e, $exception->getPrevious() );
		}
	}

	#[Test]
	#[DataProvider( 'event_publish_provider' )]
	public function handle_wraps_event_publish_failure(
		string $action,
		string $event_label,
		string $past_participle,
		string $exception_class,
	): void {

		$donation_id = EntityId::create( 5_001 );
		$donation = $this->make_donation_for_action( $action );
		$handler = $this->make_handler( $action );
		$e = new FakeApplicationEventBusException();

		$this->donations
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation_id ) )
			->andReturn( $donation );

		$this->donations
			->shouldReceive( 'update' )
			->once()
			->withArgs(
				function ( Donation $updated_donation ) use ( $action ): bool {

					$this->assert_donation_action_result( $action, $updated_donation );

					return true;
				},
			)
			->andReturnUsing( static fn ( Donation $updated_donation ): Donation => $updated_donation );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->andThrow( $e );

		try {
			$this->invoke_handler( $handler, $action, $donation_id );
			$this->fail( 'Expected DonationMutationException to be thrown.' );
		} catch ( DonationMutationException $exception ) {
			$this->assertInstanceOf( $exception_class, $exception );
			$this->assertSame( UseCaseFailureStage::EventPublish, $exception->get_stage() );
			$this->assertNull( $exception->get_reason() );
			$this->assertSame(
				sprintf(
					'Donation "5001" was %s, but publishing the %s event failed.',
					$past_participle,
					$event_label,
				),
				$exception->getMessage(),
			);
			$this->assertSame( $e, $exception->getPrevious() );
		}
	}

	public static function successful_action_provider(): array {

		return [
			'authorize' => [ 'authorize', DonationAuthorizedEvent::class ],
			'capture' => [ 'capture', DonationCapturedEvent::class ],
			'fail' => [ 'fail', DonationFailedEvent::class ],
			'refund' => [ 'refund', DonationRefundedEvent::class ],
			'cancel' => [ 'cancel', DonationCanceledEvent::class ],
		];
	}

	public static function action_phrase_provider(): array {

		return [
			'authorize' => [ 'authorize', 'authorize', AuthorizeDonationException::class ],
			'capture' => [ 'capture', 'capture', CaptureDonationException::class ],
			'fail' => [ 'fail', 'fail', FailDonationException::class ],
			'refund' => [ 'refund', 'refund', RefundDonationException::class ],
			'cancel' => [ 'cancel', 'cancel', CancelDonationException::class ],
		];
	}

	public static function event_publish_provider(): array {

		return [
			'authorize' => [ 'authorize', 'authorized', 'authorized', AuthorizeDonationException::class ],
			'capture' => [ 'capture', 'captured', 'captured', CaptureDonationException::class ],
			'fail' => [ 'fail', 'failed', 'failed', FailDonationException::class ],
			'refund' => [ 'refund', 'refunded', 'refunded', RefundDonationException::class ],
			'cancel' => [ 'cancel', 'canceled', 'canceled', CancelDonationException::class ],
		];
	}

	public static function persistence_not_found_provider(): array {

		return [
			'authorize' => [ 'authorize', 'authorize', AuthorizeDonationNotFoundException::class ],
			'capture' => [ 'capture', 'capture', CaptureDonationNotFoundException::class ],
			'fail' => [ 'fail', 'fail', FailDonationNotFoundException::class ],
			'refund' => [ 'refund', 'refund', RefundDonationNotFoundException::class ],
			'cancel' => [ 'cancel', 'cancel', CancelDonationNotFoundException::class ],
		];
	}

	private function make_handler( string $action ): object {

		return match ( $action ) {
			'authorize' => new AuthorizeDonationHandler( $this->donations, $this->event_bus ),
			'capture' => new CaptureDonationHandler( $this->donations, $this->event_bus ),
			'fail' => new FailDonationHandler( $this->donations, $this->event_bus ),
			'refund' => new RefundDonationHandler( $this->donations, $this->event_bus ),
			'cancel' => new CancelDonationHandler( $this->donations, $this->event_bus ),
		};
	}

	private function make_donation_for_action( string $action ): Donation {

		return match ( $action ) {
			'authorize', 'capture', 'fail', 'cancel' => $this->make_pending_donation(),
			'refund' => $this->make_captured_donation(),
		};
	}

	private function make_rejected_donation_for_action( string $action ): Donation {

		return match ( $action ) {
			'authorize' => $this->make_pending_donation()->authorize(),
			'capture' => $this->make_pending_donation()->fail(),
			'fail' => $this->make_captured_donation(),
			'refund' => $this->make_pending_donation(),
			'cancel' => $this->make_captured_donation(),
		};
	}

	private function invoke_handler( object $handler, string $action, EntityId $donation_id ): Donation {

		return match ( $action ) {
			'authorize', 'capture', 'fail', 'refund', 'cancel' => $handler->handle( $donation_id ),
		};
	}

	private function assert_donation_action_result( string $action, Donation $donation ): void {

		$this->assertSame( 5_001, $donation->get_id()->get_value() );

		match ( $action ) {
			'authorize' => $this->assert_authorized_donation( $donation ),
			'capture' => $this->assert_captured_donation( $donation ),
			'fail' => $this->assert_failed_donation( $donation ),
			'refund' => $this->assert_refunded_donation( $donation ),
			'cancel' => $this->assert_canceled_donation( $donation ),
		};
	}

	private function assert_authorized_donation( Donation $donation ): void {

		$this->assertSame( DonationStatus::Authorized, $donation->get_status() );
	}

	private function assert_captured_donation( Donation $donation ): void {

		$this->assertSame( DonationStatus::Captured, $donation->get_status() );
	}

	private function assert_failed_donation( Donation $donation ): void {

		$this->assertSame( DonationStatus::Failed, $donation->get_status() );
	}

	private function assert_refunded_donation( Donation $donation ): void {

		$this->assertSame( DonationStatus::Refunded, $donation->get_status() );
	}

	private function assert_canceled_donation( Donation $donation ): void {

		$this->assertSame( DonationStatus::Canceled, $donation->get_status() );
	}
}
