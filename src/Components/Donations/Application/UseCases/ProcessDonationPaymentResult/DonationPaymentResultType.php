<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult;

/**
 * Specifies normalized donation payment result types.
 *
 * @since 0.1.0
 */
enum DonationPaymentResultType: string {

	/**
	 * Payment succeeded result.
	 */
	case Succeeded = 'succeeded';

	/**
	 * Payment rejected result.
	 */
	case Rejected = 'rejected';

	/**
	 * Payment refunded result.
	 */
	case Refunded = 'refunded';
}
