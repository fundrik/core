<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Shared\Domain;

use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidCurrencyCodeException;

/**
 * Represents an ISO 4217 currency code.
 *
 * @since 0.1.0
 */
final readonly class Currency {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $code Uppercase ISO 4217 currency code.
	 */
	private function __construct(
		private string $code,
	) {}

	/**
	 * Creates a validated currency code value object.
	 *
	 * @since 0.1.0
	 *
	 * @param string $code Currency code.
	 *
	 * @return self Currency code value object.
	 *
	 * @throws InvalidCurrencyCodeException When code is not a valid ISO 4217 code.
	 */
	public static function create( string $code ): self {

		$code = strtoupper( trim( $code ) );

		if ( preg_match( '/^[A-Z]{3}$/', $code ) !== 1 ) {
			throw new InvalidCurrencyCodeException(
				sprintf( 'Currency code must be a valid ISO 4217 code. Given: "%s".', $code ),
			);
		}

		return new self( $code );
	}

	/**
	 * Returns the currency code.
	 *
	 * @since 0.1.0
	 *
	 * @return string Currency code.
	 */
	public function get_code(): string {

		return $this->code;
	}

	/**
	 * Checks whether the currency equals another currency.
	 *
	 * @since 0.1.0
	 *
	 * @param self $other Other currency.
	 *
	 * @return bool True when currency codes are equal.
	 */
	public function equals( self $other ): bool {

		return $this->code === $other->code;
	}
}
