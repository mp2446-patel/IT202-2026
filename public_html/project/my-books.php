<?php
// UCID: mp2446
// Date: 08/04/2026
// Logged-in user's book associations with filtering, sorting,
// validated limits, relationship details, counts, and removal controls.

require_once(__DIR__ . "/../../lib/app.php");

require_login();

$currentUser = current_user();
$userId = (int) ($currentUser["id"] ?? 0);

$search = clean_string($_GET["search"] ?? "");
$sourceFilter = clean_string($_GET["source"] ?? "");

if (!in_array($sourceFilter, ["", "api", "manual"], true)) {
    $sourceFilter = "";
}

$sort = clean_string($_GET["sort"] ?? "relationship_created");

$allowedSorts = [
    "relationship_created",
    "title",
    "author",
    "publish_year",
    "relationship_modified"
];

if (!in_array($sort, $allowedSorts, true)) {
    $sort = "relationship_created";
}

$direction = strtolower(clean_string($_GET["direction"] ?? "desc"));

if (!in_array($direction, ["asc", "desc"], true)) {
    $direction = "desc";
}

$limit = validate_limit($_GET["limit"] ?? null);

$sortColumns = [
    "relationship_created" => "ub.created",
    "title" => "b.title",
    "author" => "b.author",
    "publish_year" => "b.publish_year",
    "relationship_modified" => "ub.modified"
];

$orderColumn = $sortColumns[$sort];

$books = [];
$matchingCount = 0;
$displayedCount = 0;
$errors = [];

try {
    $db = getDB();

    $where = [
        "ub.user_id = :user_id"
    ];

    $params = [
        ":user_id" => $userId
    ];

    if ($search !== "") {
        $where[] = "
            (
                b.title LIKE :search
                OR b.author LIKE :search
            )
        ";

        $params[":search"] = "%" . $search . "%";
    }

    if ($sourceFilter !== "") {
        $where[] = "b.source = :source";
        $params[":source"] = $sourceFilter;
    }

    $whereSql = "WHERE " . implode(" AND ", $where);

    // Count all relationships matching the active filters.
    $countStmt = $db->prepare(
        "SELECT COUNT(*)
         FROM UserBooks ub
         INNER JOIN Books b
            ON b.id = ub.book_id
         $whereSql"
    );

    foreach ($params as $key => $value) {
        if ($key === ":user_id") {
            $countStmt->bindValue(
                $key,
                $value,
                PDO::PARAM_INT
            );
        } else {
            $countStmt->bindValue(
                $key,
                $value,
                PDO::PARAM_STR
            );
        }
    }

    $countStmt->execute();
    $matchingCount = (int) $countStmt->fetchColumn();

    // Load only the current user's matching relationship rows.
    $stmt = $db->prepare(
        "SELECT
            ub.id AS relationship_id,
            ub.created AS relationship_created,
            ub.modified AS relationship_modified,
            b.id AS book_id,
            b.title,
            b.author,
            b.publish_year,
            b.cover_id,
            b.source
         FROM UserBooks ub
         INNER JOIN Books b
            ON b.id = ub.book_id
         $whereSql
         ORDER BY $orderColumn $direction, b.title ASC
         LIMIT :limit"
    );

    foreach ($params as $key => $value) {
        if ($key === ":user_id") {
            $stmt->bindValue(
                $key,
                $value,
                PDO::PARAM_INT
            );
        } else {
            $stmt->bindValue(
                $key,
                $value,
                PDO::PARAM_STR
            );
        }
    }

    $stmt->bindValue(
        ":limit",
        $limit,
        PDO::PARAM_INT
    );

    $stmt->execute();

    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $displayedCount = count($books);
} catch (PDOException $e) {
    error_log(
        "My Books query failed for UCID mp2446: " .
        $e->getMessage()
    );

    $errors[] =
        "Your book list could not be loaded right now. Please try again.";
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

    <title>My Books</title>
</head>

<body>
    <?php render_nav(); ?>

    <main>
        <h1>My Books</h1>

        <?php render_flash(); ?>

        <?php if (!empty($errors)): ?>
            <div class="flash flash-error" role="alert">
                <?php foreach ($errors as $error): ?>
                    <p>
                        <?php echo htmlspecialchars($error); ?>
                    </p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="get" action="my-books.php">
            <div>
                <label for="search">
                    Search title or author
                </label>

                <input
                    id="search"
                    name="search"
                    type="text"
                    value="<?php echo htmlspecialchars($search); ?>"
                >
            </div>

            <div>
                <label for="source">
                    Source
                </label>

                <select id="source" name="source">
                    <option
                        value=""
                        <?php echo $sourceFilter === ""
                            ? "selected"
                            : ""; ?>
                    >
                        All sources
                    </option>

                    <option
                        value="api"
                        <?php echo $sourceFilter === "api"
                            ? "selected"
                            : ""; ?>
                    >
                        API
                    </option>

                    <option
                        value="manual"
                        <?php echo $sourceFilter === "manual"
                            ? "selected"
                            : ""; ?>
                    >
                        Manual
                    </option>
                </select>
            </div>

            <div>
                <label for="sort">
                    Sort by
                </label>

                <select id="sort" name="sort">
                    <option
                        value="relationship_created"
                        <?php echo $sort === "relationship_created"
                            ? "selected"
                            : ""; ?>
                    >
                        Date added to My Books
                    </option>

                    <option
                        value="relationship_modified"
                        <?php echo $sort === "relationship_modified"
                            ? "selected"
                            : ""; ?>
                    >
                        Relationship modified date
                    </option>

                    <option
                        value="title"
                        <?php echo $sort === "title"
                            ? "selected"
                            : ""; ?>
                    >
                        Title
                    </option>

                    <option
                        value="author"
                        <?php echo $sort === "author"
                            ? "selected"
                            : ""; ?>
                    >
                        Author
                    </option>

                    <option
                        value="publish_year"
                        <?php echo $sort === "publish_year"
                            ? "selected"
                            : ""; ?>
                    >
                        Publication year
                    </option>
                </select>
            </div>

            <div>
                <label for="direction">
                    Direction
                </label>

                <select id="direction" name="direction">
                    <option
                        value="asc"
                        <?php echo $direction === "asc"
                            ? "selected"
                            : ""; ?>
                    >
                        Ascending
                    </option>

                    <option
                        value="desc"
                        <?php echo $direction === "desc"
                            ? "selected"
                            : ""; ?>
                    >
                        Descending
                    </option>
                </select>
            </div>

            <div>
                <label for="limit">
                    Limit
                </label>

                <input
                    id="limit"
                    name="limit"
                    type="number"
                    min="1"
                    max="100"
                    value="<?php echo (int) $limit; ?>"
                >
            </div>

            <button type="submit">
                Apply
            </button>

            <a href="my-books.php">
                Reset
            </a>
        </form>

        <p>
            Matching relationships:
            <strong>
                <?php echo $matchingCount; ?>
            </strong>
        </p>

        <p>
            Displayed relationships:
            <strong>
                <?php echo $displayedCount; ?>
            </strong>
        </p>

        <?php if ($matchingCount > 0): ?>
            <form
                method="post"
                action="user-books-remove-all.php"
                onsubmit="return confirm(
                    'Remove all books from your list?'
                );"
            >
                <button type="submit">
                    Remove All My Books
                </button>
            </form>
        <?php endif; ?>

        <?php if (empty($books)): ?>
            <?php if (
                $search !== "" ||
                $sourceFilter !== ""
            ): ?>
                <p>
                    No books match the selected filters.
                </p>

                <p>
                    <a href="my-books.php">
                        Clear filters
                    </a>
                </p>
            <?php else: ?>
                <p>
                    You have not added any books to your list yet.
                </p>

                <p>
                    <a href="books.php">
                        Browse all books
                    </a>
                </p>
            <?php endif; ?>
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

                    <h2>
                        <a href="book-detail.php?id=<?php
                            echo (int) $book["book_id"];
                        ?>">
                            <?php
                            echo htmlspecialchars(
                                $book["title"]
                            );
                            ?>
                        </a>
                    </h2>

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
                        <strong>Source:</strong>
                        <?php
                        echo htmlspecialchars(
                            $book["source"]
                        );
                        ?>
                    </p>

                    <h3>Relationship Details</h3>

                    <p>
                        <strong>Relationship ID:</strong>
                        <?php
                        echo (int) $book["relationship_id"];
                        ?>
                    </p>

                    <p>
                        <strong>Created:</strong>
                        <?php
                        echo htmlspecialchars(
                            $book["relationship_created"]
                        );
                        ?>
                    </p>

                    <p>
                        <strong>Modified:</strong>
                        <?php
                        echo htmlspecialchars(
                            $book["relationship_modified"]
                        );
                        ?>
                    </p>

                    <form
                        method="post"
                        action="user-book-action.php"
                        onsubmit="return confirm(
                            'Remove this book from your list?'
                        );"
                    >
                        <input
                            type="hidden"
                            name="book_id"
                            value="<?php
                                echo (int) $book["book_id"];
                            ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="remove"
                        >

                        <button type="submit">
                            Remove from My Books
                        </button>
                    </form>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>

        <p>
            <a href="books.php">
                Browse all books
            </a>
        </p>
    </main>
</body>

</html>