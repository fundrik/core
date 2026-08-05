<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult;

/**
 * Specifies payment result processing outcomes.
 *
 * @since 0.1.0
 */
enum ProcessDonationPaymentResultStatus: string {

	/**
	 * Payment result was applied.
	 */
	case Applied = 'applied';

	/**
	 * Payment result matched already applied state.
	 */
	case Replayed = 'replayed';

	/**
	 * Payment result was ignored.
	 */
	case Ignored = 'ignored';
}
