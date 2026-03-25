<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation;

/**
 * Specifies why create-donation precondition validation failed.
 *
 * @since 0.1.0
 */
enum CreateDonationPreconditionReason: string {

	case CampaignLookupFailed = 'campaign_lookup_failed';
	case CampaignNotFound = 'campaign_not_found';
	case CampaignDoesNotAcceptDonations = 'campaign_does_not_accept_donations';
}
