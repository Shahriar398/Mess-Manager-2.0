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

$month_id = (int) ($_GET["month_id"] ?? 0);
$month_row = $month_id > 0 ? get_month_row($conn, $current_mess_id, $month_id) : null;

if (!$month_row) {
    header("Location: all_months.php");
    exit();
}

$accounts = calculate_month_accounts($conn, $current_mess_id, $month_id);
$start = substr((string) $month_row["started_at"], 0, 10);
$end = !empty($month_row["closed_at"]) ? substr((string) $month_row["closed_at"], 0, 10) : "Running";
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($month_row["month_name"]); ?> | Mess Manager</title>
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
            <h1><?php echo htmlspecialchars($month_row["month_name"]); ?></h1>
            <p class="page-lead">
                <?php echo htmlspecialchars($start . " → " . $end); ?>
                · month_id=<?php echo (int) $month_id; ?>
                · <?php echo htmlspecialchars($month_row["status"]); ?>
            </p>
            <p><a class="btn btn-light" href="all_months.php">Back to all months</a></p>
            <br>

            <?php if (!$accounts): ?>
                <div class="alert err">Unable to load this month.</div>
            <?php else: ?>
                <div class="stat-grid">
                    <div class="stat-card"><span>Total meals</span><strong><?php echo number_format($accounts["total_meals"], 1); ?></strong></div>
                    <div class="stat-card"><span>Meal rate</span><strong><?php echo money_fmt($accounts["meal_rate"]); ?> ৳</strong></div>
                    <div class="stat-card"><span>Meal cost</span><strong><?php echo money_fmt($accounts["total_meal_cost"]); ?> ৳</strong></div>
                    <div class="stat-card"><span>Deposits</span><strong><?php echo money_fmt($accounts["total_deposits"]); ?> ৳</strong></div>
                    <div class="stat-card"><span>Shared</span><strong><?php echo money_fmt($accounts["shared_cost"]); ?> ৳</strong></div>
                    <div class="stat-card"><span>Individual</span><strong><?php echo money_fmt($accounts["individual_cost"]); ?> ৳</strong></div>
                    <div class="stat-card"><span>Total cost</span><strong><?php echo money_fmt($accounts["total_cost"]); ?> ৳</strong></div>
                    <div class="stat-card"><span>Mess balance</span><strong><?php echo money_fmt($accounts["mess_balance"]); ?> ৳</strong></div>
                </div>

                <div class="panel">
                    <h3>Member balances</h3>
                    <div class="table-scroll">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Meals</th>
                                    <th>Deposit</th>
                                    <th>Meal Cost</th>
                                    <th>Shared</th>
                                    <th>Other</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($accounts["members"])): ?>
                                    <tr><td colspan="7" class="empty-note">No records for this month.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($accounts["members"] as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row["full_name"]); ?></td>
                                            <td><?php echo number_format((float) $row["meals"], 1); ?></td>
                                            <td><?php echo money_fmt($row["deposit"]); ?> ৳</td>
                                            <td><?php echo money_fmt($row["meal_cost"]); ?> ৳</td>
                                            <td><?php echo money_fmt($row["shared"]); ?> ৳</td>
                                            <td><?php echo money_fmt($row["individual"]); ?> ৳</td>
                                            <td class="<?php echo $row["balance"] >= 0 ? "balance-pos" : "balance-neg"; ?>">
                                                <?php echo money_fmt($row["balance"]); ?> ৳
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
