<?php
session_start();
include("db.php");

$token = trim($_GET["token"] ?? $_POST["token"] ?? "");
$error = "";
$success = "";
$valid_token = false;
$user_id = 0;

if ($token !== "") {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT prt.user_id
         FROM password_reset_tokens prt
         JOIN users u ON prt.user_id = u.user_id
         WHERE prt.token = ?
           AND prt.used_at IS NULL
           AND prt.expires_at > NOW()
           AND u.status = 'active'
         LIMIT 1"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) === 1) {
            $valid_token = true;
            $row = mysqli_fetch_assoc($result);
            $user_id = (int) $row["user_id"];
        }

        mysqli_stmt_close($stmt);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["reset_submit"])) {
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (!$valid_token) {
        $error = "This reset link is invalid or has expired.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $update = mysqli_prepare(
            $conn,
            "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?"
        );

        if ($update) {
            mysqli_stmt_bind_param($update, "si", $password_hash, $user_id);

            if (mysqli_stmt_execute($update)) {
                $mark_used = mysqli_prepare(
                    $conn,
                    "UPDATE password_reset_tokens SET used_at = NOW() WHERE token = ?"
                );

                if ($mark_used) {
                    mysqli_stmt_bind_param($mark_used, "s", $token);
                    mysqli_stmt_execute($mark_used);
                    mysqli_stmt_close($mark_used);
                }

                $success = "Password updated successfully. You can now log in.";
                $valid_token = false;
            } else {
                $error = "Unable to update password. Please try again.";
            }

            mysqli_stmt_close($update);
        } else {
            $error = "Unable to update password. Please try again.";
        }
    }
} elseif ($token !== "" && !$valid_token && $_SERVER["REQUEST_METHOD"] !== "POST") {
    $error = "This reset link is invalid or has expired.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Reset Password - Mess Manager</title>
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
            Reset your password
        </div>

        <?php if ($success !== ""): ?>

            <div class="message"><?php echo htmlspecialchars($success); ?></div>
            <a href="login.php" class="create-button">Go to Login</a>

        <?php elseif ($valid_token): ?>

            <form method="post">

                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <label class="form-label">New Password</label>
                <input type="password" name="password" class="input-box" placeholder="minimum 6 characters" required>

                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="input-box" placeholder="Confirm Password" required>

                <?php if ($error !== ""): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <input type="submit" name="reset_submit" value="Update Password" class="login-button">

            </form>

        <?php else: ?>

            <?php if ($error !== ""): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <a href="forgot_password.php" class="create-button">Request New Link</a>

        <?php endif; ?>

        <div class="divider">or</div>
        <a href="login.php" class="create-button">Back to Login</a>

    </div>

</div>

</body>
</html>
