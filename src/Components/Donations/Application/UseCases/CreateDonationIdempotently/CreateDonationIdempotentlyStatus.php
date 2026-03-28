<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently;

/**
 * Specifies the outcome of idempotent donation creation.
 *
 * @since 0.1.0
 */
enum CreateDonationIdempotentlyStatus: string {

	/**
	 * A new donation was created.
	 */
	case Created = 'created';

	/**
	 * An existing donation was replayed.
	 */
	case Replayed = 'replayed';
}
