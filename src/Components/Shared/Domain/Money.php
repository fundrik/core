<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Shared\Domain;

use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidMoneyAmountException;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidMoneyCurrencyException;

/**
 * Represents money in minor currency units.
 *
 * @since 0.1.0
 */
final readonly class Money {

	/**
	 * Private constructor, use factory method.
	 *
	 * @since 0.1.0
	 *
	 * @param int $amount_minor The non-negative amount in minor units.
	 * @param string $currency The uppercase ISO 4217 currency code.
	 */
	private function __construct(
		private int $amount_minor,
		private string $currency,
	) {}

	/**
	 * Creates a validated money value object.
	 *
	 * @since 0.1.0
	 *
	 * @param int $amount_minor The non-negative amount in minor units.
	 * @param string $currency The ISO 4217 currency code.
	 *
	 * @return self The money value object.
	 *
	 * @throws InvalidMoneyAmountException When amount is negative.
	 * @throws InvalidMoneyCurrencyException When currency is not a valid ISO 4217 code.
	 */
	public static function create( int $amount_minor, string $currency ): self {

		if ( $amount_minor < 0 ) {
			throw new InvalidMoneyAmountException(
				sprintf( 'Money amount must be zero or positive integer in minor units. Given: %d.', $amount_minor ),
			);
		}

		$currency = strtoupper( trim( $currency ) );

		if ( preg_match( '/^[A-Z]{3}$/', $currency ) !== 1 ) {
			throw new InvalidMoneyCurrencyException(
				sprintf( 'Money currency must be a valid ISO 4217 code. Given: "%s".', $currency ),
			);
		}

		return new self( $amount_minor, $currency );
	}

	/**
	 * Returns money amount in minor units.
	 *
	 * @since 0.1.0
	 *
	 * @return int The non-negative amount.
	 */
	public function get_amount_minor(): int {

		return $this->amount_minor;
	}

	/**
	 * Returns money currency.
	 *
	 * @since 0.1.0
	 *
	 * @return string The ISO 4217 currency code.
	 */
	public function get_currency(): string {

		return $this->currency;
	}

	/**
	 * Checks whether this money value equals another.
	 *
	 * @since 0.1.0
	 *
	 * @param self $other The money value to compare with.
	 *
	 * @return bool True when amount and currency are equal.
	 */
	public function equals( self $other ): bool {

		return $this->amount_minor === $other->amount_minor
			&& $this->currency === $other->currency;
	}
}
