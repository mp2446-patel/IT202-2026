<?php
require_once(__DIR__ . "/../../lib/app.php");

$errors = [];
$email = "";
$user = false;

if (isset($_POST["email"], $_POST["password"])) {
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }

    if ($password === "") {
        $errors[] = "Enter your password.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }

    if (empty($errors)) {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                "SELECT id AS user_id, email, password_hash
                 FROM Users
                 WHERE email = :email
                 LIMIT 1"
            );
            $stmt->execute([":email" => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Login query failed: " . $e->getMessage());
            $errors[] = "Login failed. Please try again.";
        }
    }

    /*if (empty($errors) && !$user) {
        $errors[] = "Email not found.";
    } elseif (empty($errors) && !password_verify($password, $user["password_hash"])) {
        $errors[] = "Invalid password.";
    }*/
    if (
        empty($errors)
        && (!$user || !password_verify($password, $user["password_hash"]))
    ) {
        $errors[] = "Invalid email or password.";
    }

    if (empty($errors)) {
        session_regenerate_id(true);
        $user["user_id"] = (int) $user["user_id"];
        unset($user["password_hash"]);
        $_SESSION["user"] = $user;
        header("Location: dashboard.php");
        exit;
    }
}

$message = implode("<br>", array_map("htmlspecialchars", $errors));
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>

<body>
    <?php render_nav(); ?>
    <h1>Login</h1>
    <p id="message"><?php echo $message; ?></p>
    <form method="post" action="login.php" onsubmit="return validate(this)">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required
            autocomplete="email"
            value="<?php echo htmlspecialchars($email); ?>">

        <label for="password">Password</label>
        <input id="password" name="password" type="password"
            required minlength="8"
            autocomplete="current-password">

        <button type="submit">Login</button>
    </form>
    <script>
        function validate(form) {
            const errors = [];

            if (!form.email.validity.valid) {
                errors.push("Enter a valid email address.");
            }

            if (form.password.validity.valueMissing) {
                errors.push("Enter your password.");
            } else if (form.password.validity.tooShort) {
                errors.push("Password must be at least 8 characters.");
            }

            document.getElementById("message").innerHTML = errors.join("<br>");
            return errors.length === 0;
        }
    </script>
</body>

</html>