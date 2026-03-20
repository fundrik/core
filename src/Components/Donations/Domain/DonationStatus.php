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
	case Authorized = 'authorized';
	case Captured = 'captured';
	case Failed = 'failed';
	case Refunded = 'refunded';
	case Canceled = 'canceled';

	/**
	 * Authorizes donation status.
	 *
	 * Allowed transition: pending -> authorized.
	 *
	 * @since 0.1.0
	 *
	 * @return self Authorized donation status.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	public function authorize(): self {

		$this->assert_transition_allowed( [ self::Pending ], 'authorize' );

		return self::Authorized;
	}

	/**
	 * Captures donation status.
	 *
	 * Allowed transitions: pending|authorized -> captured.
	 *
	 * @since 0.1.0
	 *
	 * @return self Captured donation status.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	public function capture(): self {

		$this->assert_transition_allowed(
			[ self::Pending, self::Authorized ],
			'capture',
		);

		return self::Captured;
	}

	/**
	 * Marks donation status as failed.
	 *
	 * Allowed transitions: pending|authorized -> failed.
	 *
	 * @since 0.1.0
	 *
	 * @return self Failed donation status.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	public function fail(): self {

		$this->assert_transition_allowed(
			[ self::Pending, self::Authorized ],
			'fail',
		);

		return self::Failed;
	}

	/**
	 * Refunds donation status.
	 *
	 * Allowed transition: captured -> refunded.
	 *
	 * @since 0.1.0
	 *
	 * @return self Refunded donation status.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	public function refund(): self {

		$this->assert_transition_allowed( [ self::Captured ], 'refund' );

		return self::Refunded;
	}

	/**
	 * Cancels donation status.
	 *
	 * Allowed transitions: pending|authorized -> canceled.
	 *
	 * @since 0.1.0
	 *
	 * @return self Canceled donation status.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	public function cancel(): self {

		$this->assert_transition_allowed(
			[ self::Pending, self::Authorized ],
			'cancel',
		);

		return self::Canceled;
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
