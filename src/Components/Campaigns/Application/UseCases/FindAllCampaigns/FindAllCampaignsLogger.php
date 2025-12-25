<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\FindAllCampaigns;

use Fundrik\Core\Components\Campaigns\Application\AbstractCampaignApplicationLogger;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;

/**
 * Logs the FindAllCampaigns use case execution.
 *
 * @since 0.1.0
 */
final readonly class FindAllCampaignsLogger extends AbstractCampaignApplicationLogger {

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
			'Finding all campaigns started.',
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
			'Finding all campaigns failed (repository error).',
			$this->logger_context(
				[
					'operation' => self::OPERATION_FIND_ALL,
					'exception' => $e,
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
			'Finding all campaigns succeeded.',
			$this->logger_context(
				[
					'operation' => self::OPERATION_FIND_ALL,
					'count' => $count,
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

		return FindAllCampaignsHandler::class;
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
