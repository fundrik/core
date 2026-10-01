<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\UseCases;

use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationPreconditionReason;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ChangeCampaignTarget\ChangeCampaignTargetException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ChangeCampaignTarget\ChangeCampaignTargetNotFoundException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign\CreateCampaignAlreadyExistsException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign\CreateCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignNotFoundException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignPreconditionReason;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DisableCampaignDonations\DisableCampaignDonationsException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DisableCampaignDonations\DisableCampaignDonationsNotFoundException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\EnableCampaignDonations\EnableCampaignDonationsException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\EnableCampaignDonations\EnableCampaignDonationsNotFoundException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ReadCampaignById\ReadCampaignByIdException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign\RenameCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign\RenameCampaignNotFoundException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot\SyncCampaignFromSnapshotException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot\SyncCampaignFromSnapshotNotFoundException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot\SyncCampaignFromSnapshotPreconditionReason;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;

#[CoversClass( CampaignMutationException::class )]
#[CoversClass( ChangeCampaignTargetException::class )]
#[CoversClass( ChangeCampaignTargetNotFoundException::class )]
#[CoversClass( CreateCampaignException::class )]
#[CoversClass( CreateCampaignAlreadyExistsException::class )]
#[CoversClass( DeleteCampaignException::class )]
#[CoversClass( DeleteCampaignNotFoundException::class )]
#[CoversClass( DisableCampaignDonationsException::class )]
#[CoversClass( DisableCampaignDonationsNotFoundException::class )]
#[CoversClass( EnableCampaignDonationsException::class )]
#[CoversClass( EnableCampaignDonationsNotFoundException::class )]
#[CoversClass( FindCampaignByIdException::class )]
#[CoversClass( ReadCampaignByIdException::class )]
#[CoversClass( RenameCampaignException::class )]
#[CoversClass( RenameCampaignNotFoundException::class )]
#[CoversClass( SyncCampaignFromSnapshotException::class )]
#[CoversClass( SyncCampaignFromSnapshotNotFoundException::class )]
#[UsesClass( UseCaseFailureStage::class )]
#[UsesClass( CampaignMutationPreconditionReason::class )]
#[UsesClass( DeleteCampaignPreconditionReason::class )]
#[UsesClass( SyncCampaignFromSnapshotPreconditionReason::class )]
#[UsesClass( EntityId::class )]
final class UseCaseExceptionsTest extends FundrikTestCase {

	#[Test]
	public function campaign_mutation_exception_exposes_stage_reason_and_previous(): void {

		$previous = new RuntimeException( 'Repository failure' );
		$exception = new CampaignMutationException(
			stage: UseCaseFailureStage::Precondition,
			message: 'Mutation failed.',
			previous: $previous,
			reason: CampaignMutationPreconditionReason::CampaignNotFound,
		);

		$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
		$this->assertSame( CampaignMutationPreconditionReason::CampaignNotFound, $exception->get_reason() );
		$this->assertSame( 'Mutation failed.', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertSame( $previous, $exception->getPrevious() );
	}

	#[Test]
	public function create_campaign_exception_exposes_stage_and_previous(): void {

		$previous = new RuntimeException( 'Repository failure' );
		$exception = new CreateCampaignException(
			stage: UseCaseFailureStage::Persistence,
			message: 'Create failed.',
			previous: $previous,
		);

		$this->assertSame( UseCaseFailureStage::Persistence, $exception->get_stage() );
		$this->assertSame( 'Create failed.', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertSame( $previous, $exception->getPrevious() );
	}

	#[Test]
	public function delete_campaign_exception_exposes_stage_reason_and_previous(): void {

		$previous = new RuntimeException( 'Donation lookup failure' );
		$exception = new DeleteCampaignException(
			stage: UseCaseFailureStage::Precondition,
			message: 'Delete failed.',
			previous: $previous,
			reason: DeleteCampaignPreconditionReason::DonationsLookupFailed,
		);

		$this->assertSame( UseCaseFailureStage::Precondition, $exception->get_stage() );
		$this->assertSame( DeleteCampaignPreconditionReason::DonationsLookupFailed, $exception->get_reason() );
		$this->assertSame( 'Delete failed.', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertSame( $previous, $exception->getPrevious() );
	}

	#[Test]
	public function sync_exception_exposes_stage_reason_and_previous(): void {

		$previous = new RuntimeException( 'Snapshot failure' );
		$exception = new SyncCampaignFromSnapshotException(
			stage: UseCaseFailureStage::EventPublish,
			message: 'Sync failed.',
			previous: $previous,
			reason: SyncCampaignFromSnapshotPreconditionReason::SnapshotInvalid,
		);

		$this->assertSame( UseCaseFailureStage::EventPublish, $exception->get_stage() );
		$this->assertSame( SyncCampaignFromSnapshotPreconditionReason::SnapshotInvalid, $exception->get_reason() );
		$this->assertSame( 'Sync failed.', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertSame( $previous, $exception->getPrevious() );
	}

	#[Test]
	public function create_already_exists_exception_formats_campaign_id_and_preserves_previous(): void {

		$id = EntityId::create( 123 );
		$previous = new RuntimeException( 'Duplicate campaign' );
		$exception = new CreateCampaignAlreadyExistsException( $id, $previous );

		$this->assertInstanceOf( CreateCampaignException::class, $exception );
		$this->assertSame( UseCaseFailureStage::Persistence, $exception->get_stage() );
		$this->assertSame( 'Cannot create campaign "123": campaign already exists.', $exception->getMessage() );
		$this->assertSame( $previous, $exception->getPrevious() );
	}

	#[Test]
	#[DataProvider( 'not_found_exception_provider' )]
	public function not_found_exceptions_format_campaign_id_and_preserve_previous(
		string $exception_class,
		string $expected_message,
	): void {

		$id = EntityId::create( 123 );
		$previous = new RuntimeException( 'Campaign disappeared' );
		$exception = new $exception_class( $id, $previous );

		$this->assertSame( UseCaseFailureStage::Persistence, $exception->get_stage() );
		$this->assertSame( $expected_message, $exception->getMessage() );
		$this->assertSame( $previous, $exception->getPrevious() );
	}

	#[Test]
	public function sync_not_found_exception_preserves_stage_reason_and_previous(): void {

		$id = EntityId::create( 123 );
		$previous = new RuntimeException( 'Campaign disappeared' );
		$exception = new SyncCampaignFromSnapshotNotFoundException(
			campaign_id: $id,
			stage: UseCaseFailureStage::Persistence,
			previous: $previous,
			reason: SyncCampaignFromSnapshotPreconditionReason::CampaignNotFound,
		);

		$this->assertSame( UseCaseFailureStage::Persistence, $exception->get_stage() );
		$this->assertSame( SyncCampaignFromSnapshotPreconditionReason::CampaignNotFound, $exception->get_reason() );
		$this->assertSame( 'Cannot sync campaign "123": campaign does not exist.', $exception->getMessage() );
		$this->assertSame( $previous, $exception->getPrevious() );
	}

	#[Test]
	#[DataProvider( 'plain_exception_provider' )]
	public function plain_exceptions_preserve_message_and_previous( string $exception_class ): void {

		$previous = new RuntimeException( 'Read failure' );
		$exception = new $exception_class( 'Read failed.', 0, $previous );

		$this->assertSame( 'Read failed.', $exception->getMessage() );
		$this->assertSame( $previous, $exception->getPrevious() );
	}

	public static function not_found_exception_provider(): array {

		return [
			'rename' => [ RenameCampaignNotFoundException::class, 'Cannot rename campaign "123": campaign does not exist.' ],
			'enable' => [ EnableCampaignDonationsNotFoundException::class, 'Cannot enable donations for campaign "123": campaign does not exist.' ],
			'disable' => [ DisableCampaignDonationsNotFoundException::class, 'Cannot disable donations for campaign "123": campaign does not exist.' ],
			'change_target' => [ ChangeCampaignTargetNotFoundException::class, 'Cannot change target for campaign "123": campaign does not exist.' ],
			'delete' => [ DeleteCampaignNotFoundException::class, 'Cannot delete campaign "123": campaign does not exist.' ],
		];
	}

	public static function plain_exception_provider(): array {

		return [
			'find' => [ FindCampaignByIdException::class ],
			'read' => [ ReadCampaignByIdException::class ],
		];
	}
}
