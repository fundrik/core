<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Shared\Domain;

use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidAmountException;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidCurrencyCodeException;

/**
 * Represents money with amount and currency.
 *
 * @since 0.1.0
 */
final readonly class Money {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param Amount $amount Positive amount.
	 * @param Currency $currency Currency.
	 */
	private function __construct(
		private Amount $amount,
		private Currency $currency,
	) {}

	/**
	 * Creates a validated money value object.
	 *
	 * @since 0.1.0
	 *
	 * @param int $amount Amount value.
	 * @param string $currency_code Currency code.
	 *
	 * @return self Money value object.
	 *
	 * @throws InvalidAmountException When amount is not positive.
	 * @throws InvalidCurrencyCodeException When currency code is not a valid ISO 4217 code.
	 */
	public static function create( int $amount, string $currency_code ): self {

		return new self(
			Amount::create( $amount ),
			Currency::create( $currency_code ),
		);
	}

	/**
	 * Returns the money amount value object.
	 *
	 * @since 0.1.0
	 *
	 * @return Amount Money amount.
	 */
	public function get_amount(): Amount {

		return $this->amount;
	}

	/**
	 * Returns the money currency value object.
	 *
	 * @since 0.1.0
	 *
	 * @return Currency Money currency.
	 */
	public function get_currency(): Currency {

		return $this->currency;
	}

	/**
	 * Checks whether this money value equals another.
	 *
	 * @since 0.1.0
	 *
	 * @param self $other Money value to compare with.
	 *
	 * @return bool True when amount and currency are equal.
	 */
	public function equals( self $other ): bool {

		return $this->amount->equals( $other->amount )
			&& $this->currency->equals( $other->currency );
	}
}
