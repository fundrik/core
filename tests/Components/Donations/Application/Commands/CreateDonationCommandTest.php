<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\Commands;

use Fundrik\Core\Components\Donations\Application\Commands\CreateDonationCommand;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CreateDonationCommand::class )]
#[UsesClass( EntityId::class )]
final class CreateDonationCommandTest extends FundrikTestCase {

	#[Test]
	public function exposes_creation_values(): void {

		$donation_id = EntityId::create( '7c1bb0b8-4d8e-4b3a-9a6e-3f1d9b1b6f5b' );
		$campaign_id = EntityId::create( 901 );
		$command = new CreateDonationCommand( id: $donation_id, campaign_id: $campaign_id, amount: 1_000 );

		$this->assertSame( $donation_id, $command->get_id() );
		$this->assertSame( $campaign_id, $command->get_campaign_id() );
		$this->assertSame( 1_000, $command->get_amount() );
	}
}
