<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Loggers\Traits;

use Fundrik\Core\Components\Campaigns\Application\Exceptions\CampaignAssemblerExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\Out\CampaignRepositoryExceptionInterface;

/**
 * Adds logging for 'find all' operations.
 *
 * @since 0.1.0
 */
trait FindAllLoggingTrait {

	private const OPERATION_FIND_ALL = 'find_all_campaigns';

	/**
	 * Logs the start of a find-all operation (debug).
	 *
	 * @since 0.1.0
	 *
	 * @codeCoverageIgnore
	 */
	public function log_find_all_started(): void {

		$this->logger->debug(
			'Finding campaigns started.',
			$this->logger_context(
				[
					'operation' => self::OPERATION_FIND_ALL,
				],
			),
		);
	}

	/**
	 * Logs repository failure during a find-all operation (error).
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignRepositoryExceptionInterface $e The repository exception that occurred.
	 */
	public function log_find_all_failed_repository( CampaignRepositoryExceptionInterface $e ): void {

		$this->logger->error(
			'Finding campaigns failed (repository error).',
			$this->logger_context(
				[
					'operation' => self::OPERATION_FIND_ALL,
					'exception' => $e,
					'exception_class' => $e::class,
				],
			),
		);
	}

	/**
	 * Logs assembler failure during a find-all operation (error).
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignAssemblerExceptionInterface $e The assembler exception that occurred.
	 */
	public function log_find_all_failed_assembler( CampaignAssemblerExceptionInterface $e ): void {

		$this->logger->error(
			'Finding campaigns failed (assembler error).',
			$this->logger_context(
				[
					'operation' => self::OPERATION_FIND_ALL,
					'exception' => $e,
					'exception_class' => $e::class,
				],
			),
		);
	}

	/**
	 * Logs successful completion of a find-all operation (debug).
	 *
	 * @since 0.1.0
	 *
	 * @param int $count The number of campaigns retrieved.
	 *
	 * @codeCoverageIgnore
	 */
	public function log_find_all_succeeded( int $count ): void {

		$this->logger->debug(
			'Finding campaigns succeeded.',
			$this->logger_context(
				[
					'operation' => self::OPERATION_FIND_ALL,
					'count' => $count,
				],
			),
		);
	}
}
