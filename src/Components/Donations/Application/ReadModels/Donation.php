<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\ReadModels;

use Fundrik\Core\Components\Shared\Domain\UtcDateTime;

/**
 * Represents a donation exposed by the public read API.
 *
 * @since 0.1.0
 */
final readonly class Donation {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id Donation identifier.
	 * @param int|string $campaign_id Campaign identifier.
	 * @param int $amount Donation amount.
	 * @param string $currency_code Donation currency code.
	 * @param string $status Donation status.
	 * @param UtcDateTime $created_at Creation timestamp.
	 * @param UtcDateTime|null $updated_at Update timestamp, null otherwise.
	 */
	public function __construct(
		private int|string $id,
		private int|string $campaign_id,
		private int $amount,
		private string $currency_code,
		private string $status,
		private UtcDateTime $created_at,
		private ?UtcDateTime $updated_at = null,
	) {}

	/**
	 * Returns the donation identifier.
	 *
	 * @since 0.1.0
	 *
	 * @return int|string Donation identifier.
	 */
	public function get_id(): int|string {

		return $this->id;
	}

	/**
	 * Returns the campaign identifier.
	 *
	 * @since 0.1.0
	 *
	 * @return int|string Campaign identifier.
	 */
	public function get_campaign_id(): int|string {

		return $this->campaign_id;
	}

	/**
	 * Returns the donation amount.
	 *
	 * @since 0.1.0
	 *
	 * @return int Donation amount.
	 */
	public function get_amount(): int {

		return $this->amount;
	}

	/**
	 * Returns the donation currency code.
	 *
	 * @since 0.1.0
	 *
	 * @return string Donation currency code.
	 */
	public function get_currency_code(): string {

		return $this->currency_code;
	}

	/**
	 * Returns the donation status.
	 *
	 * @since 0.1.0
	 *
	 * @return string Donation status.
	 */
	public function get_status(): string {

		return $this->status;
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
