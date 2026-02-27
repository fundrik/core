<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Domain;

/**
 * Defines the internal donation status dictionary.
 *
 * @since 0.1.0
 */
enum DonationStatus: string {

	case Pending = 'pending';
	case Authorized = 'authorized';
	case Captured = 'captured';
	case Failed = 'failed';
	case Refunded = 'refunded';
	case Canceled = 'canceled';
}
