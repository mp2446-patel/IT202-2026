<?php
// UCID: mp2446
// Date: 07/30/2026
// Shared navigation with public and login-based links.

$isLoggedIn = is_logged_in();
?>

<nav>
    <a href="/project/index.php">
        Home
    </a>

    <a href="/project/books.php">
        Books
    </a>

    <a href="/project/users.php">
        Users
    </a>

    <?php if ($isLoggedIn): ?>
        <a href="/project/dashboard.php">
            Dashboard
        </a>

        <a href="/project/my-books.php">
            My Books
        </a>

        <a href="/project/logout.php">
            Logout
        </a>
    <?php else: ?>
        <a href="/project/login.php">
            Login
        </a>

        <a href="/project/register.php">
            Register
        </a>
    <?php endif; ?>
</nav>