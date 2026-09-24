<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Domain\Exceptions;

use Fundrik\Core\Components\Shared\Domain\Exceptions\FundrikDomainException;

/**
 * Serves as the base exception for campaign domain errors.
 *
 * @since 1.0.0
 */
abstract class CampaignDomainException extends FundrikDomainException {}
