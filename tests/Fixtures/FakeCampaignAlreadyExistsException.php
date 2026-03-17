<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Fixtures;

use Exception;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignAlreadyExistsExceptionInterface;

final class FakeCampaignAlreadyExistsException extends Exception implements CampaignAlreadyExistsExceptionInterface {}
