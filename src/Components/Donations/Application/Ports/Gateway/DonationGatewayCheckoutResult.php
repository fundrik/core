<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Ports\Gateway;

use Fundrik\Core\Components\Shared\Application\Url;

/**
 * Represents normalized gateway checkout output.
 *
 * @since 0.1.0
 */
final readonly class DonationGatewayCheckoutResult {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param Url $redirect_url Gateway checkout redirect URL.
	 */
	public function __construct(
		private Url $redirect_url,
	) {}

	/**
	 * Returns the gateway checkout redirect URL.
	 *
	 * @since 0.1.0
	 *
	 * @return Url Gateway checkout redirect URL.
	 */
	public function get_redirect_url(): Url {

		return $this->redirect_url;
	}
}
