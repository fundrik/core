<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Shared\Domain;

use DateTimeImmutable;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidUtcDateTimeException;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( UtcDateTime::class )]
#[UsesClass( InvalidUtcDateTimeException::class )]
final class UtcDateTimeTest extends FundrikTestCase {

	#[Test]
	public function create_accepts_utc_offset_and_normalizes_timezone_name(): void {

		$date_time = new DateTimeImmutable( '2026-03-01T10:00:00+00:00' );

		$utc_date_time = UtcDateTime::create( $date_time );

		$this->assertSame( 'UTC', $utc_date_time->get_value()->getTimezone()->getName() );
		$this->assertSame( $date_time->getTimestamp(), $utc_date_time->get_value()->getTimestamp() );
	}

	#[Test]
	public function create_throws_when_timezone_offset_is_not_utc(): void {

		$this->expectException( InvalidUtcDateTimeException::class );
		$this->expectExceptionMessage( 'Timestamp must use UTC timezone offset. Given: "+03:00".' );

		UtcDateTime::create( new DateTimeImmutable( '2026-03-01T10:00:00+03:00' ) );
	}

	#[Test]
	public function now_returns_utc_timestamp(): void {

		$utc_date_time = UtcDateTime::now();

		$this->assertSame( 'UTC', $utc_date_time->get_value()->getTimezone()->getName() );
	}

	#[Test]
	public function format_returns_formatted_utc_timestamp(): void {

		$utc_date_time = UtcDateTime::create( new DateTimeImmutable( '2026-03-01T10:00:00+00:00' ) );

		$this->assertSame( '2026-03-01T10:00:00+00:00', $utc_date_time->format( 'Y-m-d\TH:i:sP' ) );
	}

	#[Test]
	public function equals_compares_timestamp_values(): void {

		$date_time1 = UtcDateTime::create( new DateTimeImmutable( '2026-03-01T10:00:00+00:00' ) );
		$date_time2 = UtcDateTime::create( new DateTimeImmutable( '2026-03-01T10:00:00+00:00' ) );
		$date_time3 = UtcDateTime::create( new DateTimeImmutable( '2026-03-01T10:00:01+00:00' ) );

		$this->assertTrue( $date_time1->equals( $date_time2 ) );
		$this->assertFalse( $date_time1->equals( $date_time3 ) );
	}
}
