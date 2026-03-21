<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases;

/**
 * Describes the supported donation mutations.
 *
 * @since 0.1.0
 */
enum DonationMutation: string {

	/**
	 * Donation success mutation.
	 */
	case Succeed = 'succeed';

	/**
	 * Donation rejection mutation.
	 */
	case Reject = 'reject';

	/**
	 * Donation refund mutation.
	 */
	case Refund = 'refund';

	/**
	 * Returns the infinitive phrase used in failure messages.
	 *
	 * @since 0.1.0
	 *
	 * @return string Mutation infinitive phrase.
	 */
	public function infinitive(): string {

		return $this->value;
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
			self::Succeed => 'succeeded',
			self::Reject => 'rejected',
			self::Refund => 'refunded',
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

		return $this->event_label();
	}
}
