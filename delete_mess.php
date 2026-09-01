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

/**
 * mess_id dependency tree (no DROP DATABASE / DROP TABLE / users delete):
 *
 * messes.mess_id
 *   ├── mess_members.mess_id
 *   ├── mess_months.mess_id
 *   ├── deposits.mess_id          (no FK — delete manually)
 *   ├── meal_expenses.mess_id     (no FK — delete manually)
 *   ├── meals.mess_id             (no FK — delete manually)
 *   └── other_expenses.mess_id    (no FK — delete manually)
 *         └── other_expense_members.other_expense_id
 *
 * users rows are never deleted.
 */

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify()) {
        $message = "সেশন মেয়াদ শেষ হয়েছে। আবার চেষ্টা করুন।";
        $message_type = "err";
    } else {
        $typed = trim($_POST["confirm_name"] ?? "");

        if ($typed !== $mess_name) {
            $message = "Confirmation name did not match the mess name.";
            $message_type = "err";
        } else {
            $conn->begin_transaction();

            try {
                $ids = [];
                $oe = $conn->prepare("SELECT other_expense_id FROM other_expenses WHERE mess_id = ?");
                $oe->bind_param("i", $current_mess_id);
                $oe->execute();
                $oe_result = $oe->get_result();

                while ($row = $oe_result->fetch_assoc()) {
                    $ids[] = (int) $row["other_expense_id"];
                }

                $oe->close();

                if (!empty($ids)) {
                    $placeholders = implode(",", array_fill(0, count($ids), "?"));
                    $types = str_repeat("i", count($ids));
                    $del_oem = $conn->prepare("DELETE FROM other_expense_members WHERE other_expense_id IN ($placeholders)");
                    $del_oem->bind_param($types, ...$ids);
                    $del_oem->execute();
                    $del_oem->close();
                }

                $tables = ["other_expenses", "meal_expenses", "deposits", "mess_members", "mess_months"];

                if (function_exists("meals_table_exists") && meals_table_exists($conn)) {
                    array_unshift($tables, "meals");
                }

                $allowed = [
                    "meals" => true,
                    "other_expenses" => true,
                    "meal_expenses" => true,
                    "deposits" => true,
                    "mess_members" => true,
                    "mess_months" => true,
                ];

                foreach ($tables as $table) {
                    if (!isset($allowed[$table])) {
                        throw new Exception("Unexpected table.");
                    }

                    $del = $conn->prepare("DELETE FROM `{$table}` WHERE mess_id = ?");
                    $del->bind_param("i", $current_mess_id);
                    $del->execute();
                    $del->close();
                }

                $del_mess = $conn->prepare("DELETE FROM messes WHERE mess_id = ?");
                $del_mess->bind_param("i", $current_mess_id);

                if (!$del_mess->execute() || $del_mess->affected_rows !== 1) {
                    throw new Exception("Mess row was not deleted.");
                }

                $del_mess->close();
                $conn->commit();

                unset($_SESSION["mess_id"]);
                header("Location: dashboard.php?mess_deleted=1");
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Unable to delete the mess. Please try again.";
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
    <title>মেস ডিলিট | Mess Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/app-pages.css">
</head>
<body>
<div class="app-container">
    <?php render_app_sidebar("delete", $user_name, $is_manager); ?>
    <div class="main-area">
        <?php render_app_topnav($user_name); ?>
        <div class="page-wrap">
            <h1>মেস ডিলিট</h1>

            <?php if ($message !== ""): ?>
                <div class="alert <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="danger-box">
                <p><strong>WARNING:</strong> Deleting this mess will permanently remove its accounting records, meals, expenses, deposits and member relationships.</p>
                <p>This action cannot be undone. User accounts will <strong>not</strong> be deleted.</p>
                <p>Type the mess name to confirm: <strong><?php echo htmlspecialchars($mess_name); ?></strong></p>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <div class="form-group">
                        <label>Mess name confirmation</label>
                        <input type="text" name="confirm_name" placeholder="<?php echo htmlspecialchars($mess_name); ?>" required>
                    </div>
                    <button class="btn btn-danger" type="submit">Permanently delete mess</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
