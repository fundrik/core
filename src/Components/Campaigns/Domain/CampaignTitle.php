<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Domain;

use Fundrik\Core\Components\Campaigns\Domain\Exceptions\InvalidCampaignTitleException;

/**
 * Represents a non-empty fundraising campaign title.
 *
 * @since 0.1.0
 */
final readonly class CampaignTitle {

	/**
	 * Private constructor, use factory method.
	 *
	 * @since 0.1.0
	 *
	 * @param string $value Validated campaign title.
	 */
	private function __construct(
		private string $value,
	) {}

	/**
	 * Creates a validated title value object.
	 *
	 * @since 0.1.0
	 *
	 * @param string $value Raw input title.
	 *
	 * @return self Campaign title value object.
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
	 * @return string Campaign title.
	 */
	public function get_value(): string {

		return $this->value;
	}

	/**
	 * Checks whether this title is equal to another.
	 *
	 * @since 0.1.0
	 *
	 * @param self $other Title to compare with.
	 *
	 * @return bool True when the two title objects are equal.
	 */
	public function equals( self $other ): bool {

		return $this->value === $other->value;
	}
}
