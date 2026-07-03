<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Ports\Gateway;

/**
 * Provides the outbound port for donation gateway checkout.
 *
 * @since 0.1.0
 */
interface DonationGatewayPort {

	/**
	 * Creates a checkout for a normalized donation.
	 *
	 * @since 0.1.0
	 *
	 * @param DonationGatewayCheckoutRequest $request Normalized checkout input.
	 *
	 * @return DonationGatewayCheckoutResult Normalized checkout output.
	 *
	 * @throws DonationGatewayExceptionInterface When checkout creation fails.
	 */
	public function create_checkout( DonationGatewayCheckoutRequest $request ): DonationGatewayCheckoutResult;
}
