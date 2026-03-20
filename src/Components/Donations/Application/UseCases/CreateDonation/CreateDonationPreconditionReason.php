<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation;

/**
 * Specifies why create-donation precondition validation failed.
 *
 * @since 0.1.0
 */
enum CreateDonationPreconditionReason: string {

	case DonationStatusMustBePending = 'donation_status_must_be_pending';
	case CampaignLookupFailed = 'campaign_lookup_failed';
	case CampaignNotFound = 'campaign_not_found';
	case CampaignCannotReceiveDonations = 'campaign_cannot_receive_donations';
	case CampaignCurrencyMismatch = 'campaign_currency_mismatch';
}
