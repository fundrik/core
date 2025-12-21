<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Shared\Domain;

use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Uuid;

/**
 * Represents a strongly typed identifier for a domain entity.
 *
 * Accepts either a positive integer or a valid UUID.
 *
 * @since 0.1.0
 */
final readonly class EntityId {

	/**
	 * Private constructor, use factory method.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $value The validated ID.
	 */
	private function __construct(
		private int|string $value,
	) {}

	/**
	 * Creates an EntityId from a raw ID.
	 *
	 * Throws if the value cannot be validated.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $value The ID to validate.
	 *
	 * @return self A valid EntityId instance.
	 *
	 * @throws InvalidEntityIdException When the ID is neither a positive integer nor a valid UUID.
	 */
	public static function create( int|string $value ): self {

		if ( is_int( $value ) ) {
			return self::from_int( $value );
		}

		return self::from_uuid( $value );
	}

	/**
	 * Returns the underlying value of this EntityId without conversion.
	 *
	 * Can be either a positive integer or a UUID string, depending on how the ID was created.
	 *
	 * @since 0.1.0
	 *
	 * @return int|string The raw identifier value.
	 */
	public function get_value(): int|string {

		return $this->value;
	}

	/**
	 * Returns the underlying value as an integer.
	 *
	 * Throws if the EntityId does not hold an integer.
	 *
	 * @since 0.1.0
	 *
	 * @return int The positive integer ID value.
	 *
	 * @throws InvalidEntityIdException When the EntityId holds a UUID instead of an integer.
	 */
	public function get_as_int(): int {

		if ( ! is_int( $this->value ) ) {
			throw new InvalidEntityIdException( 'EntityId must be an integer.' );
		}

		return $this->value;
	}

	/**
	 * Returns the underlying value as a UUID string.
	 *
	 * Throws if the EntityId does not hold a UUID.
	 *
	 * @since 0.1.0
	 *
	 * @return string The canonical UUID string.
	 *
	 * @throws InvalidEntityIdException When the EntityId holds an integer instead of a UUID.
	 */
	public function get_as_uuid(): string {

		if ( ! is_string( $this->value ) ) {
			throw new InvalidEntityIdException( 'EntityId must be a UUID string.' );
		}

		return $this->value;
	}

	/**
	 * Checks whether this EntityId is equal to another.
	 *
	 * @since 0.1.0
	 *
	 * @param self $other The EntityId to compare with.
	 *
	 * @return bool True if the two EntityId objects are equal.
	 */
	public function equals( self $other ): bool {

		return $this->value === $other->value;
	}

	/**
	 * Creates an EntityId from a positive integer.
	 *
	 * Throws if the integer is non-positive.
	 *
	 * @since 0.1.0
	 *
	 * @param int $value The positive integer ID.
	 *
	 * @return self A valid EntityId instance.
	 *
	 * @throws InvalidEntityIdException When the integer is non-positive.
	 */
	private static function from_int( int $value ): self {

		if ( $value <= 0 ) {
			throw new InvalidEntityIdException(
				sprintf( 'EntityId must be a positive integer. Given: %d.', $value ),
			);
		}

		return new self( $value );
	}

	/**
	 * Creates an EntityId from a UUID.
	 *
	 * Throws if the UUID is not valid.
	 *
	 * @since 0.1.0
	 *
	 * @param string $uuid The valid UUID.
	 *
	 * @return self A valid EntityId instance.
	 *
	 * @throws InvalidEntityIdException When the UUID string is invalid.
	 */
	private static function from_uuid( string $uuid ): self {

		try {
			return new self( Uuid::fromString( $uuid )->toString() );
		} catch ( InvalidUuidStringException $e ) {

			throw new InvalidEntityIdException(
				message: sprintf( 'EntityId must be a valid UUID. Given: "%s".', $uuid ),
				previous: $e,
			);
		}
	}
}
