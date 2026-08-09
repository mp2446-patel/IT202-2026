<?php
// UCID: mp2446
// Date: 07/18/2026
// Test page for the Open Library project API integration.

require_once(__DIR__ . "/../../lib/api_helper.php");
require_once(__DIR__ . "/../../lib/project_api.php");

$searchTerm = "";
$useCached = true;
$result = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $searchTerm = trim($_POST["search"] ?? "");
    $mode = $_POST["mode"] ?? "cached";
    $useCached = ($mode === "cached");

    $result = get_open_library_books($searchTerm, $useCached);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Open Library API Test</title>
</head>

<body>

    <h1>Open Library API Test</h1>

    <form method="POST">
        <label for="search">Book Search:</label>

        <input
            type="text"
            id="search"
            name="search"
            value="<?= htmlspecialchars($searchTerm) ?>"
            placeholder="Enter a book topic"
            required>


        <br><br>

        <button type="submit" name="mode" value="cached">
            Test Cached Response
        </button>

        <button type="submit" name="mode" value="live">
            Test Live API
        </button>
    </form>

    <hr>

    <?php if ($result !== null): ?>

        <h2>
            <?= htmlspecialchars($result["message"]) ?>
        </h2>

        <?php if ($result["success"]): ?>

            <p>
                Source:
                <strong>
                    <?= $useCached ? "Cached JSON Sample" : "Live Open Library API" ?>
                </strong>
            </p>

            <?php foreach ($result["books"] as $book): ?>

                <div>
                    <h3>
                        <?= htmlspecialchars((string)$book["title"]) ?>
                    </h3>

                    <p>
                        <strong>Author:</strong>
                        <?= htmlspecialchars((string)$book["author"]) ?>
                    </p>

                    <p>
                        <strong>First Published:</strong>
                        <?= htmlspecialchars((string)$book["publish_year"]) ?>
                    </p>

                    <p>
                        <strong>Cover ID:</strong>
                        <?= htmlspecialchars((string)($book["cover_id"] ?? "Not available")) ?>
                    </p>
                </div>

                <hr>

            <?php endforeach; ?>

        <?php endif; ?>

    <?php endif; ?>

</body>

</html>