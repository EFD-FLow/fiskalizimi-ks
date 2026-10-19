<?php

namespace EfdFlow\FiskalizimiKs\Engine;

use EfdFlow\FiskalizimiKs\Dto\FiscalConfig;
use EfdFlow\FiskalizimiKs\Exceptions\FiscalSigningException;
use EfdFlow\FiskalizimiKs\Generated\CitizenCoupon;

final class QrGenerator
{
    public function generate(CitizenCoupon $coupon, FiscalConfig $config): string
    {
        $key     = KeyLoader::load($config->privateKeyPath, $config->privateKeyPassphrase);
        $details = base64_encode($coupon->serializeToString());

        $signature = '';
        if (!openssl_sign($details, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new FiscalSigningException('CitizenCoupon QR signing failed.');
        }

        return $details . '|' . base64_encode($signature);
    }
}
