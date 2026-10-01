<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Shared\Domain;

use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityVersionException;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( EntityVersion::class )]
final class EntityVersionTest extends FundrikTestCase {

	#[Test]
	public function create_accepts_positive_integer(): void {

		$version = EntityVersion::create( 7 );

		$this->assertSame( 7, $version->get_value() );
	}

	#[Test]
	public function create_throws_when_version_is_zero(): void {

		$this->expectException( InvalidEntityVersionException::class );
		$this->expectExceptionMessage( 'Entity version must be a positive integer. Given: 0.' );

		EntityVersion::create( 0 );
	}

	#[Test]
	public function create_throws_when_version_is_negative(): void {

		$this->expectException( InvalidEntityVersionException::class );
		$this->expectExceptionMessage( 'Entity version must be a positive integer. Given: -1.' );

		EntityVersion::create( -1 );
	}

	#[Test]
	public function initial_returns_version_one(): void {

		$version = EntityVersion::initial();

		$this->assertSame( 1, $version->get_value() );
	}

	#[Test]
	public function equals_returns_true_for_identical_versions(): void {

		$v1 = EntityVersion::create( 3 );
		$v2 = EntityVersion::create( 3 );

		$this->assertTrue( $v1->equals( $v2 ) );
	}

	#[Test]
	public function equals_returns_false_for_different_versions(): void {

		$v1 = EntityVersion::create( 3 );
		$v2 = EntityVersion::create( 4 );

		$this->assertFalse( $v1->equals( $v2 ) );
	}

	#[Test]
	public function next_returns_incremented_version(): void {

		$version = EntityVersion::create( 5 );

		$next = $version->next();

		$this->assertSame( 6, $next->get_value() );
	}

	#[Test]
	public function next_does_not_mutate_original_version(): void {

		$version = EntityVersion::create( 5 );

		$version->next();

		$this->assertSame( 5, $version->get_value() );
	}
}
