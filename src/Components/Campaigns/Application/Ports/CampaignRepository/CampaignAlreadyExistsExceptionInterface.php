<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository;

/**
 * Marks campaign repository insert failures caused by an existing campaign ID.
 *
 * @since 0.1.0
 */
interface CampaignAlreadyExistsExceptionInterface extends CampaignRepositoryExceptionInterface {}
