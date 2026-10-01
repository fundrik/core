<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\Commands;

use Fundrik\Core\Components\Campaigns\Application\Commands\SyncCampaignFromSnapshotCommand;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( SyncCampaignFromSnapshotCommand::class )]
#[UsesClass( EntityId::class )]
final class SyncCampaignFromSnapshotCommandTest extends FundrikTestCase {

	#[Test]
	public function returns_all_values_for_entity_id_and_configured_target(): void {

		$id = EntityId::uuid7();
		$command = new SyncCampaignFromSnapshotCommand(
			id: $id,
			expected_version: 4,
			title: 'Synchronized campaign',
			accepts_donations: true,
			currency_code: 'RUB',
			target_amount: 7_500,
		);

		$this->assertSame( $id, $command->get_id() );
		$this->assertSame( 4, $command->get_expected_version() );
		$this->assertSame( 'Synchronized campaign', $command->get_title() );
		$this->assertTrue( $command->accepts_donations() );
		$this->assertSame( 'RUB', $command->get_currency_code() );
		$this->assertSame( 7_500, $command->get_target_amount() );
	}

	#[Test]
	public function returns_scalar_id_and_null_target(): void {

		$command = new SyncCampaignFromSnapshotCommand(
			id: 'campaign-uuid',
			expected_version: 1,
			title: 'Campaign snapshot',
			accepts_donations: false,
			currency_code: 'USD',
			target_amount: null,
		);

		$this->assertSame( 'campaign-uuid', $command->get_id() );
		$this->assertSame( 1, $command->get_expected_version() );
		$this->assertSame( 'Campaign snapshot', $command->get_title() );
		$this->assertFalse( $command->accepts_donations() );
		$this->assertSame( 'USD', $command->get_currency_code() );
		$this->assertNull( $command->get_target_amount() );
	}
}
