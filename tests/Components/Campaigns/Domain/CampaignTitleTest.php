<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Domain;

use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\InvalidCampaignTitleException;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( CampaignTitle::class )]
final class CampaignTitleTest extends FundrikTestCase {

	#[Test]
	public function creates_with_valid_title(): void {

		$title = CampaignTitle::create( 'Save the Rainforest' );

		$this->assertSame( 'Save the Rainforest', $title->get_value() );
	}

	#[Test]
	public function throws_when_title_is_empty(): void {

		$this->expectException( InvalidCampaignTitleException::class );
		$this->expectExceptionMessage( 'Campaign title must not be empty or whitespace.' );

		CampaignTitle::create( '' );
	}

	#[Test]
	public function throws_when_title_is_only_whitespace(): void {

		$this->expectException( InvalidCampaignTitleException::class );
		$this->expectExceptionMessage( 'Campaign title must not be empty or whitespace.' );

		CampaignTitle::create( '     ' );
	}

	#[Test]
	public function accepts_unicode_and_symbols(): void {

		$title = CampaignTitle::create( '💧 Вода для всех' );

		$this->assertSame( '💧 Вода для всех', $title->get_value() );
	}

	#[Test]
	public function equals_compares_by_value(): void {

		$a = CampaignTitle::create( 'Same' );
		$b = CampaignTitle::create( 'Same' );
		$c = CampaignTitle::create( 'Different' );

		$this->assertTrue( $a->equals( $b ) );
		$this->assertFalse( $a->equals( $c ) );
	}
}
