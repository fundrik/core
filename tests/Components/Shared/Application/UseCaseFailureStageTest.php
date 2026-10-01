<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Shared\Application;

use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( UseCaseFailureStage::class )]
final class UseCaseFailureStageTest extends FundrikTestCase {

	#[Test]
	public function exposes_expected_values(): void {

		$this->assertSame( 'precondition', UseCaseFailureStage::Precondition->value );
		$this->assertSame( 'persistence', UseCaseFailureStage::Persistence->value );
		$this->assertSame( 'external', UseCaseFailureStage::External->value );
		$this->assertSame( 'event_publish', UseCaseFailureStage::EventPublish->value );
	}
}
