<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\UseCases\ProcessDonationPaymentResult;

use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\DonationPaymentResultType;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResultPolicy;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResultStatus;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( ProcessDonationPaymentResultPolicy::class )]
#[CoversClass( DonationPaymentResultType::class )]
#[CoversClass( ProcessDonationPaymentResultStatus::class )]
final class ProcessDonationPaymentResultPolicyTest extends FundrikTestCase {

	#[Test]
	#[DataProvider( 'status_provider' )]
	public function determine_status_returns_expected_outcome(
		DonationStatus $current_status,
		DonationPaymentResultType $result_type,
		ProcessDonationPaymentResultStatus $expected,
	): void {

		$policy = new ProcessDonationPaymentResultPolicy();

		$this->assertSame(
			$expected,
			$policy->determine_status( $current_status, $result_type ),
		);
	}

	public static function status_provider(): array {

		return [
			'succeed ignores created' => [ DonationStatus::Created, DonationPaymentResultType::Succeeded, ProcessDonationPaymentResultStatus::Ignored ],
			'succeed applies from pending' => [ DonationStatus::Pending, DonationPaymentResultType::Succeeded, ProcessDonationPaymentResultStatus::Applied ],
			'succeed replays from succeeded' => [ DonationStatus::Succeeded, DonationPaymentResultType::Succeeded, ProcessDonationPaymentResultStatus::Replayed ],
			'succeed ignores rejected' => [ DonationStatus::Rejected, DonationPaymentResultType::Succeeded, ProcessDonationPaymentResultStatus::Ignored ],
			'succeed ignores refunded' => [ DonationStatus::Refunded, DonationPaymentResultType::Succeeded, ProcessDonationPaymentResultStatus::Ignored ],
			'reject ignores created' => [ DonationStatus::Created, DonationPaymentResultType::Rejected, ProcessDonationPaymentResultStatus::Ignored ],
			'reject applies from pending' => [ DonationStatus::Pending, DonationPaymentResultType::Rejected, ProcessDonationPaymentResultStatus::Applied ],
			'reject replays from rejected' => [ DonationStatus::Rejected, DonationPaymentResultType::Rejected, ProcessDonationPaymentResultStatus::Replayed ],
			'reject ignores succeeded' => [ DonationStatus::Succeeded, DonationPaymentResultType::Rejected, ProcessDonationPaymentResultStatus::Ignored ],
			'reject ignores refunded' => [ DonationStatus::Refunded, DonationPaymentResultType::Rejected, ProcessDonationPaymentResultStatus::Ignored ],
			'refund ignores created' => [ DonationStatus::Created, DonationPaymentResultType::Refunded, ProcessDonationPaymentResultStatus::Ignored ],
			'refund applies from succeeded' => [ DonationStatus::Succeeded, DonationPaymentResultType::Refunded, ProcessDonationPaymentResultStatus::Applied ],
			'refund replays from refunded' => [ DonationStatus::Refunded, DonationPaymentResultType::Refunded, ProcessDonationPaymentResultStatus::Replayed ],
			'refund ignores pending' => [ DonationStatus::Pending, DonationPaymentResultType::Refunded, ProcessDonationPaymentResultStatus::Ignored ],
			'refund ignores rejected' => [ DonationStatus::Rejected, DonationPaymentResultType::Refunded, ProcessDonationPaymentResultStatus::Ignored ],
		];
	}
}
