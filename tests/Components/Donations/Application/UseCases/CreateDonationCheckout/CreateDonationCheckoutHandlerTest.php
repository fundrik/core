<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\UseCases\CreateDonationCheckout;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Donations\Application\Events\DonationCreatedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationPendingEvent;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayCheckoutRequest;
use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayCheckoutResult;
use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayPort;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationAlreadyExistsException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\DonationCreationData;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutData;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutNotFoundException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutResult;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently\CreateDonationIdempotentlyHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently\CreateDonationIdempotentlyResult;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Donations\Domain\PaymentId;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Application\Url;
use Fundrik\Core\Components\Shared\Domain\Amount;
use Fundrik\Core\Components\Shared\Domain\Currency;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Tests\Fixtures\FakeApplicationEventBusException;
use Fundrik\Core\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\Core\Tests\Fixtures\FakeDonationAlreadyExistsException;
use Fundrik\Core\Tests\Fixtures\FakeDonationGatewayException;
use Fundrik\Core\Tests\Fixtures\FakeDonationNotFoundException;
use Fundrik\Core\Tests\Fixtures\FakeDonationRepositoryException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CreateDonationCheckoutHandler::class )]
#[CoversClass( CreateDonationCheckoutData::class )]
#[CoversClass( CreateDonationCheckoutResult::class )]
#[CoversClass( CreateDonationCheckoutException::class )]
#[CoversClass( CreateDonationCheckoutNotFoundException::class )]
#[CoversClass( DonationGatewayCheckoutRequest::class )]
#[CoversClass( DonationGatewayCheckoutResult::class )]
#[UsesClass( CreateDonationHandler::class )]
#[UsesClass( CreateDonationException::class )]
#[UsesClass( CreateDonationAlreadyExistsException::class )]
#[UsesClass( DonationCreatedEvent::class )]
#[UsesClass( DonationPendingEvent::class )]
#[UsesClass( DonationCreationData::class )]
#[UsesClass( CreateDonationIdempotentlyHandler::class )]
#[UsesClass( CreateDonationIdempotentlyResult::class )]
#[UsesClass( FindDonationByIdHandler::class )]
#[UsesClass( Donation::class )]
#[UsesClass( DonationStatus::class )]
#[UsesClass( DonationFactory::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( Url::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Amount::class )]
#[UsesClass( Currency::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( Money::class )]
#[UsesClass( PaymentId::class )]
#[UsesClass( UseCaseFailureStage::class )]
final class CreateDonationCheckoutHandlerTest extends MockeryTestCase {

	private CampaignRepositoryPort&MockInterface $campaigns;
	private DonationRepositoryPort&MockInterface $repository;
	private ApplicationEventBusPort&MockInterface $event_bus;
	private DonationGatewayPort&MockInterface $gateway;

	private CreateDonationCheckoutHandler $handler;

	protected function setUp(): void {

		parent::setUp();

		$this->campaigns = Mockery::mock( CampaignRepositoryPort::class );
		$this->repository = Mockery::mock( DonationRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );
		$this->gateway = Mockery::mock( DonationGatewayPort::class );

		$create_donation = new CreateDonationHandler(
			$this->campaigns,
			new DonationFactory(),
			$this->repository,
			$this->event_bus,
		);

		$this->handler = new CreateDonationCheckoutHandler(
			new CreateDonationIdempotentlyHandler(
				$create_donation,
				new FindDonationByIdHandler( $this->repository ),
			),
			$this->gateway,
			$this->repository,
			$this->event_bus,
		);
	}

	#[Test]
	public function handle_creates_checkout_for_new_donation(): void {

		$campaign = $this->make_campaign( 901, 'Campaign 901', true, 'RUB', 10_000 );
		$data = $this->make_checkout_data(
			donation_id: 5_001,
			campaign_id: 901,
			amount: 1_000,
			success_url: 'https://fundrik.test/success',
			cancel_url: 'https://fundrik.test/cancel',
			payment_description: 'Donation for campaign 901',
		);

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( $campaign );

		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->andReturnUsing( static fn ( Donation $donation ): Donation => $donation );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( static fn ( object $event ): bool => $event instanceof DonationCreatedEvent )
			->ordered();
		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs(
				static fn ( object $event ): bool => $event instanceof DonationPendingEvent
					&& $event->get_donation_id()->equals( EntityId::create( 5_001 ) )
					&& $event->get_payment_id()->equals( PaymentId::create( 'pay_5001' ) ),
			)
			->ordered();

		$this->repository
			->shouldReceive( 'update' )
			->once()
			->withArgs(
				function ( Donation $donation ): bool {

					$this->assertSame( DonationStatus::Pending, $donation->get_status() );
					$this->assertSame( 'pay_5001', $donation->get_payment_id()?->get_value() );

					return true;
				},
			)
			->andReturnUsing( static fn ( Donation $donation ): Donation => $donation );

		$this->gateway
			->shouldReceive( 'create_checkout' )
			->once()
			->withArgs(
				function ( DonationGatewayCheckoutRequest $request ): bool {

					$this->assertTrue( EntityId::create( 5_001 )->equals( $request->get_donation_id() ) );
					$this->assertTrue( EntityId::create( 901 )->equals( $request->get_campaign_id() ) );
					$this->assertTrue( Money::create( 1_000, 'RUB' )->equals( $request->get_money() ) );
					// phpcs:ignore SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
					$this->assertTrue( Url::create( 'https://fundrik.test/success' )->equals( $request->get_success_url() ) );
					// phpcs:ignore SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall
					$this->assertTrue( Url::create( 'https://fundrik.test/cancel' )->equals( $request->get_cancel_url() ) );
					$this->assertSame( 'Donation for campaign 901', $request->get_payment_description() );

					return true;
				},
			)->andReturn(
				new DonationGatewayCheckoutResult(
					PaymentId::create( 'pay_5001' ),
					Url::create( 'https://gateway.test/checkout/5001' ),
				),
			);

		$result = $this->handler->handle( $data );

		$this->assertTrue( EntityId::create( 5_001 )->equals( $result->get_donation_id() ) );
		$this->assertTrue( EntityId::create( 901 )->equals( $result->get_campaign_id() ) );
		$this->assertSame( 1_000, $result->get_amount() );
		$this->assertSame( 'RUB', $result->get_currency_code() );
		$this->assertSame( 1_000, $result->get_money()->get_amount()->get_value() );
		$this->assertSame( 'RUB', $result->get_money()->get_currency()->get_code() );
		$this->assertSame( 'pay_5001', $result->get_payment_id()->get_value() );
		$this->assertSame( 'https://gateway.test/checkout/5001', $result->get_redirect_url()->get_value() );
	}

	#[Test]
	public function handle_replays_existing_matching_donation(): void {

		$campaign = $this->make_campaign( 901, 'Campaign 901', true, 'RUB', 10_000 );
		$existing_donation = $this->make_pending_donation( 5_001, 901, 1_000, 'RUB', 'pay_5001' );
		$data = $this->make_checkout_data(
			donation_id: 5_001,
			campaign_id: 901,
			amount: 1_000,
			success_url: 'https://fundrik.test/success',
			cancel_url: 'https://fundrik.test/cancel',
			payment_description: 'Donation for campaign 901',
		);

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( $campaign );

		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->andThrow( new FakeDonationAlreadyExistsException() );

		$this->repository
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( $existing_donation );

		$this->event_bus->shouldNotReceive( 'publish' );
		$this->repository->shouldNotReceive( 'update' );
		$this->gateway
			->shouldReceive( 'create_checkout' )
			->once()
			->andReturn(
				new DonationGatewayCheckoutResult(
					PaymentId::create( 'pay_5001' ),
					Url::create( 'https://gateway.test/checkout/5001' ),
				),
			);

		$result = $this->handler->handle( $data );

		$this->assertSame( 'https://gateway.test/checkout/5001', $result->get_redirect_url()->get_value() );
	}

	#[Test]
	public function handle_attaches_payment_to_existing_created_donation(): void {

		$data = $this->make_checkout_data(
			donation_id: 5_001,
			campaign_id: 901,
			amount: 1_000,
			success_url: 'https://fundrik.test/success',
			cancel_url: 'https://fundrik.test/cancel',
			payment_description: 'Donation for campaign 901',
		);
		$this->campaigns->shouldReceive( 'find_by_id' )->once()->andReturn( $this->make_campaign( id: 901 ) );
		$this->repository->shouldReceive( 'insert' )->once()->andThrow( new FakeDonationAlreadyExistsException() );
		$this->repository
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( $this->make_created_donation() );
		$this->gateway
			->shouldReceive( 'create_checkout' )
			->once()
			->andReturn(
				new DonationGatewayCheckoutResult(
					PaymentId::create( 'pay_5001' ),
					Url::create( 'https://gateway.test/checkout/5001' ),
				),
			);
		$this->repository
			->shouldReceive( 'update' )
			->once()
			->withArgs(
				static fn ( Donation $donation ): bool => $donation->get_status() === DonationStatus::Pending
					&& $donation->get_payment_id()?->get_value() === 'pay_5001',
			)
			->andReturnUsing( static fn ( Donation $donation ): Donation => $donation );
		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( static fn ( object $event ): bool => $event instanceof DonationPendingEvent );

		$result = $this->handler->handle( $data );

		$this->assertSame( 'pay_5001', $result->get_payment_id()->get_value() );
	}

	#[Test]
	public function handle_rejects_a_different_payment_for_existing_donation(): void {

		$data = $this->make_checkout_data(
			donation_id: 5_001,
			campaign_id: 901,
			amount: 1_000,
			success_url: 'https://fundrik.test/success',
			cancel_url: 'https://fundrik.test/cancel',
			payment_description: 'Donation for campaign 901',
		);

		$this->campaigns->shouldReceive( 'find_by_id' )->once()->andReturn( $this->make_campaign( id: 901 ) );
		$this->repository->shouldReceive( 'insert' )->once()->andThrow( new FakeDonationAlreadyExistsException() );
		$this->repository
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( $this->make_pending_donation( 5_001, 901, payment_id: 'pay_original' ) );
		$this->gateway
			->shouldReceive( 'create_checkout' )
			->once()
			->andReturn(
				new DonationGatewayCheckoutResult(
					PaymentId::create( 'pay_different' ),
					Url::create( 'https://gateway.test/checkout/5001' ),
				),
			);
		$this->repository->shouldNotReceive( 'update' );
		$this->event_bus->shouldNotReceive( 'publish' );

		try {
			$this->handler->handle( $data );
			$this->fail( 'Expected CreateDonationCheckoutException to be thrown.' );
		} catch ( CreateDonationCheckoutException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame(
				'Cannot create checkout for donation "5001": donation cannot await payment.',
				$exception->getMessage(),
			);
			$this->assertInstanceOf( \DomainException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function handle_wraps_payment_persistence_failure(): void {

		$data = $this->make_checkout_data(
			donation_id: 5_001,
			campaign_id: 901,
			amount: 1_000,
			success_url: 'https://fundrik.test/success',
			cancel_url: 'https://fundrik.test/cancel',
			payment_description: 'Donation for campaign 901',
		);

		$this->campaigns->shouldReceive( 'find_by_id' )->once()->andReturn( $this->make_campaign( id: 901 ) );
		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->andReturnUsing( static fn ( Donation $donation ): Donation => $donation );
		$this->event_bus->shouldReceive( 'publish' )->once();
		$this->gateway
			->shouldReceive( 'create_checkout' )
			->once()
			->andReturn(
				new DonationGatewayCheckoutResult(
					PaymentId::create( 'pay_5001' ),
					Url::create( 'https://gateway.test/checkout/5001' ),
				),
			);
		$this->repository
			->shouldReceive( 'update' )
			->once()
			->andThrow( new FakeDonationRepositoryException() );

		try {
			$this->handler->handle( $data );
			$this->fail( 'Expected CreateDonationCheckoutException to be thrown.' );
		} catch ( CreateDonationCheckoutException $exception ) {
			$this->assertSame( UseCaseFailureStage::Persistence, $exception->get_stage() );
			$this->assertSame( 'Failed to attach payment to donation "5001".', $exception->getMessage() );
			$this->assertInstanceOf( FakeDonationRepositoryException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function handle_throws_specific_exception_when_donation_disappears_before_payment_update(): void {

		$data = $this->make_checkout_data(
			donation_id: 5_001,
			campaign_id: 901,
			amount: 1_000,
			success_url: 'https://fundrik.test/success',
			cancel_url: 'https://fundrik.test/cancel',
			payment_description: 'Donation for campaign 901',
		);
		$not_found = new FakeDonationNotFoundException();

		$this->campaigns->shouldReceive( 'find_by_id' )->once()->andReturn( $this->make_campaign( id: 901 ) );
		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->andReturnUsing( static fn ( Donation $donation ): Donation => $donation );
		$this->event_bus->shouldReceive( 'publish' )->once();
		$this->gateway
			->shouldReceive( 'create_checkout' )
			->once()
			->andReturn(
				new DonationGatewayCheckoutResult(
					PaymentId::create( 'pay_5001' ),
					Url::create( 'https://gateway.test/checkout/5001' ),
				),
			);
		$this->repository
			->shouldReceive( 'update' )
			->once()
			->andThrow( $not_found );

		try {
			$this->handler->handle( $data );
			$this->fail( 'Expected CreateDonationCheckoutNotFoundException to be thrown.' );
		} catch ( CreateDonationCheckoutNotFoundException $exception ) {
			$this->assertSame( UseCaseFailureStage::Persistence, $exception->get_stage() );
			$this->assertSame(
				'Cannot await payment for donation "5001": donation does not exist.',
				$exception->getMessage(),
			);
			$this->assertSame( $not_found, $exception->getPrevious() );
		}
	}

	#[Test]
	public function handle_reports_event_failure_after_payment_was_attached(): void {

		$data = $this->make_checkout_data(
			donation_id: 5_001,
			campaign_id: 901,
			amount: 1_000,
			success_url: 'https://fundrik.test/success',
			cancel_url: 'https://fundrik.test/cancel',
			payment_description: 'Donation for campaign 901',
		);

		$this->campaigns->shouldReceive( 'find_by_id' )->once()->andReturn( $this->make_campaign( id: 901 ) );
		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->andReturnUsing( static fn ( Donation $donation ): Donation => $donation );
		$this->gateway
			->shouldReceive( 'create_checkout' )
			->once()
			->andReturn(
				new DonationGatewayCheckoutResult(
					PaymentId::create( 'pay_5001' ),
					Url::create( 'https://gateway.test/checkout/5001' ),
				),
			);
		$this->repository
			->shouldReceive( 'update' )
			->once()
			->andReturnUsing( static fn ( Donation $donation ): Donation => $donation );
		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( static fn ( object $event ): bool => $event instanceof DonationCreatedEvent )
			->ordered();
		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( static fn ( object $event ): bool => $event instanceof DonationPendingEvent )
			->andThrow( new FakeApplicationEventBusException() )
			->ordered();

		try {
			$this->handler->handle( $data );
			$this->fail( 'Expected CreateDonationCheckoutException to be thrown.' );
		} catch ( CreateDonationCheckoutException $exception ) {
			$this->assertSame( UseCaseFailureStage::EventPublish, $exception->get_stage() );
			$this->assertSame(
				'Donation "5001" was moved to pending status, but publishing the pending event failed.',
				$exception->getMessage(),
			);
			$this->assertInstanceOf( FakeApplicationEventBusException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	#[DataProvider( 'checkout_ineligible_status_provider' )]
	public function handle_rejects_checkout_for_ineligible_donation( DonationStatus $status ): void {

		$campaign = $this->make_campaign( 901, 'Campaign 901', true, 'RUB', 10_000 );
		$existing_donation = ( new DonationFactory() )->create_from_primitives(
			id: 5_001,
			version: 1,
			campaign_id: 901,
			amount: 1_000,
			currency_code: 'RUB',
			status: $status->value,
			payment_id: 'pay_5001',
		);
		$data = $this->make_checkout_data(
			donation_id: 5_001,
			campaign_id: 901,
			amount: 1_000,
			success_url: 'https://fundrik.test/success',
			cancel_url: 'https://fundrik.test/cancel',
			payment_description: 'Donation for campaign 901',
		);

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( $campaign );

		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->andThrow( new FakeDonationAlreadyExistsException() );

		$this->repository
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( $existing_donation );

		$this->event_bus->shouldNotReceive( 'publish' );
		$this->gateway->shouldNotReceive( 'create_checkout' );

		try {
			$this->handler->handle( $data );
			$this->fail( 'Expected CreateDonationCheckoutException to be thrown.' );
		} catch ( CreateDonationCheckoutException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame( 0, $exception->getCode() );
			$this->assertSame(
				'Cannot create checkout for donation "5001": donation is neither created nor pending.',
				$exception->getMessage(),
			);
		}
	}

	public static function checkout_ineligible_status_provider(): array {

		return [
			'succeeded' => [ DonationStatus::Succeeded ],
			'rejected' => [ DonationStatus::Rejected ],
			'refunded' => [ DonationStatus::Refunded ],
		];
	}

	#[Test]
	public function handle_wraps_donation_preparation_failure(): void {

		$data = $this->make_checkout_data(
			donation_id: 5_001,
			campaign_id: 901,
			amount: 1_000,
			success_url: 'https://fundrik.test/success',
			cancel_url: 'https://fundrik.test/cancel',
			payment_description: 'Donation for campaign 901',
		);
		$campaign_exception = new FakeCampaignRepositoryException();

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->andThrow( $campaign_exception );
		$this->repository->shouldNotReceive( 'insert' );
		$this->event_bus->shouldNotReceive( 'publish' );
		$this->gateway->shouldNotReceive( 'create_checkout' );

		try {
			$this->handler->handle( $data );
			$this->fail( 'Expected CreateDonationCheckoutException to be thrown.' );
		} catch ( CreateDonationCheckoutException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame(
				'Cannot create checkout for donation "5001": donation could not be prepared.',
				$exception->getMessage(),
			);
			$this->assertInstanceOf( CreateDonationException::class, $exception->getPrevious() );
			$this->assertSame( $campaign_exception, $exception->getPrevious()->getPrevious() );
		}
	}

	#[Test]
	public function handle_wraps_gateway_failure(): void {

		$campaign = $this->make_campaign( 901, 'Campaign 901', true, 'RUB', 10_000 );
		$data = $this->make_checkout_data(
			donation_id: 5_001,
			campaign_id: 901,
			amount: 1_000,
			success_url: 'https://fundrik.test/success',
			cancel_url: 'https://fundrik.test/cancel',
			payment_description: 'Donation for campaign 901',
		);

		$this->campaigns
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( $campaign );

		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->andReturnUsing( static fn ( Donation $donation ): Donation => $donation );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once();

		$this->gateway
			->shouldReceive( 'create_checkout' )
			->once()
			->andThrow( new FakeDonationGatewayException() );

		try {
			$this->handler->handle( $data );
			$this->fail( 'Expected CreateDonationCheckoutException to be thrown.' );
		} catch ( CreateDonationCheckoutException $exception ) {
			$this->assertSame( UseCaseFailureStage::External, $exception->get_stage() );
			$this->assertSame( 'Failed to create checkout for donation "5001".', $exception->getMessage() );
		}
	}

	private function make_checkout_data(
		int|string $donation_id,
		int|string $campaign_id,
		int $amount,
		string $success_url,
		string $cancel_url,
		string $payment_description,
	): CreateDonationCheckoutData {

		return new CreateDonationCheckoutData(
			donation_creation_data: new DonationCreationData(
				donation_id: EntityId::create( $donation_id ),
				campaign_id: EntityId::create( $campaign_id ),
				amount: Amount::create( $amount ),
			),
			success_url: Url::create( $success_url ),
			cancel_url: Url::create(
				$cancel_url,
			),
			payment_description: $payment_description,
		);
	}
}
