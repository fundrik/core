<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\Services;

use Fundrik\Core\Components\Donations\Application\Ports\DonationDetailsRead\DonationDetailsReadPort;
use Fundrik\Core\Components\Donations\Application\ReadModels\DonationDetails;
use Fundrik\Core\Components\Donations\Application\Services\DonationQueryService;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdException;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdHandler;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( DonationQueryService::class )]
#[UsesClass( DonationDetails::class )]
#[UsesClass( FindDonationByIdHandler::class )]
#[UsesClass( EntityId::class )]
final class DonationQueryServiceTest extends MockeryTestCase {

	private DonationDetailsReadPort&MockInterface $donation_details_read;

	private DonationQueryService $query;

	protected function setUp(): void {

		parent::setUp();

		$this->donation_details_read = Mockery::mock( DonationDetailsReadPort::class );
		$this->query = new DonationQueryService(
			new FindDonationByIdHandler( $this->donation_details_read ),
		);
	}

	#[Test]
	public function find_by_id_uses_injected_donation_details_read_port(): void {

		$details = $this->make_donation_details(
			id: 5_001,
			campaign_id: 901,
			status: 'pending',
			updated_at: $this->make_utc_date_time( '2026-03-02T10:00:00+00:00' ),
		);
		$donation_id = EntityId::create( $details->get_id() );

		$this->donation_details_read
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_donation_id ): bool => $actual_donation_id->equals( $donation_id ),
			)
			->andReturn( $details );

		$result = $this->query->find_by_id( $donation_id->get_value() );

		$this->assertInstanceOf( DonationDetails::class, $result );
		$this->assertSame( $details->get_id(), $result->get_id() );
		$this->assertSame( $details->get_campaign_id(), $result->get_campaign_id() );
		$this->assertSame( $details->get_amount(), $result->get_amount() );
		$this->assertSame( $details->get_currency_code(), $result->get_currency_code() );
		$this->assertSame( $details->get_status(), $result->get_status() );
		$this->assertSame( $details->get_created_at(), $result->get_created_at() );
		$this->assertSame( $details->get_updated_at(), $result->get_updated_at() );
	}

	#[Test]
	public function find_by_id_throws_when_donation_id_is_invalid(): void {

		$this->expectException( FindDonationByIdException::class );
		$this->expectExceptionMessage( 'ID must be a positive integer or a valid UUID. Given: -1.' );

		$this->query->find_by_id( -1 );
	}
}
