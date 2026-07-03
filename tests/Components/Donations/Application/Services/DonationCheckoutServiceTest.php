<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Donations\Application\Commands\CreateDonationCheckoutCommand;
use Fundrik\Core\Components\Donations\Application\Commands\DonationPaymentEvent;
use Fundrik\Core\Components\Donations\Application\Commands\DonationPaymentEventType;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRead\DonationReadPort;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayCheckoutRequest;
use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayCheckoutResult;
use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayPort;
use Fundrik\Core\Components\Donations\Application\Services\DonationCheckoutService;
use Fundrik\Core\Components\Donations\Application\Services\DonationCommandService;
use Fundrik\Core\Components\Donations\Application\Services\DonationQueryService;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\DonationCreationData;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutData;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently\CreateDonationIdempotentlyHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutResult;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentEvent\ProcessDonationPaymentEventException;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentEvent\ProcessDonationPaymentEventHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentEvent\ProcessDonationPaymentEventResult;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentEvent\ProcessDonationPaymentEventResultStatus;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadDonationById\ReadDonationByIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadPaginatedDonations\ReadPaginatedDonationsHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RejectDonation\RejectDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\SucceedDonation\SucceedDonationHandler;
use Fundrik\Core\Components\Donations\Domain\Donation as DonationEntity;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Exceptions\InvalidUrlException;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Application\Url;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( DonationCheckoutService::class )]
#[UsesClass( CreateDonationCheckoutCommand::class )]
#[UsesClass( DonationPaymentEvent::class )]
#[UsesClass( DonationPaymentEventType::class )]
#[UsesClass( CreateDonationCheckoutHandler::class )]
#[UsesClass( CreateDonationCheckoutData::class )]
#[UsesClass( CreateDonationCheckoutResult::class )]
#[UsesClass( CreateDonationCheckoutException::class )]
#[UsesClass( DonationCreationData::class )]
#[UsesClass( InvalidUrlException::class )]
#[UsesClass( ProcessDonationPaymentEventHandler::class )]
#[UsesClass( ProcessDonationPaymentEventResult::class )]
#[UsesClass( ProcessDonationPaymentEventResultStatus::class )]
#[UsesClass( Url::class )]
#[UsesClass( UseCaseFailureStage::class )]
final class DonationCheckoutServiceTest extends MockeryTestCase {

	private CampaignRepositoryPort&MockInterface $campaigns;
	private DonationRepositoryPort&MockInterface $repository;
	private DonationReadPort&MockInterface $donation_read;
	private ApplicationEventBusPort&MockInterface $event_bus;
	private DonationGatewayPort&MockInterface $gateway;

	private DonationCheckoutService $service;

	protected function setUp(): void {

		parent::setUp();

		$this->campaigns = Mockery::mock( CampaignRepositoryPort::class );
		$this->repository = Mockery::mock( DonationRepositoryPort::class );
		$this->donation_read = Mockery::mock( DonationReadPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );
		$this->gateway = Mockery::mock( DonationGatewayPort::class );

		$create_donation = new CreateDonationHandler(
			$this->campaigns,
			new DonationFactory(),
			$this->repository,
			$this->event_bus,
		);

		$create_checkout = new CreateDonationCheckoutHandler(
			new CreateDonationIdempotentlyHandler(
				$create_donation,
				new FindDonationByIdHandler( $this->repository ),
			),
			$this->gateway,
		);

		$donation_command = new DonationCommandService(
			$create_donation,
			new SucceedDonationHandler( $this->repository, $this->event_bus ),
			new RejectDonationHandler( $this->repository, $this->event_bus ),
			new RefundDonationHandler( $this->repository, $this->event_bus ),
		);
		$donation_query = new DonationQueryService(
			new ReadDonationByIdHandler( $this->donation_read ),
			new ReadPaginatedDonationsHandler( $this->donation_read ),
		);

		$this->service = new DonationCheckoutService(
			$create_checkout,
			new ProcessDonationPaymentEventHandler(
				$donation_command,
				$donation_query,
			),
		);
	}

	#[Test]
	public function create_checkout_returns_gateway_redirect_url(): void {

		$campaign = $this->make_campaign( 901, 'Campaign 901', true, 'RUB', 10_000 );
		$command = new CreateDonationCheckoutCommand(
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
			->andReturnUsing( static fn ( DonationEntity $donation ): DonationEntity => $donation );

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

		$result = $this->service->create_checkout( $command );

		$this->assertSame( 5_001, $result->get_donation_id() );
		$this->assertSame( 901, $result->get_campaign_id() );
		$this->assertSame( 1_000, $result->get_amount() );
		$this->assertSame( 'RUB', $result->get_currency_code() );
		$this->assertSame( 'https://gateway.test/checkout/5001', $result->get_redirect_url() );
	}

	#[Test]
	public function create_checkout_wraps_invalid_donation_id(): void {

		$this->campaigns->shouldNotReceive( 'find_by_id' );
		$this->repository->shouldNotReceive( 'insert' );
		$this->gateway->shouldNotReceive( 'create_checkout' );

		$command = new CreateDonationCheckoutCommand(
			donation_id: -1,
			campaign_id: 901,
			amount: 1_000,
			success_url: 'https://fundrik.test/success',
			cancel_url: 'https://fundrik.test/cancel',
		);

		try {
			$this->service->create_checkout( $command );
			$this->fail( 'Expected CreateDonationCheckoutException to be thrown.' );
		} catch ( CreateDonationCheckoutException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame( 'ID must be a positive integer or a valid UUID. Given: -1.', $exception->getMessage() );
		}
	}

	#[Test]
	public function create_checkout_wraps_invalid_success_url(): void {

		$this->campaigns->shouldNotReceive( 'find_by_id' );
		$this->repository->shouldNotReceive( 'insert' );
		$this->gateway->shouldNotReceive( 'create_checkout' );

		$command = new CreateDonationCheckoutCommand(
			donation_id: 5_001,
			campaign_id: 901,
			amount: 1_000,
			success_url: 'not-a-url',
			cancel_url: 'https://fundrik.test/cancel',
		);

		try {
			$this->service->create_checkout( $command );
			$this->fail( 'Expected CreateDonationCheckoutException to be thrown.' );
		} catch ( CreateDonationCheckoutException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame( 'URL must be a valid URL. Given: "not-a-url".', $exception->getMessage() );
		}
	}

	#[Test]
	public function process_payment_event_applies_success_event_for_pending_donation(): void {

		$donation_id = EntityId::create( 5_001 );

		$this->donation_read
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual ): bool => $actual->equals( $donation_id ),
			)
			->andReturn( $this->make_donation_read_model( id: 5_001, status: 'pending' ) );

		$this->repository
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual ): bool => $actual->equals( $donation_id ),
			)
			->andReturn( $this->make_pending_donation( 5_001, 901 ) );

		$this->repository
			->shouldReceive( 'update' )
			->once()
			->withArgs(
				static fn ( DonationEntity $donation ): bool => $donation->get_status() === DonationStatus::Succeeded,
			)
			->andReturnUsing( static fn ( DonationEntity $donation ): DonationEntity => $donation );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once();

		$result = $this->service->process_payment_event(
			new DonationPaymentEvent( 5_001, DonationPaymentEventType::Succeeded ),
		);

		$this->assertSame( ProcessDonationPaymentEventResultStatus::Applied, $result->get_status() );
	}
}
