<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Domain;

use Fundrik\Core\Components\Donations\Domain\Exceptions\InvalidPaymentIdException;

/**
 * Represents an opaque payment-provider identifier.
 *
 * @since 1.1.0
 */
final readonly class PaymentId {

	/**
	 * Private constructor, use the factory method.
	 *
	 * @since 1.1.0
	 *
	 * @param string $value Validated payment ID.
	 */
	private function __construct(
		private string $value,
	) {}

	/**
	 * Creates a payment ID from a provider value.
	 *
	 * @since 1.1.0
	 *
	 * @param string|self $value Payment ID to validate.
	 *
	 * @return self Valid payment ID.
	 *
	 * @throws InvalidPaymentIdException When the payment ID is empty.
	 */
	public static function create( string|self $value ): self {

		if ( $value instanceof self ) {
			return $value;
		}

		if ( $value === '' ) {
			throw new InvalidPaymentIdException( 'Payment ID must be a non-empty string. Given: "".' );
		}

		return new self( $value );
	}

	/**
	 * Returns the provider payment ID.
	 *
	 * @since 1.1.0
	 *
	 * @return string Provider payment ID.
	 */
	public function get_value(): string {

		return $this->value;
	}

	/**
	 * Checks whether this payment ID equals another.
	 *
	 * @since 1.1.0
	 *
	 * @param self $other Payment ID to compare with.
	 *
	 * @return bool True when both payment IDs are equal.
	 */
	public function equals( self $other ): bool {

		return $this->value === $other->value;
	}
}
