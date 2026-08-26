<?php

namespace App\Services;

/**
 * GTIN rules for this project (R1).
 *
 * Simplified per the brief: any sequence of 13 or 14 digits, unique across products.
 * No check digit is computed - only the shape is validated, server side.
 */
class Gtin
{
    public static function isValidFormat(mixed $gtin): bool
    {
        if (! is_string($gtin) && ! is_int($gtin)) {
            return false;
        }

        return preg_match('/^\d{13,14}$/', (string) $gtin) === 1;
    }

    /** Splits a bulk verification textarea into the GTIN codes it contains. */
    public static function splitBulkInput(?string $input): array
    {
        if ($input === null) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $input))
            ->map(fn (string $line) => $line)
            ->filter(fn (string $line) => $line !== '')
            ->values()
            ->all();
    }
}
