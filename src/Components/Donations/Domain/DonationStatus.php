<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Domain;

use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationChangeException;

/**
 * Represents the internal donation status.
 *
 * @since 0.1.0
 */
enum DonationStatus: string {

	case Pending = 'pending';
	case Succeeded = 'succeeded';
	case Rejected = 'rejected';
	case Refunded = 'refunded';

	/**
	 * Marks donation status as succeeded.
	 *
	 * Allowed transition: pending -> succeeded.
	 *
	 * @since 0.1.0
	 *
	 * @return self Succeeded donation status.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	public function succeed(): self {

		$this->assert_transition_allowed( [ self::Pending ], 'succeed' );

		return self::Succeeded;
	}

	/**
	 * Marks donation status as rejected.
	 *
	 * Allowed transition: pending -> rejected.
	 *
	 * @since 0.1.0
	 *
	 * @return self Rejected donation status.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	public function reject(): self {

		$this->assert_transition_allowed( [ self::Pending ], 'reject' );

		return self::Rejected;
	}

	/**
	 * Marks donation status as refunded.
	 *
	 * Allowed transition: succeeded -> refunded.
	 *
	 * @since 0.1.0
	 *
	 * @return self Refunded donation status.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	public function refund(): self {

		$this->assert_transition_allowed( [ self::Succeeded ], 'refund' );

		return self::Refunded;
	}

	/**
	 * Ensures transition from current status is allowed.
	 *
	 * @since 0.1.0
	 *
	 * @param array<self> $allowed_statuses Allowed source statuses.
	 * @param string $action Action verb for exception message.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	private function assert_transition_allowed( array $allowed_statuses, string $action ): void {

		if ( in_array( $this, $allowed_statuses, true ) ) {
			return;
		}

		throw new DonationChangeException(
			sprintf( 'Cannot %s donation from status "%s".', $action, $this->value ),
		);
	}
}
