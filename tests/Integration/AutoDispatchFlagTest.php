<?php

namespace EfdFlow\FiskalizimiKs\Tests\Integration;

use Illuminate\Support\Facades\Bus;
use EfdFlow\FiskalizimiKs\Dto\CouponData;
use EfdFlow\FiskalizimiKs\Dto\FiscalConfig;
use EfdFlow\FiskalizimiKs\Dto\FiscalStatus;
use EfdFlow\FiskalizimiKs\Dto\ItemData;
use EfdFlow\FiskalizimiKs\Dto\PaymentData;
use EfdFlow\FiskalizimiKs\Dto\PaymentType;
use EfdFlow\FiskalizimiKs\Engine\AtkClientInterface;
use EfdFlow\FiskalizimiKs\Engine\CouponBuilder;
use EfdFlow\FiskalizimiKs\Engine\QrGenerator;
use EfdFlow\FiskalizimiKs\Engine\Signer;
use EfdFlow\FiskalizimiKs\Exceptions\FiscalSubmissionException;
use EfdFlow\FiskalizimiKs\FiskalizimiService;
use EfdFlow\FiskalizimiKs\Jobs\ResubmitCouponJob;
use EfdFlow\FiskalizimiKs\Tests\TestCase;
use Mockery;

class AutoDispatchFlagTest extends TestCase
{
    private string $keyPath;
    private FiscalConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        $ecKey = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        openssl_pkey_export($ecKey, $pem);
        $this->keyPath = tempnam(sys_get_temp_dir(), 'auto_dispatch_ec_') . '.pem';
        file_put_contents($this->keyPath, $pem);

        $this->config = new FiscalConfig(1001, 42, 1, 1, 'Location', $this->keyPath);

        $this->artisan('migrate', ['--path' => 'database/migrations', '--realpath' => true]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        @unlink($this->keyPath);
        Mockery::close();
    }

    public function test_no_resubmit_job_dispatched_when_flag_disabled(): void
    {
        config()->set('fiskalizimi.retry.auto_dispatch', false);
        Bus::fake();

        $atk = Mockery::mock(AtkClientInterface::class);
        $atk->shouldReceive('submit')->once()
            ->andThrow(new FiscalSubmissionException('timeout'));

        $service = new FiskalizimiService(new CouponBuilder(), new Signer(), new QrGenerator(), $atk);
        $result  = $service->fiscalize($this->couponData(), $this->config);

        $this->assertSame(FiscalStatus::Queued, $result->status);
        Bus::assertNotDispatched(ResubmitCouponJob::class);
    }

    public function test_resubmit_job_dispatched_when_flag_enabled(): void
    {
        config()->set('fiskalizimi.retry.auto_dispatch', true);
        Bus::fake();

        $atk = Mockery::mock(AtkClientInterface::class);
        $atk->shouldReceive('submit')->once()
            ->andThrow(new FiscalSubmissionException('timeout'));

        $service = new FiskalizimiService(new CouponBuilder(), new Signer(), new QrGenerator(), $atk);
        $service->fiscalize($this->couponData(), $this->config);

        Bus::assertDispatched(ResubmitCouponJob::class);
    }

    private function couponData(): CouponData
    {
        return new CouponData(
            items:      [new ItemData('Produkt A', 10000, 'cope', 1.0, 100000, 'D')],
            payments:   [new PaymentData(PaymentType::Cash, 1000)],
            operatorId: 'Cashier',
        );
    }
}
