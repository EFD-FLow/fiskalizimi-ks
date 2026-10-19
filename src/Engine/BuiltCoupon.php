<?php

namespace EfdFlow\FiskalizimiKs\Engine;

use EfdFlow\FiskalizimiKs\Generated\CitizenCoupon;
use EfdFlow\FiskalizimiKs\Generated\PosCoupon;

final class BuiltCoupon
{
    public function __construct(
        public readonly PosCoupon     $posCoupon,
        public readonly CitizenCoupon $citizenCoupon,
    ) {}
}
