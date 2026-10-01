<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Domain;

use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationChangeException;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( DonationStatus::class )]
#[UsesClass( DonationChangeException::class )]
final class DonationStatusTest extends FundrikTestCase {

	#[Test]
	public function exposes_expected_values(): void {

		$this->assertSame( 'pending', DonationStatus::Pending->value );
		$this->assertSame( 'succeeded', DonationStatus::Succeeded->value );
		$this->assertSame( 'rejected', DonationStatus::Rejected->value );
		$this->assertSame( 'refunded', DonationStatus::Refunded->value );
	}

	#[Test]
	#[DataProvider( 'allowed_transition_provider' )]
	public function allows_expected_transitions(
		DonationStatus $status,
		string $action,
		DonationStatus $expected,
	): void {

		$this->assertSame( $expected, $status->{$action}() );
	}

	#[Test]
	#[DataProvider( 'invalid_transition_provider' )]
	public function rejects_invalid_transitions( DonationStatus $status, string $action, ): void {

		$this->expectException( DonationChangeException::class );
		$this->expectExceptionMessage( sprintf( 'Cannot %s donation from status "%s".', $action, $status->value ) );

		$status->{$action}();
	}

	public static function allowed_transition_provider(): array {

		return [
			'succeed pending donation' => [ DonationStatus::Pending, 'succeed', DonationStatus::Succeeded ],
			'reject pending donation' => [ DonationStatus::Pending, 'reject', DonationStatus::Rejected ],
			'refund succeeded donation' => [ DonationStatus::Succeeded, 'refund', DonationStatus::Refunded ],
		];
	}

	public static function invalid_transition_provider(): array {

		return [
			'succeed succeeded donation' => [ DonationStatus::Succeeded, 'succeed' ],
			'succeed rejected donation' => [ DonationStatus::Rejected, 'succeed' ],
			'succeed refunded donation' => [ DonationStatus::Refunded, 'succeed' ],
			'reject succeeded donation' => [ DonationStatus::Succeeded, 'reject' ],
			'reject rejected donation' => [ DonationStatus::Rejected, 'reject' ],
			'reject refunded donation' => [ DonationStatus::Refunded, 'reject' ],
			'refund pending donation' => [ DonationStatus::Pending, 'refund' ],
			'refund rejected donation' => [ DonationStatus::Rejected, 'refund' ],
			'refund refunded donation' => [ DonationStatus::Refunded, 'refund' ],
		];
	}
}
