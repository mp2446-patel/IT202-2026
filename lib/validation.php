<?php
// UCID: mp2446
// Date: 07/26/2026
// Shared server-side validation helpers used across project pages.

function clean_string($value): string
{
    if (!is_string($value)) {
        return "";
    }
    return trim($value);
}

function validate_required(string $value, string $label, array &$errors): void
{
    if ($value === "") {
        $errors[] = "$label is required.";
    }
}

function validate_positive_int($value): ?int
{
    if (!is_numeric($value)) {
        return null;
    }
    $intValue = (int) $value;
    if ((string) $intValue !== (string) (int) $value || $intValue < 1) {
        return null;
    }
    return $intValue;
}

function validate_limit($value): int
{
    $limit = validate_positive_int($value);
    if ($limit === null) {
        return 10;
    }
    if ($limit > 100) {
        return 100;
    }
    return $limit;
}