<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Ports\Gateway;

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
	 * @param string $redirect_url Gateway checkout redirect URL.
	 */
	public function __construct(
		private string $redirect_url,
	) {}

	/**
	 * Returns the gateway checkout redirect URL.
	 *
	 * @since 0.1.0
	 *
	 * @return string Gateway checkout redirect URL.
	 */
	public function get_redirect_url(): string {

		return $this->redirect_url;
	}
}
