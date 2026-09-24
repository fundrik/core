<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Ports\DonationRepository;

/**
 * Marks donation repository insert failures caused by an existing donation ID.
 *
 * @since 1.0.0
 */
interface DonationAlreadyExistsExceptionInterface extends DonationRepositoryExceptionInterface {}
