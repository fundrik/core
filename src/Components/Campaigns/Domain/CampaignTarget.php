<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Domain;

use Fundrik\Core\Components\Campaigns\Domain\Exceptions\InvalidCampaignTargetException;
use Fundrik\Core\Components\Shared\Domain\Amount;
use Fundrik\Core\Components\Shared\Domain\Currency;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidAmountException;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidCurrencyCodeException;

/**
 * Represents campaign targeting in the campaign currency.
 *
 * @since 0.1.0
 */
final readonly class CampaignTarget {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param Currency $currency Campaign currency.
	 * @param Amount|null $amount Target amount, if configured.
	 */
	private function __construct(
		private Currency $currency,
		private ?Amount $amount,
	) {}

	/**
	 * Creates a campaign target from primitive values.
	 *
	 * @since 0.1.0
	 *
	 * @param string $currency_code Campaign currency code.
	 * @param int|null $target_amount Target amount, if configured.
	 *
	 * @return self Campaign target.
	 *
	 * @throws InvalidCampaignTargetException When the target amount is invalid.
	 */
	public static function create( string $currency_code, ?int $target_amount ): self {

		$currency = self::create_currency( $currency_code );

		if ( $target_amount === null ) {
			return new self( $currency, null );
		}

		return new self( $currency, self::create_amount( $target_amount ) );
	}

	/**
	 * Returns the campaign currency.
	 *
	 * @since 0.1.0
	 *
	 * @return Currency Campaign currency.
	 */
	public function get_currency(): Currency {

		return $this->currency;
	}

	/**
	 * Returns whether the target amount is configured.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True when the target amount is configured.
	 */
	public function has_amount(): bool {

		return $this->amount !== null;
	}

	/**
	 * Returns the target amount.
	 *
	 * @since 0.1.0
	 *
	 * @return Amount|null Target amount, if configured.
	 */
	public function get_amount(): ?Amount {

		return $this->amount;
	}

	/**
	 * Returns a copy with the same currency and a new amount.
	 *
	 * @since 0.1.0
	 *
	 * @param Amount|null $target_amount Target amount, or null to clear it.
	 *
	 * @return self Updated campaign target.
	 */
	public function with_amount( ?Amount $target_amount ): self {

		return new self( $this->currency, $target_amount );
	}

	/**
	 * Checks whether the target equals another target.
	 *
	 * @since 0.1.0
	 *
	 * @param self $other Other campaign target.
	 *
	 * @return bool True when currency code and amount are equal.
	 */
	public function equals( self $other ): bool {

		return $this->currency->equals( $other->currency )
			&& (
				( $this->amount === null && $other->amount === null )
				|| ( $this->amount !== null && $other->amount !== null && $this->amount->equals( $other->amount ) )
			);
	}

	/**
	 * Creates a validated campaign currency value object.
	 *
	 * @since 0.1.0
	 *
	 * @param string $currency_code Campaign currency code.
	 *
	 * @return Currency Campaign currency.
	 *
	 * @throws InvalidCampaignTargetException When the campaign currency code is invalid.
	 */
	private static function create_currency( string $currency_code ): Currency {

		try {
			return Currency::create( $currency_code );
		} catch ( InvalidCurrencyCodeException $e ) {
			throw new InvalidCampaignTargetException(
				sprintf(
					'Campaign currency code must be a valid ISO 4217 code. Given: "%s".',
					strtoupper( trim( $currency_code ) ),
				),
				previous: $e,
			);
		}
	}

	/**
	 * Creates a validated target amount value object.
	 *
	 * @since 0.1.0
	 *
	 * @param int $target_amount Target amount.
	 *
	 * @return Amount Target amount.
	 *
	 * @throws InvalidCampaignTargetException When the target amount is invalid.
	 */
	private static function create_amount( int $target_amount ): Amount {

		try {
			return Amount::create( $target_amount );
		} catch ( InvalidAmountException $e ) {
			throw new InvalidCampaignTargetException(
				sprintf( 'Target amount must be positive. Given: %d.', $target_amount ),
				previous: $e,
			);
		}
	}
}
