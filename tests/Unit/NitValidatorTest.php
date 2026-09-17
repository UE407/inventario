<?php

namespace Tests\Unit;

use App\Services\Utils\NitValidatorService;
use PHPUnit\Framework\TestCase;

class NitValidatorTest extends TestCase
{
    public function test_validates_cf(): void
    {
        $this->assertTrue(NitValidatorService::isValid('CF'));
        $this->assertTrue(NitValidatorService::isValid('C/F'));
        $this->assertTrue(NitValidatorService::isValid('cf'));
    }

    public function test_validates_nit_with_modulo_11(): void
    {
        // Test NIT 1234567-8:
        // 7*2 + 6*3 + 5*4 + 4*5 + 3*6 + 2*7 + 1*8 = 14 + 18 + 20 + 20 + 18 + 14 + 8 = 112
        // 112 % 11 = 2 -> (11 - 2)%11 = 9. So 1234567-9 is valid.
        $this->assertTrue(NitValidatorService::isValid('1234567-9'));
        $this->assertTrue(NitValidatorService::isValid('12345679'));
    }

    public function test_rejects_invalid_nit(): void
    {
        $this->assertFalse(NitValidatorService::isValid('1234567-0'));
        $this->assertFalse(NitValidatorService::isValid('ABCXYZ'));
    }
}
