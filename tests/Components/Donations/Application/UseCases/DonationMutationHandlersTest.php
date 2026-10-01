<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\UseCases;

use Fundrik\Core\Components\Donations\Application\Events\DonationRefundedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationRejectedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationSucceededEvent;
use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\UseCases\AbstractDonationMutationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutation;
use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutationException;
use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutationPreconditionReason;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationNotFoundException;
use Fundrik\Core\Components\Donations\Application\UseCases\RejectDonation\RejectDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\RejectDonation\RejectDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RejectDonation\RejectDonationNotFoundException;
use Fundrik\Core\Components\Donations\Application\UseCases\SucceedDonation\SucceedDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\SucceedDonation\SucceedDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\SucceedDonation\SucceedDonationNotFoundException;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\Amount;
use Fundrik\Core\Components\Shared\Domain\Currency;
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
#[CoversClass( DonationMutation::class )]
#[CoversClass( DonationMutationException::class )]
#[CoversClass( DonationMutationPreconditionReason::class )]
#[CoversClass( SucceedDonationHandler::class )]
#[CoversClass( RejectDonationHandler::class )]
#[CoversClass( RefundDonationHandler::class )]
#[UsesClass( SucceedDonationException::class )]
#[CoversClass( SucceedDonationNotFoundException::class )]
#[UsesClass( RejectDonationException::class )]
#[CoversClass( RejectDonationNotFoundException::class )]
#[UsesClass( RefundDonationException::class )]
#[CoversClass( RefundDonationNotFoundException::class )]
#[UsesClass( DonationApplicationException::class )]
#[UsesClass( FundrikApplicationException::class )]
#[UsesClass( DonationSucceededEvent::class )]
#[UsesClass( DonationRejectedEvent::class )]
#[UsesClass( DonationRefundedEvent::class )]
#[UsesClass( Donation::class )]
#[UsesClass( DonationFactory::class )]
#[UsesClass( DonationStatus::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Amount::class )]
#[UsesClass( Currency::class )]
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
		$handler = $this->make_handler( 'succeed' );
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
			$this->invoke_handler( $handler, 'succeed', $donation_id );
			$this->fail( 'Expected DonationMutationException to be thrown.' );
		} catch ( DonationMutationException $exception ) {
			$this->assertInstanceOf( SucceedDonationException::class, $exception );
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame( 0, $exception->getCode() );
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
	#[DataProvider( 'rejected_action_provider' )]
	public function handle_throws_when_donation_action_is_rejected(
		string $action,
		Donation $donation,
		string $exception_class,
	): void {

		$donation_id = EntityId::create( 5_001 );
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
				sprintf( 'Cannot %s donation "5001": change was rejected.', $action ),
				$exception->getMessage(),
			);
			$this->assertNotNull( $exception->getPrevious() );
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
	#[DataProvider( 'action_phrase_provider' )]
	public function handle_wraps_repository_failure_during_persist(
		string $action,
		string $phrase,
		string $exception_class,
	): void {

		$donation_id = EntityId::create( 5_001 );
		$handler = $this->make_handler( $action );
		$donation = $this->make_donation_for_action( $action );
		$e = new FakeDonationRepositoryException();

		$this->donations
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( $donation );
		$this->donations
			->shouldReceive( 'update' )
			->once()
			->andThrow( $e );
		$this->event_bus->shouldNotReceive( 'publish' );

		try {
			$this->invoke_handler( $handler, $action, $donation_id );
			$this->fail( 'Expected DonationMutationException to be thrown.' );
		} catch ( DonationMutationException $exception ) {
			$this->assertInstanceOf( $exception_class, $exception );
			$this->assertSame( UseCaseFailureStage::Persistence, $exception->get_stage() );
			$this->assertNull( $exception->get_reason() );
			$this->assertSame( sprintf( 'Failed to %s donation "5001".', $phrase ), $exception->getMessage() );
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
			'refund' => [ 'refund', DonationRefundedEvent::class ],
			'reject' => [ 'reject', DonationRejectedEvent::class ],
			'succeed' => [ 'succeed', DonationSucceededEvent::class ],
		];
	}

	public static function action_phrase_provider(): array {

		return [
			'refund' => [ 'refund', 'refund', RefundDonationException::class ],
			'reject' => [ 'reject', 'reject', RejectDonationException::class ],
			'succeed' => [ 'succeed', 'succeed', SucceedDonationException::class ],
		];
	}

	public static function rejected_action_provider(): array {

		return [
			'refund' => [ 'refund', self::build_pending_donation(), RefundDonationException::class ],
			'reject' => [ 'reject', self::build_succeeded_donation(), RejectDonationException::class ],
			'succeed' => [ 'succeed', self::build_rejected_donation(), SucceedDonationException::class ],
		];
	}

	public static function event_publish_provider(): array {

		return [
			'refund' => [ 'refund', 'refunded', 'refunded', RefundDonationException::class ],
			'reject' => [ 'reject', 'rejected', 'rejected', RejectDonationException::class ],
			'succeed' => [ 'succeed', 'succeeded', 'succeeded', SucceedDonationException::class ],
		];
	}

	public static function persistence_not_found_provider(): array {

		return [
			'refund' => [ 'refund', 'refund', RefundDonationNotFoundException::class ],
			'reject' => [ 'reject', 'reject', RejectDonationNotFoundException::class ],
			'succeed' => [ 'succeed', 'succeed', SucceedDonationNotFoundException::class ],
		];
	}

	private function make_handler( string $action ): object {

		return match ( $action ) {
			'succeed' => new SucceedDonationHandler( $this->donations, $this->event_bus ),
			'reject' => new RejectDonationHandler( $this->donations, $this->event_bus ),
			'refund' => new RefundDonationHandler( $this->donations, $this->event_bus ),
		};
	}

	private function make_donation_for_action( string $action ): Donation {

		return match ( $action ) {
			'succeed', 'reject' => self::build_pending_donation(),
			'refund' => self::build_succeeded_donation(),
		};
	}

	private static function build_pending_donation(): Donation {

		return ( new DonationFactory() )->create_pending_from_primitives( 5_001, 901, 1_000, 'RUB' );
	}

	private static function build_succeeded_donation(): Donation {

		return self::build_pending_donation()->succeed();
	}

	private static function build_rejected_donation(): Donation {

		return self::build_pending_donation()->reject();
	}

	private function invoke_handler( object $handler, string $action, EntityId $donation_id ): Donation {

		return match ( $action ) {
			'succeed', 'reject', 'refund' => $handler->handle( $donation_id ),
		};
	}

	private function assert_donation_action_result( string $action, Donation $donation ): void {

		$this->assertSame( 5_001, $donation->get_id()->get_value() );

		match ( $action ) {
			'succeed' => $this->assertSame( DonationStatus::Succeeded, $donation->get_status() ),
			'reject' => $this->assertSame( DonationStatus::Rejected, $donation->get_status() ),
			'refund' => $this->assertSame( DonationStatus::Refunded, $donation->get_status() ),
		};
	}
}
