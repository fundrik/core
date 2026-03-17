<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Ports\DonationRepository;

/**
 * Marks donation repository write failures caused by a missing donation ID.
 *
 * @since 0.1.0
 */
interface DonationNotFoundExceptionInterface extends DonationRepositoryExceptionInterface {}
