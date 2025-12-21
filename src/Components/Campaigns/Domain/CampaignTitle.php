<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Domain;

use Fundrik\Core\Components\Campaigns\Domain\Exceptions\InvalidCampaignTitleException;

/**
 * Represents the title of a fundraising campaign.
 *
 * Validates that the title is non-empty and trimmed.
 *
 * @since 0.1.0
 */
final readonly class CampaignTitle {

	/**
	 * Private constructor, use factory method.
	 *
	 * @since 0.1.0
	 *
	 * @param string $value The validated campaign title.
	 */
	private function __construct(
		private string $value,
	) {}

	/**
	 * Creates a validated title value object.
	 *
	 * @since 0.1.0
	 *
	 * @param string $value The raw input title.
	 *
	 * @return self The campaign title value object.
	 *
	 * @throws InvalidCampaignTitleException When the title is empty or consists only of whitespace.
	 */
	public static function create( string $value ): self {

		if ( trim( $value ) === '' ) {
			throw new InvalidCampaignTitleException( 'Campaign title must not be empty or whitespace.' );
		}

		return new self( $value );
	}

	/**
	 * Returns the validated title string.
	 *
	 * @since 0.1.0
	 *
	 * @return string The campaign title.
	 */
	public function get_value(): string {

		return $this->value;
	}

	/**
	 * Checks whether this title is equal to another.
	 *
	 * @since 0.1.0
	 *
	 * @param self $other The title to compare with.
	 *
	 * @return bool True if the two title objects are equal.
	 */
	public function equals( self $other ): bool {

		return $this->value === $other->value;
	}
}
