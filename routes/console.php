<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reenvío automático de comprobantes electrónicos pendientes/con error a SUNAT.
// Requiere tener el scheduler activo: * * * * * php artisan schedule:run
Schedule::command('facturacion:reintentar')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Resumen diario de boletas (RC): cada día a la 01:00 procesa las del día anterior.
Schedule::command('facturacion:resumen-boletas')
    ->dailyAt('01:00')
    ->withoutOverlapping();
