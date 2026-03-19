<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Commands;

use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Represents the public command for creating a campaign.
 *
 * @since 0.1.0
 */
final readonly class CreateCampaignCommand {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $id Campaign identifier.
	 * @param string $title Campaign title.
	 * @param bool $accepts_donations Whether the campaign accepts donations.
	 * @param string $currency_code Campaign currency code.
	 * @param int|null $target_amount Target amount, if configured.
	 */
	public function __construct(
		private int|string|EntityId $id,
		private string $title,
		private bool $accepts_donations,
		private string $currency_code,
		private ?int $target_amount,
	) {}

	/**
	 * Returns the campaign identifier.
	 *
	 * @since 0.1.0
	 *
	 * @return int|string|EntityId Campaign identifier.
	 */
	public function get_id(): int|string|EntityId {

		return $this->id;
	}

	/**
	 * Returns the campaign title.
	 *
	 * @since 0.1.0
	 *
	 * @return string Campaign title.
	 */
	public function get_title(): string {

		return $this->title;
	}

	/**
	 * Returns whether the campaign can receive donations.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True when the campaign can receive donations.
	 */
	public function can_receive_donations(): bool {

		return $this->accepts_donations;
	}

	/**
	 * Returns the campaign currency code.
	 *
	 * @since 0.1.0
	 *
	 * @return string Campaign currency code.
	 */
	public function get_currency_code(): string {

		return $this->currency_code;
	}

	/**
	 * Returns the target amount.
	 *
	 * @since 0.1.0
	 *
	 * @return int|null Target amount, if configured.
	 */
	public function get_target_amount(): ?int {

		return $this->target_amount;
	}
}
