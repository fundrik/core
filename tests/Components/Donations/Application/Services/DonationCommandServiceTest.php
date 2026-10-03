<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Donations\Application\Commands\CreateDonationCommand;
use Fundrik\Core\Components\Donations\Application\Events\DonationCreatedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationRefundedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationRejectedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationSucceededEvent;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\Services\DonationCommandService;
use Fundrik\Core\Components\Donations\Application\UseCases\AbstractDonationMutationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\DonationCreationData;
use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutationException;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RejectDonation\RejectDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\RejectDonation\RejectDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\SucceedDonation\SucceedDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\SucceedDonation\SucceedDonationHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Donations\Domain\PaymentId;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\Amount;
use Fundrik\Core\Components\Shared\Domain\Currency;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidAmountException;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( DonationCommandService::class )]
#[UsesClass( DonationCreatedEvent::class )]
#[UsesClass( DonationSucceededEvent::class )]
#[UsesClass( DonationRejectedEvent::class )]
#[UsesClass( DonationRefundedEvent::class )]
#[UsesClass( CreateDonationCommand::class )]
#[UsesClass( AbstractDonationMutationHandler::class )]
#[UsesClass( DonationMutationException::class )]
#[UsesClass( CreateDonationHandler::class )]
#[UsesClass( CreateDonationException::class )]
#[UsesClass( SucceedDonationHandler::class )]
#[UsesClass( RejectDonationHandler::class )]
#[UsesClass( RefundDonationHandler::class )]
#[UsesClass( RefundDonationException::class )]
#[UsesClass( RejectDonationException::class )]
#[UsesClass( Donation::class )]
#[UsesClass( DonationFactory::class )]
#[UsesClass( DonationStatus::class )]
#[UsesClass( PaymentId::class )]
#[UsesClass( Amount::class )]
#[UsesClass( Currency::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( DonationCreationData::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Money::class )]
final class DonationCommandServiceTest extends MockeryTestCase {

	private CampaignRepositoryPort&MockInterface $campaign_repository;
	private DonationRepositoryPort&MockInterface $donation_repository;
	private ApplicationEventBusPort&MockInterface $event_bus;

	private DonationCommandService $command;

	protected function setUp(): void {

		parent::setUp();

		$this->campaign_repository = Mockery::mock( CampaignRepositoryPort::class );
		$this->donation_repository = Mockery::mock( DonationRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );
		$create_donation = new CreateDonationHandler(
			$this->campaign_repository,
			new DonationFactory(),
			$this->donation_repository,
			$this->event_bus,
		);

		$this->command = new DonationCommandService(
			$create_donation,
			new SucceedDonationHandler( $this->donation_repository, $this->event_bus ),
			new RejectDonationHandler( $this->donation_repository, $this->event_bus ),
			new RefundDonationHandler( $this->donation_repository, $this->event_bus ),
		);
	}

	#[Test]
	public function create_uses_injected_ports(): void {

		$campaign = $this->make_campaign( 901, 'Campaign 901', true, 'RUB', 10_000 );
		$command = new CreateDonationCommand( id: 5_001, campaign_id: 901, amount: 1_000 );

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $campaign_id ): bool => $campaign_id->equals( EntityId::create( 901 ) ),
			)
			->andReturn( $campaign );

		$this->donation_repository
			->shouldReceive( 'insert' )
			->once()
			->withArgs(
				function ( Donation $donation ): bool {

					$this->assertSame( 5_001, $donation->get_id()->get_value() );
					$this->assertSame( 901, $donation->get_campaign_id()->get_value() );
					$this->assertSame( 1_000, $donation->get_money()->get_amount()->get_value() );
					$this->assertSame( 'RUB', $donation->get_money()->get_currency()->get_code() );
					$this->assertSame( DonationStatus::Created, $donation->get_status() );
					$this->assertNull( $donation->get_payment_id() );

					return true;
				},
			)
			->andReturnUsing( static fn ( Donation $donation ): Donation => $donation );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( DonationCreatedEvent::class, EntityId::create( 5_001 ) ) );

		$this->command->create( $command );
	}

	#[Test]
	#[DataProvider( 'mutation_provider' )]
	public function mutation_methods_use_injected_ports(
		string $method,
		string $event_class,
		string $expected_status,
		string $source_state,
	): void {

		$donation_id = EntityId::create( 5_001 );
		$donation = $source_state === 'succeeded'
			? $this->make_succeeded_donation( 5_001, 901 )
			: $this->make_pending_donation( 5_001, 901 );

		$this->donation_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_donation_id ): bool => $actual_donation_id->equals( $donation_id ),
			)
			->andReturn( $donation );

		$this->donation_repository
			->shouldReceive( 'update' )
			->once()
			->withArgs(
				function ( Donation $updated_donation ) use ( $expected_status ): bool {

					$this->assertSame( $expected_status, $updated_donation->get_status()->value );

					return true;
				},
			)
			->andReturnUsing( static fn ( Donation $updated_donation ): Donation => $updated_donation );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( $event_class, $donation_id ) );

		$this->command->{$method}( $donation_id->get_value() );
	}

	public static function mutation_provider(): array {

		return [
			'succeed' => [ 'succeed', DonationSucceededEvent::class, 'succeeded', 'pending' ],
			'reject' => [ 'reject', DonationRejectedEvent::class, 'rejected', 'pending' ],
			'refund' => [ 'refund', DonationRefundedEvent::class, 'refunded', 'succeeded' ],
		];
	}

	#[Test]
	#[DataProvider( 'invalid_mutation_id_provider' )]
	public function mutation_methods_throw_precondition_exception_for_invalid_donation_id(
		string $method,
		string $exception_class,
	): void {

		try {
			$this->command->{$method}( -1 );
			$this->fail( 'Expected donation mutation exception to be thrown.' );
		} catch ( DonationMutationException $exception ) {
			$this->assertInstanceOf( $exception_class, $exception );
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame( 'ID must be a positive integer or a valid UUID. Given: "-1".', $exception->getMessage() );
			$this->assertInstanceOf( InvalidEntityIdException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	#[DataProvider( 'invalid_creation_data_provider' )]
	public function create_throws_precondition_exception_for_invalid_input(
		int|string $id,
		int|string $campaign_id,
		int $amount,
		string $message,
		string $previous_class,
	): void {

		try {
			$this->command->create( new CreateDonationCommand( $id, $campaign_id, $amount ) );
			$this->fail( 'Expected CreateDonationException to be thrown.' );
		} catch ( CreateDonationException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame( $message, $exception->getMessage() );
			$this->assertInstanceOf( $previous_class, $exception->getPrevious() );
		}
	}

	public static function invalid_mutation_id_provider(): array {

		return [
			'succeed' => [ 'succeed', SucceedDonationException::class ],
			'reject' => [ 'reject', RejectDonationException::class ],
			'refund' => [ 'refund', RefundDonationException::class ],
		];
	}

	public static function invalid_creation_data_provider(): array {

		return [
			'invalid donation id' => [ -1, 901, 1_000, 'ID must be a positive integer or a valid UUID. Given: "-1".', InvalidEntityIdException::class ],
			'invalid campaign id' => [ 5_001, -1, 1_000, 'ID must be a positive integer or a valid UUID. Given: "-1".', InvalidEntityIdException::class ],
			'invalid amount' => [ 5_001, 901, 0, 'Amount must be a positive integer. Given: 0.', InvalidAmountException::class ],
		];
	}

	private function event_of_type( string $event_class, EntityId $donation_id ): callable {

		return function ( object $event ) use ( $event_class, $donation_id ): bool {

			$this->assertInstanceOf( $event_class, $event );
			$this->assertTrue( $event->get_donation_id()->equals( $donation_id ) );

			return true;
		};
	}
}
