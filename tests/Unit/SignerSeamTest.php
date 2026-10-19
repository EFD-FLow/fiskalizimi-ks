<?php

declare(strict_types=1);

namespace EfdFlow\FiskalizimiKs\Tests\Unit;

use EfdFlow\FiskalizimiKs\Engine\Signer;
use EfdFlow\FiskalizimiKs\Engine\SignerInterface;
use EfdFlow\FiskalizimiKs\Tests\TestCase;

class SignerSeamTest extends TestCase
{
    public function test_default_binding_is_openssl_signer(): void
    {
        $this->assertInstanceOf(Signer::class, app(SignerInterface::class));
    }
}
