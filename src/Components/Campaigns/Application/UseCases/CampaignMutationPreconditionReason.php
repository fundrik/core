<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases;

/**
 * Specifies why campaign mutation precondition validation failed.
 *
 * @since 0.1.0
 */
enum CampaignMutationPreconditionReason: string {

	case CampaignLookupFailed = 'campaign_lookup_failed';
	case CampaignNotFound = 'campaign_not_found';
	case CampaignMutationRejected = 'campaign_mutation_rejected';
}
