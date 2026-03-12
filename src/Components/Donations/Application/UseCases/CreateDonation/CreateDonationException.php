<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation;

use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Throwable;

/**
 * Thrown when create-donation operation fails.
 *
 * @since 0.1.0
 */
final class CreateDonationException extends DonationApplicationException {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param UseCaseFailureStage $stage Processing stage where failure happened.
	 * @param string $message Exception message.
	 * @param int $code Exception code.
	 * @param Throwable|null $previous Previous exception.
	 * @param CreateDonationPreconditionReason|null $reason Optional precondition failure reason.
	 */
	public function __construct(
		private readonly UseCaseFailureStage $stage,
		string $message = '',
		int $code = 0,
		?Throwable $previous = null,
		private readonly ?CreateDonationPreconditionReason $reason = null,
	) {

		parent::__construct( $message, $code, $previous );
	}

	/**
	 * Returns processing stage where failure happened.
	 *
	 * @since 0.1.0
	 *
	 * @return UseCaseFailureStage Failure stage.
	 */
	public function get_stage(): UseCaseFailureStage {

		return $this->stage;
	}

	/**
	 * Returns precondition failure reason, when available.
	 *
	 * @since 0.1.0
	 *
	 * @return CreateDonationPreconditionReason|null Precondition reason.
	 */
	public function get_reason(): ?CreateDonationPreconditionReason {

		return $this->reason;
	}
}
