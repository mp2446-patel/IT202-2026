<?php
// UCID: mp2446
// Date: 08/04/2026
// Removes only the logged-in user's relationship rows.
// User accounts and Books records remain unchanged.

require_once(__DIR__ . "/../../lib/app.php");

require_login();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    flash_set("Invalid removal request.", "error");
    redirect_to("my-books.php");
}

$currentUser = current_user();
$userId = (int) ($currentUser["id"] ?? 0);

if ($userId < 1) {
    flash_set("Your user account could not be verified.", "error");
    redirect_to("login.php");
}

try {
    $db = getDB();

    $stmt = $db->prepare(
        "DELETE FROM UserBooks
         WHERE user_id = :user_id"
    );

    $stmt->bindValue(
        ":user_id",
        $userId,
        PDO::PARAM_INT
    );

    $stmt->execute();

    $removedCount = $stmt->rowCount();

    if ($removedCount > 0) {
        flash_set(
            $removedCount .
            " book relationship(s) were removed from your list.",
            "success"
        );
    } else {
        flash_set(
            "There were no books to remove from your list.",
            "info"
        );
    }
} catch (PDOException $e) {
    error_log(
        "Remove-all UserBooks failed for UCID mp2446: " .
        $e->getMessage()
    );

    flash_set(
        "Your books could not be removed right now.",
        "error"
    );
}

redirect_to("my-books.php");