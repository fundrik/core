<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Donations\Application\Services;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRead\DonationReadPort;
use Fundrik\Core\Components\Donations\Application\ReadModels\Donation;
use Fundrik\Core\Components\Donations\Application\Services\DonationQueryService;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadDonationById\ReadDonationByIdException;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadDonationById\ReadDonationByIdHandler;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( DonationQueryService::class )]
#[UsesClass( Donation::class )]
#[UsesClass( ReadDonationByIdHandler::class )]
#[UsesClass( EntityId::class )]
final class DonationQueryServiceTest extends MockeryTestCase {

	private DonationReadPort&MockInterface $donation_read;

	private DonationQueryService $query;

	protected function setUp(): void {

		parent::setUp();

		$this->donation_read = Mockery::mock( DonationReadPort::class );
		$this->query = new DonationQueryService(
			new ReadDonationByIdHandler( $this->donation_read ),
		);
	}

	#[Test]
	public function find_by_id_uses_injected_donation_read_port(): void {

		$donation = $this->make_donation_read_model(
			id: 5_001,
			campaign_id: 901,
			status: 'pending',
			updated_at: $this->make_utc_date_time( '2026-03-02T10:00:00+00:00' ),
		);
		$donation_id = EntityId::create( $donation->get_id() );

		$this->donation_read
			->shouldReceive( 'find_by_id' )
			->once()
			->withArgs(
				static fn ( EntityId $actual_donation_id ): bool => $actual_donation_id->equals( $donation_id ),
			)
			->andReturn( $donation );

		$result = $this->query->find_by_id( $donation_id->get_value() );

		$this->assertInstanceOf( Donation::class, $result );
		$this->assertSame( $donation->get_id(), $result->get_id() );
		$this->assertSame( $donation->get_campaign_id(), $result->get_campaign_id() );
		$this->assertSame( $donation->get_amount(), $result->get_amount() );
		$this->assertSame( $donation->get_currency_code(), $result->get_currency_code() );
		$this->assertSame( $donation->get_status(), $result->get_status() );
		$this->assertSame( $donation->get_created_at(), $result->get_created_at() );
		$this->assertSame( $donation->get_updated_at(), $result->get_updated_at() );
	}

	#[Test]
	public function find_by_id_throws_when_donation_id_is_invalid(): void {

		$this->expectException( ReadDonationByIdException::class );
		$this->expectExceptionMessage( 'ID must be a positive integer or a valid UUID. Given: -1.' );

		$this->query->find_by_id( -1 );
	}
}
