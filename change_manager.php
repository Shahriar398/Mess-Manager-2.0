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
require_manager_page($is_manager);

$message = "";
$message_type = "";
$success = isset($_GET["success"]);

$candidates = [];
$stmt = $conn->prepare(
    "SELECT u.user_id, u.full_name
     FROM mess_members mem
     JOIN users u ON mem.user_id = u.user_id
     WHERE mem.mess_id = ? AND mem.user_id <> ?"
    . (table_has_column($conn, "mess_members", "status") ? " AND mem.status = 'active'" : "")
    . " ORDER BY u.full_name ASC"
);
$stmt->bind_param("ii", $current_mess_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $candidates[] = $row;
}

$stmt->close();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify()) {
        $message = "সেশন মেয়াদ শেষ হয়েছে। আবার চেষ্টা করুন।";
        $message_type = "err";
    } else {
        $new_manager_id = (int) ($_POST["new_manager_id"] ?? 0);

        if ($new_manager_id <= 0 || $new_manager_id === $user_id) {
            $message = "Select a different active member.";
            $message_type = "err";
        } elseif (!user_is_active_member($conn, $new_manager_id, $current_mess_id)) {
            $message = "The selected user is not an active member of this mess.";
            $message_type = "err";
        } else {
            $conn->begin_transaction();

            try {
                $demote = $conn->prepare(
                    "UPDATE mess_members SET role = 'member'
                     WHERE mess_id = ? AND user_id = ? AND role = 'manager'"
                );
                $demote->bind_param("ii", $current_mess_id, $user_id);
                $demote->execute();

                if ($demote->affected_rows !== 1) {
                    throw new Exception("Could not update current manager.");
                }
                $demote->close();

                $promote_sql = "UPDATE mess_members SET role = 'manager'
                                WHERE mess_id = ? AND user_id = ?"
                    . (table_has_column($conn, "mess_members", "status") ? " AND status = 'active'" : "");
                $promote = $conn->prepare($promote_sql);
                $promote->bind_param("ii", $current_mess_id, $new_manager_id);
                $promote->execute();

                if ($promote->affected_rows !== 1) {
                    throw new Exception("Could not assign the new manager.");
                }
                $promote->close();

                $count = $conn->prepare(
                    "SELECT COUNT(*) AS c FROM mess_members
                     WHERE mess_id = ? AND role = 'manager'"
                    . (table_has_column($conn, "mess_members", "status") ? " AND status = 'active'" : "")
                );
                $count->bind_param("i", $current_mess_id);
                $count->execute();
                $managers = (int) $count->get_result()->fetch_assoc()["c"];
                $count->close();

                if ($managers !== 1) {
                    throw new Exception("Manager count invalid.");
                }

                $conn->commit();
                header("Location: dashboard.php?manager_changed=1");
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Unable to transfer manager. Please try again.";
                $message_type = "err";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ম্যানেজার পরিবর্তন | Mess Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/app-pages.css">
</head>
<body>
<div class="app-container">
    <?php render_app_sidebar("manager", $user_name, $is_manager); ?>
    <div class="main-area">
        <?php render_app_topnav($user_name); ?>
        <div class="page-wrap">
            <h1>ম্যানেজার পরিবর্তন</h1>
            <p class="page-lead">Current manager: <?php echo htmlspecialchars($user_name); ?></p>

            <?php if ($success): ?>
                <div class="alert ok">Manager transferred. You are now a regular member.</div>
            <?php endif; ?>
            <?php if ($message !== ""): ?>
                <div class="alert <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="panel">
                <?php if (empty($candidates)): ?>
                    <p class="empty-note">No other active members available.</p>
                <?php else: ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                        <div class="form-group">
                            <label>New manager</label>
                            <select name="new_manager_id" required>
                                <option value="">Select member</option>
                                <?php foreach ($candidates as $row): ?>
                                    <option value="<?php echo (int) $row["user_id"]; ?>">
                                        <?php echo htmlspecialchars($row["full_name"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="btn btn-primary" type="submit"
                            onclick="return confirm('Are you sure you want to transfer manager responsibility?');">
                            Transfer Manager
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
