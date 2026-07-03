<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Fixtures;

use Exception;
use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayExceptionInterface;

final class FakeDonationGatewayException extends Exception implements DonationGatewayExceptionInterface {}
