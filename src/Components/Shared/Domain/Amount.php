<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Shared\Domain;

use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidAmountException;

/**
 * Represents a positive integer amount.
 *
 * @since 0.1.0
 */
final readonly class Amount {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param int $value Positive integer amount.
	 */
	private function __construct(
		private int $value,
	) {}

	/**
	 * Creates a validated amount value object.
	 *
	 * @since 0.1.0
	 *
	 * @param int $value Amount value.
	 *
	 * @return self Amount value object.
	 *
	 * @throws InvalidAmountException When amount is not positive.
	 */
	public static function create( int $value ): self {

		if ( $value <= 0 ) {
			throw new InvalidAmountException(
				sprintf( 'Amount must be a positive integer. Given: %d.', $value ),
			);
		}

		return new self( $value );
	}

	/**
	 * Returns the amount value.
	 *
	 * @since 0.1.0
	 *
	 * @return int Amount value.
	 */
	public function get_value(): int {

		return $this->value;
	}

	/**
	 * Checks whether the amount equals another amount.
	 *
	 * @since 0.1.0
	 *
	 * @param self $other Other amount.
	 *
	 * @return bool True when amount values are equal.
	 */
	public function equals( self $other ): bool {

		return $this->value === $other->value;
	}
}
