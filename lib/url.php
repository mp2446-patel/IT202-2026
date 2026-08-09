<?php
// UCID: mp2446
// Date: 07/26/2026
// URL helpers: redirect and sticky-query-string building for filter/sort/limit controls.

function redirect_to(string $path): void
{
    header("Location: " . $path);
    exit;
}

function build_query_string(array $params): string
{
    $clean = [];
    foreach ($params as $key => $value) {
        if ($value === null || $value === "") {
            continue;
        }
        $clean[$key] = $value;
    }
    if (empty($clean)) {
        return "";
    }
    return "?" . http_build_query($clean);
}