<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign;

/**
 * Specifies why delete-campaign precondition validation failed.
 *
 * @since 0.1.0
 */
enum DeleteCampaignPreconditionReason: string {

	case DonationsLookupFailed = 'donations_lookup_failed';
	case HasDonations = 'has_donations';
}
