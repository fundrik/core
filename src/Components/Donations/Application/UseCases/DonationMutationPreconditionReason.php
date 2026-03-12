<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases;

/**
 * Specifies why donation mutation precondition validation failed.
 *
 * @since 0.1.0
 */
enum DonationMutationPreconditionReason: string {

	case DonationLookupFailed = 'donation_lookup_failed';
	case DonationNotFound = 'donation_not_found';
	case DonationMutationRejected = 'donation_mutation_rejected';
}
