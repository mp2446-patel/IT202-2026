<?php
require_once(__DIR__ . "/../../lib/app.php");

session_unset();
session_destroy();

header("Location: login.php");
exit;
?>