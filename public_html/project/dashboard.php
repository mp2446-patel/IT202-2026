<?php
require_once(__DIR__ . "/../../lib/app.php");

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$currentUser = $_SESSION["user"];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>
<body>
    <?php render_nav(); ?>
    <h1>Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($currentUser["email"]); ?></p>
</body>
</html>
