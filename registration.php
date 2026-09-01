<?php
include("db.php");

$name = "";
$email = "";
$password = "";
$confirmPassword = "";

$nameError = "";
$emailError = "";
$passwordError = "";
$confirmPasswordError = "";
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if (empty($name)) $nameError = "Enter Name";

    if (empty($email)) $emailError = "Enter Email";
    else if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $emailError = "Enter a valid email";

    if (empty($password)) $passwordError = "Enter Password";
    else if (strlen($password) < 6)
        $passwordError = "Password must be at least 6 characters";

    if (empty($confirmPassword)) $confirmPasswordError = "Confirm Password";
    else if ($password !== $confirmPassword)
        $confirmPasswordError = "Passwords do not match";

    if (
        $nameError == "" && $emailError == "" &&
        $passwordError == "" && $confirmPasswordError == ""
    ) {

        $check = "SELECT user_id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $check);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                $message = "Email Already Exists";
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO users
                        (full_name, email, password_hash, status)
                        VALUES (?, ?, ?, 'active')";

                $insertStmt = mysqli_prepare($conn, $sql);

                if ($insertStmt) {
                    mysqli_stmt_bind_param(
                        $insertStmt,
                        "sss",
                        $name,
                        $email,
                        $passwordHash
                    );

                    if (mysqli_stmt_execute($insertStmt)) {
                        $message = "Registration Successful";
                        $name = "";
                        $email = "";
                        $password = "";
                        $confirmPassword = "";
                    } else {
                        $message = "Registration Failed";
                    }

                    mysqli_stmt_close($insertStmt);
                } else {
                    $message = "Database Query Failed";
                }
            }

            mysqli_stmt_close($stmt);
        } else {
            $message = "Database Query Failed";
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Registration - Mess Manager</title>

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="css/registration.css">
</head>

<body>

    <button class="language" id="language-button" onclick="changeLanguage()">বং</button>

    <div class="main">

        <div class="left">

            <img src="src/img/registration_img/login.webp"
                class="phones"
                alt="Mess Manager">

            <div class="bottom-links">

                <div class="bottom-item">
                    <a href="https://www.facebook.com/MessManagerM27Lab/?_rdr" target="_blank">
                        <i class="fa-brands fa-facebook"></i>
                        <span>Our Facebook Page</span>
                    </a>
                </div>

                <div class="bottom-item">
                    <a href="https://www.youtube.com/watch?v=dyEZAiEaQ1o" target="_blank">
                        <i class="fa-brands fa-youtube"></i>
                        <span>How to use the App</span>
                    </a>
                </div>

                <div class="bottom-item">
                    <a href="https://play.google.com/store/apps/details?id=com.m27lab.messmanager.app&pli=1"
                        target="_blank">
                        <i class="fa-solid fa-mobile-screen-button"></i>
                        <span>Mobile App</span>
                    </a>
                </div>

            </div>
        </div>

        <div class="registration-card">

            <div class="logo">
                <img src="src/img/registration_img/logo.png"
                    alt="Mess Manager Logo">
            </div>

            <div class="title" id="title">
                Create Account<br>
                with Email
            </div>

            <form method="post">

                <label class="form-label" id="name-label">Your Name</label>

                <input type="text"
                    name="name"
                    class="input-box"
                    id="name"
                    placeholder="e.g. Md Rahim"
                    value="<?php echo htmlspecialchars($name); ?>">

                <?php if ($nameError != ""): ?>
                    <div class="error"><?php echo htmlspecialchars($nameError); ?></div>
                <?php endif; ?>


                <label class="form-label" id="email-label">Your Email</label>

                <input type="email"
                    name="email"
                    class="input-box"
                    id="email"
                    placeholder="e.g. rahim@gmail.com"
                    value="<?php echo htmlspecialchars($email); ?>">

                <?php if ($emailError != ""): ?>
                    <div class="error"><?php echo htmlspecialchars($emailError); ?></div>
                <?php endif; ?>


                <label class="form-label" id="password-label">Enter Password</label>

                <input type="password"
                    name="password"
                    class="input-box"
                    id="password"
                    placeholder="minimum 6 characters">

                <?php if ($passwordError != ""): ?>
                    <div class="error"><?php echo htmlspecialchars($passwordError); ?></div>
                <?php endif; ?>


                <label class="form-label" id="confirm-password-label">
                    Confirm Password
                </label>

                <input type="password"
                    name="confirm_password"
                    class="input-box"
                    id="confirm-password"
                    placeholder="Confirm Password">

                <?php if ($confirmPasswordError != ""): ?>
                    <div class="error"><?php echo htmlspecialchars($confirmPasswordError); ?></div>
                <?php endif; ?>


                <?php if ($message != ""): ?>
                    <div class="message"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <input type="submit"
                    value="Create Account"
                    class="create-button"
                    id="create-button">

            </form>

            <div class="divider">or</div>

            <a href="login.php"
                class="login-button"
                id="login-button">
                Already have an account? Login.
            </a>

        </div>
    </div>

    <script src="js/registration.js"></script>

</body>

</html>