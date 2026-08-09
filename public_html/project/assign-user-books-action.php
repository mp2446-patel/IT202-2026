<?php
// UCID: mp2446
// Date: 08/04/2026
// Admin-only handler that toggles each selected user/book pair.
// Missing relationships are created and existing relationships are removed.

require_once(__DIR__ . "/../../lib/app.php");

require_role("admin");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    flash_set("Invalid assignment request.", "error");
    redirect_to("assign-user-books.php");
}

$submittedUserIds = $_POST["user_ids"] ?? [];
$submittedBookIds = $_POST["book_ids"] ?? [];

if (
    !is_array($submittedUserIds) ||
    !is_array($submittedBookIds)
) {
    flash_set("Invalid assignment selection.", "error");
    redirect_to("assign-user-books.php");
}

$userIds = [];
$bookIds = [];

foreach ($submittedUserIds as $value) {
    $id = validate_positive_int($value);

    if ($id !== null) {
        $userIds[$id] = $id;
    }
}

foreach ($submittedBookIds as $value) {
    $id = validate_positive_int($value);

    if ($id !== null) {
        $bookIds[$id] = $id;
    }
}

$userIds = array_values($userIds);
$bookIds = array_values($bookIds);

if (empty($userIds) || empty($bookIds)) {
    flash_set(
        "Select at least one user and one book.",
        "error"
    );

    redirect_to("assign-user-books.php");
}

$addedCount = 0;
$removedCount = 0;

try {
    $db = getDB();
    $db->beginTransaction();

    $userCheckStmt = $db->prepare(
        "SELECT id
         FROM Users
         WHERE id = :user_id
         LIMIT 1"
    );

    $bookCheckStmt = $db->prepare(
        "SELECT id
         FROM Books
         WHERE id = :book_id
         LIMIT 1"
    );

    $existingStmt = $db->prepare(
        "SELECT id
         FROM UserBooks
         WHERE user_id = :user_id
           AND book_id = :book_id
         LIMIT 1"
    );

    $insertStmt = $db->prepare(
        "INSERT INTO UserBooks (
            user_id,
            book_id
         )
         VALUES (
            :user_id,
            :book_id
         )"
    );

    $deleteStmt = $db->prepare(
        "DELETE FROM UserBooks
         WHERE user_id = :user_id
           AND book_id = :book_id"
    );

    foreach ($userIds as $userId) {
        $userCheckStmt->bindValue(
            ":user_id",
            $userId,
            PDO::PARAM_INT
        );

        $userCheckStmt->execute();

        if (!$userCheckStmt->fetch(PDO::FETCH_ASSOC)) {
            continue;
        }

        foreach ($bookIds as $bookId) {
            $bookCheckStmt->bindValue(
                ":book_id",
                $bookId,
                PDO::PARAM_INT
            );

            $bookCheckStmt->execute();

            if (!$bookCheckStmt->fetch(PDO::FETCH_ASSOC)) {
                continue;
            }

            $existingStmt->bindValue(
                ":user_id",
                $userId,
                PDO::PARAM_INT
            );

            $existingStmt->bindValue(
                ":book_id",
                $bookId,
                PDO::PARAM_INT
            );

            $existingStmt->execute();

            $existingRelationship =
                $existingStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingRelationship) {
                $deleteStmt->bindValue(
                    ":user_id",
                    $userId,
                    PDO::PARAM_INT
                );

                $deleteStmt->bindValue(
                    ":book_id",
                    $bookId,
                    PDO::PARAM_INT
                );

                $deleteStmt->execute();

                $removedCount += $deleteStmt->rowCount();
            } else {
                $insertStmt->bindValue(
                    ":user_id",
                    $userId,
                    PDO::PARAM_INT
                );

                $insertStmt->bindValue(
                    ":book_id",
                    $bookId,
                    PDO::PARAM_INT
                );

                $insertStmt->execute();

                $addedCount += $insertStmt->rowCount();
            }
        }
    }

    $db->commit();

    flash_set(
        $addedCount .
        " relationship(s) created and " .
        $removedCount .
        " relationship(s) removed.",
        "success"
    );
} catch (PDOException $e) {
    if (
        isset($db) &&
        $db instanceof PDO &&
        $db->inTransaction()
    ) {
        $db->rollBack();
    }

    error_log(
        "Assignment toggle failed for UCID mp2446: " .
        $e->getMessage()
    );

    flash_set(
        "The selected relationships could not be updated.",
        "error"
    );
}

redirect_to("all-user-books.php");