<?php
// UCID: mp2446
// Date: 07/26/2026
// Role-aware book list. Everyone can browse; only an admin sees edit/delete
// controls. Supports filtering by source, sorting, and a validated limit.
require_once(__DIR__ . "/../../lib/app.php");

$search = clean_string($_GET["search"] ?? "");
$sourceFilter = clean_string($_GET["source"] ?? "");
if (!in_array($sourceFilter, ["api", "manual", ""], true)) {
    $sourceFilter = "";
}

$sort = clean_string($_GET["sort"] ?? "title");
$allowedSorts = ["title", "author", "publish_year", "created"];
if (!in_array($sort, $allowedSorts, true)) {
    $sort = "title";
}

$direction = clean_string($_GET["direction"] ?? "asc");
if (!in_array($direction, ["asc", "desc"], true)) {
    $direction = "asc";
}

$limit = validate_limit($_GET["limit"] ?? null);

$books = [];
$errors = [];

try {
    $db = getDB();

    $where = [];
    $params = [];

    if ($search !== "") {
        $where[] = "(title LIKE :search OR author LIKE :search)";
        $params[":search"] = "%" . $search . "%";
    }

    if ($sourceFilter !== "") {
        $where[] = "source = :source";
        $params[":source"] = $sourceFilter;
    }

    $whereSql = "";
    if (!empty($where)) {
        $whereSql = "WHERE " . implode(" AND ", $where);
    }

    $sql = "SELECT id, title, author, publish_year, cover_id, source, created, modified
            FROM Books
            $whereSql
            ORDER BY $sort $direction
            LIMIT :limit";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->execute();
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Book list query failed: " . $e->getMessage());
    $errors[] = "Could not load books right now. Please try again.";
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books</title>
</head>

<body>
    <?php render_nav(); ?>
    <h1>Books</h1>
    <?php render_flash(); ?>

    <?php if (!empty($errors)): ?>
        <p class="flash flash-error"><?php echo implode("<br>", array_map("htmlspecialchars", $errors)); ?></p>
    <?php endif; ?>

    <?php if (is_admin()): ?>
        <p><a href="books-import.php">+ Import from Open Library</a> | <a href="books-create.php">+ Add manually</a></p>
    <?php endif; ?>

    <form method="get" action="books.php">
        <label for="search">Search</label>
        <input id="search" name="search" type="text" value="<?php echo htmlspecialchars($search); ?>">

        <label for="source">Source</label>
        <select id="source" name="source">
            <option value="" <?php echo $sourceFilter === "" ? "selected" : ""; ?>>All</option>
            <option value="api" <?php echo $sourceFilter === "api" ? "selected" : ""; ?>>API</option>
            <option value="manual" <?php echo $sourceFilter === "manual" ? "selected" : ""; ?>>Manual</option>
        </select>

        <label for="sort">Sort by</label>
        <select id="sort" name="sort">
            <option value="title" <?php echo $sort === "title" ? "selected" : ""; ?>>Title</option>
            <option value="author" <?php echo $sort === "author" ? "selected" : ""; ?>>Author</option>
            <option value="publish_year" <?php echo $sort === "publish_year" ? "selected" : ""; ?>>Publish Year</option>
            <option value="created" <?php echo $sort === "created" ? "selected" : ""; ?>>Date Added</option>
        </select>

        <label for="direction">Direction</label>
        <select id="direction" name="direction">
            <option value="asc" <?php echo $direction === "asc" ? "selected" : ""; ?>>Ascending</option>
            <option value="desc" <?php echo $direction === "desc" ? "selected" : ""; ?>>Descending</option>
        </select>

        <label for="limit">Show</label>
        <input id="limit" name="limit" type="number" min="1" max="100"
            value="<?php echo (int) $limit; ?>">

        <button type="submit">Apply</button>
    </form>

    <?php if (empty($books)): ?>
        <p>No books match those filters yet.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Year</th>
                    <th>Source</th>
                    <?php if (is_admin()): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $book): ?>
                    <tr>
                        <td><a href="book-detail.php?id=<?php echo (int) $book["id"]; ?>">
                            <?php echo htmlspecialchars($book["title"]); ?>
                        </a></td>
                        <td><?php echo htmlspecialchars($book["author"]); ?></td>
                        <td><?php echo htmlspecialchars((string) $book["publish_year"]); ?></td>
                        <td><?php echo htmlspecialchars($book["source"]); ?></td>
                        <?php if (is_admin()): ?>
                            <td>
                                <a href="books-edit.php?id=<?php echo (int) $book["id"]; ?>">Edit</a>
                                <form method="post" action="books-delete.php" style="display:inline"
                                    onsubmit="return confirm('Delete this book?');">
                                    <input type="hidden" name="id" value="<?php echo (int) $book["id"]; ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>

</html>