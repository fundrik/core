<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Shared\Domain;

use DateTimeImmutable;
use DateTimeZone;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidUtcDateTimeException;

/**
 * Represents a timestamp normalized to UTC timezone.
 *
 * @since 0.1.0
 */
final readonly class UtcDateTime {

	/**
	 * UTC timezone name.
	 *
	 * @since 0.1.0
	 */
	private const string UTC_TIMEZONE = 'UTC';

	/**
	 * Private constructor, use factory methods.
	 *
	 * @since 0.1.0
	 *
	 * @param DateTimeImmutable $value UTC timestamp.
	 */
	private function __construct(
		private DateTimeImmutable $value,
	) {}

	/**
	 * Creates a UTC timestamp value object.
	 *
	 * @since 0.1.0
	 *
	 * @param DateTimeImmutable $value Timestamp to validate.
	 *
	 * @return self UTC timestamp value object.
	 *
	 * @throws InvalidUtcDateTimeException When timestamp offset is not UTC.
	 */
	public static function create( DateTimeImmutable $value ): self {

		if ( $value->getOffset() !== 0 ) {
			throw new InvalidUtcDateTimeException(
				sprintf(
					'Timestamp must use UTC timezone offset. Given: "%s".',
					$value->format( 'P' ),
				),
			);
		}

		return new self( $value->setTimezone( new DateTimeZone( self::UTC_TIMEZONE ) ) );
	}

	/**
	 * Returns current UTC timestamp.
	 *
	 * @since 0.1.0
	 *
	 * @return self Current UTC timestamp.
	 */
	public static function now(): self {

		return new self( new DateTimeImmutable( 'now', new DateTimeZone( self::UTC_TIMEZONE ) ) );
	}

	/**
	 * Returns UTC timestamp value.
	 *
	 * @since 0.1.0
	 *
	 * @return DateTimeImmutable UTC timestamp.
	 */
	public function get_value(): DateTimeImmutable {

		return $this->value;
	}

	/**
	 * Checks whether this UTC timestamp equals another.
	 *
	 * @since 0.1.0
	 *
	 * @param self $other UTC timestamp to compare with.
	 *
	 * @return bool True when both timestamps represent the same instant.
	 */
	public function equals( self $other ): bool {

		return $this->value->format( 'U.u' ) === $other->value->format( 'U.u' );
	}
}
