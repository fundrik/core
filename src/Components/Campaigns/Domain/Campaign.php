<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Domain;

use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignChangeException;
use Fundrik\Core\Components\Shared\Domain\Amount;
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
	 * @param EntityId $id Campaign ID.
	 * @param EntityVersion $version Campaign version.
	 * @param CampaignTitle $title Campaign title.
	 * @param bool $is_open Whether the campaign is open for donations.
	 * @param CampaignTarget $target Campaign target.
	 */
	public function __construct(
		private EntityId $id,
		private EntityVersion $version,
		private CampaignTitle $title,
		private bool $is_open,
		private CampaignTarget $target,
	) {}

	/**
	 * Returns the campaign ID value object.
	 *
	 * @since 0.1.0
	 *
	 * @return EntityId Campaign ID value object.
	 */
	public function get_id(): EntityId {

		return $this->id;
	}

	/**
	 * Returns the campaign version value object.
	 *
	 * @since 0.1.0
	 *
	 * @return EntityVersion Campaign version value object.
	 */
	public function get_version(): EntityVersion {

		return $this->version;
	}

	/**
	 * Returns the campaign title.
	 *
	 * @since 0.1.0
	 *
	 * @return string Campaign title string.
	 */
	public function get_title(): string {

		return $this->title->get_value();
	}

	/**
	 * Returns whether the campaign can receive new donations.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True when the campaign is open for donations.
	 */
	public function can_receive_donations(): bool {

		return $this->is_open;
	}

	/**
	 * Returns whether the campaign has a target.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True when the campaign has a target.
	 */
	public function has_target(): bool {

		return $this->target->has_amount();
	}

	/**
	 * Returns the campaign target value object.
	 *
	 * @since 0.1.0
	 *
	 * @return CampaignTarget Campaign target value object.
	 */
	public function get_target(): CampaignTarget {

		return $this->target;
	}

	/**
	 * Changes the campaign title.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignTitle $new_title New title.
	 *
	 * @return self Campaign with updated title.
	 *
	 * @throws CampaignChangeException When the title matches the current one.
	 */
	public function rename( CampaignTitle $new_title ): self {

		if ( $new_title->equals( $this->title ) ) {

			throw new CampaignChangeException(
				sprintf(
					'Campaign title must be different from the current one. Given: "%s".',
					$new_title->get_value(),
				),
			);
		}

		return new self( $this->id, $this->version, $new_title, $this->is_open, $this->target );
	}

	/**
	 * Opens the campaign for donations.
	 *
	 * @since 0.1.0
	 *
	 * @return self Campaign in open state.
	 *
	 * @throws CampaignChangeException When the campaign is already open.
	 */
	public function open(): self {

		if ( $this->is_open ) {
			throw new CampaignChangeException( 'Cannot open campaign: already open.' );
		}

		return new self( $this->id, $this->version, $this->title, true, $this->target );
	}

	/**
	 * Closes the campaign for donations.
	 *
	 * @since 0.1.0
	 *
	 * @return self Campaign in closed state.
	 *
	 * @throws CampaignChangeException When the campaign is already closed.
	 */
	public function close(): self {

		if ( ! $this->is_open ) {
			throw new CampaignChangeException( 'Cannot close campaign: already closed.' );
		}

		return new self( $this->id, $this->version, $this->title, false, $this->target );
	}

	/**
	 * Changes the campaign target amount.
	 *
	 * @since 0.1.0
	 *
	 * @param Amount|null $target_amount New campaign target amount.
	 *
	 * @return self Campaign with updated targeting.
	 *
	 * @throws CampaignChangeException When the operation would not change the current state.
	 */
	public function change_target_amount( ?Amount $target_amount ): self {

		$updated_target = $this->target->with_amount( $target_amount );

		if ( $updated_target->equals( $this->target ) ) {
			throw new CampaignChangeException( 'Target amount must be different from the current one.' );
		}

		return new self( $this->id, $this->version, $this->title, $this->is_open, $updated_target );
	}
}
