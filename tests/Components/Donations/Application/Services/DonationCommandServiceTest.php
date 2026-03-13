<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Donations\Application\Events\DonationAuthorizedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationCanceledEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationCapturedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationCreatedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationFailedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationRefundedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationUpdatedEvent;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\Services\DonationCommandService;
use Fundrik\Core\Components\Donations\Application\Services\DonationCommandServiceFactory;
use Fundrik\Core\Components\Donations\Application\UseCases\AbstractDonationMutationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\AuthorizeDonation\AuthorizeDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CancelDonation\CancelDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CaptureDonation\CaptureDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FailDonation\FailDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\UpdateDonation\UpdateDonationHandler;
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
#[UsesClass( DonationCommandServiceFactory::class )]
#[UsesClass( DonationCreatedEvent::class )]
#[UsesClass( DonationUpdatedEvent::class )]
#[UsesClass( DonationAuthorizedEvent::class )]
#[UsesClass( DonationCapturedEvent::class )]
#[UsesClass( DonationFailedEvent::class )]
#[UsesClass( DonationRefundedEvent::class )]
#[UsesClass( DonationCanceledEvent::class )]
#[UsesClass( AbstractDonationMutationHandler::class )]
#[UsesClass( CreateDonationHandler::class )]
#[UsesClass( UpdateDonationHandler::class )]
#[UsesClass( AuthorizeDonationHandler::class )]
#[UsesClass( CaptureDonationHandler::class )]
#[UsesClass( FailDonationHandler::class )]
#[UsesClass( RefundDonationHandler::class )]
#[UsesClass( CancelDonationHandler::class )]
#[UsesClass( Donation::class )]
#[UsesClass( DonationFactory::class )]
#[UsesClass( DonationStatus::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
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

		$this->command = ( new DonationCommandServiceFactory(
			$this->campaign_repository,
			$this->donation_repository,
			$this->event_bus,
		) )->create();
	}

	#[Test]
	public function create_uses_injected_ports(): void {

		$campaign = $this->make_campaign( 901, 'Campaign 901', true, true, true, 10_000 );
		$donation = $this->make_pending_donation( 5_001, 901 );

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation->get_campaign_id() ) )
			->andReturn( $campaign );

		$this->donation_repository
			->shouldReceive( 'insert' )
			->once()
			->with( $this->identicalTo( $donation ) )
			->andReturn( $donation );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( DonationCreatedEvent::class, $donation->get_id() ) );

		$result = $this->command->create( $donation );

		$this->assertSame( $donation, $result );
	}

	#[Test]
	public function update_uses_injected_ports(): void {

		$donation = $this->make_pending_donation( 5_001, 901 );

		$this->donation_repository
			->shouldReceive( 'update' )
			->once()
			->with( $this->identicalTo( $donation ) )
			->andReturn( $donation );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( DonationUpdatedEvent::class, $donation->get_id() ) );

		$result = $this->command->update( $donation );

		$this->assertSame( $donation, $result );
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
			->with( $this->identicalTo( $donation_id ) )
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

		$result = $this->command->{$method}( $donation_id );

		$this->assertSame( $expected_status, $result->get_status()->value );
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

	private function event_of_type( string $event_class, EntityId $donation_id ): callable {

		return function ( object $event ) use ( $event_class, $donation_id ): bool {

			$this->assertInstanceOf( $event_class, $event );
			$this->assertTrue( $event->get_donation_id()->equals( $donation_id ) );

			return true;
		};
	}
}
