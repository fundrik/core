<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository;

/**
 * Marks campaign repository write failures caused by a missing campaign ID.
 *
 * @since 0.1.0
 */
interface CampaignNotFoundExceptionInterface extends CampaignRepositoryExceptionInterface {}
