<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\ReadModels;

use Fundrik\Core\Components\Shared\Domain\UtcDateTime;

/**
 * Represents campaign details exposed by the public read API.
 *
 * @since 0.1.0
 */
final readonly class CampaignDetails {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id Campaign identifier.
	 * @param string $title Campaign title.
	 * @param bool $accepts_donations Whether the campaign accepts donations.
	 * @param string $currency_code Campaign currency code.
	 * @param int|null $target_amount Target amount, if configured.
	 * @param UtcDateTime $created_at Creation timestamp.
	 * @param UtcDateTime|null $updated_at Update timestamp, null otherwise.
	 */
	public function __construct(
		private int|string $id,
		private string $title,
		private bool $accepts_donations,
		private string $currency_code,
		private ?int $target_amount,
		private UtcDateTime $created_at,
		private ?UtcDateTime $updated_at = null,
	) {}

	/**
	 * Returns the campaign identifier.
	 *
	 * @since 0.1.0
	 *
	 * @return int|string Campaign identifier.
	 */
	public function get_id(): int|string {

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
	 * Returns whether the campaign has a configured target.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True when a target is configured.
	 */
	public function has_target(): bool {

		return $this->target_amount !== null;
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

	/**
	 * Returns the creation timestamp.
	 *
	 * @since 0.1.0
	 *
	 * @return UtcDateTime Creation timestamp.
	 */
	public function get_created_at(): UtcDateTime {

		return $this->created_at;
	}

	/**
	 * Returns the last update timestamp.
	 *
	 * @since 0.1.0
	 *
	 * @return UtcDateTime|null Update timestamp, null otherwise.
	 */
	public function get_updated_at(): ?UtcDateTime {

		return $this->updated_at;
	}
}
