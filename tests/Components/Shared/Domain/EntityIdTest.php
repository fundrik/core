<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Shared\Domain;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( EntityId::class )]
final class EntityIdTest extends FundrikTestCase {

	#[Test]
	public function creates_from_positive_int(): void {

		$campaign_id = EntityId::create( 123 );

		$this->assertSame( 123, $campaign_id->get_value() );
		$this->assertSame( 123, $campaign_id->get_as_int() );
	}

	#[Test]
	public function throws_when_negative_int_provided(): void {

		$this->expectException( InvalidEntityIdException::class );
		$this->expectExceptionMessage( 'EntityId must be a positive integer. Given: -123.' );

		EntityId::create( -123 );
	}

	#[Test]
	public function throws_when_zero_provided(): void {

		$this->expectException( InvalidEntityIdException::class );
		$this->expectExceptionMessage( 'EntityId must be a positive integer. Given: 0.' );

		EntityId::create( 0 );
	}

	#[Test]
	public function creates_from_valid_uuid(): void {

		$uuid = '0196930b-f2ef-7ec8-b685-cffc19cbf0e3';

		$campaign_id = EntityId::create( $uuid );

		$this->assertSame( $uuid, $campaign_id->get_value() );
		$this->assertSame( $uuid, $campaign_id->get_as_uuid() );
	}

	#[Test]
	public function throws_when_invalid_uuid_provided(): void {

		$this->expectException( InvalidEntityIdException::class );
		$this->expectExceptionMessage( 'EntityId must be a valid UUID. Given: "invalid-uuid".' );

		EntityId::create( 'invalid-uuid' );
	}

	#[Test]
	public function checks_uuid_case_normalization(): void {

		$upper = '0196A27F-1441-7692-AAEF-92889618FC12';
		$normalized = '0196a27f-1441-7692-aaef-92889618fc12';

		$campaign_id = EntityId::create( $upper );

		$this->assertSame( $normalized, $campaign_id->get_value() );
	}

	#[Test]
	public function get_value_returns_int_when_created_from_int(): void {

		$id = EntityId::create( 123 );

		$this->assertSame( 123, $id->get_value() );
		$this->assertIsInt( $id->get_value() );
	}

	#[Test]
	public function get_value_returns_uuid_when_created_from_uuid(): void {

		$uuid = '0196930b-f2ef-7ec8-b685-cffc19cbf0e3';
		$id = EntityId::create( $uuid );

		$this->assertSame( $uuid, $id->get_value() );
		$this->assertIsString( $id->get_value() );
	}

	#[Test]
	public function get_as_int_returns_integer_when_id_is_int(): void {

		$id = EntityId::create( 123 );

		$this->assertSame( 123, $id->get_as_int() );
	}

	#[Test]
	public function get_as_uuid_returns_string_when_id_is_uuid(): void {

		$uuid = '0196930b-f2ef-7ec8-b685-cffc19cbf0e3';
		$id = EntityId::create( $uuid );

		$this->assertSame( $uuid, $id->get_as_uuid() );
	}

	#[Test]
	public function get_as_int_throws_when_holds_uuid(): void {

		$uuid_id = EntityId::create( '0196930b-f2ef-7ec8-b685-cffc19cbf0e3' );

		$this->expectException( InvalidEntityIdException::class );
		$this->expectExceptionMessage( 'EntityId must be an integer.' );

		$uuid_id->get_as_int();
	}

	#[Test]
	public function get_as_uuid_throws_when_holds_int(): void {

		$int_id = EntityId::create( 10 );

		$this->expectException( InvalidEntityIdException::class );
		$this->expectExceptionMessage( 'EntityId must be a UUID string.' );

		$int_id->get_as_uuid();
	}

	#[Test]
	public function entity_ids_with_same_value_are_equal(): void {

		$id1 = EntityId::create( 1 );
		$id2 = EntityId::create( 1 );

		$this->assertTrue( $id1->equals( $id2 ) );
		$this->assertTrue( $id2->equals( $id1 ) );
	}

	#[Test]
	public function entity_ids_with_different_values_are_not_equal(): void {

		$id1 = EntityId::create( 1 );
		$id2 = EntityId::create( 2 );

		$this->assertFalse( $id1->equals( $id2 ) );
	}
}
