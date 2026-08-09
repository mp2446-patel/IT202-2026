<?php
// UCID: mp2446
// Date: 07/26/2026
// Admin-only manual book creation page.
// Uses HTML, JavaScript, and PHP validation with sticky form values.

require_once(__DIR__ . "/../../lib/app.php");

require_role("admin");

$errors = [];

$title = "";
$author = "";
$publishYear = "";
$coverId = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = clean_string($_POST["title"] ?? "");
    $author = clean_string($_POST["author"] ?? "");
    $publishYear = clean_string($_POST["publish_year"] ?? "");
    $coverId = clean_string($_POST["cover_id"] ?? "");

    /*
     * Server-side validation
     */
    validate_required($title, "Title", $errors);
    validate_required($author, "Author", $errors);

    if (strlen($title) > 255) {
        $errors[] = "Title must be 255 characters or fewer.";
    }

    if (strlen($author) > 255) {
        $errors[] = "Author must be 255 characters or fewer.";
    }

    $validatedPublishYear = null;

    if ($publishYear !== "") {
        $validatedPublishYear = filter_var(
            $publishYear,
            FILTER_VALIDATE_INT
        );

        if (
            $validatedPublishYear === false ||
            $validatedPublishYear < 1000 ||
            $validatedPublishYear > ((int) date("Y") + 1)
        ) {
            $errors[] = "Publication year must be a reasonable year.";
        }
    }

    $validatedCoverId = null;

    if ($coverId !== "") {
        $validatedCoverId = filter_var(
            $coverId,
            FILTER_VALIDATE_INT,
            [
                "options" => [
                    "min_range" => 1
                ]
            ]
        );

        if ($validatedCoverId === false) {
            $errors[] = "Cover ID must be a positive number.";
        }
    }

    /*
     * Manual and API books share the same Books table shape.
     * The source field distinguishes manual rows from API rows.
     *
     * The normalized title and author create one consistent
     * duplicate-record key.
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
                    'manual',
                    :external_key
                )"
            );

            $stmt->execute([
                ":title" => $title,
                ":author" => $author,
                ":publish_year" => $validatedPublishYear,
                ":cover_id" => $validatedCoverId,
                ":external_key" => $externalKey
            ]);

            flash_set(
                "The book \"$title\" was created successfully.",
                "success"
            );

            redirect_to("books.php");
        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                $errors[] =
                    "A book with this title and author already exists.";
            } else {
                error_log(
                    "Manual book creation failed for UCID mp2446: " .
                    $e->getMessage()
                );

                $errors[] =
                    "The book could not be created. Please try again.";
            }
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
        content="width=device-width, initial-scale=1.0"
    >

    <title>Add Book Manually</title>
</head>

<body>
    <?php render_nav(); ?>

    <main>
        <h1>Add a Book Manually</h1>

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

        <form
            id="book-form"
            method="post"
            action="books-create.php"
            onsubmit="return validateBookForm();"
        >
            <div>
                <label for="title">Title</label>

                <input
                    id="title"
                    name="title"
                    type="text"
                    required
                    minlength="1"
                    maxlength="255"
                    value="<?php echo htmlspecialchars($title); ?>"
                >
            </div>

            <div>
                <label for="author">Author</label>

                <input
                    id="author"
                    name="author"
                    type="text"
                    required
                    minlength="1"
                    maxlength="255"
                    value="<?php echo htmlspecialchars($author); ?>"
                >
            </div>

            <div>
                <label for="publish_year">
                    Publication Year
                </label>

                <input
                    id="publish_year"
                    name="publish_year"
                    type="number"
                    min="1000"
                    max="<?php echo (int) date("Y") + 1; ?>"
                    value="<?php echo htmlspecialchars($publishYear); ?>"
                >
            </div>

            <div>
                <label for="cover_id">
                    Open Library Cover ID
                </label>

                <input
                    id="cover_id"
                    name="cover_id"
                    type="number"
                    min="1"
                    value="<?php echo htmlspecialchars($coverId); ?>"
                >
            </div>

            <div
                id="client-errors"
                class="flash flash-error"
                role="alert"
                style="display:none;"
            ></div>

            <button type="submit">
                Create Book
            </button>
        </form>

        <p>
            <a href="books-import.php">
                Import from Open Library
            </a>

            |

            <a href="books.php">
                Return to Books
            </a>
        </p>
    </main>

    <script>
        function validateBookForm() {
            const title = document
                .getElementById("title")
                .value
                .trim();

            const author = document
                .getElementById("author")
                .value
                .trim();

            const publishYearValue = document
                .getElementById("publish_year")
                .value
                .trim();

            const coverIdValue = document
                .getElementById("cover_id")
                .value
                .trim();

            const errors = [];

            if (title === "") {
                errors.push("Title is required.");
            }

            if (title.length > 255) {
                errors.push(
                    "Title must be 255 characters or fewer."
                );
            }

            if (author === "") {
                errors.push("Author is required.");
            }

            if (author.length > 255) {
                errors.push(
                    "Author must be 255 characters or fewer."
                );
            }

            if (publishYearValue !== "") {
                const publishYear =
                    Number.parseInt(publishYearValue, 10);

                const maximumYear =
                    new Date().getFullYear() + 1;

                if (
                    Number.isNaN(publishYear) ||
                    publishYear < 1000 ||
                    publishYear > maximumYear
                ) {
                    errors.push(
                        "Publication year must be a reasonable year."
                    );
                }
            }

            if (coverIdValue !== "") {
                const coverId =
                    Number.parseInt(coverIdValue, 10);

                if (
                    Number.isNaN(coverId) ||
                    coverId < 1
                ) {
                    errors.push(
                        "Cover ID must be a positive number."
                    );
                }
            }

            const errorBox =
                document.getElementById("client-errors");

            if (errors.length > 0) {
                errorBox.innerHTML = "";

                errors.forEach(function (error) {
                    const paragraph =
                        document.createElement("p");

                    paragraph.textContent = error;
                    errorBox.appendChild(paragraph);
                });

                errorBox.style.display = "block";

                return false;
            }

            errorBox.style.display = "none";
            errorBox.innerHTML = "";

            return true;
        }
    </script>
</body>

</html>