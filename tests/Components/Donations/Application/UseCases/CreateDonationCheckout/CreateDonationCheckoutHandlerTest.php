<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\UseCases\CreateDonationCheckout;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Donations\Application\Events\DonationCreatedEvent;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayCheckoutRequest;
use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayCheckoutResult;
use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayPort;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\DonationCreationData;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutData;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutResult;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently\CreateDonationIdempotentlyHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently\CreateDonationIdempotentlyResult;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently\CreateDonationIdempotentlyStatus;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Application\Url;
use Fundrik\Core\Components\Shared\Domain\Amount;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Tests\Fixtures\FakeDonationAlreadyExistsException;
use Fundrik\Core\Tests\Fixtures\FakeDonationGatewayException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CreateDonationCheckoutHandler::class )]
#[UsesClass( CreateDonationCheckoutData::class )]
#[UsesClass( DonationGatewayCheckoutRequest::class )]
#[UsesClass( DonationGatewayCheckoutResult::class )]
#[UsesClass( CreateDonationCheckoutResult::class )]
#[UsesClass( CreateDonationCheckoutException::class )]
#[UsesClass( CreateDonationException::class )]
#[UsesClass( DonationCreatedEvent::class )]
#[UsesClass( DonationCreationData::class )]
#[UsesClass( CreateDonationIdempotentlyHandler::class )]
#[UsesClass( CreateDonationIdempotentlyResult::class )]
#[UsesClass( CreateDonationIdempotentlyStatus::class )]
#[UsesClass( FindDonationByIdHandler::class )]
#[UsesClass( Donation::class )]
#[UsesClass( DonationFactory::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( Url::class )]
#[UsesClass( EntityId::class )]
#[UsesClass( Amount::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( Money::class )]
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
			->withArgs(
				function ( DonationGatewayCheckoutRequest $request ): bool {

					$this->assertSame( 5_001, $request->get_donation_id() );
					$this->assertSame( 901, $request->get_campaign_id() );
					$this->assertSame( 1_000, $request->get_amount() );
					$this->assertSame( 'RUB', $request->get_currency_code() );
					$this->assertSame( 'https://fundrik.test/success', $request->get_success_url() );
					$this->assertSame( 'https://fundrik.test/cancel', $request->get_cancel_url() );

					return true;
				},
			)
			->andReturn( new DonationGatewayCheckoutResult( 'https://gateway.test/checkout/5001' ) );

		$result = $this->handler->handle( $data );

		$this->assertTrue( EntityId::create( 5_001 )->equals( $result->get_donation_id() ) );
		$this->assertTrue( EntityId::create( 901 )->equals( $result->get_campaign_id() ) );
		$this->assertSame( 1_000, $result->get_amount() );
		$this->assertSame( 'RUB', $result->get_currency_code() );
		$this->assertSame( 1_000, $result->get_money()->get_amount()->get_value() );
		$this->assertSame( 'RUB', $result->get_money()->get_currency()->get_code() );
		$this->assertSame( 'https://gateway.test/checkout/5001', $result->get_redirect_url()->get_value() );
	}

	#[Test]
	public function handle_replays_existing_matching_donation(): void {

		$campaign = $this->make_campaign( 901, 'Campaign 901', true, 'RUB', 10_000 );
		$existing_donation = $this->make_pending_donation( 5_001, 901, 1_000, 'RUB' );
		$data = $this->make_checkout_data(
			donation_id: 5_001,
			campaign_id: 901,
			amount: 1_000,
			success_url: 'https://fundrik.test/success',
			cancel_url: 'https://fundrik.test/cancel',
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
		$this->gateway
			->shouldReceive( 'create_checkout' )
			->once()
			->andReturn( new DonationGatewayCheckoutResult( 'https://gateway.test/checkout/5001' ) );

		$result = $this->handler->handle( $data );

		$this->assertSame( 'https://gateway.test/checkout/5001', $result->get_redirect_url()->get_value() );
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
	): CreateDonationCheckoutData {

		return new CreateDonationCheckoutData(
			donation_creation_data: new DonationCreationData(
				donation_id: EntityId::create( $donation_id ),
				campaign_id: EntityId::create( $campaign_id ),
				amount: Amount::create( $amount ),
			),
			success_url: Url::create( $success_url ),
			cancel_url: Url::create( $cancel_url ),
		);
	}
}
