<?php
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/includes/auth.php";
include __DIR__ . "/includes/meals.php";

require_login();

$user_id = get_logged_in_user_id();
$user_name = get_logged_in_user_name();

$has_mess = false;
$mess_name = "";
$month_name = "";
$current_mess_id = 0;
$current_month_id = 0;
$is_manager = false;

$message = "";
$message_type = "";
$active_tab = ($_GET["tab"] ?? "daily") === "monthly" ? "monthly" : "daily";
$selected_date = $_GET["date"] ?? date("Y-m-d");

if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $selected_date)) {
    $selected_date = date("Y-m-d");
}

$active_mess = get_active_mess($conn, $user_id);

if ($active_mess) {
    $has_mess = true;
    $current_mess_id = (int) $active_mess["mess_id"];
    $current_month_id = (int) $active_mess["month_id"];
    $mess_name = $active_mess["mess_name"];
    $month_name = $active_mess["month_name"];
    $is_manager = user_is_manager($conn, $user_id, $current_mess_id);
}

$members = $has_mess ? get_mess_members($conn, $current_mess_id) : [];
$table_ready = $has_mess ? ensure_meals_table($conn) : false;

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION["csrf_token"];

function meals_csrf_valid(): bool
{
    $posted = $_POST["csrf_token"] ?? "";
    $session_token = $_SESSION["csrf_token"] ?? "";

    return $posted !== "" && $session_token !== "" && hash_equals($session_token, $posted);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $has_mess && $table_ready) {
    if (!meals_csrf_valid()) {
        $message = "সেশন মেয়াদ শেষ হয়েছে। আবার চেষ্টা করুন।";
        $message_type = "error";
    } elseif (isset($_POST["delete_meal"])) {
        $meal_id = (int) ($_POST["meal_id"] ?? 0);
        $record = get_meal_by_id($conn, $meal_id, $current_mess_id);

        if (!$record) {
            $message = "মিল রেকর্ড পাওয়া যায়নি।";
            $message_type = "error";
        } elseif ((int) $record["month_id"] !== $current_month_id) {
            $message = "শুধু চলতি মাসের মিল মুছা যাবে।";
            $message_type = "error";
        } elseif (!can_manage_member_meal($conn, $user_id, $current_mess_id, (int) $record["user_id"])) {
            $message = "এই মিল মুছতে আপনার অনুমতি নেই।";
            $message_type = "error";
        } elseif (delete_meal_record($conn, $meal_id, $current_mess_id)) {
            header("Location: meals.php?tab=daily&date=" . urlencode($record["meal_date"]) . "&deleted=1");
            exit();
        } else {
            $message = "মিল মুছতে সমস্যা হয়েছে। আবার চেষ্টা করুন।";
            $message_type = "error";
        }
    } elseif (isset($_POST["save_daily_meals"])) {
        $meal_date = $_POST["meal_date"] ?? $selected_date;

        if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $meal_date)) {
            $message = "সঠিক তারিখ দিন।";
            $message_type = "error";
        } elseif ($current_month_id <= 0) {
            $message = "সক্রিয় মাস পাওয়া যায়নি।";
            $message_type = "error";
        } else {
            $meal_rows = $_POST["meals"] ?? [];
            $failed = false;
            $saved = 0;

            foreach ($members as $member) {
                $member_id = (int) $member["user_id"];

                if (!isset($meal_rows[$member_id])) {
                    continue;
                }

                if (!user_belongs_to_mess($conn, $member_id, $current_mess_id)) {
                    $message = "মেম্বার এই মেসের সদস্য নন।";
                    $message_type = "error";
                    $failed = true;
                    break;
                }

                if (!can_manage_member_meal($conn, $user_id, $current_mess_id, $member_id)) {
                    continue;
                }

                $row = $meal_rows[$member_id];
                $breakfast = normalize_meal_count($row["breakfast"] ?? 0);
                $lunch = normalize_meal_count($row["lunch"] ?? 0);
                $dinner = normalize_meal_count($row["dinner"] ?? 0);

                if (!save_member_meal(
                    $conn,
                    $current_mess_id,
                    $current_month_id,
                    $member_id,
                    $meal_date,
                    $breakfast,
                    $lunch,
                    $dinner
                )) {
                    $failed = true;
                    $message = "মিল সংরক্ষণ করতে সমস্যা হয়েছে। আবার চেষ্টা করুন।";
                    $message_type = "error";
                    break;
                }

                $saved++;
            }

            if (!$failed) {
                if ($saved === 0) {
                    $message = "সংরক্ষণ করার মতো কোনো মিল নেই।";
                    $message_type = "error";
                } else {
                    header("Location: meals.php?tab=daily&date=" . urlencode($meal_date) . "&success=1");
                    exit();
                }
            }
        }
    }
}

$success = isset($_GET["success"]) && $_GET["success"] === "1";
$deleted = isset($_GET["deleted"]) && $_GET["deleted"] === "1";
$daily_meals = [];
$member_summary = [];
$date_summary = [];
$mess_total_meals = 0;
$user_total_meals = 0;
$day_breakfast = 0.0;
$day_lunch = 0.0;
$day_dinner = 0.0;
$day_total = 0.0;

if ($has_mess && $table_ready) {
    $daily_meals = get_meals_for_date($conn, $current_mess_id, $current_month_id, $selected_date);
    $member_summary = get_member_monthly_summary($conn, $current_mess_id, $current_month_id);
    $date_summary = get_date_monthly_summary($conn, $current_mess_id, $current_month_id);
    $mess_total_meals = get_mess_total_meals($conn, $current_mess_id, $current_month_id);
    $user_total_meals = get_member_total_meals($conn, $current_mess_id, $current_month_id, $user_id);

    foreach ($daily_meals as $row) {
        $day_breakfast += (float) $row["breakfast"];
        $day_lunch += (float) $row["lunch"];
        $day_dinner += (float) $row["dinner"];
    }

    $day_total = meal_day_total($day_breakfast, $day_lunch, $day_dinner);
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>মিল যুক্ত | Mess Manager</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/meals.css">
</head>
<body>

<?php if ($has_mess): ?>

<div class="app-container">

    <aside class="sidebar">
        <div class="sidebar-logo">
            <a href="home.php" class="logo-box">
                <img src="src/img/home_img/logo.png" alt="Mess Manager Logo">
            </a>
        </div>

        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fa-solid fa-border-all"></i> হোম পেজ</a></li>
            <li><a href="deposit.php"><i class="fa-solid fa-bangladeshi-taka-sign"></i> টাকা জমা</a></li>
            <li class="active"><a href="meals.php"><i class="fa-solid fa-bowl-rice"></i> মিল যুক্ত</a></li>
            <li><a href="add_expense.php"><i class="fa-solid fa-bag-shopping"></i> খরচ যুক্ত</a></li>
            <li><a href="#"><i class="fa-regular fa-calendar-check"></i> মাসের বিস্তারিত হিসাব</a></li>
            <li><a href="#"><i class="fa-regular fa-calendar"></i> সকল মাসের হিসাব</a></li>
            <li><a href="#"><i class="fa-solid fa-folder-plus"></i> নতুন মাস শুরু করুন</a></li>
            <li><a href="#"><i class="fa-solid fa-users"></i> মেস মেম্বার</a></li>
            <li><a href="#"><i class="fa-solid fa-user-tie"></i> ম্যানেজার পরিবর্তন</a></li>
            <li><a href="#"><i class="fa-solid fa-gear"></i> মেস সেটিংস</a></li>
            <li><a href="#"><i class="fa-solid fa-trash"></i> মেস ডিলিট</a></li>
        </ul>

        <div class="sidebar-footer">
            <div class="user-info">
                <img src="src/img/dashboard_img/profile.png" alt="Profile">
                <div><span class="role"><?php echo htmlspecialchars($user_name); ?></span></div>
            </div>
            <a href="logout.php" class="logout-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
        </div>
    </aside>

    <div class="main-area">
        <header class="top-nav">
            <div class="nav-icons">
                <a href="dashboard.php" class="nav-icon active"><i class="fa-solid fa-table-cells-large"></i></a>
                <a href="home.php" class="nav-icon"><i class="fa-solid fa-house"></i></a>
                <a href="#" class="nav-icon"><i class="fa-regular fa-circle-question"></i></a>
                <a href="#" class="nav-icon"><i class="fa-solid fa-bell"></i></a>
                <a href="profile.php" class="nav-icon"><i class="fa-regular fa-user"></i></a>
            </div>
            <a href="profile.php" class="nav-profile">
                <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                <img src="src/img/dashboard_img/profile.png" alt="Profile">
            </a>
        </header>

        <main class="meals-page">
            <div class="meals-container">

                <div class="meals-header">
                    <div>
                        <h2>মিল যুক্ত করুন</h2>
                        <p><?php echo htmlspecialchars($mess_name . " — " . $month_name); ?></p>
                    </div>
                    <div class="meals-summary-cards">
                        <div class="meals-summary-card">
                            <span>মেস মোট মিল</span>
                            <strong><?php echo number_format($mess_total_meals, 1); ?></strong>
                        </div>
                        <div class="meals-summary-card">
                            <span>আজকের তারিখের মোট</span>
                            <strong><?php echo number_format($day_total, 1); ?></strong>
                        </div>
                    </div>
                </div>

                <?php if (!$table_ready): ?>
                    <div class="meals-alert error">
                        Meals table not found. Please run <code>database/meals.sql</code> in phpMyAdmin first.
                    </div>
                <?php else: ?>

                    <?php if ($success): ?>
                        <div class="meals-alert success">মিল সফলভাবে সংরক্ষিত হয়েছে।</div>
                    <?php endif; ?>

                    <?php if ($deleted): ?>
                        <div class="meals-alert success">মিল মুছে ফেলা হয়েছে।</div>
                    <?php endif; ?>

                    <?php if ($message !== ""): ?>
                        <div class="meals-alert <?php echo htmlspecialchars($message_type); ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="meals-tabs">
                        <a href="meals.php?tab=daily&date=<?php echo urlencode($selected_date); ?>"
                           class="meals-tab <?php echo $active_tab === "daily" ? "active" : ""; ?>">
                            দৈনিক মিল
                        </a>
                        <a href="meals.php?tab=monthly"
                           class="meals-tab <?php echo $active_tab === "monthly" ? "active" : ""; ?>">
                            মাসিক হিসাব
                        </a>
                    </div>

                    <?php if ($active_tab === "daily"): ?>

                        <div class="meals-card">
                            <form method="get" class="meals-date-form">
                                <input type="hidden" name="tab" value="daily">
                                <label for="meal_date_picker">তারিখ নির্বাচন</label>
                                <div class="meals-date-row">
                                    <input type="date" id="meal_date_picker" name="date"
                                           value="<?php echo htmlspecialchars($selected_date); ?>" required>
                                    <button type="submit" class="meals-btn-secondary">দেখুন</button>
                                </div>
                            </form>

                            <form method="post" class="meals-daily-form" id="mealsSaveForm">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="meal_date" value="<?php echo htmlspecialchars($selected_date); ?>">

                                <div class="table-scroll">
                                    <table class="meals-table">
                                        <thead>
                                            <tr>
                                                <th>মেম্বার</th>
                                                <th>সকাল</th>
                                                <th>দুপুর</th>
                                                <th>রাত</th>
                                                <th>মোট</th>
                                                <th>অ্যাকশন</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($members)): ?>
                                                <tr>
                                                    <td colspan="6" class="empty-cell">কোনো মেম্বার নেই।</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($members as $member):
                                                    $mid = (int) $member["user_id"];
                                                    $existing = $daily_meals[$mid] ?? null;
                                                    $b = $existing ? (float) $existing["breakfast"] : 0;
                                                    $l = $existing ? (float) $existing["lunch"] : 0;
                                                    $d = $existing ? (float) $existing["dinner"] : 0;
                                                    $can_edit = can_manage_member_meal($conn, $user_id, $current_mess_id, $mid);
                                                    $row_total = meal_day_total($b, $l, $d);
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($member["full_name"]); ?></td>
                                                    <td>
                                                        <input type="number" step="0.5" min="0" max="9"
                                                            <?php if ($can_edit): ?>name="meals[<?php echo $mid; ?>][breakfast]"<?php endif; ?>
                                                            value="<?php echo htmlspecialchars((string) $b); ?>"
                                                            class="meal-count-input"
                                                            data-member="<?php echo $mid; ?>"
                                                            data-type="breakfast"
                                                            <?php echo $can_edit ? "" : "disabled"; ?>>
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.5" min="0" max="9"
                                                            <?php if ($can_edit): ?>name="meals[<?php echo $mid; ?>][lunch]"<?php endif; ?>
                                                            value="<?php echo htmlspecialchars((string) $l); ?>"
                                                            class="meal-count-input"
                                                            data-member="<?php echo $mid; ?>"
                                                            data-type="lunch"
                                                            <?php echo $can_edit ? "" : "disabled"; ?>>
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.5" min="0" max="9"
                                                            <?php if ($can_edit): ?>name="meals[<?php echo $mid; ?>][dinner]"<?php endif; ?>
                                                            value="<?php echo htmlspecialchars((string) $d); ?>"
                                                            class="meal-count-input"
                                                            data-member="<?php echo $mid; ?>"
                                                            data-type="dinner"
                                                            <?php echo $can_edit ? "" : "disabled"; ?>>
                                                    </td>
                                                    <td>
                                                        <span class="meal-row-total" id="total-<?php echo $mid; ?>">
                                                            <?php echo number_format($row_total, 1); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($existing && $can_edit): ?>
                                                            <button type="submit" name="delete_meal" value="1"
                                                                class="meals-btn-danger"
                                                                form="deleteMealForm-<?php echo (int) $existing["meal_id"]; ?>"
                                                                onclick="return confirm('এই দিনের মিল মুছে ফেলবেন?');">
                                                                মুছুন
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="muted-text">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <tr class="meals-day-total-row">
                                                    <td><strong>দিনের মোট</strong></td>
                                                    <td><strong id="day-breakfast-total"><?php echo number_format($day_breakfast, 1); ?></strong></td>
                                                    <td><strong id="day-lunch-total"><?php echo number_format($day_lunch, 1); ?></strong></td>
                                                    <td><strong id="day-dinner-total"><?php echo number_format($day_dinner, 1); ?></strong></td>
                                                    <td><strong id="day-grand-total"><?php echo number_format($day_total, 1); ?></strong></td>
                                                    <td></td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <?php if (!empty($members)): ?>
                                    <?php if (!$is_manager): ?>
                                        <p class="meals-help-text">আপনি শুধু নিজের মিল যুক্ত বা সম্পাদনা করতে পারবেন।</p>
                                    <?php endif; ?>
                                    <button type="submit" name="save_daily_meals" value="1" class="meals-btn-primary">
                                        <i class="fa-solid fa-floppy-disk"></i> মিল সংরক্ষণ করুন
                                    </button>
                                <?php endif; ?>
                            </form>

                            <?php foreach ($members as $member):
                                $mid = (int) $member["user_id"];
                                $existing = $daily_meals[$mid] ?? null;
                                if (!$existing || !can_manage_member_meal($conn, $user_id, $current_mess_id, $mid)) {
                                    continue;
                                }
                            ?>
                            <form method="post" id="deleteMealForm-<?php echo (int) $existing["meal_id"]; ?>" class="meals-hidden-form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="meal_id" value="<?php echo (int) $existing["meal_id"]; ?>">
                            </form>
                            <?php endforeach; ?>
                        </div>

                    <?php else: ?>

                        <div class="meals-grid">
                            <div class="meals-card">
                                <h3>মেম্বার অনুযায়ী মাসিক মিল</h3>
                                <div class="table-scroll">
                                    <table class="meals-table">
                                        <thead>
                                            <tr>
                                                <th>মেম্বার</th>
                                                <th>সকাল</th>
                                                <th>দুপুর</th>
                                                <th>রাত</th>
                                                <th>মোট</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($member_summary)): ?>
                                                <tr><td colspan="5" class="empty-cell">কোনো মিল রেকর্ড নেই।</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($member_summary as $row): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row["full_name"]); ?></td>
                                                    <td><?php echo number_format((float) $row["breakfast_total"], 1); ?></td>
                                                    <td><?php echo number_format((float) $row["lunch_total"], 1); ?></td>
                                                    <td><?php echo number_format((float) $row["dinner_total"], 1); ?></td>
                                                    <td><strong><?php echo number_format((float) $row["meal_total"], 1); ?></strong></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="meals-card">
                                <h3>তারিখ অনুযায়ী মাসিক মিল</h3>
                                <div class="table-scroll">
                                    <table class="meals-table">
                                        <thead>
                                            <tr>
                                                <th>তারিখ</th>
                                                <th>সকাল</th>
                                                <th>দুপুর</th>
                                                <th>রাত</th>
                                                <th>মোট</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($date_summary)): ?>
                                                <tr><td colspan="6" class="empty-cell">এই মাসে কোনো মিল নেই।</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($date_summary as $row): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars(date("d M Y", strtotime($row["meal_date"]))); ?></td>
                                                    <td><?php echo number_format((float) $row["breakfast_total"], 1); ?></td>
                                                    <td><?php echo number_format((float) $row["lunch_total"], 1); ?></td>
                                                    <td><?php echo number_format((float) $row["dinner_total"], 1); ?></td>
                                                    <td><strong><?php echo number_format((float) $row["meal_total"], 1); ?></strong></td>
                                                    <td>
                                                        <a class="meals-link"
                                                           href="meals.php?tab=daily&date=<?php echo urlencode($row["meal_date"]); ?>">
                                                            সম্পাদনা
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

                    <?php endif; ?>

                <?php endif; ?>

            </div>
        </main>
    </div>
</div>

<?php else: ?>

<div class="no-mess-container">
    <h2>আপনি এখনো কোনো মেসে যুক্ত হননি।</h2>
    <br>
    <a href="dashboard.php">Dashboard এ ফিরে যান</a>
</div>

<?php endif; ?>

<script src="js/meals.js"></script>
</body>
</html>
