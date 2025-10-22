<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Loggers\Traits;

use Fundrik\Core\Components\Campaigns\Application\Ports\Out\CampaignRepositoryExceptionInterface;
use Throwable;

/**
 * Adds logging for 'delete' operations.
 *
 * @since 0.1.0
 */
trait DeleteLoggingTrait {

	private const OPERATION_DELETE = 'delete_campaign';

	/**
	 * Logs the start of a delete operation (debug).
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id The campaign ID being deleted.
	 *
	 * @codeCoverageIgnore
	 */
	public function log_delete_started( int|string $id ): void {

		$this->logger->debug(
			'Deleting campaign started.',
			$this->logger_context(
				[
					'operation' => self::OPERATION_DELETE,
					'id' => $id,
				],
			),
		);
	}

	/**
	 * Logs repository failure during a delete operation (error).
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id The campaign ID being deleted.
	 * @param CampaignRepositoryExceptionInterface $e The repository exception that occurred.
	 */
	public function log_delete_failed_repository( int|string $id, CampaignRepositoryExceptionInterface $e ): void {

		$this->logger->error(
			'Deleting campaign failed (repository error).',
			$this->logger_context(
				[
					'operation' => self::OPERATION_DELETE,
					'id' => $id,
					'exception' => $e,
					'exception_class' => $e::class,
				],
			),
		);
	}

	/**
	 * Logs a warning when publishing CampaignDeletedEvent fails (warning).
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id The campaign ID related to the failed publish.
	 * @param Throwable $e The exception thrown by a listener or the event bus.
	 */
	public function log_publish_deleted_event_failed( int|string $id, Throwable $e ): void {

		$this->logger->warning(
			'Publishing CampaignDeletedEvent failed (event bus error).',
			$this->logger_context(
				[
					'operation' => self::OPERATION_DELETE,
					'id' => $id,
					'exception' => $e,
					'exception_class' => $e::class,
				],
			),
		);
	}

	/**
	 * Logs successful completion of a delete operation (info).
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id The campaign ID deleted.
	 */
	public function log_delete_succeeded( int|string $id ): void {

		$this->logger->info(
			'Deleting campaign succeeded.',
			$this->logger_context(
				[
					'operation' => self::OPERATION_DELETE,
					'id' => $id,
				],
			),
		);
	}
}
