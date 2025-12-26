<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Domain;

use Fundrik\Core\Components\Campaigns\Domain\CampaignVersion;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\InvalidCampaignVersionException;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( CampaignVersion::class )]
final class CampaignVersionTest extends FundrikTestCase {

	#[Test]
	public function create_accepts_positive_integer(): void {

		$version = CampaignVersion::create( 7 );

		$this->assertSame( 7, $version->get_value() );
	}

	#[Test]
	public function create_throws_when_version_is_zero(): void {

		$this->expectException( InvalidCampaignVersionException::class );
		$this->expectExceptionMessage( 'Campaign version must be a positive integer. Given: 0.' );

		CampaignVersion::create( 0 );
	}

	#[Test]
	public function create_throws_when_version_is_negative(): void {

		$this->expectException( InvalidCampaignVersionException::class );
		$this->expectExceptionMessage( 'Campaign version must be a positive integer. Given: -1.' );

		CampaignVersion::create( -1 );
	}

	#[Test]
	public function initial_returns_version_one(): void {

		$version = CampaignVersion::initial();

		$this->assertSame( 1, $version->get_value() );
	}

	#[Test]
	public function equals_returns_true_for_identical_versions(): void {

		$v1 = CampaignVersion::create( 3 );
		$v2 = CampaignVersion::create( 3 );

		$this->assertTrue( $v1->equals( $v2 ) );
	}

	#[Test]
	public function equals_returns_false_for_different_versions(): void {

		$v1 = CampaignVersion::create( 3 );
		$v2 = CampaignVersion::create( 4 );

		$this->assertFalse( $v1->equals( $v2 ) );
	}
}
