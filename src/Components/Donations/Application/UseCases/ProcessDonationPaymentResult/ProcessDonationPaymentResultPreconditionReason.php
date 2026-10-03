<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult;

/**
 * Specifies why payment-result precondition validation failed.
 *
 * @since 1.1.0
 */
enum ProcessDonationPaymentResultPreconditionReason: string {

	/**
	 * Donation does not exist.
	 */
	case DonationNotFound = 'donation_not_found';

	/**
	 * Donation has no attached payment.
	 */
	case PaymentNotAttached = 'payment_not_attached';

	/**
	 * Result payment ID differs from the attached payment ID.
	 */
	case PaymentIdMismatch = 'payment_id_mismatch';
}
