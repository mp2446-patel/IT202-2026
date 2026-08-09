<?php
// UCID: mp2446
// Date: 07/26/2026
// Admin-only edit page. Only title, author, and publication year may change.
// The record ID, source, external key, created date, and API identity remain protected.

require_once(__DIR__ . "/../../lib/app.php");

require_role("admin");

$id = validate_positive_int($_GET["id"] ?? $_POST["id"] ?? null);

if ($id === null) {
    flash_set("That book could not be found.", "error");
    redirect_to("books.php");
}

$errors = [];
$book = null;

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
} catch (PDOException $e) {
    error_log(
        "Book edit lookup failed for UCID mp2446: " .
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

$title = $book["title"];
$author = $book["author"];
$publishYear = $book["publish_year"] !== null
    ? (string) $book["publish_year"]
    : "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = clean_string($_POST["title"] ?? "");
    $author = clean_string($_POST["author"] ?? "");
    $publishYear = clean_string($_POST["publish_year"] ?? "");

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

    if (empty($errors)) {
        try {
            /*
             * Editable-field allowlist:
             * Only title, author, and publish_year are updated.
             */
            $stmt = $db->prepare(
                "UPDATE Books
                 SET
                    title = :title,
                    author = :author,
                    publish_year = :publish_year
                 WHERE id = :id"
            );

            $stmt->bindValue(":title", $title, PDO::PARAM_STR);
            $stmt->bindValue(":author", $author, PDO::PARAM_STR);

            if ($validatedPublishYear === null) {
                $stmt->bindValue(
                    ":publish_year",
                    null,
                    PDO::PARAM_NULL
                );
            } else {
                $stmt->bindValue(
                    ":publish_year",
                    $validatedPublishYear,
                    PDO::PARAM_INT
                );
            }

            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $stmt->execute();

            flash_set(
                "The book was updated successfully.",
                "success"
            );

            redirect_to("book-detail.php?id=" . $id);
        } catch (PDOException $e) {
            error_log(
                "Book update failed for UCID mp2446: " .
                $e->getMessage()
            );

            $errors[] =
                "Could not save the changes. Please try again.";
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

    <title>Edit Book</title>
</head>

<body>
    <?php render_nav(); ?>

    <main>
        <h1>Edit Book</h1>

        <?php render_flash(); ?>

        <?php if (!empty($errors)): ?>
            <div
                id="message"
                class="flash flash-error"
                role="alert"
            >
                <?php foreach ($errors as $error): ?>
                    <p>
                        <?php echo htmlspecialchars($error); ?>
                    </p>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div
                id="message"
                class="flash flash-error"
                role="alert"
                style="display:none;"
            ></div>
        <?php endif; ?>

        <form
            method="post"
            action="books-edit.php?id=<?php echo (int) $id; ?>"
            onsubmit="return validateBookEdit(this);"
        >
            <input
                type="hidden"
                name="id"
                value="<?php echo (int) $id; ?>"
            >

            <div>
                <label for="title">Title</label>

                <input
                    id="title"
                    name="title"
                    type="text"
                    required
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

            <p>
                <strong>Source:</strong>
                <?php echo htmlspecialchars($book["source"]); ?>
                <em>(protected and not editable)</em>
            </p>

            <p>
                <strong>Record ID:</strong>
                <?php echo (int) $book["id"]; ?>
                <em>(protected and not editable)</em>
            </p>

            <p>
                <strong>Created:</strong>
                <?php echo htmlspecialchars($book["created"]); ?>
                <em>(system managed)</em>
            </p>

            <button type="submit">
                Save Changes
            </button>
        </form>

        <p>
            <a href="book-detail.php?id=<?php echo (int) $id; ?>">
                Cancel and return to book
            </a>
        </p>
    </main>

    <script>
        function validateBookEdit(form) {
            const errors = [];

            const title = form.title.value.trim();
            const author = form.author.value.trim();
            const publishYearValue =
                form.publish_year.value.trim();

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

            const message =
                document.getElementById("message");

            if (errors.length > 0) {
                message.innerHTML = "";

                errors.forEach(function (error) {
                    const paragraph =
                        document.createElement("p");

                    paragraph.textContent = error;
                    message.appendChild(paragraph);
                });

                message.style.display = "block";

                return false;
            }

            message.innerHTML = "";
            message.style.display = "none";

            return true;
        }
    </script>
</body>

</html>