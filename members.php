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

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["remove_member"])) {
    if (!csrf_verify()) {
        $message = "সেশন মেয়াদ শেষ হয়েছে। আবার চেষ্টা করুন।";
        $message_type = "err";
    } elseif (!$is_manager) {
        $message = "Only the mess manager can remove a member.";
        $message_type = "err";
    } else {
        $target_id = (int) ($_POST["user_id"] ?? 0);

        if ($target_id <= 0 || !user_belongs_to_mess($conn, $target_id, $current_mess_id)) {
            $message = "Member does not belong to this mess.";
            $message_type = "err";
        } elseif ($target_id === $user_id) {
            $message = "You cannot remove yourself. Transfer manager first if needed.";
            $message_type = "err";
        } elseif (user_is_manager($conn, $target_id, $current_mess_id)) {
            $message = "Transfer the manager role before removing this member.";
            $message_type = "err";
        } else {
            if (table_has_column($conn, "mess_members", "status")) {
                $stmt = $conn->prepare(
                    "UPDATE mess_members
                     SET status = 'left', left_at = NOW()
                     WHERE mess_id = ? AND user_id = ? AND role = 'member'"
                );
                $stmt->bind_param("ii", $current_mess_id, $target_id);
            } else {
                $stmt = $conn->prepare(
                    "DELETE FROM mess_members
                     WHERE mess_id = ? AND user_id = ? AND role = 'member'"
                );
                $stmt->bind_param("ii", $current_mess_id, $target_id);
            }

            if ($stmt && $stmt->execute() && $stmt->affected_rows > 0) {
                $message = "Member removed from the mess. Historical records were kept.";
                $message_type = "ok";
            } else {
                $message = "Unable to remove member. Please try again.";
                $message_type = "err";
            }

            if ($stmt) {
                $stmt->close();
            }
        }
    }
}

$has_status = table_has_column($conn, "mess_members", "status");
$list_sql = "SELECT u.user_id, u.full_name, u.email, u.phone, mem.role, mem.joined_at"
    . ($has_status ? ", mem.status, mem.left_at" : "")
    . " FROM mess_members mem
        JOIN users u ON mem.user_id = u.user_id
        WHERE mem.mess_id = ?
        ORDER BY mem.role ASC, u.full_name ASC";

$members = [];
$stmt = $conn->prepare($list_sql);
$stmt->bind_param("i", $current_mess_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>মেস মেম্বার | Mess Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/app-pages.css">
</head>
<body>
<div class="app-container">
    <?php render_app_sidebar("members", $user_name, $is_manager); ?>
    <div class="main-area">
        <?php render_app_topnav($user_name); ?>
        <div class="page-wrap">
            <h1>মেস মেম্বার</h1>
            <p class="page-lead"><?php echo htmlspecialchars($mess_name); ?></p>

            <?php if ($message !== ""): ?>
                <div class="alert <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="panel">
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Status</th>
                                <?php if ($is_manager): ?><th>Actions</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($members)): ?>
                                <tr><td colspan="7" class="empty-note">No members found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($members as $member):
                                    $status = $has_status ? ($member["status"] ?? "active") : "active";
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($member["full_name"]); ?></td>
                                        <td><?php echo htmlspecialchars($member["email"]); ?></td>
                                        <td><?php echo htmlspecialchars($member["phone"] ?: "—"); ?></td>
                                        <td>
                                            <span class="badge <?php echo $member["role"] === "manager" ? "badge-manager" : "badge-closed"; ?>">
                                                <?php echo htmlspecialchars($member["role"]); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars(substr((string) $member["joined_at"], 0, 10)); ?></td>
                                        <td>
                                            <span class="badge <?php echo $status === "active" ? "badge-active" : "badge-left"; ?>">
                                                <?php echo htmlspecialchars($status); ?>
                                            </span>
                                        </td>
                                        <?php if ($is_manager): ?>
                                            <td>
                                                <?php if ($status === "active" && $member["role"] !== "manager" && (int) $member["user_id"] !== $user_id): ?>
                                                    <form method="post" onsubmit="return confirm('Remove this member from the mess? Their account and old records will remain.');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                                        <input type="hidden" name="user_id" value="<?php echo (int) $member["user_id"]; ?>">
                                                        <button class="btn btn-danger" type="submit" name="remove_member" value="1">Remove</button>
                                                    </form>
                                                <?php else: ?>
                                                    —
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
