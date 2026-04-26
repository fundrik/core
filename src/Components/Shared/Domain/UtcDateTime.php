<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Shared\Domain;

use DateTimeImmutable;
use DateTimeZone;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidUtcDateTimeException;
use ValueError;

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
	 * Creates a UTC timestamp value object from a formatted string.
	 *
	 * @since 0.1.0
	 *
	 * @param string $value Formatted timestamp string.
	 * @param string $format DateTime format string.
	 *
	 * @return self UTC timestamp value object.
	 *
	 * @throws InvalidUtcDateTimeException When timestamp cannot be parsed as a valid date using the given format.
	 */
	public static function create_from_format( string $value, string $format ): self {

		$exception_message = sprintf( 'Timestamp must be parseable using format "%s". Given: "%s".', $format, $value );

		try {
			$date_time = DateTimeImmutable::createFromFormat(
				'!' . $format,
				$value,
				new DateTimeZone( self::UTC_TIMEZONE ),
			);
		} catch ( ValueError $e ) {
			throw new InvalidUtcDateTimeException( $exception_message, previous: $e );
		}

		if ( $date_time === false || DateTimeImmutable::getLastErrors() !== false ) {
			throw new InvalidUtcDateTimeException( $exception_message );
		}

		return self::create( $date_time );
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
	 * Formats UTC timestamp using DateTime format.
	 *
	 * @since 0.1.0
	 *
	 * @param string $format DateTime format string.
	 *
	 * @return string Formatted UTC timestamp.
	 */
	public function format( string $format ): string {

		return $this->value->format( $format );
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
