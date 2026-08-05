<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\UseCases\ProcessDonationPaymentResult;

use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\DonationPaymentResultType;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResultPolicy;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResultStatus;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass( ProcessDonationPaymentResultPolicy::class )]
final class ProcessDonationPaymentResultPolicyTest extends TestCase {

	#[Test]
	public function determine_status_returns_applied_for_valid_pending_success(): void {

		$policy = new ProcessDonationPaymentResultPolicy();

		$this->assertSame(
			ProcessDonationPaymentResultStatus::Applied,
			$policy->determine_status(
				DonationStatus::Pending,
				DonationPaymentResultType::Succeeded,
			),
		);
	}

	#[Test]
	public function determine_status_returns_replayed_for_already_applied_status(): void {

		$policy = new ProcessDonationPaymentResultPolicy();

		$this->assertSame(
			ProcessDonationPaymentResultStatus::Replayed,
			$policy->determine_status(
				DonationStatus::Succeeded,
				DonationPaymentResultType::Succeeded,
			),
		);
	}

	#[Test]
	public function determine_status_returns_ignored_for_stale_result(): void {

		$policy = new ProcessDonationPaymentResultPolicy();

		$this->assertSame(
			ProcessDonationPaymentResultStatus::Ignored,
			$policy->determine_status(
				DonationStatus::Refunded,
				DonationPaymentResultType::Succeeded,
			),
		);
	}
}
