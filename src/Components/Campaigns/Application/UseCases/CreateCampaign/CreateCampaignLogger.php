<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign;

use Fundrik\Core\Components\Campaigns\Application\AbstractCampaignApplicationLogger;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepositoryExceptionInterface;
use Throwable;

/**
 * Logs the CreateCampaign use case execution.
 *
 * @since 0.1.0
 */
final readonly class CreateCampaignLogger extends AbstractCampaignApplicationLogger {

	private const OPERATION_CREATE = 'create_campaign';

	/**
	 * Logs the start of a create operation (debug).
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id The campaign ID being created.
	 *
	 * @codeCoverageIgnore
	 */
	public function log_create_started( int|string $id ): void {

		$this->logger->debug(
			'Creating campaign started.',
			$this->logger_context(
				[
					'operation' => self::OPERATION_CREATE,
					'id' => $id,
				],
			),
		);
	}

	/**
	 * Logs the repository failure during a create operation (error).
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id The campaign ID being created.
	 * @param CampaignRepositoryExceptionInterface $e The repository exception that occurred.
	 */
	public function log_create_failed_repository( int|string $id, CampaignRepositoryExceptionInterface $e ): void {

		$this->logger->error(
			'Creating campaign failed (repository error).',
			$this->logger_context(
				[
					'operation' => self::OPERATION_CREATE,
					'id' => $id,
					'exception' => $e,
				],
			),
		);
	}

	// phpcs:disable SlevomatCodingStandard.Files.LineLength.LineTooLong
	/**
	 * Logs a warning when publishing CampaignCreatedEvent fails (warning).
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id The campaign ID related to the failed publish.
	 * @param Throwable $e The exception thrown by a listener or the event bus.
	 */
	public function log_publish_created_event_failed( int|string $id, Throwable $e ): void {

		$this->logger->warning(
			'Publishing CampaignCreatedEvent failed (event bus error).',
			$this->logger_context(
				[
					'operation' => self::OPERATION_CREATE,
					'id' => $id,
					'exception' => $e,
				],
			),
		);
	}
	// phpcs:enable

	/**
	 * Logs the successful completion of a create operation (info).
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id The campaign ID created.
	 */
	public function log_create_succeeded( int|string $id ): void {

		$this->logger->info(
			'Creating campaign succeeded.',
			$this->logger_context(
				[
					'operation' => self::OPERATION_CREATE,
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

		return CreateCampaignHandler::class;
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
