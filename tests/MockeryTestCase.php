<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

abstract class MockeryTestCase extends FundrikTestCase {

	// phpcs:ignore SlevomatCodingStandard.Classes.TraitUseSpacing.IncorrectLinesCountAfterLastUse
	use MockeryPHPUnitIntegration;
}
