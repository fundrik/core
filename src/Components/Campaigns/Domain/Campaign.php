<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Domain;

use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignChangeException;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\InvalidCampaignTargetException;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\InvalidCampaignTitleException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;

/**
 * Represents a fundraising campaign.
 *
 * @since 0.1.0
 */
final readonly class Campaign {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The campaign ID.
	 * @param EntityVersion $version The campaign version.
	 * @param CampaignTitle $title The campaign title.
	 * @param bool $is_active Whether the campaign is active.
	 * @param bool $is_open Whether the campaign is open for donations.
	 * @param CampaignTarget $target The campaign target.
	 */
	public function __construct(
		private EntityId $id,
		private EntityVersion $version,
		private CampaignTitle $title,
		private bool $is_active,
		private bool $is_open,
		private CampaignTarget $target,
	) {}

	/**
	 * Returns the campaign ID.
	 *
	 * @since 0.1.0
	 *
	 * @return int|string The campaign ID (positive integer or UUID).
	 */
	public function get_id(): int|string {

		return $this->id->get_value();
	}

	/**
	 * Returns the campaign ID value object.
	 *
	 * @since 0.1.0
	 *
	 * @return EntityId The campaign ID value object.
	 */
	public function get_entity_id(): EntityId {

		return $this->id;
	}

	/**
	 * Returns the campaign version value object.
	 *
	 * @since 0.1.0
	 *
	 * @return EntityVersion The campaign version value object.
	 */
	public function get_version(): EntityVersion {

		return $this->version;
	}

	/**
	 * Returns the campaign title.
	 *
	 * @since 0.1.0
	 *
	 * @return string The campaign title string.
	 */
	public function get_title(): string {

		return $this->title->get_value();
	}

	/**
	 * Returns whether the campaign is active.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if the campaign is active.
	 */
	public function is_active(): bool {

		return $this->is_active;
	}

	/**
	 * Returns whether the campaign is open for donations.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if the campaign is currently open.
	 */
	public function is_open(): bool {

		return $this->is_open;
	}

	/**
	 * Returns whether the campaign has a target.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if the campaign has a target.
	 */
	public function has_target(): bool {

		return $this->target->is_enabled();
	}

	/**
	 * Returns the campaign target amount.
	 *
	 * Returns zero if targeting is disabled.
	 *
	 * @since 0.1.0
	 *
	 * @return int The target amount in minor units.
	 */
	public function get_target_amount(): int {

		return $this->target->get_amount();
	}

	/**
	 * Changes the campaign title.
	 *
	 * @since 0.1.0
	 *
	 * @param string|CampaignTitle $new_title The new title.
	 *
	 * @return self The campaign with updated title.
	 *
	 * @throws InvalidCampaignTitleException When the provided title is invalid.
	 * @throws CampaignChangeException When the title matches the current one.
	 */
	public function rename( string|CampaignTitle $new_title ): self {

		if ( is_string( $new_title ) ) {
			$new_title = CampaignTitle::create( $new_title );
		}

		if ( $new_title->equals( $this->title ) ) {

			throw new CampaignChangeException(
				sprintf(
					'Campaign title must be different from the current one. Given: "%s".',
					$new_title->get_value(),
				),
			);
		}

		return new self( $this->id, $this->version, $new_title, $this->is_active, $this->is_open, $this->target );
	}

	/**
	 * Activates the campaign.
	 *
	 * @since 0.1.0
	 *
	 * @return self The campaign in active state.
	 *
	 * @throws CampaignChangeException When the campaign is already active.
	 */
	public function activate(): self {

		if ( $this->is_active ) {
			throw new CampaignChangeException( 'Cannot activate campaign: already active.' );
		}

		return new self( $this->id, $this->version, $this->title, true, $this->is_open, $this->target );
	}

	/**
	 * Deactivates the campaign.
	 *
	 * @since 0.1.0
	 *
	 * @return self The campaign in inactive state.
	 *
	 * @throws CampaignChangeException When the campaign is already inactive.
	 */
	public function deactivate(): self {

		if ( ! $this->is_active ) {
			throw new CampaignChangeException( 'Cannot deactivate campaign: already inactive.' );
		}

		return new self( $this->id, $this->version, $this->title, false, $this->is_open, $this->target );
	}

	/**
	 * Opens the campaign for donations.
	 *
	 * @since 0.1.0
	 *
	 * @return self The campaign in open state.
	 *
	 * @throws CampaignChangeException When the campaign is already open.
	 */
	public function open(): self {

		if ( $this->is_open ) {
			throw new CampaignChangeException( 'Cannot open campaign: already open.' );
		}

		return new self( $this->id, $this->version, $this->title, $this->is_active, true, $this->target );
	}

	/**
	 * Closes the campaign for donations.
	 *
	 * @since 0.1.0
	 *
	 * @return self The campaign in closed state.
	 *
	 * @throws CampaignChangeException When the campaign is already closed.
	 */
	public function close(): self {

		if ( ! $this->is_open ) {
			throw new CampaignChangeException( 'Cannot close campaign: already closed.' );
		}

		return new self( $this->id, $this->version, $this->title, $this->is_active, false, $this->target );
	}

	/**
	 * Enables targeting with the specified amount.
	 *
	 * @since 0.1.0
	 *
	 * @param int $amount The positive target amount in minor currency units.
	 *
	 * @return self The campaign with targeting enabled and amount set.
	 *
	 * @throws InvalidCampaignTargetException When the amount is invalid.
	 * @throws CampaignChangeException When targeting is already enabled with the same amount.
	 */
	public function enable_target( int $amount ): self {

		$new = CampaignTarget::create( true, $amount );

		if ( $new->equals( $this->target ) ) {

			throw new CampaignChangeException(
				sprintf( 'Target amount must be different from the current one. Given: %d.', $amount ),
			);
		}

		return new self( $this->id, $this->version, $this->title, $this->is_active, $this->is_open, $new );
	}

	/**
	 * Disables targeting (amount becomes zero).
	 *
	 * @since 0.1.0
	 *
	 * @return self The campaign with targeting disabled.
	 *
	 * @throws CampaignChangeException When targeting is already disabled.
	 */
	public function disable_target(): self {

		if ( ! $this->target->is_enabled() ) {
			throw new CampaignChangeException( 'Cannot disable target: already disabled.' );
		}

		$new = CampaignTarget::create( false, 0 );

		return new self( $this->id, $this->version, $this->title, $this->is_active, $this->is_open, $new );
	}

	/**
	 * Sets the target amount.
	 *
	 * Amount 0 disables targeting, positive amount enables or updates it.
	 *
	 * @since 0.1.0
	 *
	 * @param int $amount The desired target amount in minor currency units (0 to disable; >0 to enable/update).
	 *
	 * @return self The campaign with updated targeting state.
	 *
	 * @throws InvalidCampaignTargetException When the amount is invalid (e.g., negative).
	 * @throws CampaignChangeException When the operation would not change the current state.
	 */
	public function set_target_amount( int $amount ): self {

		if ( $amount === 0 ) {
			return $this->disable_target();
		}

		return $this->enable_target( $amount );
	}
}
