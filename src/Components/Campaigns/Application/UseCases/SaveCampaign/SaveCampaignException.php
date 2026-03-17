<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\SaveCampaign;

use Fundrik\Core\Components\Campaigns\Application\Exceptions\CampaignApplicationException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Throwable;

/**
 * Thrown when save-campaign operation fails.
 *
 * @since 0.1.0
 */
final class SaveCampaignException extends CampaignApplicationException {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param UseCaseFailureStage $stage Processing stage where failure happened.
	 * @param string $message Exception message.
	 * @param Throwable|null $previous Previous exception.
	 */
	public function __construct(
		private readonly UseCaseFailureStage $stage,
		string $message = '',
		?Throwable $previous = null,
	) {

		parent::__construct( $message, 0, $previous );
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
