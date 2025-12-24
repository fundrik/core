<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Shared\Application\Ports;

use Fundrik\Core\Components\Shared\Application\Events\ApplicationEventInterface;

/**
 * Provides the outbound port for publishing application events.
 *
 * @since 0.1.0
 */
interface EventBusPort {

	/**
	 * Publishes the given event to all registered listeners.
	 *
	 * @since 0.1.0
	 *
	 * @param ApplicationEventInterface $event The event object to publish.
	 */
	public function publish( ApplicationEventInterface $event ): void;
}
