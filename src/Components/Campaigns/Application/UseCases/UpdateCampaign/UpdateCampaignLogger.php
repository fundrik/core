<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\UpdateCampaign;

use Fundrik\Core\Components\Campaigns\Application\AbstractCampaignApplicationLogger;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepositoryExceptionInterface;
use Throwable;

/**
 * Logs the UpdateCampaign use case execution.
 *
 * @since 0.1.0
 */
final readonly class UpdateCampaignLogger extends AbstractCampaignApplicationLogger {

	private const OPERATION_UPDATE = 'update_campaign';

	/**
	 * Logs the start of an update operation (debug).
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id The campaign ID being updated.
	 *
	 * @codeCoverageIgnore
	 */
	public function log_update_started( int|string $id ): void {

		$this->logger->debug(
			'Updating campaign started.',
			$this->logger_context(
				[
					'operation' => self::OPERATION_UPDATE,
					'id' => $id,
				],
			),
		);
	}

	/**
	 * Logs the repository failure during an update operation (error).
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id The campaign ID being updated.
	 * @param CampaignRepositoryExceptionInterface $e The repository exception that occurred.
	 */
	public function log_update_failed_repository( int|string $id, CampaignRepositoryExceptionInterface $e ): void {

		$this->logger->error(
			'Updating campaign failed (repository error).',
			$this->logger_context(
				[
					'operation' => self::OPERATION_UPDATE,
					'id' => $id,
					'exception' => $e,
				],
			),
		);
	}

	/**
	 * Logs a warning when publishing CampaignUpdatedEvent fails (warning).
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id The campaign ID related to the failed publish.
	 * @param Throwable $e The exception thrown by a listener or the event bus.
	 */
	public function log_publish_updated_event_failed( int|string $id, Throwable $e ): void {

		$this->logger->warning(
			'Publishing CampaignUpdatedEvent failed (event bus error).',
			$this->logger_context(
				[
					'operation' => self::OPERATION_UPDATE,
					'id' => $id,
					'exception' => $e,
				],
			),
		);
	}

	/**
	 * Logs the successful completion of an update operation (info).
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id The campaign ID updated.
	 */
	public function log_update_succeeded( int|string $id ): void {

		$this->logger->info(
			'Updating campaign succeeded.',
			$this->logger_context(
				[
					'operation' => self::OPERATION_UPDATE,
					'id' => $id,
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

		return UpdateCampaignHandler::class;
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
