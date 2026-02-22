<?php

namespace App\Support;

class CsvSanitizer
{
    /**
     * Neutralize spreadsheet formulas in CSV cells to prevent CSV injection.
     */
    public static function sanitizeCell(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = str_replace("\0", '', $value);
        if ($value === '') {
            return $value;
        }

        $trimmed = ltrim($value);
        if ($trimmed === '') {
            return $value;
        }

        $first = $trimmed[0];
        if (in_array($first, ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        if (preg_match('/^[\t\r\n]/', $value) === 1) {
            return "'" . $value;
        }

        return $value;
    }

    public static function sanitizeRow(array $row): array
    {
        return array_map(static fn ($cell) => self::sanitizeCell($cell), $row);
    }
}
