<?php
// UCID: mp2446
// Date: 07/26/2026
// Flash message helpers: store a friendly message for exactly one page load.

function flash_set(string $message, string $type = "success"): void
{
    $_SESSION["flash"] = [
        "message" => $message,
        "type" => $type,
    ];
}

function flash_get(): ?array
{
    if (!isset($_SESSION["flash"])) {
        return null;
    }
    $flash = $_SESSION["flash"];
    unset($_SESSION["flash"]);
    return $flash;
}

function render_flash(): void
{
    $flash = flash_get();
    if ($flash === null) {
        return;
    }
    $class = htmlspecialchars($flash["type"]);
    $message = htmlspecialchars($flash["message"]);
    echo "<p class=\"flash flash-{$class}\">{$message}</p>";
}