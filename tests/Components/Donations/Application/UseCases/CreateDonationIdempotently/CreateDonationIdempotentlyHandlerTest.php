<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\UseCases\CreateDonationIdempotently;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Donations\Application\Events\DonationCreatedEvent;
use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\DonationCreationData;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently\CreateDonationIdempotentlyConflictException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently\CreateDonationIdempotentlyHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently\CreateDonationIdempotentlyResult;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently\CreateDonationIdempotentlyStatus;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdException;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\Amount;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Tests\Fixtures\FakeDonationAlreadyExistsException;
use Fundrik\Core\Tests\Fixtures\FakeDonationRepositoryException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CreateDonationIdempotentlyHandler::class )]
#[UsesClass( CreateDonationIdempotentlyConflictException::class )]
#[UsesClass( CreateDonationIdempotentlyResult::class )]
#[UsesClass( CreateDonationIdempotentlyStatus::class )]
#[UsesClass( CreateDonationException::class )]
#[UsesClass( UseCaseFailureStage::class )]
#[UsesClass( DonationApplicationException::class )]
#[UsesClass( FundrikApplicationException::class )]
#[UsesClass( FindDonationByIdException::class )]
#[UsesClass( DonationCreatedEvent::class )]
#[UsesClass( DonationCreationData::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( Donation::class )]
#[UsesClass( DonationFactory::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Amount::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( Money::class )]
final class CreateDonationIdempotentlyHandlerTest extends MockeryTestCase {

	private CampaignRepositoryPort&MockInterface $campaigns;
	private DonationRepositoryPort&MockInterface $repository;
	private ApplicationEventBusPort&MockInterface $event_bus;

	private CreateDonationIdempotentlyHandler $handler;

	protected function setUp(): void {

		parent::setUp();

		$this->campaigns = Mockery::mock( CampaignRepositoryPort::class );
		$this->repository = Mockery::mock( DonationRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );
		$create_donation = new CreateDonationHandler(
			$this->campaigns,
			new DonationFactory(),
			$this->repository,
			$this->event_bus,
		);
		$this->handler = new CreateDonationIdempotentlyHandler(
			$create_donation,
			new FindDonationByIdHandler( $this->repository ),
		);
	}

	#[Test]
	public function handle_returns_created_result_for_new_donation(): void {

		$data = new DonationCreationData(
			donation_id: EntityId::create( 5_001 ),
			campaign_id: EntityId::create( 901 ),
			amount: Amount::create( 1_000 ),
		);
		$campaign = $this->make_donation_campaign();

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $data->get_campaign_id() ) )
			->andReturn( $campaign );

		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->andReturnUsing( static fn ( Donation $donation ): Donation => $donation );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once();

		$result = $this->handler->handle( $data );

		$this->assertSame( CreateDonationIdempotentlyStatus::Created, $result->get_status() );
		$this->assertSame( $data->get_donation_id(), $result->get_donation()->get_id() );
	}

	#[Test]
	public function handle_returns_replayed_result_for_matching_existing_donation(): void {

		$data = new DonationCreationData(
			donation_id: EntityId::create( 5_001 ),
			campaign_id: EntityId::create( 901 ),
			amount: Amount::create( 1_000 ),
		);
		$campaign = $this->make_donation_campaign();
		$existing_donation = $this->make_pending_donation( 5_001, 901 );

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $data->get_campaign_id() ) )
			->andReturn( $campaign );

		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->andThrow( new FakeDonationAlreadyExistsException() );

		$this->repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $data->get_donation_id() ) )
			->andReturn( $existing_donation );

		$this->event_bus->shouldNotReceive( 'publish' );

		$result = $this->handler->handle( $data );

		$this->assertSame( CreateDonationIdempotentlyStatus::Replayed, $result->get_status() );
		$this->assertSame( $existing_donation, $result->get_donation() );
	}

	#[Test]
	public function handle_throws_conflict_for_different_existing_donation_payload(): void {

		$data = new DonationCreationData(
			donation_id: EntityId::create( 5_001 ),
			campaign_id: EntityId::create( 901 ),
			amount: Amount::create( 1_000 ),
		);
		$campaign = $this->make_donation_campaign();
		$existing_donation = $this->make_pending_donation( 5_001, 901, 2_000 );

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $data->get_campaign_id() ) )
			->andReturn( $campaign );

		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->andThrow( new FakeDonationAlreadyExistsException() );

		$this->repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $data->get_donation_id() ) )
			->andReturn( $existing_donation );

		$this->event_bus->shouldNotReceive( 'publish' );

		try {
			$this->handler->handle( $data );
			$this->fail( 'Expected CreateDonationIdempotentlyConflictException to be thrown.' );
		} catch ( CreateDonationIdempotentlyConflictException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame(
				'Cannot create donation "5001": request payload does not match existing donation.',
				$exception->getMessage(),
			);
		}
	}

	#[Test]
	public function handle_throws_when_existing_donation_lookup_fails_after_duplicate_create(): void {

		$data = new DonationCreationData(
			donation_id: EntityId::create( 5_001 ),
			campaign_id: EntityId::create( 901 ),
			amount: Amount::create( 1_000 ),
		);
		$campaign = $this->make_donation_campaign();
		$exception = new FakeDonationRepositoryException();

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $data->get_campaign_id() ) )
			->andReturn( $campaign );

		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->andThrow( new FakeDonationAlreadyExistsException() );

		$this->repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $data->get_donation_id() ) )
			->andThrow( $exception );

		$this->event_bus->shouldNotReceive( 'publish' );

		try {
			$this->handler->handle( $data );
			$this->fail( 'Expected CreateDonationException to be thrown.' );
		} catch ( CreateDonationException $caught_exception ) {
			$find_donation_by_id_exception = $caught_exception->getPrevious();

			$this->assertSame( UseCaseFailureStage::Persistence, $caught_exception->get_stage() );
			$this->assertSame( 'Failed to retrieve existing donation "5001".', $caught_exception->getMessage() );
			$this->assertInstanceOf( FindDonationByIdException::class, $find_donation_by_id_exception );
			$this->assertSame( $exception, $find_donation_by_id_exception->getPrevious() );
		}
	}

	private function make_donation_campaign( bool $accepts_donations = true, string $currency_code = 'RUB' ): Campaign {

		return $this->make_campaign( 901, 'Campaign 901', $accepts_donations, $currency_code, 10_000 );
	}
}
