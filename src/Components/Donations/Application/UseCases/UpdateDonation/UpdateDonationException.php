<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\UpdateDonation;

use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Throwable;

/**
 * Thrown when update-donation operation fails.
 *
 * @since 0.1.0
 */
final class UpdateDonationException extends DonationApplicationException {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param UseCaseFailureStage $stage Processing stage where failure happened.
	 * @param string $message Exception message.
	 * @param int $code Exception code.
	 * @param Throwable|null $previous Previous exception.
	 */
	public function __construct(
		private readonly UseCaseFailureStage $stage,
		string $message = '',
		int $code = 0,
		?Throwable $previous = null,
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
}
