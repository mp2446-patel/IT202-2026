<?php
// UCID: mp2446
// Date: 08/04/2026
// Admin-protected list of books that currently have no user association.
// Includes filtering, sorting, validated limits, matching counts,
// displayed counts, detail links, and a clear no-results state.

require_once(__DIR__ . "/../../lib/app.php");

require_role("admin");

$search = clean_string($_GET["search"] ?? "");
$sourceFilter = clean_string($_GET["source"] ?? "");

if (!in_array($sourceFilter, ["", "api", "manual"], true)) {
    $sourceFilter = "";
}

$sort = clean_string($_GET["sort"] ?? "title");

$allowedSorts = [
    "title",
    "author",
    "publish_year",
    "source",
    "created"
];

if (!in_array($sort, $allowedSorts, true)) {
    $sort = "title";
}

$direction = strtolower(
    clean_string($_GET["direction"] ?? "asc")
);

if (!in_array($direction, ["asc", "desc"], true)) {
    $direction = "asc";
}

$limit = validate_limit($_GET["limit"] ?? null);

$sortColumns = [
    "title" => "b.title",
    "author" => "b.author",
    "publish_year" => "b.publish_year",
    "source" => "b.source",
    "created" => "b.created"
];

$orderColumn = $sortColumns[$sort];

$books = [];
$matchingCount = 0;
$displayedCount = 0;
$errors = [];

try {
    $db = getDB();

    $where = [
        "NOT EXISTS (
            SELECT 1
            FROM UserBooks ub
            WHERE ub.book_id = b.id
        )"
    ];

    $params = [];

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

    $countStmt = $db->prepare(
        "SELECT COUNT(*)
         FROM Books b
         $whereSql"
    );

    foreach ($params as $key => $value) {
        $countStmt->bindValue(
            $key,
            $value,
            PDO::PARAM_STR
        );
    }

    $countStmt->execute();

    $matchingCount = (int) $countStmt->fetchColumn();

    $stmt = $db->prepare(
        "SELECT
            b.id,
            b.title,
            b.author,
            b.publish_year,
            b.cover_id,
            b.source,
            b.created,
            b.modified
         FROM Books b
         $whereSql
         ORDER BY $orderColumn $direction, b.id ASC
         LIMIT :limit"
    );

    foreach ($params as $key => $value) {
        $stmt->bindValue(
            $key,
            $value,
            PDO::PARAM_STR
        );
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
        "Unassociated books query failed for UCID mp2446: " .
        $e->getMessage()
    );

    $errors[] =
        "The unassociated books could not be loaded right now.";
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

    <title>Unassociated Books</title>
</head>

<body>
    <?php render_nav(); ?>

    <main>
        <h1>Unassociated Books</h1>

        <?php render_flash(); ?>

        <p>
            This administrator-only page shows books that are not assigned
            to any user.
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
            action="unassociated-books.php"
        >
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

                    <option
                        value="source"
                        <?php echo $sort === "source"
                            ? "selected"
                            : ""; ?>
                    >
                        Source
                    </option>

                    <option
                        value="created"
                        <?php echo $sort === "created"
                            ? "selected"
                            : ""; ?>
                    >
                        Date added
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

            <a href="unassociated-books.php">
                Reset
            </a>
        </form>

        <p>
            Matching books:
            <strong>
                <?php echo $matchingCount; ?>
            </strong>
        </p>

        <p>
            Displayed books:
            <strong>
                <?php echo $displayedCount; ?>
            </strong>
        </p>

        <?php if (empty($books)): ?>
            <p>
                No unassociated books match the selected filters.
            </p>
        <?php else: ?>
            <table border="1" cellpadding="6">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Publication Year</th>
                        <th>Source</th>
                        <th>Created</th>
                        <th>Modified</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($books as $book): ?>
                        <tr>
                            <td>
                                <a href="book-detail.php?id=<?php
                                    echo (int) $book["id"];
                                ?>">
                                    <?php
                                    echo htmlspecialchars(
                                        $book["title"]
                                    );
                                    ?>
                                </a>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $book["author"]
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo $book["publish_year"] !== null
                                    ? htmlspecialchars(
                                        (string) $book["publish_year"]
                                    )
                                    : "Not available";
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $book["source"]
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $book["created"]
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $book["modified"]
                                );
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
</body>

</html>