<?php
// UCID: mp2446
// Date: 07/30/2026
// Public list of users with the number of books in each user's list.

require_once(__DIR__ . "/../../lib/app.php");

$users = [];

try {
    $db = getDB();

    $stmt = $db->prepare(
        "SELECT
            u.id,
            u.username,
            u.created,
            COUNT(ub.book_id) AS book_count
         FROM Users u
         LEFT JOIN UserBooks ub
            ON ub.user_id = u.id
         GROUP BY
            u.id,
            u.username,
            u.created
         ORDER BY u.username ASC"
    );

    $stmt->execute();

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log(
        "Public users query failed for UCID mp2446: " .
        $e->getMessage()
    );

    flash_set(
        "The user list could not be loaded right now.",
        "error"
    );
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

    <title>Users</title>
</head>

<body>
    <?php render_nav(); ?>

    <main>
        <h1>Users</h1>

        <?php render_flash(); ?>

        <?php if (empty($users)): ?>
            <p>No users were found.</p>
        <?php else: ?>
            <p>
                Total users:
                <strong><?php echo count($users); ?></strong>
            </p>

            <?php foreach ($users as $user): ?>
                <article>
                    <h2>
                        <a href="user.php?id=<?php
                            echo (int) $user["id"];
                        ?>">
                            <?php
                            echo htmlspecialchars(
                                $user["username"]
                            );
                            ?>
                        </a>
                    </h2>

                    <p>
                        <strong>Books:</strong>
                        <?php echo (int) $user["book_count"]; ?>
                    </p>

                    <p>
                        <strong>Member Since:</strong>
                        <?php
                        echo htmlspecialchars(
                            $user["created"]
                        );
                        ?>
                    </p>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</body>

</html>