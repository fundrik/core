<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Shared\Application;

use Fundrik\Core\Components\Shared\Application\Exceptions\InvalidUrlException;

/**
 * Represents a valid URL.
 *
 * @since 0.1.0
 */
final readonly class Url {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $value Validated URL.
	 */
	private function __construct(
		private string $value,
	) {}

	/**
	 * Creates a validated URL.
	 *
	 * @since 0.1.0
	 *
	 * @param string $value URL input.
	 *
	 * @return self URL value object.
	 *
	 * @throws InvalidUrlException When URL is invalid.
	 */
	public static function create( string $value ): self {

		if ( filter_var( $value, FILTER_VALIDATE_URL ) === false ) {
			throw new InvalidUrlException(
				sprintf( 'URL must be a valid URL. Given: "%s".', $value ),
			);
		}

		return new self( $value );
	}

	/**
	 * Returns the URL string.
	 *
	 * @since 0.1.0
	 *
	 * @return string URL string.
	 */
	public function get_value(): string {

		return $this->value;
	}

	/**
	 * Checks whether this URL equals another.
	 *
	 * @since 0.1.0
	 *
	 * @param self $other URL to compare with.
	 *
	 * @return bool True when URL strings are equal.
	 */
	public function equals( self $other ): bool {

		return $this->value === $other->value;
	}
}
