<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\UseCases\DeleteCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeletedEvent;
use Fundrik\Core\Components\Campaigns\Application\Exceptions\CampaignApplicationException;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignPreconditionReason;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\Core\Tests\Fixtures\FakeApplicationEventBusException;
use Fundrik\Core\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\Core\Tests\Fixtures\FakeDonationRepositoryException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( DeleteCampaignHandler::class )]
#[UsesClass( DeleteCampaignException::class )]
#[UsesClass( UseCaseFailureStage::class )]
#[UsesClass( DeleteCampaignPreconditionReason::class )]
#[UsesClass( CampaignApplicationException::class )]
#[UsesClass( FundrikApplicationException::class )]
#[UsesClass( CampaignDeletedEvent::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( Donation::class )]
#[UsesClass( DonationFactory::class )]
#[UsesClass( DonationStatus::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Money::class )]
#[UsesClass( UtcDateTime::class )]
final class DeleteCampaignHandlerTest extends MockeryTestCase {

	private CampaignRepositoryPort&MockInterface $repository;
	private DonationRepositoryPort&MockInterface $donations;
	private ApplicationEventBusPort&MockInterface $event_bus;

	private DeleteCampaignHandler $handler;

	protected function setUp(): void {

		parent::setUp();

		$this->repository = Mockery::mock( CampaignRepositoryPort::class );
		$this->donations = Mockery::mock( DonationRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );

		$this->handler = new DeleteCampaignHandler( $this->repository, $this->donations, $this->event_bus );
	}

	#[Test]
	public function handle_deletes_campaign_and_publishes_deleted_event(): void {

		$campaign_id = $this->make_campaign()->get_id();

		$this->donations
			->shouldReceive( 'find_all_by_campaign_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( [] );

		$this->repository
			->shouldReceive( 'delete' )
			->once()
			->with( $this->identicalTo( $campaign_id ) );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs(
				function ( object $event ) use ( $campaign_id ): bool {

					$this->assertInstanceOf( CampaignDeletedEvent::class, $event );
					$this->assertSame( $campaign_id, $event->get_campaign_id() );

					return true;
				},
			);

		$this->handler->handle( $campaign_id );

		$this->assertTrue( true );
	}

	#[Test]
	public function handle_propagates_repository_exception_without_publishing(): void {

		$campaign_id = $this->make_campaign()->get_id();
		$e = new FakeCampaignRepositoryException();

		$this->donations
			->shouldReceive( 'find_all_by_campaign_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( [] );

		$this->repository
			->shouldReceive( 'delete' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andThrow( $e );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		try {
			$this->handler->handle( $campaign_id );
			$this->fail( 'Expected DeleteCampaignException to be thrown.' );
		} catch ( DeleteCampaignException $exception ) {
			$this->assertSame( UseCaseFailureStage::Persistence, $exception->get_stage() );
			$this->assertSame( $e, $exception->getPrevious() );
		}
	}

	#[Test]
	public function handle_throws_event_bus_exception_when_publishing_fails(): void {

		$campaign_id = $this->make_campaign()->get_id();
		$e = new FakeApplicationEventBusException();

		$this->donations
			->shouldReceive( 'find_all_by_campaign_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( [] );

		$this->repository
			->shouldReceive( 'delete' )
			->once()
			->with( $this->identicalTo( $campaign_id ) );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->andThrow( $e );

		try {
			$this->handler->handle( $campaign_id );
			$this->fail( 'Expected DeleteCampaignException to be thrown.' );
		} catch ( DeleteCampaignException $exception ) {
			$this->assertSame( UseCaseFailureStage::EventPublish, $exception->get_stage() );
			$this->assertSame( $e, $exception->getPrevious() );
		}
	}

	#[Test]
	public function handle_throws_when_campaign_has_donations(): void {

		$campaign_id = $this->make_campaign()->get_id();
		$donation = $this->make_donation( $campaign_id );

		$this->donations
			->shouldReceive( 'find_all_by_campaign_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( [ $donation ] );

		$this->repository
			->shouldNotReceive( 'delete' );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		try {
			$this->handler->handle( $campaign_id );
			$this->fail( 'Expected DeleteCampaignException to be thrown.' );
		} catch ( DeleteCampaignException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame( DeleteCampaignPreconditionReason::HasDonations, $exception->get_reason() );
			$this->assertSame( 'Cannot delete campaign "1": campaign already has donations.', $exception->getMessage() );
		}
	}

	#[Test]
	public function handle_propagates_donation_repository_exception(): void {

		$campaign_id = $this->make_campaign()->get_id();
		$e = new FakeDonationRepositoryException();

		$this->donations
			->shouldReceive( 'find_all_by_campaign_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andThrow( $e );

		$this->repository
			->shouldNotReceive( 'delete' );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		try {
			$this->handler->handle( $campaign_id );
			$this->fail( 'Expected DeleteCampaignException to be thrown.' );
		} catch ( DeleteCampaignException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame( DeleteCampaignPreconditionReason::DonationsLookupFailed, $exception->get_reason() );
			$this->assertSame( $e, $exception->getPrevious() );
		}
	}

	private function make_donation( EntityId $campaign_id ): Donation {

		$factory = new DonationFactory();

		return $factory->create_pending(
			id: EntityId::create( 5_001 ),
			campaign_id: $campaign_id,
			money: Money::create( 1_000, 'RUB' ),
		);
	}
}
