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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify()) {
        $message = "সেশন মেয়াদ শেষ হয়েছে। আবার চেষ্টা করুন।";
        $message_type = "err";
    } else {
        $new_month_name = trim($_POST["month_name"] ?? "");

        if ($new_month_name === "") {
            $message = "নতুন মাসের নাম দিন।";
            $message_type = "err";
        } else {
            $conn->begin_transaction();

            try {
                $lock = $conn->prepare(
                    "SELECT month_id, month_name FROM mess_months
                     WHERE mess_id = ? AND status = 'active'
                     FOR UPDATE"
                );
                $lock->bind_param("i", $current_mess_id);
                $lock->execute();
                $lock_result = $lock->get_result();
                $active_rows = [];

                while ($row = $lock_result->fetch_assoc()) {
                    $active_rows[] = $row;
                }

                $lock->close();

                if (count($active_rows) === 0) {
                    throw new Exception("No active month to close.");
                }

                if (count($active_rows) > 1) {
                    throw new Exception("Multiple active months found. Fix data before opening a new month.");
                }

                $old_id = (int) $active_rows[0]["month_id"];

                $close = $conn->prepare(
                    "UPDATE mess_months
                     SET status = 'closed', closed_at = NOW()
                     WHERE month_id = ? AND mess_id = ? AND status = 'active'"
                );
                $close->bind_param("ii", $old_id, $current_mess_id);
                $close->execute();

                if ($close->affected_rows !== 1) {
                    throw new Exception("Could not close the current month.");
                }
                $close->close();

                $insert = $conn->prepare(
                    "INSERT INTO mess_months (mess_id, month_name, status)
                     VALUES (?, ?, 'active')"
                );
                $insert->bind_param("is", $current_mess_id, $new_month_name);

                if (!$insert->execute()) {
                    throw new Exception("Could not create the new month.");
                }
                $insert->close();

                $still_active = $conn->prepare(
                    "SELECT COUNT(*) AS c FROM mess_months WHERE mess_id = ? AND status = 'active'"
                );
                $still_active->bind_param("i", $current_mess_id);
                $still_active->execute();
                $count = (int) $still_active->get_result()->fetch_assoc()["c"];
                $still_active->close();

                if ($count !== 1) {
                    throw new Exception("Active month count is invalid.");
                }

                $conn->commit();
                header("Location: open_new_month.php?success=1");
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Unable to open a new month. Please try again.";
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
    <title>নতুন মাস | Mess Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/app-pages.css">
</head>
<body>
<div class="app-container">
    <?php render_app_sidebar("open_month", $user_name, $is_manager); ?>
    <div class="main-area">
        <?php render_app_topnav($user_name); ?>
        <div class="page-wrap">
            <h1>নতুন মাস শুরু করুন</h1>
            <p class="page-lead">Current month will be closed. Old meals, expenses and deposits stay in the closed month.</p>

            <?php if ($success): ?>
                <div class="alert ok">New month opened. Previous month is closed and still available under All Months.</div>
            <?php endif; ?>
            <?php if ($message !== ""): ?>
                <div class="alert <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="panel">
                <p><strong>Current active month:</strong> <?php echo htmlspecialchars($month_name); ?> (ID <?php echo (int) $current_month_id; ?>)</p>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <div class="form-group">
                        <label>নতুন মাসের নাম</label>
                        <input type="text" name="month_name" placeholder="e.g. September 2026" required>
                    </div>
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Close the current month and start a new one? Old data will not be deleted.');">
                        Open new month
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
