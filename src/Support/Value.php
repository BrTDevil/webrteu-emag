<?php

declare(strict_types=1);

namespace WeBRTeu\Emag\Support;

/**
 * Citire defensivă din răspunsurile eMAG (câmpurile lipsesc sau vin cu tipuri amestecate).
 */
final class Value
{
    /**
     * @param  array<array-key, mixed>  $row
     */
    public static function string(array $row, string $key): ?string
    {
        $value = $row[$key] ?? null;

        return is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null;
    }

    /**
     * @param  array<array-key, mixed>  $row
     */
    public static function int(array $row, string $key): ?int
    {
        $value = $row[$key] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param  array<array-key, mixed>  $row
     */
    public static function float(array $row, string $key): ?float
    {
        $value = $row[$key] ?? null;

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * @param  array<array-key, mixed>  $row
     * @return array<array-key, mixed>
     */
    public static function array(array $row, string $key): array
    {
        $value = $row[$key] ?? null;

        return is_array($value) ? $value : [];
    }
}
