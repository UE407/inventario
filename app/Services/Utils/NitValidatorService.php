<?php

namespace App\Services\Utils;

class NitValidatorService
{
    /**
     * Valida si una cadena es un NIT válido de Guatemala o C/F.
     */
    public static function isValid(?string $nit): bool
    {
        if (empty($nit)) {
            return false;
        }

        $clean = strtoupper(trim(str_replace(['-', ' '], '', $nit)));

        // Consumidor Final
        if (in_array($clean, ['CF', 'C/F', 'CONSUMIDORFINAL'])) {
            return true;
        }

        // Formato básico: dígitos seguidos de un dígito o 'K'
        if (!preg_match('/^\d+([0-9K])$/', $clean, $matches)) {
            return false;
        }

        $len = strlen($clean);
        if ($len < 2) {
            return false;
        }

        $verifier = substr($clean, -1);
        $number = substr($clean, 0, -1);

        $sum = 0;
        $factor = 2;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $sum += (int) $number[$i] * $factor;
            $factor++;
        }

        $remainder = $sum % 11;
        $expectedVerifier = (11 - $remainder) % 11;

        $expectedChar = match ($expectedVerifier) {
            10 => 'K',
            default => (string) $expectedVerifier,
        };

        return $verifier === $expectedChar;
    }

    /**
     * Formatea un NIT con su guión (ej. 12345678 -> 1234567-8).
     */
    public static function format(?string $nit): string
    {
        if (empty($nit)) {
            return 'C/F';
        }

        $clean = strtoupper(trim(str_replace(['-', ' '], '', $nit)));
        if (in_array($clean, ['CF', 'C/F', 'CONSUMIDORFINAL'])) {
            return 'C/F';
        }

        if (strlen($clean) >= 2) {
            return substr($clean, 0, -1) . '-' . substr($clean, -1);
        }

        return $clean;
    }
}
