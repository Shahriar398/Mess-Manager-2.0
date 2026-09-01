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

$accounts = calculate_month_accounts($conn, $current_mess_id, $current_month_id);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>মাসের হিসাব | Mess Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/app-pages.css">
</head>
<body>
<div class="app-container">
    <?php render_app_sidebar("this_month", $user_name, $is_manager); ?>
    <div class="main-area">
        <?php render_app_topnav($user_name); ?>
        <div class="page-wrap">
            <h1>চলতি মাসের হিসাব</h1>
            <p class="page-lead">
                <?php echo htmlspecialchars($mess_name . " — " . ($accounts["month"]["month_name"] ?? $month_name)); ?>
                · Active month ID: <?php echo (int) $current_month_id; ?>
            </p>

            <?php if (!$accounts): ?>
                <div class="alert err">Unable to load this month's accounts.</div>
            <?php else: ?>
                <div class="stat-grid">
                    <div class="stat-card"><span>মোট সকাল</span><strong><?php echo number_format($accounts["breakfast"], 1); ?></strong></div>
                    <div class="stat-card"><span>মোট দুপুর</span><strong><?php echo number_format($accounts["lunch"], 1); ?></strong></div>
                    <div class="stat-card"><span>মোট রাত</span><strong><?php echo number_format($accounts["dinner"], 1); ?></strong></div>
                    <div class="stat-card"><span>মোট মিল</span><strong><?php echo number_format($accounts["total_meals"], 1); ?></strong></div>
                    <div class="stat-card"><span>মোট মিল খরচ</span><strong><?php echo money_fmt($accounts["total_meal_cost"]); ?> ৳</strong></div>
                    <div class="stat-card"><span>মিল রেট</span><strong><?php echo $accounts["total_meals"] > 0 ? money_fmt($accounts["meal_rate"]) . " ৳" : "0.00 ৳"; ?></strong></div>
                    <div class="stat-card"><span>মোট খরচ</span><strong><?php echo money_fmt($accounts["total_expenses"]); ?> ৳</strong></div>
                    <div class="stat-card"><span>মোট জমা</span><strong><?php echo money_fmt($accounts["total_deposits"]); ?> ৳</strong></div>
                    <div class="stat-card"><span>যৌথ খরচ</span><strong><?php echo money_fmt($accounts["shared_cost"]); ?> ৳</strong></div>
                    <div class="stat-card"><span>ব্যক্তিগত খরচ</span><strong><?php echo money_fmt($accounts["individual_cost"]); ?> ৳</strong></div>
                    <div class="stat-card"><span>সর্বমোট খরচ</span><strong><?php echo money_fmt($accounts["total_cost"]); ?> ৳</strong></div>
                    <div class="stat-card"><span>মেস ব্যালেন্স</span><strong><?php echo money_fmt($accounts["mess_balance"]); ?> ৳</strong></div>
                </div>

                <div class="panel">
                    <h3>মেম্বার সামারি</h3>
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
                                    <th>Total Cost</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($accounts["members"])): ?>
                                    <tr><td colspan="8" class="empty-note">No members found for this month.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($accounts["members"] as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row["full_name"]); ?></td>
                                            <td><?php echo number_format((float) $row["meals"], 1); ?></td>
                                            <td><?php echo money_fmt($row["deposit"]); ?> ৳</td>
                                            <td><?php echo money_fmt($row["meal_cost"]); ?> ৳</td>
                                            <td><?php echo money_fmt($row["shared"]); ?> ৳</td>
                                            <td><?php echo money_fmt($row["individual"]); ?> ৳</td>
                                            <td><?php echo money_fmt($row["total_cost"]); ?> ৳</td>
                                            <td class="<?php echo $row["balance"] >= 0 ? "balance-pos" : "balance-neg"; ?>">
                                                <?php echo ($row["balance"] >= 0 ? "+" : "") . money_fmt($row["balance"]); ?> ৳
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($accounts["total_meals"] <= 0): ?>
                        <p class="page-lead" style="margin-top:12px">No meals recorded yet. Meal rate is 0.00.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
