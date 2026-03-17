<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\UseCases\CreateDonation;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Donations\Application\Events\DonationCreatedEvent;
use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationAlreadyExistsException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationPreconditionReason;
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
use Fundrik\Core\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\Core\Tests\Fixtures\FakeDonationAlreadyExistsException;
use Fundrik\Core\Tests\Fixtures\FakeDonationRepositoryException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CreateDonationHandler::class )]
#[UsesClass( CreateDonationAlreadyExistsException::class )]
#[UsesClass( CreateDonationException::class )]
#[UsesClass( CreateDonationPreconditionReason::class )]
#[UsesClass( UseCaseFailureStage::class )]
#[UsesClass( DonationApplicationException::class )]
#[UsesClass( FundrikApplicationException::class )]
#[UsesClass( DonationCreatedEvent::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( Donation::class )]
#[UsesClass( DonationFactory::class )]
#[UsesClass( DonationStatus::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( Money::class )]
final class CreateDonationHandlerTest extends MockeryTestCase {

	private CampaignRepositoryPort&MockInterface $campaigns;
	private DonationRepositoryPort&MockInterface $repository;
	private ApplicationEventBusPort&MockInterface $event_bus;

	private CreateDonationHandler $handler;

	protected function setUp(): void {

		parent::setUp();

		$this->campaigns = Mockery::mock( CampaignRepositoryPort::class );
		$this->repository = Mockery::mock( DonationRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );
		$this->handler = new CreateDonationHandler( $this->campaigns, $this->repository, $this->event_bus );
	}

	#[Test]
	public function handle_inserts_donation(): void {

		$donation = $this->make_pending_donation();
		$campaign = $this->make_donation_campaign();

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation->get_campaign_id() ) )
			->andReturn( $campaign );

		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->with( $this->identicalTo( $donation ) )
			->andReturn( $donation );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs(
				function ( object $event ) use ( $donation ): bool {

					$this->assertInstanceOf( DonationCreatedEvent::class, $event );
					$this->assertSame( $donation->get_id(), $event->get_donation_id() );

					return true;
				},
			);

		$result = $this->handler->handle( $donation );

		$this->assertSame( $donation, $result );
	}

	#[Test]
	public function handle_throws_when_donation_already_exists(): void {

		$donation = $this->make_pending_donation();
		$campaign = $this->make_donation_campaign();
		$e = new FakeDonationAlreadyExistsException();

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation->get_campaign_id() ) )
			->andReturn( $campaign );

		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->with( $this->identicalTo( $donation ) )
			->andThrow( $e );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		try {
			$this->handler->handle( $donation );
			$this->fail( 'Expected CreateDonationAlreadyExistsException to be thrown.' );
		} catch ( CreateDonationAlreadyExistsException $exception ) {
			$this->assertSame( UseCaseFailureStage::Persistence, $exception->get_stage() );
			$this->assertSame( $e, $exception->getPrevious() );
			$this->assertSame( 'Cannot create donation "5001": donation already exists.', $exception->getMessage() );
		}
	}

	#[Test]
	public function handle_wraps_repository_exception(): void {

		$donation = $this->make_pending_donation();
		$campaign = $this->make_donation_campaign();
		$e = new FakeDonationRepositoryException();

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation->get_campaign_id() ) )
			->andReturn( $campaign );

		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->with( $this->identicalTo( $donation ) )
			->andThrow( $e );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		try {
			$this->handler->handle( $donation );
			$this->fail( 'Expected CreateDonationException to be thrown.' );
		} catch ( CreateDonationException $exception ) {
			$this->assertSame( UseCaseFailureStage::Persistence, $exception->get_stage() );
			$this->assertSame( $e, $exception->getPrevious() );
			$this->assertNull( $exception->get_reason() );
		}
	}

	#[Test]
	public function handle_throws_when_created_event_publishing_fails(): void {

		$donation = $this->make_pending_donation();
		$campaign = $this->make_donation_campaign();
		$e = new FakeApplicationEventBusException();

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation->get_campaign_id() ) )
			->andReturn( $campaign );

		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->with( $this->identicalTo( $donation ) )
			->andReturn( $donation );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->andThrow( $e );

		try {
			$this->handler->handle( $donation );
			$this->fail( 'Expected CreateDonationException to be thrown.' );
		} catch ( CreateDonationException $exception ) {
			$this->assertSame( UseCaseFailureStage::EventPublish, $exception->get_stage() );
			$this->assertSame( $e, $exception->getPrevious() );
			$this->assertSame(
				'Donation "5001" was created, but publishing the created event failed.',
				$exception->getMessage(),
			);
			$this->assertNull( $exception->get_reason() );
		}
	}

	#[Test]
	public function handle_throws_when_campaign_lookup_fails(): void {

		$donation = $this->make_pending_donation();
		$e = new FakeCampaignRepositoryException();

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation->get_campaign_id() ) )
			->andThrow( $e );

		$this->repository
			->shouldNotReceive( 'insert' );

		try {
			$this->handler->handle( $donation );
			$this->fail( 'Expected CreateDonationException to be thrown.' );
		} catch ( CreateDonationException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame( CreateDonationPreconditionReason::CampaignLookupFailed, $exception->get_reason() );
			$this->assertSame( $e, $exception->getPrevious() );
			$this->assertSame( 'Failed to retrieve campaign "901".', $exception->getMessage() );
		}
	}

	#[Test]
	public function handle_throws_when_campaign_does_not_exist(): void {

		$donation = $this->make_pending_donation();

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation->get_campaign_id() ) )
			->andReturn( null );

		$this->repository
			->shouldNotReceive( 'insert' );

		try {
			$this->handler->handle( $donation );
			$this->fail( 'Expected CreateDonationException to be thrown.' );
		} catch ( CreateDonationException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame( CreateDonationPreconditionReason::CampaignNotFound, $exception->get_reason() );
			$this->assertSame(
				'Cannot create donation "5001": campaign "901" does not exist.',
				$exception->getMessage(),
			);
		}
	}

	#[Test]
	public function handle_throws_when_campaign_cannot_receive_donations_because_it_is_inactive(): void {

		$donation = $this->make_pending_donation();
		$campaign = $this->make_donation_campaign( is_active: false, is_open: true );

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation->get_campaign_id() ) )
			->andReturn( $campaign );

		$this->repository
			->shouldNotReceive( 'insert' );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		try {
			$this->handler->handle( $donation );
			$this->fail( 'Expected CreateDonationException to be thrown.' );
		} catch ( CreateDonationException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame(
				CreateDonationPreconditionReason::CampaignCannotReceiveDonations,
				$exception->get_reason(),
			);
			$this->assertSame(
				'Cannot create donation "5001": campaign "901" cannot receive donations.',
				$exception->getMessage(),
			);
		}
	}

	#[Test]
	public function handle_throws_when_campaign_cannot_receive_donations_because_it_is_closed(): void {

		$donation = $this->make_pending_donation();
		$campaign = $this->make_donation_campaign( is_active: true, is_open: false );

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $donation->get_campaign_id() ) )
			->andReturn( $campaign );

		$this->repository
			->shouldNotReceive( 'insert' );

		$this->event_bus
			->shouldNotReceive( 'publish' );

		try {
			$this->handler->handle( $donation );
			$this->fail( 'Expected CreateDonationException to be thrown.' );
		} catch ( CreateDonationException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame(
				CreateDonationPreconditionReason::CampaignCannotReceiveDonations,
				$exception->get_reason(),
			);
			$this->assertSame(
				'Cannot create donation "5001": campaign "901" cannot receive donations.',
				$exception->getMessage(),
			);
		}
	}

	private function make_donation_campaign( bool $is_active = true, bool $is_open = true ): Campaign {

		return $this->make_campaign( 901, 'Campaign 901', $is_active, $is_open, true, 10_000 );
	}
}
