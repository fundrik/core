<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Exceptions;

use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationException;

/**
 * Serves as the base exception for campaign application errors.
 *
 * @since 0.1.0
 */
abstract class CampaignApplicationException extends FundrikApplicationException {}
