<?php
require_once(__DIR__ . "/../../../lib/db.php");


$q = trim($_GET["q"] ?? "");
$pageError = "";
$db = getDB();
// query is OK here because no user input is inside the SQL.
/*$stmt = $db->query(
    "SELECT id, email, modified, created
     FROM Users
     ORDER BY created DESC
     LIMIT 10"
);*/
try {
    $stmt = $db->prepare(
        "SELECT id, email
     FROM Users
     WHERE email LIKE :q
     ORDER BY email
     LIMIT 10"
    );
    $stmt->execute([":q" => "%" . $q . "%"]);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $users = [];
    $pageError = "Database is unavailable right now.";
}
?>

<?php if(!empty($pageError)):?>
    <p style="color:red"><?php echo htmlspecialchars($pageError);?></p>
<?php endif;?>
<?php if (count($users) === 0): ?>
    <p>No users yet.</p>
<?php else: ?>
    <ul>
        <?php foreach ($users as $user): ?>
            <li>
                <!-- Escape database values before showing them in HTML. -->
                <?php echo htmlspecialchars($user["email"]); ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>