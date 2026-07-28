<?php
require_once(__DIR__ . "/../../lib/app.php");
$errors = [];
$email = "";

if (isset($_POST["email"], $_POST["password"], $_POST["confirm_password"])) {
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirm_password"];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Passwords must match.";
    }

    if (empty($errors)) {
        // TODO: connect to the database, hash the password, and insert the user.
        try {
            $db = getDB();
            $hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $db->prepare(
                "INSERT INTO Users (email, password_hash)
             VALUES (:email, :password_hash)"
            );
            $stmt->execute([
                ":email" => $email,
                ":password_hash" => $hash,
            ]);

            error_log("Registration insert succeeded for user id " . $db->lastInsertId());
            echo "Registration saved. This temporary message can be replaced later.";
            $email = "";
        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                $errors[] = "That email is already registered.";
            } else {
                error_log("Registration failed: " . $e->getMessage());
                $errors[] = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>

<body>
    <?php render_nav(); ?>
    <h1>Register</h1>
    <?php
    if (!empty($errors)) {
        echo "<pre>";
        echo var_export($errors, true);
        echo "</pre>";
    }
    ?>
    <form method="post" action="register.php" onsubmit="return validate(this);">
        <p id="form-message"></p>
        <label for="email">Email</label>
        <input id="email" name="email" type="email"
            required autocomplete="email"
            value="<?php echo htmlspecialchars($email); ?>">

        <label for="password">Password</label>
        <input id="password" name="password" type="password"
            required minlength="8" autocomplete="new-password">

        <label for="confirm_password">Confirm Password</label>
        <input id="confirm_password" name="confirm_password" type="password"
            required minlength="8" autocomplete="new-password">

        <button type="submit">Register</button>
    </form>
    <script>
        function validate(form) {
            const message = document.querySelector("#form-message");
            const email = form.email.value.trim();
            const password = form.password.value;
            const confirmPassword = form.confirm_password.value;
            const errors = [];

            if (!email || !form.email.validity.valid) {
                errors.push("Enter a valid email address.");
            }

            if (password.length < 8) {
                errors.push("Password must be at least 8 characters.");
            }

            if (password !== confirmPassword) {
                errors.push("Passwords must match.");
            }

            message.innerHTML = errors.join("<br>");
            return errors.length === 0;
        }
    </script>
</body>


</html>

<form method="POST" onsubmit="return validate();">

Username:
<input
    type="text"
    name="username"
    pattern="[A-Za-z0-9_]{3,30}"
    value="<?= htmlspecialchars($username ?? '') ?>">

Email:
<input
    type="email"
    name="email"
    value="<?= htmlspecialchars($email ?? '') ?>">

Password:
<input type="password" name="password">

Confirm Password:
<input type="password" name="confirm_password">

<input type="submit" value="Register">
</form>