<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Loggers\Traits;

use Fundrik\Core\Components\Campaigns\Application\Ports\Out\CampaignRepositoryExceptionInterface;

/**
 * Adds logging for 'find by id' operations.
 *
 * @since 0.1.0
 */
trait FindByIdLoggingTrait {

	private const OPERATION_FIND_BY_ID = 'find_campaign_by_id';

	/**
	 * Logs the start of a find-by-ID operation (debug).
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id The campaign ID being searched.
	 *
	 * @codeCoverageIgnore
	 */
	public function log_find_by_id_started( int|string $id ): void {

		$this->logger->debug(
			'Finding campaign by ID started.',
			$this->logger_context(
				[
					'operation' => self::OPERATION_FIND_BY_ID,
					'id' => $id,
				],
			),
		);
	}

	/**
	 * Logs repository failure during a find-by-ID operation (error).
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id The campaign ID being searched.
	 * @param CampaignRepositoryExceptionInterface $e The repository exception that occurred.
	 */
	public function log_find_by_id_failed_repository( int|string $id, CampaignRepositoryExceptionInterface $e ): void {

		$this->logger->error(
			'Finding campaign by ID failed (repository error).',
			$this->logger_context(
				[
					'operation' => self::OPERATION_FIND_BY_ID,
					'id' => $id,
					'exception' => $e,
					'exception_class' => $e::class,
				],
			),
		);
	}

	/**
	 * Logs that the requested campaign was not found (debug).
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id The campaign ID that was not found.
	 *
	 * @codeCoverageIgnore
	 */
	public function log_find_by_id_not_found( int|string $id ): void {

		$this->logger->debug(
			'Finding campaign by ID not found.',
			$this->logger_context(
				[
					'operation' => self::OPERATION_FIND_BY_ID,
					'id' => $id,
				],
			),
		);
	}

	/**
	 * Logs successful completion of a find-by-ID operation (debug).
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id The campaign ID found.
	 *
	 * @codeCoverageIgnore
	 */
	public function log_find_by_id_succeeded( int|string $id ): void {

		$this->logger->debug(
			'Finding campaign by ID succeeded.',
			$this->logger_context(
				[
					'operation' => self::OPERATION_FIND_BY_ID,
					'id' => $id,
				],
			),
		);
	}
}
