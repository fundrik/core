<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\Commands;

use Fundrik\Core\Components\Campaigns\Application\Commands\CreateCampaignCommand;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CreateCampaignCommand::class )]
#[UsesClass( EntityId::class )]
final class CreateCampaignCommandTest extends FundrikTestCase {

	#[Test]
	public function returns_all_values_for_entity_id_and_configured_target(): void {

		$id = EntityId::uuid7();
		$command = new CreateCampaignCommand(
			id: $id,
			title: 'Save the rainforest',
			accepts_donations: false,
			currency_code: 'RUB',
			target_amount: 5_000,
		);

		$this->assertSame( $id, $command->get_id() );
		$this->assertSame( 'Save the rainforest', $command->get_title() );
		$this->assertFalse( $command->accepts_donations() );
		$this->assertSame( 'RUB', $command->get_currency_code() );
		$this->assertSame( 5_000, $command->get_target_amount() );
	}

	#[Test]
	public function returns_scalar_id_and_null_target(): void {

		$command = new CreateCampaignCommand(
			id: 10,
			title: 'Save the cats',
			accepts_donations: true,
			currency_code: 'USD',
			target_amount: null,
		);

		$this->assertSame( 10, $command->get_id() );
		$this->assertSame( 'Save the cats', $command->get_title() );
		$this->assertTrue( $command->accepts_donations() );
		$this->assertSame( 'USD', $command->get_currency_code() );
		$this->assertNull( $command->get_target_amount() );
	}
}
