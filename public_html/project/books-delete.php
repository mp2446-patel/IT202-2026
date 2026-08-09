<?php
// UCID: mp2446
// Date: 07/26/2026
// Admin-only POST handler that hard-deletes one book by validated ID.

require_once(__DIR__ . "/../../lib/app.php");

require_role("admin");

/*
 * Delete requests must use POST.
 */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    flash_set("Invalid delete request.", "error");
    redirect_to("books.php");
}

/*
 * Validate the submitted record ID.
 */
$id = validate_positive_int($_POST["id"] ?? null);

if ($id === null) {
    flash_set("Invalid book ID.", "error");
    redirect_to("books.php");
}

try {
    $db = getDB();

    /*
     * The prepared statement ensures only the requested validated
     * record can be deleted.
     */
    $stmt = $db->prepare(
        "DELETE FROM Books
         WHERE id = :id"
    );

    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 1) {
        flash_set(
            "The book was deleted successfully.",
            "success"
        );
    } else {
        flash_set(
            "That book could not be found or was already removed.",
            "error"
        );
    }
} catch (PDOException $e) {
    error_log(
        "Book delete failed for UCID mp2446: " .
        $e->getMessage()
    );

    flash_set(
        "The book could not be deleted. Please try again.",
        "error"
    );
}

redirect_to("books.php");