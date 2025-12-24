<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\SaveCampaign;

use Fundrik\Core\Components\Campaigns\Application\AbstractCampaignApplicationLogger;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepositorySaveResult;
use Throwable;

/**
 * Logs the SaveCampaign use case execution.
 *
 * @since 0.1.0
 */
final readonly class SaveCampaignLogger extends AbstractCampaignApplicationLogger {

	private const OPERATION_SAVE = 'save_campaign';

	/**
	 * Logs the start of a save operation (debug).
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id The campaign ID being saved.
	 *
	 * @codeCoverageIgnore
	 */
	public function log_save_started( int|string $id ): void {

		$this->logger->debug(
			'Saving campaign started.',
			$this->logger_context(
				[
					'operation' => self::OPERATION_SAVE,
					'id' => $id,
				],
			),
		);
	}

	/**
	 * Logs the repository failure during a save operation (error).
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id The campaign ID being saved.
	 * @param CampaignRepositoryExceptionInterface $e The repository exception that occurred.
	 */
	public function log_save_failed_repository( int|string $id, CampaignRepositoryExceptionInterface $e ): void {

		$this->logger->error(
			'Saving campaign failed (repository error).',
			$this->logger_context(
				[
					'operation' => self::OPERATION_SAVE,
					'id' => $id,
					'exception' => $e,
				],
			),
		);
	}

	/**
	 * Logs a warning when publishing CampaignCreatedEvent/CampaignUpdatedEvent fails (warning).
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id The campaign ID related to the failed publish.
	 * @param Throwable $e The exception thrown by a listener or the event bus.
	 * @param CampaignRepositorySaveResult $result The repository outcome that determined the event type.
	 */
	public function log_publish_saved_event_failed(
		int|string $id,
		Throwable $e,
		CampaignRepositorySaveResult $result,
	): void {

		$this->logger->warning(
			'Publishing campaign saved event failed (event bus error).',
			$this->logger_context(
				[
					'operation' => self::OPERATION_SAVE,
					'id' => $id,
					'save_result' => $result->value,
					'exception' => $e,
				],
			),
		);
	}

	/**
	 * Logs the successful completion of a save operation (info).
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id The campaign ID saved.
	 * @param CampaignRepositorySaveResult $result The repository outcome (Inserted or Updated).
	 */
	public function log_save_succeeded( int|string $id, CampaignRepositorySaveResult $result ): void {

		$this->logger->info(
			'Saving campaign succeeded.',
			$this->logger_context(
				[
					'operation' => self::OPERATION_SAVE,
					'id' => $id,
					'save_result' => $result->value,
				],
			),
		);
	}

	/**
	 * Returns the class name of the subject being logged.
	 *
	 * @since 0.1.0
	 *
	 * @return string The fully qualified class name of the subject service to attribute the log entries to.
	 *
	 * @phpstan-return class-string
	 */
	protected function subject_class(): string {

		return SaveCampaignHandler::class;
	}

	/**
	 * Provides platform-/runtime-specific context fields.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, mixed> The platform-specific context entries.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	protected function platform_context(): array {

		return [ 'system' => 'core' ];
	}
}
