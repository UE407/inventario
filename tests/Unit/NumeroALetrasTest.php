<?php

namespace Tests\Unit;

use App\Support\NumeroALetras;
use PHPUnit\Framework\TestCase;

class NumeroALetrasTest extends TestCase
{
    public function test_converts_quetzales_to_words(): void
    {
        $res = NumeroALetras::moneda(1250.50, 'Q');
        $this->assertEquals('MIL DOSCIENTOS CINCUENTA CON 50/100 QUETZALES', $res);
    }
}
