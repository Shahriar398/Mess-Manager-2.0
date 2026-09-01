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

$months = get_mess_months($conn, $current_mess_id);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>সকল মাস | Mess Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/app-pages.css">
</head>
<body>
<div class="app-container">
    <?php render_app_sidebar("all_months", $user_name, $is_manager); ?>
    <div class="main-area">
        <?php render_app_topnav($user_name); ?>
        <div class="page-wrap">
            <h1>সকল মাসের হিসাব</h1>
            <p class="page-lead"><?php echo htmlspecialchars($mess_name); ?> — each row uses that month's month_id only.</p>

            <div class="panel">
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Date range</th>
                                <th>Meals</th>
                                <th>Expense</th>
                                <th>Deposit</th>
                                <th>Meal Rate</th>
                                <th>Total Cost</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($months)): ?>
                                <tr><td colspan="9" class="empty-note">No previous months available.</td></tr>
                            <?php else: ?>
                                <?php foreach ($months as $month):
                                    $calc = calculate_month_accounts($conn, $current_mess_id, (int) $month["month_id"]);
                                    $start = substr((string) $month["started_at"], 0, 10);
                                    $end = !empty($month["closed_at"]) ? substr((string) $month["closed_at"], 0, 10) : "Running";
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($month["month_name"]); ?></td>
                                        <td><?php echo htmlspecialchars($start . " → " . $end); ?></td>
                                        <td><?php echo number_format((float) ($calc["total_meals"] ?? 0), 1); ?></td>
                                        <td><?php echo money_fmt($calc["total_expenses"] ?? 0); ?> ৳</td>
                                        <td><?php echo money_fmt($calc["total_deposits"] ?? 0); ?> ৳</td>
                                        <td><?php echo money_fmt($calc["meal_rate"] ?? 0); ?> ৳</td>
                                        <td><?php echo money_fmt($calc["total_cost"] ?? 0); ?> ৳</td>
                                        <td>
                                            <span class="badge <?php echo $month["status"] === "active" ? "badge-active" : "badge-closed"; ?>">
                                                <?php echo htmlspecialchars($month["status"]); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a class="btn btn-light" href="month_details.php?month_id=<?php echo (int) $month["month_id"]; ?>">
                                                View details
                                            </a>
                                        </td>
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
