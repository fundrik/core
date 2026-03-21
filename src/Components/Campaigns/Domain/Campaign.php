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
	 * @param bool $accepts_donations Whether the campaign accepts donations.
	 * @param CampaignTarget $target Campaign target.
	 */
	public function __construct(
		private EntityId $id,
		private EntityVersion $version,
		private CampaignTitle $title,
		private bool $accepts_donations,
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
	 * Returns whether the campaign accepts donations.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True when the campaign accepts donations.
	 */
	public function accepts_donations(): bool {

		return $this->accepts_donations;
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

		return new self( $this->id, $this->version, $new_title, $this->accepts_donations, $this->target );
	}

	/**
	 * Enables accepting donations for the campaign.
	 *
	 * @since 0.1.0
	 *
	 * @return self Campaign with donation acceptance enabled.
	 *
	 * @throws CampaignChangeException When donation acceptance is already enabled.
	 */
	public function enable_donations(): self {

		if ( $this->accepts_donations ) {
			throw new CampaignChangeException( 'Cannot enable donations for campaign: already enabled.' );
		}

		return new self( $this->id, $this->version, $this->title, true, $this->target );
	}

	/**
	 * Disables accepting donations for the campaign.
	 *
	 * @since 0.1.0
	 *
	 * @return self Campaign with donation acceptance disabled.
	 *
	 * @throws CampaignChangeException When donation acceptance is already disabled.
	 */
	public function disable_donations(): self {

		if ( ! $this->accepts_donations ) {
			throw new CampaignChangeException( 'Cannot disable donations for campaign: already disabled.' );
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

		return new self( $this->id, $this->version, $this->title, $this->accepts_donations, $updated_target );
	}
}
