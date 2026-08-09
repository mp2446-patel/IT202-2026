<?php
// UCID: mp2446
// Date: 08/04/2026
// Admin-only assignment page for searching users and books.
// Each search returns no more than 25 results and provides checkboxes
// for selecting user-to-book relationship pairs.

require_once(__DIR__ . "/../../lib/app.php");

require_role("admin");

$usernameSearch = clean_string($_GET["username"] ?? "");
$bookSearch = clean_string($_GET["book"] ?? "");

$users = [];
$books = [];
$errors = [];

try {
    $db = getDB();

    if ($usernameSearch !== "") {
        $userStmt = $db->prepare(
            "SELECT
                id,
                username,
                created
             FROM Users
             WHERE username LIKE :username
             ORDER BY username ASC
             LIMIT 25"
        );

        $userStmt->bindValue(
            ":username",
            "%" . $usernameSearch . "%",
            PDO::PARAM_STR
        );

        $userStmt->execute();

        $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($bookSearch !== "") {
        $bookStmt = $db->prepare(
            "SELECT
                id,
                title,
                author,
                publish_year,
                source
             FROM Books
             WHERE title LIKE :book_search
                OR author LIKE :book_search
             ORDER BY title ASC
             LIMIT 25"
        );

        $bookStmt->bindValue(
            ":book_search",
            "%" . $bookSearch . "%",
            PDO::PARAM_STR
        );

        $bookStmt->execute();

        $books = $bookStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log(
        "Assignment search failed for UCID mp2446: " .
        $e->getMessage()
    );

    $errors[] =
        "The assignment search could not be completed right now.";
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

    <title>Assign User Books</title>
</head>

<body>
    <?php render_nav(); ?>

    <main>
        <h1>Assign User Books</h1>

        <?php render_flash(); ?>

        <p>
            This administrator-only page toggles relationships between
            selected users and selected books.
        </p>

        <?php if (!empty($errors)): ?>
            <div class="flash flash-error" role="alert">
                <?php foreach ($errors as $error): ?>
                    <p>
                        <?php echo htmlspecialchars($error); ?>
                    </p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form
            method="get"
            action="assign-user-books.php"
        >
            <div>
                <label for="username">
                    Partial username
                </label>

                <input
                    id="username"
                    name="username"
                    type="text"
                    value="<?php
                        echo htmlspecialchars($usernameSearch);
                    ?>"
                >
            </div>

            <div>
                <label for="book">
                    Book title or author
                </label>

                <input
                    id="book"
                    name="book"
                    type="text"
                    value="<?php
                        echo htmlspecialchars($bookSearch);
                    ?>"
                >
            </div>

            <button type="submit">
                Search
            </button>

            <a href="assign-user-books.php">
                Reset
            </a>
        </form>

        <?php if (
            $usernameSearch === "" &&
            $bookSearch === ""
        ): ?>
            <p>
                Enter a partial username and a book title or author
                to begin.
            </p>
        <?php else: ?>
            <form
                method="post"
                action="assign-user-books-action.php"
                onsubmit="return confirm(
                    'Apply the selected user and book relationships?'
                );"
            >
                <div>
                    <section>
                        <h2>User Results</h2>

                        <?php if ($usernameSearch === ""): ?>
                            <p>
                                Enter a username search.
                            </p>
                        <?php elseif (empty($users)): ?>
                            <p>
                                No users match that username.
                            </p>
                        <?php else: ?>
                            <p>
                                Showing up to 25 matching users.
                            </p>

                            <?php foreach ($users as $user): ?>
                                <div>
                                    <label>
                                        <input
                                            type="checkbox"
                                            name="user_ids[]"
                                            value="<?php
                                                echo (int) $user["id"];
                                            ?>"
                                        >

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $user["username"]
                                            );
                                            ?>
                                        </strong>

                                        — User ID:
                                        <?php echo (int) $user["id"]; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </section>

                    <section>
                        <h2>Book Results</h2>

                        <?php if ($bookSearch === ""): ?>
                            <p>
                                Enter a book search.
                            </p>
                        <?php elseif (empty($books)): ?>
                            <p>
                                No books match that title or author.
                            </p>
                        <?php else: ?>
                            <p>
                                Showing up to 25 matching books.
                            </p>

                            <?php foreach ($books as $book): ?>
                                <div>
                                    <label>
                                        <input
                                            type="checkbox"
                                            name="book_ids[]"
                                            value="<?php
                                                echo (int) $book["id"];
                                            ?>"
                                        >

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $book["title"]
                                            );
                                            ?>
                                        </strong>

                                        by
                                        <?php
                                        echo htmlspecialchars(
                                            $book["author"]
                                        );
                                        ?>

                                        — Book ID:
                                        <?php echo (int) $book["id"]; ?>
                                    </label>

                                    <p>
                                        Year:
                                        <?php
                                        echo $book["publish_year"] !== null
                                            ? htmlspecialchars(
                                                (string)
                                                $book["publish_year"]
                                            )
                                            : "Not available";
                                        ?>

                                        |
                                        Source:
                                        <?php
                                        echo htmlspecialchars(
                                            $book["source"]
                                        );
                                        ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </section>
                </div>

                <?php if (
                    !empty($users) &&
                    !empty($books)
                ): ?>
                    <button type="submit">
                        Apply Selected Relationships
                    </button>
                <?php endif; ?>
            </form>
        <?php endif; ?>

        <p>
            <a href="all-user-books.php">
                View all current relationships
            </a>
        </p>
    </main>
</body>

</html>