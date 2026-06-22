<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\ReadModels;

use Fundrik\Core\Components\Donations\Application\ReadModels\Donation;
use Fundrik\Core\Components\Donations\Application\ReadModels\PaginatedDonations;
use Fundrik\Core\Tests\MockeryTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( PaginatedDonations::class )]
#[CoversClass( Donation::class )]
final class PaginatedDonationsTest extends MockeryTestCase {

	#[Test]
	public function page_returns_expected_values(): void {

		$donation1 = $this->make_donation_read_model( id: 1 );
		$donation2 = $this->make_donation_read_model( id: 2 );
		$page = new PaginatedDonations(
			items: [ $donation1, $donation2 ],
			page: 3,
			per_page: 25,
			total: 51,
		);

		$this->assertSame( [ $donation1, $donation2 ], $page->get_items() );
		$this->assertSame( 3, $page->get_page() );
		$this->assertSame( 25, $page->get_per_page() );
		$this->assertSame( 51, $page->get_total() );
		$this->assertSame( 3, $page->get_total_pages() );
	}

	#[Test]
	public function page_returns_zero_total_pages_when_empty(): void {

		$page = new PaginatedDonations(
			items: [],
			page: 1,
			per_page: 25,
			total: 0,
		);

		$this->assertSame( 0, $page->get_total_pages() );
	}
}
