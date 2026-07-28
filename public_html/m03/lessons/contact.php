<?php
$submittedName = "";

if (isset($_POST["name"])) {
    // trim removes accidental spaces before display.
    $submittedName = trim($_POST["name"]);
}
?>

<form method="POST">
  <label for="name">Name</label>
  <input id="name" name="name" type="text" required>
  <button type="submit">Send</button>
</form>

<?php if (!empty($submittedName)): ?>
  <p>Received: <?php echo htmlspecialchars($submittedName); ?></p>
<?php endif; ?>
