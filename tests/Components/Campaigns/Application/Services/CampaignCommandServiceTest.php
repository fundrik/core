<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\Commands\CreateCampaignCommand;
use Fundrik\Core\Components\Campaigns\Application\Commands\SyncCampaignFromSnapshotCommand;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignCreatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeletedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDonationsDisabledEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDonationsEnabledEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignRenamedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignSynchronizedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignTargetChangedEvent;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignCommandService;
use Fundrik\Core\Components\Campaigns\Application\UseCases\AbstractCampaignMutationHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ChangeCampaignTarget\ChangeCampaignTargetException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ChangeCampaignTarget\ChangeCampaignTargetHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign\CreateCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign\CreateCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DisableCampaignDonations\DisableCampaignDonationsException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DisableCampaignDonations\DisableCampaignDonationsHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\EnableCampaignDonations\EnableCampaignDonationsException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\EnableCampaignDonations\EnableCampaignDonationsHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign\RenameCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign\RenameCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot\SyncCampaignFromSnapshotException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot\SyncCampaignFromSnapshotHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot\SyncCampaignFromSnapshotPreconditionReason;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignFactoryException;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\InvalidCampaignTitleException;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\Amount;
use Fundrik\Core\Components\Shared\Domain\Currency;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidAmountException;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CampaignCommandService::class )]
#[UsesClass( CreateCampaignCommand::class )]
#[UsesClass( SyncCampaignFromSnapshotCommand::class )]
#[UsesClass( CreateCampaignException::class )]
#[UsesClass( SyncCampaignFromSnapshotException::class )]
#[UsesClass( SyncCampaignFromSnapshotPreconditionReason::class )]
#[UsesClass( CampaignCreatedEvent::class )]
#[UsesClass( CampaignRenamedEvent::class )]
#[UsesClass( CampaignDonationsEnabledEvent::class )]
#[UsesClass( CampaignDonationsDisabledEvent::class )]
#[UsesClass( CampaignTargetChangedEvent::class )]
#[UsesClass( CampaignDeletedEvent::class )]
#[UsesClass( CampaignSynchronizedEvent::class )]
#[UsesClass( AbstractCampaignMutationHandler::class )]
#[UsesClass( CreateCampaignHandler::class )]
#[UsesClass( SyncCampaignFromSnapshotHandler::class )]
#[UsesClass( RenameCampaignHandler::class )]
#[UsesClass( EnableCampaignDonationsHandler::class )]
#[UsesClass( DisableCampaignDonationsHandler::class )]
#[UsesClass( ChangeCampaignTargetHandler::class )]
#[UsesClass( DeleteCampaignHandler::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignFactory::class )]
#[UsesClass( CampaignTitle::class )]
#[UsesClass( CampaignTarget::class )]
#[UsesClass( Amount::class )]
#[UsesClass( Currency::class )]
#[UsesClass( CampaignMutationException::class )]
#[UsesClass( InvalidCampaignTitleException::class )]
#[UsesClass( DeleteCampaignException::class )]
#[UsesClass( EntityVersion::class )]
#[UsesClass( EntityId::class )]
final class CampaignCommandServiceTest extends MockeryTestCase {

	private CampaignRepositoryPort&MockInterface $campaign_repository;
	private DonationRepositoryPort&MockInterface $donation_repository;
	private ApplicationEventBusPort&MockInterface $event_bus;

	private CampaignCommandService $command;

	protected function setUp(): void {

		parent::setUp();

		$this->campaign_repository = Mockery::mock( CampaignRepositoryPort::class );
		$this->donation_repository = Mockery::mock( DonationRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );

		$this->command = new CampaignCommandService(
			new CreateCampaignHandler( $this->campaign_repository, $this->event_bus ),
			new CampaignFactory(),
			new SyncCampaignFromSnapshotHandler( $this->campaign_repository, $this->event_bus ),
			new RenameCampaignHandler( $this->campaign_repository, $this->event_bus ),
			new EnableCampaignDonationsHandler( $this->campaign_repository, $this->event_bus ),
			new DisableCampaignDonationsHandler( $this->campaign_repository, $this->event_bus ),
			new ChangeCampaignTargetHandler( $this->campaign_repository, $this->event_bus ),
			new DeleteCampaignHandler( $this->campaign_repository, $this->donation_repository, $this->event_bus ),
		);
	}

	#[Test]
	public function create_uses_injected_ports(): void {

		$campaign_id = EntityId::uuid7();
		$campaign = $this->make_campaign(
			id: $campaign_id->get_value(),
			title: 'New Campaign',
			accepts_donations: false,
			target_amount: 5_000,
		);
		$command = new CreateCampaignCommand(
			id: $campaign_id,
			title: 'New Campaign',
			accepts_donations: false,
			currency_code: 'RUB',
			target_amount: 5_000,
		);

		$this->campaign_repository
			->shouldReceive( 'insert' )
			->once()
			->withArgs(
				function ( Campaign $created_campaign ) use ( $campaign_id ): bool {

					$this->assertSame( $campaign_id->get_value(), $created_campaign->get_id()->get_value() );
					$this->assertSame( 'New Campaign', $created_campaign->get_title() );
					$this->assertFalse( $created_campaign->accepts_donations() );
					$this->assertSame( 'RUB', $created_campaign->get_target()->get_currency()->get_code() );
					$this->assertSame( 5_000, $created_campaign->get_target()->get_amount()?->get_value() );

					return true;
				},
			)
			->andReturn( $campaign );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( CampaignCreatedEvent::class, $campaign->get_id() ) );

		$this->command->create( $command );
	}

	#[Test]
	public function create_proxies_invalid_id_message_from_entity_id(): void {

		$this->campaign_repository
			->shouldNotReceive( 'insert' );

		$command = new CreateCampaignCommand(
			id: 'invalid-id',
			title: 'New Campaign',
			accepts_donations: false,
			currency_code: 'RUB',
			target_amount: null,
		);

		try {
			$this->command->create( $command );
			$this->fail( 'Expected CreateCampaignException to be thrown.' );
		} catch ( CreateCampaignException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame(
				'ID must be a positive integer or a valid UUID. Given: "invalid-id".',
				$exception->getMessage(),
			);
			$this->assertInstanceOf( CampaignFactoryException::class, $exception->getPrevious() );
			$this->assertInstanceOf( InvalidEntityIdException::class, $exception->getPrevious()->getPrevious() );
		}
	}

	#[Test]
	public function sync_from_snapshot_uses_injected_ports(): void {

		$campaign = $this->make_campaign( id: 55, title: 'Old Campaign', accepts_donations: false, target_amount: 1_000 );
		$campaign_id = $campaign->get_id();
		$command = new SyncCampaignFromSnapshotCommand(
			id: $campaign_id,
			expected_version: 1,
			title: 'Synchronized Campaign',
			accepts_donations: true,
			currency_code: 'RUB',
			target_amount: 5_000,
		);

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_campaign_id ): bool => $actual_campaign_id->equals( $campaign_id ),
			)
			->andReturn( $campaign );

		$this->campaign_repository
			->shouldReceive( 'update' )
			->once()
			->withArgs(
				function ( Campaign $updated_campaign ): bool {

					$this->assertSame( 'Synchronized Campaign', $updated_campaign->get_title() );
					$this->assertTrue( $updated_campaign->accepts_donations() );
					$this->assertSame( 5_000, $updated_campaign->get_target()->get_amount()?->get_value() );

					return true;
				},
			)
			->andReturnUsing( static fn ( Campaign $updated_campaign ): Campaign => $updated_campaign );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( CampaignSynchronizedEvent::class, $campaign_id ) );

		$this->command->sync_from_snapshot( $command );
	}

	#[Test]
	public function sync_from_snapshot_wraps_invalid_id_message(): void {

		$this->campaign_repository
			->shouldNotReceive( 'find_by_id' );

		$command = new SyncCampaignFromSnapshotCommand(
			id: 'invalid-id',
			expected_version: 1,
			title: 'Snapshot Campaign',
			accepts_donations: true,
			currency_code: 'RUB',
			target_amount: 500,
		);

		try {
			$this->command->sync_from_snapshot( $command );
			$this->fail( 'Expected SyncCampaignFromSnapshotException to be thrown.' );
		} catch ( SyncCampaignFromSnapshotException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame( SyncCampaignFromSnapshotPreconditionReason::SnapshotInvalid, $exception->get_reason() );
			$this->assertSame(
				'ID must be a positive integer or a valid UUID. Given: "invalid-id".',
				$exception->getMessage(),
			);
			$this->assertInstanceOf( CampaignFactoryException::class, $exception->getPrevious() );
			$this->assertInstanceOf( InvalidEntityIdException::class, $exception->getPrevious()->getPrevious() );
		}
	}

	#[Test]
	public function rename_uses_injected_ports(): void {

		$campaign = $this->make_campaign( title: 'Old title' );
		$campaign_id = $campaign->get_id();
		$new_title = 'Renamed campaign';

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_campaign_id ): bool => $actual_campaign_id->equals( $campaign_id ),
			)
			->andReturn( $campaign );

		$this->campaign_repository
			->shouldReceive( 'update' )
			->once()
			->withArgs(
				function ( Campaign $updated_campaign ) use ( $new_title ): bool {

					$this->assertSame( $new_title, $updated_campaign->get_title() );

					return true;
				},
			)
			->andReturnUsing( static fn ( Campaign $updated_campaign ): Campaign => $updated_campaign );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( CampaignRenamedEvent::class, $campaign_id ) );

		$this->command->rename( $campaign_id->get_value(), $new_title );
	}

	#[Test]
	public function rename_wraps_invalid_title_input(): void {

		$this->campaign_repository
			->shouldNotReceive( 'find_by_id' );

		try {
			$this->command->rename( 101, '   ' );
			$this->fail( 'Expected RenameCampaignException to be thrown.' );
		} catch ( RenameCampaignException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame(
				'Campaign title must not be empty or whitespace.',
				$exception->getMessage(),
			);
			$this->assertInstanceOf( InvalidCampaignTitleException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function enable_donations_uses_injected_ports(): void {

		$campaign = $this->make_campaign( accepts_donations: false );
		$campaign_id = $campaign->get_id();

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_campaign_id ): bool => $actual_campaign_id->equals( $campaign_id ),
			)
			->andReturn( $campaign );

		$this->campaign_repository
			->shouldReceive( 'update' )
			->once()
			->withArgs( static fn ( Campaign $updated_campaign ): bool => $updated_campaign->accepts_donations() )
			->andReturnUsing( static fn ( Campaign $updated_campaign ): Campaign => $updated_campaign );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( CampaignDonationsEnabledEvent::class, $campaign_id ) );

		$this->command->enable_donations( $campaign_id->get_value() );
	}

	#[Test]
	public function disable_donations_uses_injected_ports(): void {

		$campaign = $this->make_campaign( accepts_donations: true );
		$campaign_id = $campaign->get_id();

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_campaign_id ): bool => $actual_campaign_id->equals( $campaign_id ),
			)
			->andReturn( $campaign );

		$this->campaign_repository
			->shouldReceive( 'update' )
			->once()
			->withArgs( static fn ( Campaign $updated_campaign ): bool => ! $updated_campaign->accepts_donations() )
			->andReturnUsing( static fn ( Campaign $updated_campaign ): Campaign => $updated_campaign );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( CampaignDonationsDisabledEvent::class, $campaign_id ) );

		$this->command->disable_donations( $campaign_id->get_value() );
	}

	#[Test]
	public function change_target_amount_uses_injected_ports(): void {

		$campaign = $this->make_campaign( target_amount: 100 );
		$campaign_id = $campaign->get_id();

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_campaign_id ): bool => $actual_campaign_id->equals( $campaign_id ),
			)
			->andReturn( $campaign );

		$this->campaign_repository
			->shouldReceive( 'update' )
			->once()
			->withArgs(
				static fn ( Campaign $updated_campaign ): bool => $updated_campaign->get_target()->get_amount()?->get_value() === 50_000,
			)
			->andReturnUsing( static fn ( Campaign $updated_campaign ): Campaign => $updated_campaign );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( CampaignTargetChangedEvent::class, $campaign_id ) );

		$this->command->change_target_amount( $campaign_id->get_value(), 50_000 );
	}

	#[Test]
	public function change_target_amount_accepts_null_to_clear_existing_target(): void {

		$campaign = $this->make_campaign( target_amount: 100 );
		$campaign_id = $campaign->get_id();

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_campaign_id ): bool => $actual_campaign_id->equals( $campaign_id ),
			)
			->andReturn( $campaign );

		$this->campaign_repository
			->shouldReceive( 'update' )
			->once()
			->withArgs(
				static fn ( Campaign $updated_campaign ): bool => $updated_campaign->get_target()->get_amount() === null,
			)
			->andReturnUsing( static fn ( Campaign $updated_campaign ): Campaign => $updated_campaign );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( CampaignTargetChangedEvent::class, $campaign_id ) );

		$this->command->change_target_amount( $campaign_id->get_value(), null );
	}

	#[Test]
	public function change_target_amount_wraps_invalid_amount_input(): void {

		$campaign_id = EntityId::create( 101 );

		$this->campaign_repository
			->shouldNotReceive( 'find_by_id' );

		try {
			$this->command->change_target_amount( $campaign_id->get_value(), 0 );
			$this->fail( 'Expected ChangeCampaignTargetException to be thrown.' );
		} catch ( ChangeCampaignTargetException $exception ) {
			$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
			$this->assertSame( 'Amount must be a positive integer. Given: 0.', $exception->getMessage() );
			$this->assertInstanceOf( InvalidAmountException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	#[DataProvider( 'invalid_campaign_id_operation_provider' )]
	public function wraps_invalid_campaign_id_for_existing_campaign_operations(
		string $operation,
		string $exception_class,
	): void {

		$this->expectException( $exception_class );

		match ( $operation ) {
			'rename' => $this->command->rename( 'invalid-id', 'New title' ),
			'enable' => $this->command->enable_donations( 'invalid-id' ),
			'disable' => $this->command->disable_donations( 'invalid-id' ),
			'change_target' => $this->command->change_target_amount( 'invalid-id', 500 ),
			'delete' => $this->command->delete( 'invalid-id' ),
		};
	}

	public static function invalid_campaign_id_operation_provider(): array {

		return [
			'rename' => [ 'rename', RenameCampaignException::class ],
			'enable' => [ 'enable', EnableCampaignDonationsException::class ],
			'disable' => [ 'disable', DisableCampaignDonationsException::class ],
			'change_target' => [ 'change_target', ChangeCampaignTargetException::class ],
			'delete' => [ 'delete', DeleteCampaignException::class ],
		];
	}

	#[Test]
	public function delete_uses_injected_ports(): void {

		$campaign_id = $this->make_campaign()->get_id();

		$this->donation_repository
			->shouldReceive( 'exists_by_campaign_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_campaign_id ): bool => $actual_campaign_id->equals( $campaign_id ),
			)
			->andReturn( false );

		$this->campaign_repository
			->shouldReceive( 'delete' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_campaign_id ): bool => $actual_campaign_id->equals( $campaign_id ),
			);

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->withArgs( $this->event_of_type( CampaignDeletedEvent::class, $campaign_id ) );

		$this->command->delete( $campaign_id->get_value() );
	}

	private function event_of_type( string $event_class, EntityId $campaign_id ): callable {

		return function ( object $event ) use ( $event_class, $campaign_id ): bool {

			$this->assertInstanceOf( $event_class, $event );
			$this->assertTrue( $event->get_campaign_id()->equals( $campaign_id ) );

			return true;
		};
	}
}
