<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Shared\Domain;

use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityVersionException;

/**
 * Represents the version of an entity state in persistence, used for optimistic concurrency control.
 *
 * @since 0.1.0
 */
final readonly class EntityVersion {

	/**
	 * Private constructor, use factory methods.
	 *
	 * @since 0.1.0
	 *
	 * @param int $value Holds the validated persisted entity-state version.
	 */
	private function __construct(
		private int $value,
	) {}

	/**
	 * Creates a validated persisted entity-state version value object.
	 *
	 * @since 0.1.0
	 *
	 * @param int $value Accepts the input persisted version.
	 *
	 * @return self Provides the persisted entity-state version value object.
	 *
	 * @throws InvalidEntityVersionException When the version is not a positive integer.
	 */
	public static function create( int $value ): self {

		if ( $value <= 0 ) {
			throw new InvalidEntityVersionException(
				sprintf( 'Entity version must be a positive integer. Given: %d.', $value ),
			);
		}

		return new self( $value );
	}

	/**
	 * Creates the initial persisted entity-state version.
	 *
	 * @since 0.1.0
	 *
	 * @return self Provides the initial persisted entity-state version.
	 */
	public static function initial(): self {

		return new self( 1 );
	}

	/**
	 * Returns the persisted entity-state version value.
	 *
	 * @since 0.1.0
	 *
	 * @return int Provides the persisted entity-state version.
	 */
	public function get_value(): int {

		return $this->value;
	}

	/**
	 * Checks whether this version is equal to another.
	 *
	 * @since 0.1.0
	 *
	 * @param self $other Provides the version to compare with.
	 *
	 * @return bool True when the two version objects are equal.
	 */
	public function equals( self $other ): bool {

		return $this->value === $other->value;
	}

	/**
	 * Returns the next persisted entity-state version.
	 *
	 * @since 0.1.0
	 *
	 * @return self Provides the incremented persisted entity-state version.
	 */
	public function next(): self {

		return new self( $this->value + 1 );
	}
}
