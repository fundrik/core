<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Loggers;

use Fundrik\Core\Components\Campaigns\Application\Loggers\Traits\FindAllLoggingTrait;
use Fundrik\Core\Components\Campaigns\Application\Loggers\Traits\FindByIdLoggingTrait;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryService;

/**
 * Logs application-level operations of the CampaignQueryService.
 *
 * @since 0.1.0
 */
final readonly class CampaignQueryServiceLogger extends AbstractCampaignServiceLogger {

	use FindByIdLoggingTrait;
	use FindAllLoggingTrait;

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

		return CampaignQueryService::class;
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
