<?php
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/includes/auth.php";

require_login();

$user_id = get_logged_in_user_id();
$user_name = get_logged_in_user_name();
$user_email = isset($_SESSION["email"]) ? $_SESSION["email"] : "";

if (isset($_GET["lang"]) && in_array($_GET["lang"], ["bn", "en"], true)) {
    $_SESSION["language"] = $_GET["lang"];
    header("Location: profile.php");
    exit();
}

$info_message = "";
$info_error = "";
$password_message = "";
$password_error = "";

$user_phone = "";
$user_profile_image = null;

$stmt = $conn->prepare(
    "SELECT full_name, email, phone, profile_image FROM users WHERE user_id = ? LIMIT 1"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $user_name = $row["full_name"];
    $user_email = $row["email"];
    $user_phone = $row["phone"] ?? "";
    $user_profile_image = $row["profile_image"];
    $_SESSION["name"] = $user_name;
    $_SESSION["email"] = $user_email;
}

$stmt->close();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_info"])) {
    $full_name = trim($_POST["full_name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");

    if ($full_name === "") {
        $info_error = "Name is required.";
    } elseif (strlen($full_name) > 100) {
        $info_error = "Name is too long.";
    } elseif ($phone !== "" && !preg_match("/^[0-9+\-\s()]{6,20}$/", $phone)) {
        $info_error = "Enter a valid phone number.";
    } else {
        $update = $conn->prepare(
            "UPDATE users SET full_name = ?, phone = ?, updated_at = NOW() WHERE user_id = ?"
        );
        $update->bind_param("ssi", $full_name, $phone, $user_id);

        if ($update->execute()) {
            $user_name = $full_name;
            $user_phone = $phone;
            $_SESSION["name"] = $full_name;
            $info_message = "Profile updated successfully.";
        } else {
            $info_error = "Unable to update profile. Please try again.";
        }

        $update->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["change_password"])) {
    $current_password = $_POST["current_password"] ?? "";
    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if ($current_password === "" || $new_password === "" || $confirm_password === "") {
        $password_error = "All password fields are required.";
    } elseif (strlen($new_password) < 6) {
        $password_error = "New password must be at least 6 characters.";
    } elseif ($new_password !== $confirm_password) {
        $password_error = "New passwords do not match.";
    } else {
        $check = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ? LIMIT 1");
        $check->bind_param("i", $user_id);
        $check->execute();
        $check_result = $check->get_result();
        $user_row = $check_result->fetch_assoc();
        $check->close();

        if (!$user_row || !password_verify($current_password, $user_row["password_hash"])) {
            $password_error = "Current password is incorrect.";
        } else {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

            $update = $conn->prepare(
                "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?"
            );
            $update->bind_param("si", $password_hash, $user_id);

            if ($update->execute()) {
                $password_message = "Password changed successfully.";
            } else {
                $password_error = "Unable to change password. Please try again.";
            }

            $update->close();
        }
    }
}

$profile_image_src = "src/img/dashboard_img/profile.png";

if (!empty($user_profile_image) && file_exists($user_profile_image)) {
    $profile_image_src = $user_profile_image;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mess Manager | Profile</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/profile.css">
</head>

<body>

<header class="top-nav">

    <div class="nav-logo">
        <a href="dashboard.php">
            <img src="src/img/home_img/logo.png" alt="Mess Manager Logo">
        </a>
    </div>

    <div class="nav-icons">
        <a href="dashboard.php" class="nav-icon">
            <i class="fa-solid fa-table-cells-large"></i>
        </a>
        <a href="home.php" class="nav-icon">
            <i class="fa-solid fa-house"></i>
        </a>
        <a href="#" class="nav-icon">
            <i class="fa-regular fa-circle-question"></i>
        </a>
        <a href="#" class="nav-icon">
            <i class="fa-solid fa-bell"></i>
        </a>
        <a href="profile.php" class="nav-icon active">
            <i class="fa-regular fa-user"></i>
        </a>
    </div>

    <div class="nav-user">
        <span><?php echo htmlspecialchars($user_name); ?></span>
        <a href="profile.php">
            <img src="<?php echo htmlspecialchars($profile_image_src); ?>" alt="Profile">
        </a>
    </div>

</header>

<main class="profile-page">

    <div class="profile-card">

        <div class="profile-image-box">
            <img src="<?php echo htmlspecialchars($profile_image_src); ?>" alt="Profile">
        </div>

        <h1><?php echo htmlspecialchars(strtoupper($user_name)); ?></h1>

        <div class="profile-row language-row">
            <div class="row-left">
                <i class="fa-solid fa-language"></i>
                <span>Select Language</span>
            </div>
            <div class="language-switch">
                <a href="profile.php?lang=bn"
                   class="<?php echo (($_SESSION['language'] ?? 'en') === 'bn') ? 'active-language' : ''; ?>">
                    বাং
                </a>
                <a href="profile.php?lang=en"
                   class="<?php echo (($_SESSION['language'] ?? 'en') === 'en') ? 'active-language' : ''; ?>">
                    En
                </a>
            </div>
        </div>

        <div class="section">
            <h3>Account Info</h3>

            <div class="profile-row">
                <div class="row-left">
                    <i class="fa-regular fa-at"></i>
                    <span>Email: <?php echo htmlspecialchars($user_email); ?></span>
                </div>
            </div>

            <div class="profile-row">
                <div class="row-left">
                    <i class="fa-solid fa-phone"></i>
                    <span>
                        Phone:
                        <?php echo $user_phone !== "" ? htmlspecialchars($user_phone) : "Not Set Yet"; ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="section">
            <h3>Membership / Coins</h3>

            <div class="profile-row">
                <div class="row-left">
                    <i class="fa-solid fa-cube"></i>
                    <span>My Package: basic-single</span>
                </div>
            </div>

            <div class="profile-row">
                <div class="row-left">
                    <i class="fa-regular fa-calendar-days"></i>
                    <span>Package Expires: Not Set</span>
                </div>
            </div>
        </div>

        <div class="section">
            <h3>Account Settings</h3>

            <form method="post" class="profile-form">
                <label for="full_name">Full Name</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    value="<?php echo htmlspecialchars($user_name); ?>"
                    required>

                <label for="phone">Phone</label>
                <input
                    type="text"
                    id="phone"
                    name="phone"
                    value="<?php echo htmlspecialchars($user_phone); ?>"
                    placeholder="e.g. 017XXXXXXXX">

                <?php if ($info_message !== ""): ?>
                    <div class="profile-alert success"><?php echo htmlspecialchars($info_message); ?></div>
                <?php endif; ?>

                <?php if ($info_error !== ""): ?>
                    <div class="profile-alert error"><?php echo htmlspecialchars($info_error); ?></div>
                <?php endif; ?>

                <button type="submit" name="update_info" class="profile-btn">
                    Save Information
                </button>
            </form>

            <form method="post" class="profile-form profile-form-spaced">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>

                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" placeholder="minimum 6 characters" required>

                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>

                <?php if ($password_message !== ""): ?>
                    <div class="profile-alert success"><?php echo htmlspecialchars($password_message); ?></div>
                <?php endif; ?>

                <?php if ($password_error !== ""): ?>
                    <div class="profile-alert error"><?php echo htmlspecialchars($password_error); ?></div>
                <?php endif; ?>

                <button type="submit" name="change_password" class="profile-btn profile-btn-dark">
                    Change Password
                </button>
            </form>
        </div>

        <a href="logout.php" class="logout-button">Logout</a>

    </div>

</main>

</body>
</html>
