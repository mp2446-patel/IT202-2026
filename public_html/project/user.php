<?php
// UCID: mp2446
// Date: 07/30/2026
// Public user profile showing the user's username and assigned books.

require_once(__DIR__ . "/../../lib/app.php");

$userId = validate_positive_int($_GET["id"] ?? null);

if ($userId === null) {
    flash_set("That user could not be found.", "error");
    redirect_to("users.php");
}

$profileUser = null;
$books = [];

try {
    $db = getDB();

    $userStmt = $db->prepare(
        "SELECT
            id,
            username,
            created
         FROM Users
         WHERE id = :id
         LIMIT 1"
    );

    $userStmt->bindValue(
        ":id",
        $userId,
        PDO::PARAM_INT
    );

    $userStmt->execute();

    $profileUser =
        $userStmt->fetch(PDO::FETCH_ASSOC);

    if ($profileUser) {
        $bookStmt = $db->prepare(
            "SELECT
                b.id,
                b.title,
                b.author,
                b.publish_year,
                b.cover_id,
                ub.created AS assigned_created
             FROM UserBooks ub
             INNER JOIN Books b
                ON b.id = ub.book_id
             WHERE ub.user_id = :user_id
             ORDER BY ub.created DESC, b.title ASC"
        );

        $bookStmt->bindValue(
            ":user_id",
            $userId,
            PDO::PARAM_INT
        );

        $bookStmt->execute();

        $books = $bookStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log(
        "Public user profile query failed for UCID mp2446: " .
        $e->getMessage()
    );

    flash_set(
        "The user profile could not be loaded right now.",
        "error"
    );

    redirect_to("users.php");
}

if (!$profileUser) {
    flash_set("That user could not be found.", "error");
    redirect_to("users.php");
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
        <?php
        echo htmlspecialchars($profileUser["username"]);
        ?>
    </title>
</head>

<body>
    <?php render_nav(); ?>

    <main>
        <h1>
            <?php
            echo htmlspecialchars($profileUser["username"]);
            ?>
        </h1>

        <?php render_flash(); ?>

        <p>
            <strong>Member Since:</strong>
            <?php
            echo htmlspecialchars($profileUser["created"]);
            ?>
        </p>

        <p>
            <strong>Total Books:</strong>
            <?php echo count($books); ?>
        </p>

        <h2>Books</h2>

        <?php if (empty($books)): ?>
            <p>
                This user has not added any books yet.
            </p>
        <?php else: ?>
            <?php foreach ($books as $book): ?>
                <article>
                    <?php if (!empty($book["cover_id"])): ?>
                        <img
                            src="https://covers.openlibrary.org/b/id/<?php
                                echo (int) $book["cover_id"];
                            ?>-S.jpg"
                            alt="Cover of <?php
                                echo htmlspecialchars(
                                    $book["title"]
                                );
                            ?>"
                        >
                    <?php endif; ?>

                    <h3>
                        <a href="book-detail.php?id=<?php
                            echo (int) $book["id"];
                        ?>">
                            <?php
                            echo htmlspecialchars(
                                $book["title"]
                            );
                            ?>
                        </a>
                    </h3>

                    <p>
                        <strong>Author:</strong>
                        <?php
                        echo htmlspecialchars(
                            $book["author"]
                        );
                        ?>
                    </p>

                    <p>
                        <strong>Publication Year:</strong>
                        <?php
                        echo $book["publish_year"] !== null
                            ? htmlspecialchars(
                                (string) $book["publish_year"]
                            )
                            : "Not available";
                        ?>
                    </p>

                    <p>
                        <strong>Added to List:</strong>
                        <?php
                        echo htmlspecialchars(
                            $book["assigned_created"]
                        );
                        ?>
                    </p>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>

        <p>
            <a href="users.php">
                &larr; Back to users
            </a>
        </p>
    </main>
</body>

</html>