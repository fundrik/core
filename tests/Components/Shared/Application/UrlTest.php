<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Shared\Application;

use Fundrik\Core\Components\Shared\Application\Exceptions\InvalidUrlException;
use Fundrik\Core\Components\Shared\Application\Url;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( Url::class )]
#[UsesClass( InvalidUrlException::class )]
final class UrlTest extends FundrikTestCase {

	#[Test]
	public function create_builds_valid_url(): void {

		$url = Url::create( 'https://fundrik.test/success' );

		$this->assertSame( 'https://fundrik.test/success', $url->get_value() );
	}

	#[Test]
	public function create_throws_when_url_is_invalid(): void {

		$this->expectException( InvalidUrlException::class );
		$this->expectExceptionMessage( 'URL must be a valid URL. Given: "not-a-url".' );

		Url::create( 'not-a-url' );
	}

	#[Test]
	public function equals_returns_true_for_same_value(): void {

		$url1 = Url::create( 'https://fundrik.test/success' );
		$url2 = Url::create( 'https://fundrik.test/success' );

		$this->assertTrue( $url1->equals( $url2 ) );
	}
}
