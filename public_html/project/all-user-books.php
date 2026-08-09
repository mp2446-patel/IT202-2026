<?php
// UCID: mp2446
// Date: 08/04/2026
// Admin-protected list of all user-to-book relationships.
// Shows safe usernames, book summaries, relationship details,
// filters, sorting, validated limits, and matching/displayed counts.

require_once(__DIR__ . "/../../lib/app.php");

require_role("admin");

$usernameSearch = clean_string($_GET["username"] ?? "");
$bookSearch = clean_string($_GET["book"] ?? "");
$sourceFilter = clean_string($_GET["source"] ?? "");

if (!in_array($sourceFilter, ["", "api", "manual"], true)) {
    $sourceFilter = "";
}

$sort = clean_string($_GET["sort"] ?? "relationship_created");

$allowedSorts = [
    "relationship_created",
    "relationship_modified",
    "username",
    "title",
    "author",
    "publish_year"
];

if (!in_array($sort, $allowedSorts, true)) {
    $sort = "relationship_created";
}

$direction = strtolower(
    clean_string($_GET["direction"] ?? "desc")
);

if (!in_array($direction, ["asc", "desc"], true)) {
    $direction = "desc";
}

$limit = validate_limit($_GET["limit"] ?? null);

$sortColumns = [
    "relationship_created" => "ub.created",
    "relationship_modified" => "ub.modified",
    "username" => "u.username",
    "title" => "b.title",
    "author" => "b.author",
    "publish_year" => "b.publish_year"
];

$orderColumn = $sortColumns[$sort];

$relationships = [];
$matchingCount = 0;
$displayedCount = 0;
$errors = [];

try {
    $db = getDB();

    $where = [];
    $params = [];

    if ($usernameSearch !== "") {
        $where[] = "u.username LIKE :username";
        $params[":username"] =
            "%" . $usernameSearch . "%";
    }

    if ($bookSearch !== "") {
        $where[] = "
            (
                b.title LIKE :book_search
                OR b.author LIKE :book_search
            )
        ";

        $params[":book_search"] =
            "%" . $bookSearch . "%";
    }

    if ($sourceFilter !== "") {
        $where[] = "b.source = :source";
        $params[":source"] = $sourceFilter;
    }

    $whereSql = "";

    if (!empty($where)) {
        $whereSql =
            "WHERE " . implode(" AND ", $where);
    }

    $countStmt = $db->prepare(
        "SELECT COUNT(*)
         FROM UserBooks ub
         INNER JOIN Users u
            ON u.id = ub.user_id
         INNER JOIN Books b
            ON b.id = ub.book_id
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
    $matchingCount =
        (int) $countStmt->fetchColumn();

    $stmt = $db->prepare(
        "SELECT
            ub.id AS relationship_id,
            ub.created AS relationship_created,
            ub.modified AS relationship_modified,
            u.id AS user_id,
            u.username,
            b.id AS book_id,
            b.title,
            b.author,
            b.publish_year,
            b.source
         FROM UserBooks ub
         INNER JOIN Users u
            ON u.id = ub.user_id
         INNER JOIN Books b
            ON b.id = ub.book_id
         $whereSql
         ORDER BY $orderColumn $direction,
                  ub.id DESC
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

    $relationships =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

    $displayedCount = count($relationships);
} catch (PDOException $e) {
    error_log(
        "All-associated query failed for UCID mp2446: " .
        $e->getMessage()
    );

    $errors[] =
        "The relationship list could not be loaded right now.";
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

    <title>All User Books</title>
</head>

<body>
    <?php render_nav(); ?>

    <main>
        <h1>All User Book Relationships</h1>

        <?php render_flash(); ?>

        <?php if (!empty($errors)): ?>
            <div class="flash flash-error" role="alert">
                <?php foreach ($errors as $error): ?>
                    <p>
                        <?php
                        echo htmlspecialchars($error);
                        ?>
                    </p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p>
            This view is restricted to administrators.
        </p>

        <form
            method="get"
            action="all-user-books.php"
        >
            <div>
                <label for="username">
                    Username
                </label>

                <input
                    id="username"
                    name="username"
                    type="text"
                    value="<?php
                        echo htmlspecialchars(
                            $usernameSearch
                        );
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
                        echo htmlspecialchars(
                            $bookSearch
                        );
                    ?>"
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
                        Relationship created
                    </option>

                    <option
                        value="relationship_modified"
                        <?php echo $sort === "relationship_modified"
                            ? "selected"
                            : ""; ?>
                    >
                        Relationship modified
                    </option>

                    <option
                        value="username"
                        <?php echo $sort === "username"
                            ? "selected"
                            : ""; ?>
                    >
                        Username
                    </option>

                    <option
                        value="title"
                        <?php echo $sort === "title"
                            ? "selected"
                            : ""; ?>
                    >
                        Book title
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

            <a href="all-user-books.php">
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

        <?php if (empty($relationships)): ?>
            <p>
                No user-book relationships match
                the selected filters.
            </p>
        <?php else: ?>
            <table border="1" cellpadding="6">
                <thead>
                    <tr>
                        <th>Relationship ID</th>
                        <th>Username</th>
                        <th>Book</th>
                        <th>Author</th>
                        <th>Year</th>
                        <th>Source</th>
                        <th>Created</th>
                        <th>Modified</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        $relationships as $relationship
                    ): ?>
                        <tr>
                            <td>
                                <?php
                                echo (int)
                                    $relationship[
                                        "relationship_id"
                                    ];
                                ?>
                            </td>

                            <td>
                                <a href="user.php?id=<?php
                                    echo (int)
                                        $relationship["user_id"];
                                ?>">
                                    <?php
                                    echo htmlspecialchars(
                                        $relationship["username"]
                                    );
                                    ?>
                                </a>
                            </td>

                            <td>
                                <a href="book-detail.php?id=<?php
                                    echo (int)
                                        $relationship["book_id"];
                                ?>">
                                    <?php
                                    echo htmlspecialchars(
                                        $relationship["title"]
                                    );
                                    ?>
                                </a>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $relationship["author"]
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo $relationship[
                                    "publish_year"
                                ] !== null
                                    ? htmlspecialchars(
                                        (string)
                                        $relationship[
                                            "publish_year"
                                        ]
                                    )
                                    : "Not available";
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $relationship["source"]
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $relationship[
                                        "relationship_created"
                                    ]
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $relationship[
                                        "relationship_modified"
                                    ]
                                );
                                ?>
                            </td>

                            <td>
                                <form
                                    method="post"
                                    action="admin-user-book-remove.php"
                                    onsubmit="return confirm(
                                        'Remove this relationship?'
                                    );"
                                >
                                    <input
                                        type="hidden"
                                        name="relationship_id"
                                        value="<?php
                                            echo (int)
                                                $relationship[
                                                    "relationship_id"
                                                ];
                                        ?>"
                                    >

                                    <button type="submit">
                                        Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
</body>

</html>