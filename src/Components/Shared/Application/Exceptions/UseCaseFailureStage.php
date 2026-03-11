<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Shared\Application\Exceptions;

/**
 * Specifies where use-case processing failed.
 *
 * @since 0.1.0
 */
enum UseCaseFailureStage: string {

	/**
	 * The use case failed before changing persistent state.
	 */
	case Precondition = 'precondition';

	/**
	 * The use case failed while storing the requested state change.
	 *
	 * Event publishing was not attempted.
	 */
	case Persistence = 'persistence';

	/**
	 * The requested state change was stored, but publishing the follow-up event failed.
	 *
	 * This stage reflects a publish failure reported by the event bus, not a delivery guarantee.
	 */
	case EventPublish = 'event_publish';
}
