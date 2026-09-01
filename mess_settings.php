<?php
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/includes/auth.php";
include __DIR__ . "/includes/schema_extend.php";
include __DIR__ . "/includes/meals.php";
include __DIR__ . "/includes/accounting.php";
include __DIR__ . "/includes/layout.php";
include __DIR__ . "/includes/boot_mess.php";

require_mess_page($has_mess);

$message = "";
$message_type = "";

$mess_stmt = $conn->prepare(
    "SELECT mess_id, mess_name, created_by, created_at"
    . (table_has_column($conn, "messes", "description") ? ", description" : "")
    . (table_has_column($conn, "messes", "address") ? ", address" : "")
    . " FROM messes WHERE mess_id = ? LIMIT 1"
);
$mess_stmt->bind_param("i", $current_mess_id);
$mess_stmt->execute();
$mess = $mess_stmt->get_result()->fetch_assoc();
$mess_stmt->close();

$description = $mess["description"] ?? "";
$address = $mess["address"] ?? "";

$manager_name = $user_name;
$mgr = $conn->prepare(
    "SELECT u.full_name FROM mess_members mem
     JOIN users u ON mem.user_id = u.user_id
     WHERE mem.mess_id = ? AND mem.role = 'manager'
     LIMIT 1"
);
$mgr->bind_param("i", $current_mess_id);
$mgr->execute();
$mgr_row = $mgr->get_result()->fetch_assoc();
$mgr->close();

if ($mgr_row) {
    $manager_name = $mgr_row["full_name"];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify()) {
        $message = "সেশন মেয়াদ শেষ হয়েছে। আবার চেষ্টা করুন।";
        $message_type = "err";
    } elseif (!$is_manager) {
        $message = "Only the mess manager can edit settings.";
        $message_type = "err";
    } else {
        $new_name = trim($_POST["mess_name"] ?? "");
        $new_description = trim($_POST["description"] ?? "");
        $new_address = trim($_POST["address"] ?? "");

        if ($new_name === "") {
            $message = "Mess name is required.";
            $message_type = "err";
        } else {
            $sql = "UPDATE messes SET mess_name = ?";
            $types = "s";
            $params = [$new_name];

            if (table_has_column($conn, "messes", "description")) {
                $sql .= ", description = ?";
                $types .= "s";
                $params[] = $new_description;
            }

            if (table_has_column($conn, "messes", "address")) {
                $sql .= ", address = ?";
                $types .= "s";
                $params[] = $new_address;
            }

            $sql .= " WHERE mess_id = ?";
            $types .= "i";
            $params[] = $current_mess_id;

            $update = $conn->prepare($sql);
            $update->bind_param($types, ...$params);

            if ($update->execute()) {
                $mess_name = $new_name;
                $description = $new_description;
                $address = $new_address;
                $mess["mess_name"] = $new_name;
                $message = "Settings saved.";
                $message_type = "ok";
            } else {
                $message = "Unable to save settings. Please try again.";
                $message_type = "err";
            }

            $update->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>মেস সেটিংস | Mess Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/app-pages.css">
</head>
<body>
<div class="app-container">
    <?php render_app_sidebar("settings", $user_name, $is_manager); ?>
    <div class="main-area">
        <?php render_app_topnav($user_name); ?>
        <div class="page-wrap">
            <h1>মেস সেটিংস</h1>
            <p class="page-lead">Mess ID is used to join. It never changes.</p>

            <?php if ($message !== ""): ?>
                <div class="alert <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="panel">
                <div class="form-group">
                    <label>Mess ID</label>
                    <input type="text" value="<?php echo (int) $current_mess_id; ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Created</label>
                    <input type="text" value="<?php echo htmlspecialchars($mess["created_at"] ?? ""); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Current manager</label>
                    <input type="text" value="<?php echo htmlspecialchars($manager_name); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <input type="text" value="Active" readonly>
                </div>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <div class="form-group">
                        <label>Mess name</label>
                        <input type="text" name="mess_name" value="<?php echo htmlspecialchars($mess["mess_name"] ?? $mess_name); ?>" <?php echo $is_manager ? "required" : "readonly"; ?>>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3" <?php echo $is_manager ? "" : "readonly"; ?>><?php echo htmlspecialchars($description); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($address); ?>" <?php echo $is_manager ? "" : "readonly"; ?>>
                    </div>
                    <?php if ($is_manager): ?>
                        <button class="btn btn-primary" type="submit">Save settings</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
