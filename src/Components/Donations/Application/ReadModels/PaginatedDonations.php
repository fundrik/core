<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\ReadModels;

/**
 * Represents a paginated list of donation read models.
 *
 * @since 0.1.0
 */
final readonly class PaginatedDonations {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param array $items Donation read models.
	 * @param int $page Page number.
	 * @param int $per_page Donations per page.
	 * @param int $total Total number of donations.
	 *
	 * @phpstan-param list<Donation> $items Donation read models.
	 */
	public function __construct(
		private array $items,
		private int $page,
		private int $per_page,
		private int $total,
	) {}

	/**
	 * Returns the donation read models.
	 *
	 * @since 0.1.0
	 *
	 * @return list<Donation> Donation read models.
	 */
	public function get_items(): array {

		return $this->items;
	}

	/**
	 * Returns the page number.
	 *
	 * @since 0.1.0
	 *
	 * @return int Page number.
	 */
	public function get_page(): int {

		return $this->page;
	}

	/**
	 * Returns the donations per page.
	 *
	 * @since 0.1.0
	 *
	 * @return int Donations per page.
	 */
	public function get_per_page(): int {

		return $this->per_page;
	}

	/**
	 * Returns the total number of donations.
	 *
	 * @since 0.1.0
	 *
	 * @return int Total number of donations.
	 */
	public function get_total(): int {

		return $this->total;
	}

	/**
	 * Returns the total number of pages.
	 *
	 * @since 0.1.0
	 *
	 * @return int Total number of pages.
	 */
	public function get_total_pages(): int {

		if ( $this->total === 0 ) {
			return 0;
		}

		return (int) ceil( $this->total / $this->per_page );
	}
}
