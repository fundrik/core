<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases;

/**
 * Describes the supported campaign mutations.
 *
 * @since 0.1.0
 */
enum CampaignMutation: string {

	/**
	 * Campaign title mutation.
	 */
	case Rename = 'rename';

	/**
	 * Campaign donations-enable mutation.
	 */
	case EnableDonations = 'enable_donations';

	/**
	 * Campaign donations-disable mutation.
	 */
	case DisableDonations = 'disable_donations';

	/**
	 * Campaign target mutation.
	 */
	case ChangeTarget = 'change_target';

	/**
	 * Returns the infinitive phrase used in failure messages.
	 *
	 * @since 0.1.0
	 *
	 * @return string Mutation infinitive phrase.
	 */
	public function infinitive(): string {

		return match ( $this ) {
			self::ChangeTarget => 'change target for',
			self::EnableDonations => 'enable donations for',
			self::DisableDonations => 'disable donations for',
			default => $this->value,
		};
	}

	/**
	 * Returns the event label used in event-publish failures.
	 *
	 * @since 0.1.0
	 *
	 * @return string Mutation event label.
	 */
	public function event_label(): string {

		return match ( $this ) {
			self::Rename => 'renamed',
			self::EnableDonations => 'donations enabled',
			self::DisableDonations => 'donations disabled',
			self::ChangeTarget => 'target changed',
		};
	}

	/**
	 * Returns the past participle used in event-publish failures.
	 *
	 * @since 0.1.0
	 *
	 * @return string Mutation past participle.
	 */
	public function past_participle(): string {

		return match ( $this ) {
			self::ChangeTarget => 'updated',
			self::EnableDonations => 'updated',
			self::DisableDonations => 'updated',
			default => $this->event_label(),
		};
	}
}
