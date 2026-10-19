<?php

namespace EfdFlow\FiskalizimiKs\Facades;

use Illuminate\Support\Facades\Facade;
use EfdFlow\FiskalizimiKs\FiskalizimiService;

/**
 * @method static \EfdFlow\FiskalizimiKs\Dto\FiscalResult fiscalize(\EfdFlow\FiskalizimiKs\Dto\CouponData $data, \EfdFlow\FiskalizimiKs\Dto\FiscalConfig $config)
 */
class Fiscalizer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FiskalizimiService::class;
    }
}
