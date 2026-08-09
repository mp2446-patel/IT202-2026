<?php
// UCID: mp2446
// Date: 07/26/2026
// Helper for a friendly message when a registration email is already taken.

function email_already_registered(string $email): bool
{
    return find_user_by_email($email) !== null;
}