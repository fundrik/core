<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\UseCases;

use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutation;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( CampaignMutation::class )]
final class CampaignMutationTest extends FundrikTestCase {

	#[Test]
	#[DataProvider( 'mutation_provider' )]
	public function exposes_mutation_value_and_message_phrases(
		CampaignMutation $mutation,
		string $value,
		string $infinitive,
		string $event_label,
		string $past_participle,
	): void {

		$this->assertSame( $value, $mutation->value );
		$this->assertSame( $infinitive, $mutation->infinitive() );
		$this->assertSame( $event_label, $mutation->event_label() );
		$this->assertSame( $past_participle, $mutation->past_participle() );
	}

	public static function mutation_provider(): array {

		return [
			'rename' => [ CampaignMutation::Rename, 'rename', 'rename', 'renamed', 'renamed' ],
			'enable_donations' => [ CampaignMutation::EnableDonations, 'enable_donations', 'enable donations for', 'donations enabled', 'updated' ],
			'disable_donations' => [ CampaignMutation::DisableDonations, 'disable_donations', 'disable donations for', 'donations disabled', 'updated' ],
			'change_target' => [ CampaignMutation::ChangeTarget, 'change_target', 'change target for', 'target changed', 'updated' ],
		];
	}
}
