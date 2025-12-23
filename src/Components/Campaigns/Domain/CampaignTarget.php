<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Domain;

use Fundrik\Core\Components\Campaigns\Domain\Exceptions\InvalidCampaignTargetException;

/**
 * Represents the fundraising target of a campaign.
 *
 * Enforces the following invariants:
 * - If targeting is enabled, the target amount must be a positive integer.
 * - If targeting is disabled, the target amount must be exactly zero.
 *
 * @since 0.1.0
 */
final readonly class CampaignTarget {

	/**
	 * Private constructor, use factory method.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $is_enabled Whether targeting is enabled.
	 * @param int $amount The target amount in minor currency units, must be >= 0 when targeting is enabled.
	 */
	private function __construct(
		private bool $is_enabled,
		private int $amount,
	) {}

	/**
	 * Creates a campaign target value object.
	 *
	 * Validates consistency between enablement flag and amount:
	 * - If enabled, amount must be positive.
	 * - If disabled, amount must be zero.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $is_enabled Whether targeting is enabled.
	 * @param int $amount The fundraising target amount.
	 *
	 * @return self The campaign target value object.
	 *
	 * @throws InvalidCampaignTargetException When the target amount violates the enablement rules.
	 */
	public static function create( bool $is_enabled, int $amount ): self {

		if ( $is_enabled && $amount <= 0 ) {
			throw new InvalidCampaignTargetException(
				sprintf( 'Target amount must be positive when targeting is enabled. Given: %d.', $amount ),
			);
		}

		if ( ! $is_enabled && $amount !== 0 ) {
			throw new InvalidCampaignTargetException(
				sprintf( 'Target amount must be zero when targeting is disabled. Given: %d.', $amount ),
			);
		}

		return new self( $is_enabled, $amount );
	}

	/**
	 * Returns whether targeting is enabled.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if targeting is enabled.
	 */
	public function is_enabled(): bool {

		return $this->is_enabled;
	}

	/**
	 * Returns the fundraising target amount.
	 *
	 * @since 0.1.0
	 *
	 * @return int The target amount (positive if enabled, zero if disabled).
	 */
	public function get_amount(): int {

		return $this->amount;
	}

	/**
	 * Checks whether this target is equal to another.
	 *
	 * @since 0.1.0
	 *
	 * @param self $other The target to compare with.
	 *
	 * @return bool True if the two target objects are equal.
	 */
	public function equals( self $other ): bool {

		return $this->is_enabled === $other->is_enabled
			&& $this->amount === $other->amount;
	}
}
