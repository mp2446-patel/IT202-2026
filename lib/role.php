<?php
// UCID: mp2446
// Date: 07/26/2026
// Role helpers: read the current user's role and guard admin-only pages.

function current_role(): string
{
    $user = current_user();
    if ($user === null || !isset($user["role"])) {
        return "";
    }
    return (string) $user["role"];
}

function is_admin(): bool
{
    return current_role() === "admin";
}

function require_role(string $role): void
{
    require_login();

    if (current_role() !== $role) {
        flash_set("You do not have access to that page.", "error");
        header("Location: dashboard.php");
        exit;
    }
}