<?php
// UCID: mp2446
// Date: 07/26/2026
// Session helpers: safe session start (idempotent) + current-user access.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Returns the logged-in user's session array, or null when no one is logged in.
 */
function current_user(): ?array
{
    if (isset($_SESSION["user"]) && is_array($_SESSION["user"])) {
        return $_SESSION["user"];
    }
    return null;
}

/**
 * True when a user is currently logged in.
 */
function is_logged_in(): bool
{
    return current_user() !== null;
}

/**
 * Redirects to login.php with a flash message when no one is logged in.
 * Call at the top of any page that requires a logged-in user.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        flash_set("Please log in to continue.", "error");
        header("Location: login.php");
        exit;
    }
}