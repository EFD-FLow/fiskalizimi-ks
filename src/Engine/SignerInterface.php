<?php

declare(strict_types=1);

namespace EfdFlow\FiskalizimiKs\Engine;

use EfdFlow\FiskalizimiKs\Dto\FiscalConfig;
use EfdFlow\FiskalizimiKs\Generated\PosCoupon;

interface SignerInterface
{
    public function sign(PosCoupon $coupon, FiscalConfig $config): SignedPayload;
}
