<?php

namespace EfdFlow\FiskalizimiKs\Tests\Integration;

use EfdFlow\FiskalizimiKs\Dto\CouponData;
use EfdFlow\FiskalizimiKs\Dto\FiscalConfig;
use EfdFlow\FiskalizimiKs\Dto\FiscalStatus;
use EfdFlow\FiskalizimiKs\Dto\ItemData;
use EfdFlow\FiskalizimiKs\Dto\PaymentData;
use EfdFlow\FiskalizimiKs\Dto\PaymentType;
use EfdFlow\FiskalizimiKs\Engine\AtkClient;
use EfdFlow\FiskalizimiKs\Engine\CouponBuilder;
use EfdFlow\FiskalizimiKs\Engine\QrGenerator;
use EfdFlow\FiskalizimiKs\Engine\Signer;
use EfdFlow\FiskalizimiKs\Engine\VerificationNo;
use EfdFlow\FiskalizimiKs\FiskalizimiService;
use EfdFlow\FiskalizimiKs\Tests\TestCase;

class AtkSubmissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!getenv('ATK_TEST_KEY_PATH')) {
            $this->markTestSkipped('ATK_TEST_KEY_PATH not set — skipping live ATK integration test');
        }
    }

    public function test_submits_sale_coupon_to_atk_test_environment_and_gets_transaction_no(): void
    {
        $config = new FiscalConfig(
            businessId:     (int) getenv('ATK_TEST_BUSINESS_ID'),
            applicationId:  (int) getenv('ATK_TEST_APPLICATION_ID'),
            posId:          (int) getenv('ATK_TEST_POS_ID'),
            branchId:       (int) getenv('ATK_TEST_BRANCH_ID'),
            location:       (string) getenv('ATK_TEST_LOCATION'),
            privateKeyPath: (string) getenv('ATK_TEST_KEY_PATH'),
            atkBaseUrl:     (string) (getenv('ATK_TEST_BASE_URL') ?: 'https://fiskalizimi-test.atk-ks.org'),
            atkCouponPath:  '/pos/coupon',
            atkTimeout:     15,
        );

        $this->artisan('migrate', ['--path' => 'database/migrations', '--realpath' => true]);

        $service = new FiskalizimiService(
            new CouponBuilder(),
            new Signer(),
            new QrGenerator(),
            new AtkClient(),
        );

        $data = new CouponData(
            items: [
                new ItemData('Test Product', 10000, 'cope', 1.0, 100000, 'D'),
            ],
            payments: [
                new PaymentData(PaymentType::Cash, 1000),
            ],
            operatorId:     'TestCashier',
            idempotencyKey: 'integration-test-' . uniqid(),
        );

        $result = $service->fiscalize($data, $config);

        $this->assertSame(FiscalStatus::Fiscalized, $result->status);
        $this->assertNotNull($result->transactionNo);
        // Digits only, and never a wrapped negative: the uint64 must survive the
        // round trip whole.
        $this->assertMatchesRegularExpression('/^[1-9]\d*$/', $result->transactionNo);
        $this->assertMatchesRegularExpression(VerificationNo::PATTERN, $result->verificationNo);
        $this->assertStringContainsString('|', $result->citizenQr);
    }
}
