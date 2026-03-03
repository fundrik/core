<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Shared\Application\Exceptions;

/**
 * Specifies where use-case processing failed.
 *
 * @since 0.1.0
 */
enum UseCaseFailureStage: string {

	case Precondition = 'precondition';
	case Persistence = 'persistence';
	case EventPublish = 'event_publish';
}
