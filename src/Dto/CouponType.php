<?php

namespace EfdFlow\FiskalizimiKs\Dto;

enum CouponType: string
{
    case Sale   = 'sale';
    case Return = 'return';
    case Cancel = 'cancel';
}
