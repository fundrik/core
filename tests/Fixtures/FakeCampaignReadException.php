<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Fixtures;

use Exception;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRead\CampaignReadExceptionInterface;

final class FakeCampaignReadException extends Exception implements CampaignReadExceptionInterface {}
