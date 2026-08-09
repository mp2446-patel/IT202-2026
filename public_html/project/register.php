<?php
// UCID: mp2446
// Date: 07/27/2026
// Registration page with HTML, JavaScript, and PHP validation.
// New users are created with the default role of "user".

require_once(__DIR__ . "/../../lib/app.php");

$errors = [];

$username = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = clean_string($_POST["username"] ?? "");
    $email = filter_var(
        trim($_POST["email"] ?? ""),
        FILTER_SANITIZE_EMAIL
    );

    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if (
        !preg_match(
            "/^[A-Za-z0-9_]{3,30}$/",
            $username
        )
    ) {
        $errors[] =
            "Username must be 3 to 30 characters and use only letters, numbers, or underscores.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }

    if (strlen($password) < 8) {
        $errors[] =
            "Password must be at least 8 characters.";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Passwords must match.";
    }

    if (empty($errors)) {
        try {
            $db = getDB();

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $db->prepare(
                "INSERT INTO Users (
                    username,
                    email,
                    password_hash,
                    role
                )
                VALUES (
                    :username,
                    :email,
                    :password_hash,
                    'user'
                )"
            );

            $stmt->execute([
                ":username" => $username,
                ":email" => $email,
                ":password_hash" => $passwordHash
            ]);

            flash_set(
                "Registration successful. You can now log in.",
                "success"
            );

            redirect_to("login.php");
        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                $errors[] =
                    "That username or email is already registered.";
            } else {
                error_log(
                    "Registration failed for UCID mp2446: " .
                    $e->getMessage()
                );

                $errors[] =
                    "Registration failed. Please try again.";
            }
        }
    }
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

    <title>Register</title>
</head>

<body>
    <?php render_nav(); ?>

    <main>
        <h1>Register</h1>

        <?php render_flash(); ?>

        <?php if (!empty($errors)): ?>
            <div
                id="form-message"
                class="flash flash-error"
                role="alert"
            >
                <?php foreach ($errors as $error): ?>
                    <p>
                        <?php echo htmlspecialchars($error); ?>
                    </p>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div
                id="form-message"
                class="flash flash-error"
                role="alert"
                style="display:none;"
            ></div>
        <?php endif; ?>

        <form
            method="post"
            action="register.php"
            onsubmit="return validateRegistration(this);"
        >
            <div>
                <label for="username">
                    Username
                </label>

                <input
                    id="username"
                    name="username"
                    type="text"
                    required
                    minlength="3"
                    maxlength="30"
                    pattern="[A-Za-z0-9_]{3,30}"
                    autocomplete="username"
                    value="<?php
                        echo htmlspecialchars($username);
                    ?>"
                >
            </div>

            <div>
                <label for="email">
                    Email
                </label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    required
                    autocomplete="email"
                    value="<?php
                        echo htmlspecialchars($email);
                    ?>"
                >
            </div>

            <div>
                <label for="password">
                    Password
                </label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                >
            </div>

            <div>
                <label for="confirm_password">
                    Confirm Password
                </label>

                <input
                    id="confirm_password"
                    name="confirm_password"
                    type="password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                >
            </div>

            <button type="submit">
                Register
            </button>
        </form>
    </main>

    <script>
        function validateRegistration(form) {
            const errors = [];

            const username = form.username.value.trim();
            const email = form.email.value.trim();
            const password = form.password.value;
            const confirmPassword =
                form.confirm_password.value;

            const usernamePattern =
                /^[A-Za-z0-9_]{3,30}$/;

            if (!usernamePattern.test(username)) {
                errors.push(
                    "Username must be 3 to 30 characters and use only letters, numbers, or underscores."
                );
            }

            if (
                email === "" ||
                !form.email.validity.valid
            ) {
                errors.push(
                    "Enter a valid email address."
                );
            }

            if (password.length < 8) {
                errors.push(
                    "Password must be at least 8 characters."
                );
            }

            if (password !== confirmPassword) {
                errors.push(
                    "Passwords must match."
                );
            }

            const message =
                document.getElementById("form-message");

            if (errors.length > 0) {
                message.innerHTML = "";

                errors.forEach(function (error) {
                    const paragraph =
                        document.createElement("p");

                    paragraph.textContent = error;
                    message.appendChild(paragraph);
                });

                message.style.display = "block";

                return false;
            }

            message.innerHTML = "";
            message.style.display = "none";

            return true;
        }
    </script>
</body>

</html>