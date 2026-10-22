<?php

namespace EfdFlow\FiskalizimiKs\Tests\Unit\Models;

use EfdFlow\FiskalizimiKs\Dto\CouponData;
use EfdFlow\FiskalizimiKs\Dto\CouponType;
use EfdFlow\FiskalizimiKs\Dto\FiscalConfig;
use EfdFlow\FiskalizimiKs\Dto\ItemData;
use EfdFlow\FiskalizimiKs\Dto\PaymentData;
use EfdFlow\FiskalizimiKs\Dto\PaymentType;
use EfdFlow\FiskalizimiKs\Engine\CouponBuilder;
use EfdFlow\FiskalizimiKs\Engine\CouponSnapshot;
use EfdFlow\FiskalizimiKs\Generated\CouponType as ProtoCouponType;
use EfdFlow\FiskalizimiKs\Generated\PosCoupon;
use EfdFlow\FiskalizimiKs\Models\FiscalCoupon;
use EfdFlow\FiskalizimiKs\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class FiscalCouponTest extends TestCase
{
    public function test_a_correction_built_by_this_engine_carries_negative_amounts(): void
    {
        $coupon = $this->journalRowFor(CouponType::Return);

        $this->assertTrue($coupon->carriesNegativeAmounts());
    }

    public function test_a_sale_does_not(): void
    {
        $coupon = $this->journalRowFor(CouponType::Sale);

        $this->assertFalse($coupon->carriesNegativeAmounts());
    }

    public function test_a_correction_sent_positive_by_0_10_does_not(): void
    {
        $payload = (new PosCoupon())
            ->setType(ProtoCouponType::PBReturn)
            ->setTotal(22500)
            ->serializeToString();

        $coupon = new FiscalCoupon(['fiscal_details' => base64_encode($payload)]);

        $this->assertFalse($coupon->carriesNegativeAmounts());
    }

    #[DataProvider('unreadableDetails')]
    public function test_missing_or_unreadable_details_do_not(?string $details): void
    {
        $coupon = new FiscalCoupon(['fiscal_details' => $details]);

        $this->assertFalse($coupon->carriesNegativeAmounts());
    }

    public static function unreadableDetails(): array
    {
        return [
            'missing'        => [null],
            'empty'          => [''],
            'not base64'     => ['not base64!!'],
            'not a protobuf' => [base64_encode('garbage')],
        ];
    }

    private function journalRowFor(CouponType $type): FiscalCoupon
    {
        $data = new CouponData(
            items:            [new ItemData('Ujë mineral 0,5 L', 5000, 'CP', 2.0, 10000, 'E', 'PJA')],
            payments:         [new PaymentData(PaymentType::Cash, 100)],
            operatorId:       'John',
            type:             $type,
            referenceNo:      $type === CouponType::Sale ? null : 77,
            expectedTotal:    100,
            expectedTotalTax: 15,
        );

        $built = (new CouponBuilder())->build(
            CouponSnapshot::generate(existsChecker: fn() => false, verificationNo: '0000000000000001'),
            $data,
            new FiscalConfig(
                businessId: 1001,
                applicationId: 42,
                posId: 1,
                branchId: 1,
                location: 'Test Location',
                privateKeyPath: '/dev/null',
            ),
            couponId: 12,
        );

        return new FiscalCoupon([
            'fiscal_details' => base64_encode($built->posCoupon->serializeToString()),
        ]);
    }
}
