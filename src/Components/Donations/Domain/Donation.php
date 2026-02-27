<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Domain;

use DateTimeImmutable;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationChangeException;
use Fundrik\Core\Components\Donations\Domain\Exceptions\InvalidDonationAmountException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;

/**
 * Represents a fundraising donation.
 *
 * @since 0.1.0
 */
final readonly class Donation {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id Donation ID.
	 * @param EntityVersion $version Donation version.
	 * @param EntityId $campaign_id Campaign ID.
	 * @param Money $money Donation amount and currency.
	 * @param DonationStatus $status Donation status.
	 * @param DateTimeImmutable $created_at Creation timestamp.
	 * @param DateTimeImmutable|null $captured_at Capture timestamp.
	 * @param DateTimeImmutable|null $status_changed_at Status change timestamp.
	 *
	 * @throws InvalidDonationAmountException When amount is zero or negative.
	 * @throws DonationChangeException When state/timestamps are inconsistent.
	 */
	public function __construct(
		private EntityId $id,
		private EntityVersion $version,
		private EntityId $campaign_id,
		private Money $money,
		private DonationStatus $status,
		private DateTimeImmutable $created_at,
		private ?DateTimeImmutable $captured_at = null,
		private ?DateTimeImmutable $status_changed_at = null,
	) {

		if ( $this->money->get_amount_minor() <= 0 ) {

			throw new InvalidDonationAmountException(
				sprintf(
					'Donation amount must be a positive integer in minor units. Given: %d.',
					$this->money->get_amount_minor(),
				),
			);
		}

		$this->assert_state_consistency();
	}

	/**
	 * Returns donation ID value object.
	 *
	 * @since 0.1.0
	 *
	 * @return EntityId Donation ID value object.
	 */
	public function get_id(): EntityId {

		return $this->id;
	}

	/**
	 * Returns donation version value object.
	 *
	 * @since 0.1.0
	 *
	 * @return EntityVersion Donation version value object.
	 */
	public function get_version(): EntityVersion {

		return $this->version;
	}

	/**
	 * Returns campaign ID value object.
	 *
	 * @since 0.1.0
	 *
	 * @return EntityId Campaign ID value object.
	 */
	public function get_campaign_id(): EntityId {

		return $this->campaign_id;
	}

	/**
	 * Returns donation money value object.
	 *
	 * @since 0.1.0
	 *
	 * @return Money Donation money.
	 */
	public function get_money(): Money {

		return $this->money;
	}

	/**
	 * Returns donation status.
	 *
	 * @since 0.1.0
	 *
	 * @return DonationStatus Donation status.
	 */
	public function get_status(): DonationStatus {

		return $this->status;
	}

	/**
	 * Returns creation timestamp.
	 *
	 * @since 0.1.0
	 *
	 * @return DateTimeImmutable Creation timestamp.
	 */
	public function get_created_at(): DateTimeImmutable {

		return $this->created_at;
	}

	/**
	 * Returns capture timestamp.
	 *
	 * @since 0.1.0
	 *
	 * @return DateTimeImmutable|null Capture timestamp.
	 */
	public function get_captured_at(): ?DateTimeImmutable {

		return $this->captured_at;
	}

	/**
	 * Returns status change timestamp.
	 *
	 * @since 0.1.0
	 *
	 * @return DateTimeImmutable|null Status change timestamp.
	 */
	public function get_status_changed_at(): ?DateTimeImmutable {

		return $this->status_changed_at;
	}

	/**
	 * Authorizes donation.
	 *
	 * Allowed transition: pending -> authorized.
	 *
	 * @since 0.1.0
	 *
	 * @param DateTimeImmutable|null $at Optional authorization timestamp.
	 *
	 * @return self Donation in authorized status.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	public function authorize( ?DateTimeImmutable $at = null ): self {

		$this->assert_transition_allowed( [ DonationStatus::Pending ], 'authorize' );

		return $this->with_status(
			status: DonationStatus::Authorized,
			status_changed_at: self::resolve_time( $at ),
		);
	}

	/**
	 * Captures donation.
	 *
	 * Allowed transitions: pending|authorized -> captured.
	 *
	 * @since 0.1.0
	 *
	 * @param DateTimeImmutable|null $at Optional capture timestamp.
	 *
	 * @return self Donation in captured status.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	public function capture( ?DateTimeImmutable $at = null ): self {

		$this->assert_transition_allowed(
			[ DonationStatus::Pending, DonationStatus::Authorized ],
			'capture',
		);

		$at = self::resolve_time( $at );

		return $this->with_status( status: DonationStatus::Captured, captured_at: $at, status_changed_at: $at );
	}

	/**
	 * Marks donation as failed.
	 *
	 * Allowed transitions: pending|authorized -> failed.
	 *
	 * @since 0.1.0
	 *
	 * @param DateTimeImmutable|null $at Optional failure timestamp.
	 *
	 * @return self Donation in failed status.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	public function fail( ?DateTimeImmutable $at = null ): self {

		$this->assert_transition_allowed(
			[ DonationStatus::Pending, DonationStatus::Authorized ],
			'fail',
		);

		return $this->with_status(
			status: DonationStatus::Failed,
			status_changed_at: self::resolve_time( $at ),
		);
	}

	/**
	 * Refunds donation.
	 *
	 * Allowed transition: captured -> refunded.
	 *
	 * @since 0.1.0
	 *
	 * @param DateTimeImmutable|null $at Optional refund timestamp.
	 *
	 * @return self Donation in refunded status.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	public function refund( ?DateTimeImmutable $at = null ): self {

		$this->assert_transition_allowed( [ DonationStatus::Captured ], 'refund' );

		return $this->with_status(
			status: DonationStatus::Refunded,
			captured_at: $this->captured_at,
			status_changed_at: self::resolve_time( $at ),
		);
	}

	/**
	 * Cancels donation.
	 *
	 * Allowed transitions: pending|authorized -> canceled.
	 *
	 * @since 0.1.0
	 *
	 * @param DateTimeImmutable|null $at Optional cancel timestamp.
	 *
	 * @return self Donation in canceled status.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	public function cancel( ?DateTimeImmutable $at = null ): self {

		$this->assert_transition_allowed(
			[ DonationStatus::Pending, DonationStatus::Authorized ],
			'cancel',
		);

		return $this->with_status(
			status: DonationStatus::Canceled,
			status_changed_at: self::resolve_time( $at ),
		);
	}

	/**
	 * Creates new immutable donation instance with changed status fields.
	 *
	 * @since 0.1.0
	 *
	 * @param DonationStatus $status New status.
	 * @param DateTimeImmutable|null $captured_at Capture timestamp.
	 * @param DateTimeImmutable|null $status_changed_at Status change timestamp.
	 *
	 * @return self Updated immutable donation.
	 */
	private function with_status(
		DonationStatus $status,
		?DateTimeImmutable $captured_at = null,
		?DateTimeImmutable $status_changed_at = null,
	): self {

		return new self(
			id: $this->id,
			version: $this->version,
			campaign_id: $this->campaign_id,
			money: $this->money,
			status: $status,
			created_at: $this->created_at,
			captured_at: $captured_at,
			status_changed_at: $status_changed_at,
		);
	}

	/**
	 * Validates internal state consistency.
	 *
	 * @since 0.1.0
	 *
	 * @throws DonationChangeException When state/timestamps are inconsistent.
	 */
	private function assert_state_consistency(): void {

		$this->assert_pending_state();
		$this->assert_non_pending_state();
		$this->assert_captured_state();
		$this->assert_temporal_order();
	}

	/**
	 * Validates pending state.
	 *
	 * @since 0.1.0
	 *
	 * @throws DonationChangeException When pending state carries status timestamps.
	 */
	private function assert_pending_state(): void {

		if ( $this->status !== DonationStatus::Pending ) {
			return;
		}

		if ( $this->captured_at !== null || $this->status_changed_at !== null ) {
			throw new DonationChangeException( 'Pending donation must not have status timestamps.' );
		}
	}

	/**
	 * Validates non-pending state.
	 *
	 * @since 0.1.0
	 *
	 * @throws DonationChangeException When non-pending state has no status timestamp.
	 */
	private function assert_non_pending_state(): void {

		if ( $this->status === DonationStatus::Pending || $this->status_changed_at !== null ) {
			return;
		}

		throw new DonationChangeException( 'Non-pending donation must have status_changed_at timestamp.' );
	}

	/**
	 * Validates captured/refunded state requirements.
	 *
	 * @since 0.1.0
	 *
	 * @throws DonationChangeException When captured_at usage is inconsistent.
	 */
	private function assert_captured_state(): void {

		$requires_capture = in_array(
			$this->status,
			[ DonationStatus::Captured, DonationStatus::Refunded ],
			true,
		);

		if ( $requires_capture && $this->captured_at === null ) {
			throw new DonationChangeException( 'Captured/refunded donation must have captured_at timestamp.' );
		}

		if ( ! $requires_capture && $this->captured_at !== null ) {
			throw new DonationChangeException( 'Only captured/refunded donations can have captured_at timestamp.' );
		}
	}

	/**
	 * Validates timestamp order.
	 *
	 * @since 0.1.0
	 *
	 * @throws DonationChangeException When timestamp order is invalid.
	 */
	private function assert_temporal_order(): void {

		$this->assert_not_before_created( $this->captured_at, 'captured_at' );
		$this->assert_not_before_created( $this->status_changed_at, 'status_changed_at' );

		if (
			$this->status === DonationStatus::Refunded
			&& $this->captured_at !== null
			&& $this->status_changed_at !== null
			&& $this->status_changed_at < $this->captured_at
		) {
			throw new DonationChangeException(
				'status_changed_at must not be earlier than captured_at for refunded donation.',
			);
		}
	}

	/**
	 * Ensures the timestamp is not before created_at.
	 *
	 * @since 0.1.0
	 *
	 * @param DateTimeImmutable|null $timestamp Timestamp to validate.
	 * @param string $field_name Field name for exception message.
	 *
	 * @throws DonationChangeException When timestamp is earlier than created_at.
	 */
	private function assert_not_before_created( ?DateTimeImmutable $timestamp, string $field_name ): void {

		if ( $timestamp === null || $timestamp >= $this->created_at ) {
			return;
		}

		throw new DonationChangeException(
			sprintf( '%s must not be earlier than created_at.', $field_name ),
		);
	}

	/**
	 * Ensures transition from current status is allowed.
	 *
	 * @since 0.1.0
	 *
	 * @param array<DonationStatus> $allowed_statuses Allowed source statuses.
	 * @param string $action Action verb for exception message.
	 *
	 * @throws DonationChangeException When transition is not allowed.
	 */
	private function assert_transition_allowed( array $allowed_statuses, string $action ): void {

		if ( in_array( $this->status, $allowed_statuses, true ) ) {
			return;
		}

		throw new DonationChangeException(
			sprintf( 'Cannot %s donation from status "%s".', $action, $this->status->value ),
		);
	}

	/**
	 * Resolves optional timestamp.
	 *
	 * @since 0.1.0
	 *
	 * @param DateTimeImmutable|null $at Optional timestamp.
	 *
	 * @return DateTimeImmutable Provided timestamp or now.
	 */
	private static function resolve_time( ?DateTimeImmutable $at ): DateTimeImmutable {

		return $at ?? new DateTimeImmutable();
	}
}
