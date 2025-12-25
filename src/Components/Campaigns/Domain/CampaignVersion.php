<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Domain;

use Fundrik\Core\Components\Campaigns\Domain\Exceptions\InvalidCampaignVersionException;

/**
 * Represents the persisted version of a campaign.
 *
 * @since 0.1.0
 */
final readonly class CampaignVersion {

	/**
	 * Private constructor, use factory methods.
	 *
	 * @since 0.1.0
	 *
	 * @param int $value The validated campaign version.
	 */
	private function __construct(
		private int $value,
	) {}

	/**
	 * Creates a validated version value object.
	 *
	 * @since 0.1.0
	 *
	 * @param int $value The raw input version.
	 *
	 * @return self The campaign version value object.
	 *
	 * @throws InvalidCampaignVersionException When the version is not a positive integer.
	 */
	public static function create( int $value ): self {

		if ( $value <= 0 ) {
			throw new InvalidCampaignVersionException(
				sprintf( 'Campaign version must be a positive integer. Given: %d.', $value ),
			);
		}

		return new self( $value );
	}

	/**
	 * Creates the initial campaign version.
	 *
	 * @since 0.1.0
	 *
	 * @return self The initial campaign version.
	 */
	public static function initial(): self {

		return new self( 1 );
	}

	/**
	 * Returns the persisted version value.
	 *
	 * @since 0.1.0
	 *
	 * @return int The campaign version.
	 */
	public function get_value(): int {

		return $this->value;
	}

	/**
	 * Checks whether this version is equal to another.
	 *
	 * @since 0.1.0
	 *
	 * @param self $other The version to compare with.
	 *
	 * @return bool True if the two version objects are equal.
	 */
	public function equals( self $other ): bool {

		return $this->value === $other->value;
	}
}
