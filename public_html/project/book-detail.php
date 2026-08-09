<?php
// UCID: mp2446
// Date: 07/30/2026
// Public detail page for one book with logged-in user association controls.

require_once(__DIR__ . "/../../lib/app.php");

$id = validate_positive_int($_GET["id"] ?? null);

if ($id === null) {
    flash_set("That book could not be found.", "error");
    redirect_to("books.php");
}

$book = null;
$isAdmin = is_admin();
$isInMyBooks = false;

try {
    $db = getDB();

    $stmt = $db->prepare(
        "SELECT
            id,
            title,
            author,
            publish_year,
            cover_id,
            source,
            external_key,
            created,
            modified
         FROM Books
         WHERE id = :id
         LIMIT 1"
    );

    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->execute();

    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($book && is_logged_in()) {
        $user = current_user();
        $userId = (int) $user["id"];

        $associationStmt = $db->prepare(
            "SELECT id
             FROM UserBooks
             WHERE user_id = :user_id
             AND book_id = :book_id
             LIMIT 1"
        );

        $associationStmt->bindValue(
            ":user_id",
            $userId,
            PDO::PARAM_INT
        );

        $associationStmt->bindValue(
            ":book_id",
            $id,
            PDO::PARAM_INT
        );

        $associationStmt->execute();

        $isInMyBooks =
            (bool) $associationStmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log(
        "Book detail query failed for UCID mp2446: " .
        $e->getMessage()
    );

    flash_set(
        "The book could not be loaded right now. Please try again.",
        "error"
    );

    redirect_to("books.php");
}

if (!$book) {
    flash_set("That book could not be found.", "error");
    redirect_to("books.php");
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php echo htmlspecialchars($book["title"]); ?>
    </title>
</head>

<body>
    <?php render_nav(); ?>

    <main>
        <h1>
            <?php echo htmlspecialchars($book["title"]); ?>
        </h1>

        <?php render_flash(); ?>

        <?php if (!empty($book["cover_id"])): ?>
            <img
                src="https://covers.openlibrary.org/b/id/<?php
                    echo (int) $book["cover_id"];
                ?>-M.jpg"
                alt="Cover of <?php
                    echo htmlspecialchars($book["title"]);
                ?>"
            >
        <?php endif; ?>

        <p>
            <strong>Author:</strong>
            <?php echo htmlspecialchars($book["author"]); ?>
        </p>

        <p>
            <strong>Publication Year:</strong>
            <?php
            echo $book["publish_year"] !== null
                ? htmlspecialchars((string) $book["publish_year"])
                : "Not available";
            ?>
        </p>

        <p>
            <strong>Source:</strong>
            <?php echo htmlspecialchars($book["source"]); ?>
        </p>

        <p>
            <strong>Cover ID:</strong>
            <?php
            echo $book["cover_id"] !== null
                ? htmlspecialchars((string) $book["cover_id"])
                : "Not available";
            ?>
        </p>

        <p>
            <strong>Added:</strong>
            <?php echo htmlspecialchars($book["created"]); ?>
        </p>

        <p>
            <strong>Last Updated:</strong>
            <?php echo htmlspecialchars($book["modified"]); ?>
        </p>

        <?php if (is_logged_in()): ?>
            <section>
                <h2>My Book List</h2>

                <form
                    method="post"
                    action="user-book-action.php"
                >
                    <input
                        type="hidden"
                        name="book_id"
                        value="<?php echo (int) $book["id"]; ?>"
                    >

                    <?php if ($isInMyBooks): ?>
                        <input
                            type="hidden"
                            name="action"
                            value="remove"
                        >

                        <p>
                            This book is currently in your list.
                        </p>

                        <button type="submit">
                            Remove from My Books
                        </button>
                    <?php else: ?>
                        <input
                            type="hidden"
                            name="action"
                            value="add"
                        >

                        <button type="submit">
                            Add to My Books
                        </button>
                    <?php endif; ?>
                </form>
            </section>
        <?php else: ?>
            <p>
                <a href="login.php">
                    Log in to add this book to your list.
                </a>
            </p>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
            <p>
                <a href="books-edit.php?id=<?php
                    echo (int) $book["id"];
                ?>">
                    Edit
                </a>
            </p>

            <form
                method="post"
                action="books-delete.php"
                onsubmit="return confirm('Delete this book?');"
            >
                <input
                    type="hidden"
                    name="id"
                    value="<?php echo (int) $book["id"]; ?>"
                >

                <button type="submit">
                    Delete
                </button>
            </form>
        <?php endif; ?>

        <p>
            <a href="books.php">
                &larr; Back to all books
            </a>
        </p>
    </main>
</body>

</html>