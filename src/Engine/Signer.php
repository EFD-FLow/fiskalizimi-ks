<?php

declare(strict_types=1);

namespace EfdFlow\FiskalizimiKs\Engine;

use EfdFlow\FiskalizimiKs\Dto\FiscalConfig;
use EfdFlow\FiskalizimiKs\Exceptions\FiscalSigningException;
use EfdFlow\FiskalizimiKs\Generated\PosCoupon;

final class Signer implements SignerInterface
{
    public function sign(PosCoupon $coupon, FiscalConfig $config): SignedPayload
    {
        $key     = KeyLoader::load($config->privateKeyPath, $config->privateKeyPassphrase);
        $details = base64_encode($coupon->serializeToString());

        $signature = '';
        if (!openssl_sign($details, $signature, $key, OPENSSL_ALGO_SHA256)) {
            $errors = $this->opensslErrors();
            throw new FiscalSigningException('ECDSA signing failed. OpenSSL: ' . implode('; ', $errors));
        }

        return new SignedPayload(details: $details, signature: base64_encode($signature));
    }

    private function opensslErrors(): array
    {
        $errors = [];
        while (($error = openssl_error_string()) !== false) {
            $errors[] = $error;
        }
        return $errors;
    }
}
