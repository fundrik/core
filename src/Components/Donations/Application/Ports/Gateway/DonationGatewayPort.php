<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Ports\Gateway;

/**
 * Provides the outbound port for donation gateway checkout.
 *
 * @since 1.0.0
 */
interface DonationGatewayPort {

	/**
	 * Creates or returns an idempotent checkout for a normalized donation.
	 *
	 * Repeated calls with the same donation ID must not create or charge a second payment.
	 * The adapter uses the donation ID as a stable provider idempotency key or equivalent merchant payment key.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationGatewayCheckoutRequest $request Normalized checkout input.
	 *
	 * @return DonationGatewayCheckoutResult Normalized checkout output.
	 *
	 * @throws DonationGatewayExceptionInterface When checkout creation fails.
	 */
	public function create_checkout( DonationGatewayCheckoutRequest $request ): DonationGatewayCheckoutResult;
}
