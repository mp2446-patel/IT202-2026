<?php
// UCID: mp2446
// Date: 08/04/2026
// Admin-only handler that removes one UserBooks relationship row.
// It does not delete the related user or book record.

require_once(__DIR__ . "/../../lib/app.php");

require_role("admin");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    flash_set("Invalid removal request.", "error");
    redirect_to("all-user-books.php");
}

$relationshipId = validate_positive_int(
    $_POST["relationship_id"] ?? null
);

if ($relationshipId === null) {
    flash_set(
        "Invalid relationship selection.",
        "error"
    );

    redirect_to("all-user-books.php");
}

try {
    $db = getDB();

    $stmt = $db->prepare(
        "DELETE FROM UserBooks
         WHERE id = :relationship_id"
    );

    $stmt->bindValue(
        ":relationship_id",
        $relationshipId,
        PDO::PARAM_INT
    );

    $stmt->execute();

    if ($stmt->rowCount() === 1) {
        flash_set(
            "The user-book relationship was removed.",
            "success"
        );
    } else {
        flash_set(
            "That relationship no longer exists.",
            "error"
        );
    }
} catch (PDOException $e) {
    error_log(
        "Admin relationship removal failed for UCID mp2446: " .
            $e->getMessage()
    );

    flash_set(
        "The relationship could not be removed right now.",
        "error"
    );
}

redirect_to("all-user-books.php");
