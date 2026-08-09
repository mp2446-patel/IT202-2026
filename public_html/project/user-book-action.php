<?php
// UCID: mp2446
// Date: 07/30/2026
// Adds or removes a book from the logged-in user's personal book list.

require_once(__DIR__ . "/../../lib/app.php");

require_login();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    flash_set("Invalid request method.", "error");
    redirect_to("books.php");
}

$bookId = validate_positive_int($_POST["book_id"] ?? null);
$action = clean_string($_POST["action"] ?? "");

if ($bookId === null) {
    flash_set("Invalid book selection.", "error");
    redirect_to("books.php");
}

if (!in_array($action, ["add", "remove"], true)) {
    flash_set("Invalid book action.", "error");
    redirect_to("book-detail.php?id=" . $bookId);
}

$user = current_user();
$userId = (int) $user["id"];

try {
    $db = getDB();

    $bookStmt = $db->prepare(
        "SELECT id
         FROM Books
         WHERE id = :book_id
         LIMIT 1"
    );

    $bookStmt->bindValue(
        ":book_id",
        $bookId,
        PDO::PARAM_INT
    );

    $bookStmt->execute();

    if (!$bookStmt->fetch(PDO::FETCH_ASSOC)) {
        flash_set("That book could not be found.", "error");
        redirect_to("books.php");
    }

    if ($action === "add") {
        $stmt = $db->prepare(
            "INSERT INTO UserBooks (
                user_id,
                book_id
             )
             VALUES (
                :user_id,
                :book_id
             )
             ON DUPLICATE KEY UPDATE
                modified = CURRENT_TIMESTAMP"
        );

        $stmt->bindValue(
            ":user_id",
            $userId,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ":book_id",
            $bookId,
            PDO::PARAM_INT
        );

        $stmt->execute();

        flash_set(
            "The book was added to your book list.",
            "success"
        );
    } else {
        $stmt = $db->prepare(
            "DELETE FROM UserBooks
             WHERE user_id = :user_id
             AND book_id = :book_id"
        );

        $stmt->bindValue(
            ":user_id",
            $userId,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ":book_id",
            $bookId,
            PDO::PARAM_INT
        );

        $stmt->execute();

        flash_set(
            "The book was removed from your book list.",
            "success"
        );
    }
} catch (PDOException $e) {
    error_log(
        "User book action failed for UCID mp2446: " .
            $e->getMessage()
    );

    flash_set(
        "The book list could not be updated. Please try again.",
        "error"
    );
}

redirect_to("book-detail.php?id=" . $bookId);
