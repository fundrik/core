<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Fixtures;

use Exception;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignDetailsRead\CampaignDetailsReadExceptionInterface;

final class FakeCampaignDetailsReadException extends Exception implements CampaignDetailsReadExceptionInterface {}
