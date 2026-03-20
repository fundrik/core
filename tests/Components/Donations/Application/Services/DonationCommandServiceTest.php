<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Donations\Application\Commands\CreateDonationCommand;
use Fundrik\Core\Components\Donations\Application\Events\DonationAuthorizedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationCanceledEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationCapturedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationCreatedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationFailedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationRefundedEvent;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\ReadModels\DonationDetails;
use Fundrik\Core\Components\Donations\Application\ReadModels\DonationDetailsMapper;
use Fundrik\Core\Components\Donations\Application\Services\DonationCommandService;
use Fundrik\Core\Components\Donations\Application\UseCases\AbstractDonationMutationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\AuthorizeDonation\AuthorizeDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\AuthorizeDonation\AuthorizeDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CancelDonation\CancelDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CaptureDonation\CaptureDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FailDonation\FailDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
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
#[UsesClass( DonationAuthorizedEvent::class )]
#[UsesClass( DonationCapturedEvent::class )]
#[UsesClass( DonationFailedEvent::class )]
#[UsesClass( DonationRefundedEvent::class )]
#[UsesClass( DonationCanceledEvent::class )]
#[UsesClass( CreateDonationCommand::class )]
#[UsesClass( DonationDetails::class )]
#[UsesClass( DonationDetailsMapper::class )]
#[UsesClass( AbstractDonationMutationHandler::class )]
#[UsesClass( CreateDonationHandler::class )]
#[UsesClass( AuthorizeDonationHandler::class )]
#[UsesClass( CaptureDonationHandler::class )]
#[UsesClass( FailDonationHandler::class )]
#[UsesClass( RefundDonationHandler::class )]
#[UsesClass( CancelDonationHandler::class )]
#[UsesClass( Donation::class )]
#[UsesClass( DonationFactory::class )]
#[UsesClass( DonationStatus::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTitle::class )]
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

		$this->command = new DonationCommandService(
			new CreateDonationHandler( $this->campaign_repository, $this->donation_repository, $this->event_bus ),
			new DonationFactory(),
			new DonationDetailsMapper(),
			new AuthorizeDonationHandler( $this->donation_repository, $this->event_bus ),
			new CaptureDonationHandler( $this->donation_repository, $this->event_bus ),
			new FailDonationHandler( $this->donation_repository, $this->event_bus ),
			new RefundDonationHandler( $this->donation_repository, $this->event_bus ),
			new CancelDonationHandler( $this->donation_repository, $this->event_bus ),
		);
	}

	#[Test]
	public function create_uses_injected_ports(): void {

		$campaign = $this->make_campaign( 901, 'Campaign 901', true, 'RUB', 10_000 );
		$command = new CreateDonationCommand( id: 5_001, campaign_id: 901, amount: 1_000, currency_code: 'RUB' );

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
					$this->assertSame( DonationStatus::Pending, $donation->get_status() );

					return true;
				},
			)
			->andReturnUsing( static fn ( Donation $donation ): Donation => $donation );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( DonationCreatedEvent::class, EntityId::create( 5_001 ) ) );

		$result = $this->command->create( $command );

		$this->assertInstanceOf( DonationDetails::class, $result );
		$this->assertSame( 5_001, $result->get_id() );
		$this->assertSame( 901, $result->get_campaign_id() );
		$this->assertSame( 1_000, $result->get_amount() );
		$this->assertSame( 'RUB', $result->get_currency_code() );
		$this->assertSame( DonationStatus::Pending->value, $result->get_status() );
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
		$donation = $source_state === 'captured'
			? $this->make_captured_donation( 5_001, 901 )
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

		$result = $this->command->{$method}( $donation_id->get_value() );

		$this->assertInstanceOf( DonationDetails::class, $result );
		$this->assertSame( $expected_status, $result->get_status() );
		$this->assertSame( $donation_id->get_value(), $result->get_id() );
	}

	public static function mutation_provider(): array {

		return [
			'authorize' => [ 'authorize', DonationAuthorizedEvent::class, 'authorized', 'pending' ],
			'capture' => [ 'capture', DonationCapturedEvent::class, 'captured', 'pending' ],
			'fail' => [ 'fail', DonationFailedEvent::class, 'failed', 'pending' ],
			'refund' => [ 'refund', DonationRefundedEvent::class, 'refunded', 'captured' ],
			'cancel' => [ 'cancel', DonationCanceledEvent::class, 'canceled', 'pending' ],
		];
	}

	#[Test]
	public function authorize_throws_precondition_exception_for_invalid_donation_id(): void {

		$this->expectException( AuthorizeDonationException::class );
		$this->expectExceptionMessage( 'ID must be a positive integer or a valid UUID. Given: -1.' );

		$this->command->authorize( -1 );
	}

	private function event_of_type( string $event_class, EntityId $donation_id ): callable {

		return function ( object $event ) use ( $event_class, $donation_id ): bool {

			$this->assertInstanceOf( $event_class, $event );
			$this->assertTrue( $event->get_donation_id()->equals( $donation_id ) );

			return true;
		};
	}
}
