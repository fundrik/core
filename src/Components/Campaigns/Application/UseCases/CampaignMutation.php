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
	 * Campaign activation mutation.
	 */
	case Activate = 'activate';

	/**
	 * Campaign deactivation mutation.
	 */
	case Deactivate = 'deactivate';

	/**
	 * Campaign open mutation.
	 */
	case Open = 'open';

	/**
	 * Campaign close mutation.
	 */
	case Close = 'close';

	/**
	 * Campaign target mutation.
	 */
	case SetTargetAmount = 'set_target_amount';

	/**
	 * Returns the infinitive phrase used in failure messages.
	 *
	 * @since 0.1.0
	 *
	 * @return string Mutation infinitive phrase.
	 */
	public function infinitive(): string {

		return match ( $this ) {
			self::SetTargetAmount => 'set target amount for',
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
			self::Activate => 'activated',
			self::Deactivate => 'deactivated',
			self::Open => 'opened',
			self::Close => 'closed',
			self::SetTargetAmount => 'target changed',
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
			self::SetTargetAmount => 'updated',
			default => $this->event_label(),
		};
	}
}
