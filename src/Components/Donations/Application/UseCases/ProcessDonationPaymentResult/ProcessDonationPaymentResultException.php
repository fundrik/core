<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult;

use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Throwable;

/**
 * Thrown when processing a payment result fails.
 *
 * @since 1.0.0
 */
final class ProcessDonationPaymentResultException extends DonationApplicationException {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Added the `$reason` parameter.
	 *
	 * @param UseCaseFailureStage $stage Processing stage where failure happened.
	 * @param string $message Exception message.
	 * @param Throwable|null $previous Previous exception.
	 * @param ProcessDonationPaymentResultPreconditionReason|null $reason Optional precondition failure reason.
	 */
	public function __construct(
		private readonly UseCaseFailureStage $stage,
		string $message = '',
		?Throwable $previous = null,
		private readonly ?ProcessDonationPaymentResultPreconditionReason $reason = null,
	) {

		parent::__construct( $message, 0, $previous );
	}

	/**
	 * Returns processing stage where failure happened.
	 *
	 * @since 1.0.0
	 *
	 * @return UseCaseFailureStage Failure stage.
	 */
	public function get_stage(): UseCaseFailureStage {

		return $this->stage;
	}

	/**
	 * Returns precondition failure reason, when available.
	 *
	 * @since 1.1.0
	 *
	 * @return ProcessDonationPaymentResultPreconditionReason|null Precondition reason.
	 */
	public function get_reason(): ?ProcessDonationPaymentResultPreconditionReason {

		return $this->reason;
	}
}
