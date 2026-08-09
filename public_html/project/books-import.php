<?php
// UCID: mp2446
// Date: 07/26/2026
// Admin-only page that searches the Open Library API and imports selected
// books into the Books table using a consistent duplicate-record rule.

require_once(__DIR__ . "/../../lib/app.php");

require_role("admin");

$errors = [];
$searchTerm = clean_string($_GET["q"] ?? "");
$searchResult = null;

/*
 * Search Open Library through the server-side API helper.
 */
if ($searchTerm !== "") {
    if (strlen($searchTerm) < 2) {
        $errors[] = "Search must contain at least 2 characters.";
    } else {
        $searchResult = get_open_library_books($searchTerm);

        if (
            !is_array($searchResult) ||
            !isset($searchResult["success"]) ||
            !$searchResult["success"]
        ) {
            $errors[] = $searchResult["message"]
                ?? "Could not search Open Library right now.";
        }
    }
}

/*
 * Import one selected API result.
 */
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["import_title"])
) {
    $title = clean_string($_POST["import_title"] ?? "");
    $author = clean_string($_POST["import_author"] ?? "");
    $publishYearRaw = clean_string(
        $_POST["import_publish_year"] ?? ""
    );
    $coverIdRaw = clean_string($_POST["import_cover_id"] ?? "");

    validate_required($title, "Title", $errors);

    if ($author === "") {
        $author = "Unknown Author";
    }

    /*
     * Publication year is optional, but it must be a reasonable integer
     * when the API provides one.
     */
    $publishYear = null;

    if ($publishYearRaw !== "") {
        $publishYear = filter_var(
            $publishYearRaw,
            FILTER_VALIDATE_INT
        );

        if (
            $publishYear === false ||
            $publishYear < 1000 ||
            $publishYear > ((int) date("Y") + 1)
        ) {
            $errors[] = "Publication year must be a reasonable year.";
        }
    }

    /*
     * Cover ID is optional, but it must be a positive integer when present.
     */
    $coverId = null;

    if ($coverIdRaw !== "") {
        $coverId = filter_var(
            $coverIdRaw,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]]
        );

        if ($coverId === false) {
            $errors[] = "Cover ID must be a positive number.";
        }
    }

    /*
     * Duplicate strategy:
     * Create one normalized key from the book title and author.
     * The Books table should have a UNIQUE constraint on external_key.
     */
    $externalKey =
        strtolower(trim($title)) .
        "|" .
        strtolower(trim($author));

    if (empty($errors)) {
        try {
            $db = getDB();

            $stmt = $db->prepare(
                "INSERT INTO Books (
                    title,
                    author,
                    publish_year,
                    cover_id,
                    source,
                    external_key
                )
                VALUES (
                    :title,
                    :author,
                    :publish_year,
                    :cover_id,
                    'api',
                    :external_key
                )"
            );

            $stmt->execute([
                ":title" => $title,
                ":author" => $author,
                ":publish_year" => $publishYear,
                ":cover_id" => $coverId,
                ":external_key" => $externalKey
            ]);

            flash_set(
                "Imported \"$title\" from Open Library.",
                "success"
            );

            redirect_to(
                "books-import.php" .
                    build_query_string(["q" => $searchTerm])
            );
        } catch (PDOException $e) {
            /*
             * SQLSTATE 23000 normally means the UNIQUE external_key
             * duplicate rule was triggered.
             */
            if ($e->getCode() === "23000") {
                flash_set(
                    "\"$title\" by $author has already been imported.",
                    "error"
                );

                redirect_to(
                    "books-import.php" .
                        build_query_string(["q" => $searchTerm])
                );
            }

            error_log(
                "Book import failed for UCID mp2446: " .
                    $e->getMessage()
            );

            $errors[] = "Import failed. Please try again.";
        }
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Import Books</title>
</head>

<body>
    <?php render_nav(); ?>

    <main>
        <h1>Import Books from Open Library</h1>

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

        <form method="get" action="books-import.php">
            <div>
                <label for="q">Search Open Library</label>

                <input
                    id="q"
                    name="q"
                    type="text"
                    required
                    minlength="2"
                    maxlength="100"
                    value="<?php echo htmlspecialchars($searchTerm); ?>">
            </div>

            <button type="submit">Search</button>
        </form>

        <?php
        if (
            $searchResult !== null &&
            isset($searchResult["success"]) &&
            $searchResult["success"]
        ):
        ?>
            <h2>
                Results for
                “<?php echo htmlspecialchars($searchTerm); ?>”
            </h2>

            <?php if (empty($searchResult["books"])): ?>
                <p>No books were found for that search.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($searchResult["books"] as $book): ?>
                        <?php
                        $bookTitle = clean_string(
                            $book["title"] ?? ""
                        );

                        $bookAuthor = clean_string(
                            $book["author"] ?? "Unknown Author"
                        );

                        if ($bookAuthor === "") {
                            $bookAuthor = "Unknown Author";
                        }

                        $bookYear = $book["publish_year"] ?? "";
                        $bookCoverId = $book["cover_id"] ?? "";
                        ?>

                        <li>
                            <strong>
                                <?php
                                echo htmlspecialchars($bookTitle);
                                ?>
                            </strong>

                            by

                            <?php
                            echo htmlspecialchars($bookAuthor);
                            ?>

                            <?php if ($bookYear !== ""): ?>
                                (
                                <?php
                                echo htmlspecialchars(
                                    (string) $bookYear
                                );
                                ?>
                                )
                            <?php endif; ?>

                            <form
                                method="post"
                                action="books-import.php<?php
                                                        echo htmlspecialchars(
                                                            build_query_string([
                                                                "q" => $searchTerm
                                                            ])
                                                        );
                                                        ?>"
                                style="display:inline">
                                <input
                                    type="hidden"
                                    name="import_title"
                                    value="<?php
                                            echo htmlspecialchars($bookTitle);
                                            ?>">

                                <input
                                    type="hidden"
                                    name="import_author"
                                    value="<?php
                                            echo htmlspecialchars($bookAuthor);
                                            ?>">

                                <input
                                    type="hidden"
                                    name="import_publish_year"
                                    value="<?php
                                            echo htmlspecialchars(
                                                (string) $bookYear
                                            );
                                            ?>">

                                <input
                                    type="hidden"
                                    name="import_cover_id"
                                    value="<?php
                                            echo htmlspecialchars(
                                                (string) $bookCoverId
                                            );
                                            ?>">

                                <button type="submit">
                                    Import
                                </button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>

        <p>
            <a href="books-create.php">
                + Add a book manually
            </a>

            |

            <a href="books.php">
                View all books
            </a>
        </p>
    </main>
</body>

</html>