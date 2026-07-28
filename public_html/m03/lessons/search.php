<?php
// Read the URL value; blank keeps the first page load quiet.
$term = $_GET["term"] ?? "";
?>

<form method="GET" action="search.php">
  <label for="term">Search term</label>
  <input id="term" name="term" type="text">
  <button type="submit">Search</button>
</form>

<p>Current term: <?php echo htmlspecialchars($term); ?></p>