<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Ports\Gateway;

use Fundrik\Core\Components\Donations\Domain\PaymentId;
use Fundrik\Core\Components\Shared\Application\Url;

/**
 * Represents normalized gateway checkout output.
 *
 * @since 1.0.0
 */
final readonly class DonationGatewayCheckoutResult {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Added the `$payment_id` parameter.
	 *
	 * @param PaymentId $payment_id Provider payment ID.
	 * @param Url $redirect_url Gateway checkout redirect URL.
	 */
	public function __construct(
		private PaymentId $payment_id,
		private Url $redirect_url,
	) {}

	/**
	 * Returns the provider payment ID.
	 *
	 * @since 1.1.0
	 *
	 * @return PaymentId Provider payment ID.
	 */
	public function get_payment_id(): PaymentId {

		return $this->payment_id;
	}

	/**
	 * Returns the gateway checkout redirect URL.
	 *
	 * @since 1.0.0
	 *
	 * @return Url Gateway checkout redirect URL.
	 */
	public function get_redirect_url(): Url {

		return $this->redirect_url;
	}
}
