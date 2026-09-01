<?php
session_start();
include("db.php");

$email = "";
$error = "";
$success = "";
$reset_link = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");

    if (empty($email)) {
        $error = "Enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Enter a valid email address.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? AND status = 'active' LIMIT 1");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) === 1) {
                $user = mysqli_fetch_assoc($result);
                $user_id = (int) $user["user_id"];

                $token = bin2hex(random_bytes(32));
                $expires_at = date("Y-m-d H:i:s", time() + 3600);

                $invalidate = mysqli_prepare(
                    $conn,
                    "UPDATE password_reset_tokens SET used_at = NOW()
                     WHERE user_id = ? AND used_at IS NULL"
                );

                if ($invalidate) {
                    mysqli_stmt_bind_param($invalidate, "i", $user_id);
                    mysqli_stmt_execute($invalidate);
                    mysqli_stmt_close($invalidate);
                }

                $insert = mysqli_prepare(
                    $conn,
                    "INSERT INTO password_reset_tokens (user_id, token, expires_at)
                     VALUES (?, ?, ?)"
                );

                if ($insert) {
                    mysqli_stmt_bind_param($insert, "iss", $user_id, $token, $expires_at);

                    if (mysqli_stmt_execute($insert)) {
                        $protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
                        $host = $_SERVER["HTTP_HOST"] ?? "localhost";
                        $base = rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/\\");
                        $reset_link = $protocol . "://" . $host . $base . "/reset_password.php?token=" . urlencode($token);
                        $success = "If an account exists for that email, a reset link has been generated.";
                    } else {
                        $error = "Unable to process request. Please try again.";
                    }

                    mysqli_stmt_close($insert);
                } else {
                    $error = "Password reset is not available. Please run database/password_reset_tokens.sql first.";
                }
            } else {
                $success = "If an account exists for that email, a reset link has been generated.";
            }

            mysqli_stmt_close($stmt);
        } else {
            $error = "Unable to process request. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Forgot Password - Mess Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>

<div class="main" style="justify-content: center;">

    <div class="login-card">

        <div class="logo">
            <img src="src/img/login_img/logo.png" alt="Mess Manager">
        </div>

        <div class="title">
            Forgot your password?
            <br>
            Enter your email to reset it
        </div>

        <form method="post">

            <label class="form-label">Your Email</label>

            <input
                type="email"
                name="email"
                class="input-box"
                placeholder="Your Email"
                value="<?php echo htmlspecialchars($email); ?>"
                required>

            <?php if ($error !== ""): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success !== ""): ?>
                <div class="message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if ($reset_link !== ""): ?>
                <div class="message" style="word-break: break-all; font-size: 12px;">
                    Local dev reset link:
                    <a href="<?php echo htmlspecialchars($reset_link); ?>">
                        Reset Password
                    </a>
                </div>
            <?php endif; ?>

            <input type="submit" value="Send Reset Link" class="login-button">

        </form>

        <div class="divider">or</div>

        <a href="login.php" class="create-button">Back to Login</a>

    </div>

</div>

</body>
</html>
