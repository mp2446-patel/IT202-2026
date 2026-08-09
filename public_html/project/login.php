<?php
// UCID: mp2446
// Date: 08/04/2026
// Login page that validates credentials and stores the user's
// id, username, email, and role in the session.

require_once(__DIR__ . "/../../lib/app.php");

$errors = [];
$email = "";
$user = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = filter_var(
        trim($_POST["email"] ?? ""),
        FILTER_SANITIZE_EMAIL
    );

    $password = $_POST["password"] ?? "";

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
                "SELECT
                    id,
                    username,
                    email,
                    password_hash,
                    role
                 FROM Users
                 WHERE email = :email
                 LIMIT 1"
            );

            $stmt->execute([
                ":email" => $email
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log(
                "Login query failed for UCID mp2446: " .
                $e->getMessage()
            );

            $errors[] =
                "Login failed. Please try again.";
        }
    }

    if (
        empty($errors) &&
        (
            !$user ||
            !password_verify(
                $password,
                $user["password_hash"]
            )
        )
    ) {
        $errors[] = "Invalid email or password.";
    }

    if (empty($errors)) {
        session_regenerate_id(true);

        $user["id"] = (int) $user["id"];

        unset($user["password_hash"]);

        $_SESSION["user"] = $user;

        flash_set(
            "Welcome back!",
            "success"
        );

        redirect_to("dashboard.php");
    }
}

$message = implode(
    "<br>",
    array_map("htmlspecialchars", $errors)
);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login</title>
</head>

<body>
    <?php render_nav(); ?>

    <main>
        <h1>Login</h1>

        <?php render_flash(); ?>

        <?php if ($message !== ""): ?>
            <p
                id="message"
                class="flash flash-error"
                role="alert"
            >
                <?php echo $message; ?>
            </p>
        <?php else: ?>
            <p
                id="message"
                class="flash flash-error"
                role="alert"
                style="display:none;"
            ></p>
        <?php endif; ?>

        <form
            method="post"
            action="login.php"
            onsubmit="return validateLogin(this);"
        >
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
                    autocomplete="current-password"
                >
            </div>

            <button type="submit">
                Login
            </button>
        </form>
    </main>

    <script>
        function validateLogin(form) {
            const errors = [];

            if (!form.email.validity.valid) {
                errors.push(
                    "Enter a valid email address."
                );
            }

            if (form.password.validity.valueMissing) {
                errors.push(
                    "Enter your password."
                );
            } else if (form.password.validity.tooShort) {
                errors.push(
                    "Password must be at least 8 characters."
                );
            }

            const message =
                document.getElementById("message");

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