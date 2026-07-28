<?php
// Temporary: a later lesson will move this check into shared auth utilities.
$isLoggedIn = isset($_SESSION["user"]);
?>
<nav>
  <a href="/project/index.php">Home</a>
  <?php if ($isLoggedIn): ?>
    <a href="/project/dashboard.php">Dashboard</a>
    <a href="/project/logout.php">Logout</a>
  <?php else: ?>
    <a href="/project/login.php">Login</a>
    <a href="/project/register.php">Register</a>
  <?php endif; ?>
</nav>
