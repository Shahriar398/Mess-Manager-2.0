<?php
session_start();
include("db.php");

// Login check
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$user_name = isset($_SESSION["name"]) ? $_SESSION["name"] : "User";

// ==========================================
// ACTIVE MESS বের করা
// ==========================================
$has_mess = false;
$mess_name = "";
$month_name = "";
$current_mess_id = "";
$current_month_id = 0;

$query = "SELECT m.mess_id, m.mess_name, mm.month_name, mm.month_id
          FROM mess_members mem
          JOIN messes m ON mem.mess_id = m.mess_id
          JOIN mess_months mm ON m.mess_id = mm.mess_id
          WHERE mem.user_id = ?
          AND mm.status = 'active'
          LIMIT 1";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $has_mess = true;
    $current_mess_id = $row["mess_id"];
    $mess_name = $row["mess_name"];
    $month_name = $row["month_name"];
    $current_month_id = (int) ($row["month_id"] ?? 0);
}


// ==========================================
// এই মেসের সকল Member বের করা
// ==========================================
$members = [];

if ($has_mess) {

    $member_query = "SELECT u.user_id, u.full_name
                     FROM mess_members mem
                     JOIN users u ON mem.user_id = u.user_id
                     WHERE mem.mess_id = ?
                     ORDER BY u.full_name ASC";

    $member_stmt = $conn->prepare($member_query);
    $member_stmt->bind_param("i", $current_mess_id);
    $member_stmt->execute();
    $member_result = $member_stmt->get_result();

    while ($member = $member_result->fetch_assoc()) {
        $members[] = $member;
    }
}


// ==========================================
// Deposit Save
// ==========================================
$message = "";
$message_type = "";
$success = isset($_GET["success"]) && $_GET["success"] == "1";

if ($_SERVER["REQUEST_METHOD"] == "POST" && $has_mess) {

    $deposit_date = $_POST["deposit_date"] ?? "";
    $amount = $_POST["amount"] ?? "";
    $note = trim($_POST["note"] ?? "");
    $deposit_user_id = $_POST["deposit_user_id"] ?? "";

    // Validation
    if (
        empty($deposit_date) ||
        empty($amount) ||
        empty($deposit_user_id)
    ) {

        $message = "সব প্রয়োজনীয় তথ্য পূরণ করুন।";
        $message_type = "error";

    } elseif (!is_numeric($amount) || $amount <= 0) {

        $message = "সঠিক টাকার পরিমাণ দিন।";
        $message_type = "error";

    } else {

        // Security:
        // নির্বাচিত member সত্যিই এই mess-এর member কিনা check
        $check_member = $conn->prepare(
            "SELECT member_id
             FROM mess_members
             WHERE mess_id = ? AND user_id = ?"
        );

        $check_member->bind_param(
            "ii",
            $current_mess_id,
            $deposit_user_id
        );

        $check_member->execute();
        $check_result = $check_member->get_result();

        if ($check_result->num_rows === 0) {

            $message = "নির্বাচিত মেম্বার এই মেসের সদস্য নন।";
            $message_type = "error";

        } else {

            $col = $conn->query("SHOW COLUMNS FROM deposits LIKE 'month_id'");
            $has_month_col = $col && $col->num_rows > 0;

            if ($has_month_col && $current_month_id > 0) {
                $insert = $conn->prepare(
                    "INSERT INTO deposits
                    (mess_id, month_id, user_id, amount, deposit_date, note)
                    VALUES (?, ?, ?, ?, ?, ?)"
                );
                $insert->bind_param(
                    "iiidss",
                    $current_mess_id,
                    $current_month_id,
                    $deposit_user_id,
                    $amount,
                    $deposit_date,
                    $note
                );
            } else {
                $insert = $conn->prepare(
                    "INSERT INTO deposits
                    (mess_id, user_id, amount, deposit_date, note)
                    VALUES (?, ?, ?, ?, ?)"
                );
                $insert->bind_param(
                    "iidss",
                    $current_mess_id,
                    $deposit_user_id,
                    $amount,
                    $deposit_date,
                    $note
                );
            }

                if ($insert->execute()) {

                    header("Location: deposit.php?success=1");
                    exit();

                } else {

                $message = "টাকা জমা যুক্ত করতে সমস্যা হয়েছে।";
                $message_type = "error";

            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="bn">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>টাকা জমা | Mess Manager</title>

    <!-- Google Fonts -->
    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&family=Poppins:wght@400;500;600&display=swap"
          rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- তোমার existing dashboard CSS -->
    <link rel="stylesheet"
          href="css/dashboard.css">

    <link rel="stylesheet" href="css/deposit.css">

</head>

<body>

<?php if ($has_mess): ?>

<div class="app-container">

    <!-- ========================================= -->
    <!-- SIDEBAR -->
    <!-- ========================================= -->
    <aside class="sidebar">

        <div class="sidebar-logo">

            <a href="home.php"
               class="logo-box">

                <img src="src/img/home_img/logo.png"
                     alt="Mess Manager Logo">

            </a>

        </div>


        <ul class="sidebar-menu">

            <li>
                <a href="dashboard.php">
                    <i class="fa-solid fa-border-all"></i>
                    হোম পেজ
                </a>
            </li>

            <li class="active">
                <a href="deposit.php">
                    <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                    টাকা জমা
                </a>
            </li>

            <li>
                <a href="meals.php">
                    <i class="fa-solid fa-bowl-rice"></i>
                    মিল যুক্ত
                </a>
            </li>

            <li>
                <a href="add_expense.php">
                    <i class="fa-solid fa-bag-shopping"></i>
                    খরচ যুক্ত
                </a>
            </li>

            <li>
                <a href="#">
                    <i class="fa-regular fa-calendar-check"></i>
                    মাসের বিস্তারিত হিসাব
                </a>
            </li>

            <li>
                <a href="#">
                    <i class="fa-regular fa-calendar"></i>
                    সকল মাসের হিসাব
                </a>
            </li>

            <li>
                <a href="#">
                    <i class="fa-solid fa-folder-plus"></i>
                    নতুন মাস শুরু করুন
                </a>
            </li>

            <li>
                <a href="#">
                    <i class="fa-solid fa-users"></i>
                    মেস মেম্বার
                </a>
            </li>

            <li>
                <a href="#">
                    <i class="fa-solid fa-user-tie"></i>
                    ম্যানেজার পরিবর্তন
                </a>
            </li>

            <li>
                <a href="#">
                    <i class="fa-solid fa-gear"></i>
                    মেস সেটিংস
                </a>
            </li>

            <li>
                <a href="#">
                    <i class="fa-solid fa-trash"></i>
                    মেস ডিলিট
                </a>
            </li>

        </ul>


        <div class="sidebar-footer">

            <div class="user-info">

                <img src="src/img/dashboard_img/profile.png"
                     alt="Profile">

                <div>
                    <span class="role">
                        <?php echo htmlspecialchars($user_name); ?>
                    </span>
                </div>

            </div>

            <a 
                href="logout.php" class="logout-btn">

                <i class="fa-solid fa-arrow-right-from-bracket"></i>

            </a>

        </div>

    </aside>


    <!-- ========================================= -->
    <!-- MAIN AREA -->
    <!-- ========================================= -->
    <div class="main-area">

        <!-- TOP NAV -->
        <header class="top-nav">

            <div class="nav-icons">

                <a href="dashboard.php"
                   class="nav-icon active">

                    <i class="fa-solid fa-table-cells-large"></i>

                </a>

                <a href="home.php"
                   class="nav-icon">

                    <i class="fa-solid fa-house"></i>

                </a>

                <a href="#"
                   class="nav-icon">

                    <i class="fa-regular fa-circle-question"></i>

                </a>

                <a href="#"
                   class="nav-icon">

                    <i class="fa-solid fa-bell"></i>

                </a>

                <a href="#"
                   class="nav-icon">

                    <i class="fa-regular fa-user"></i>

                </a>

            </div>


            <div class="nav-profile">

                <span class="user-name">
                    <?php echo htmlspecialchars($user_name); ?>
                </span>

                <img src="src/img/dashboard_img/profile.png"
                     alt="Profile">

            </div>

        </header>


        <!-- ========================================= -->
        <!-- DEPOSIT CONTENT -->
        <!-- ========================================= -->
        <main class="deposit-page">

            <div class="deposit-container">

                <div class="deposit-header">
                    <p>
                        যখন মেম্বার মেসে টাকা জমা দেবে,
                        এই অপশন থেকে যুক্ত করুন
                    </p>

                </div>

                    <?php if ($success): ?>

                    <div class="deposit-success-overlay">

                        <div class="deposit-success-mini-card">

                            <div class="success-icon">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>

                            <h2>টাকা জমা করা হয়েছে।</h2>

                            <p>
                                হিসাব চেক করুন মাসের বিস্তারিত হিসাব থেকে
                            </p>

                            <div class="success-buttons">

                                <a href="month_details.php" class="btn-month-details">
                                    মাসের বিস্তারিত হিসাব
                                </a>

                                <a href="deposit.php" class="btn-more-deposit">
                                    আরো টাকা জমা করুন
                                </a>

                                <a href="dashboard.php" class="btn-dashboard">
                                    ড্যাশবোর্ডে যান
                                </a>

                            </div>

                        </div>

                    </div>

                    <?php endif; ?>

                <div class="deposit-form-card">

                    <?php if ($message != ""): ?>

                        <div class="deposit-message <?php echo $message_type; ?>">

                            <?php echo htmlspecialchars($message); ?>

                        </div>

                    <?php endif; ?>


                    <form method="POST">


                        <!-- টাকা জমাদানের তারিখ -->
                        <div class="deposit-form-group">

                            <label>
                                টাকা জমাদানের তারিখ
                            </label>

                            <input
                                type="date"
                                name="deposit_date"
                                value="<?php echo date('Y-m-d'); ?>"
                                required>

                        </div>


                        <!-- টাকার পরিমাণ -->
                        <div class="deposit-form-group">

                            <label>
                                টাকার পরিমাণ
                            </label>

                            <input
                                type="number"
                                name="amount"
                                placeholder="যেমন: 500"
                                step="0.01"
                                min="0.01"
                                required>

                        </div>


                        <!-- নোট -->
                        <div class="deposit-form-group">

                            <label>
                                টাকা জমা সংক্রান্ত নোট
                                (বাধ্যতামূলক নয়)
                            </label>

                            <textarea
                                name="note"
                                placeholder="প্রয়োজনে এখানে নোট লিখুন"></textarea>

                        </div>


                        <!-- Member Select -->
                        <div class="deposit-form-group">

                            <label>
                                টাকা জমা দানকারী মেম্বার
                            </label>

                            <div class="member-select-wrapper">

                                <select
                                    name="deposit_user_id"
                                    required>

                                    <option value="">
                                        মেম্বার নির্বাচন করুন
                                    </option>

                                    <?php foreach ($members as $member): ?>

                                        <option
                                            value="<?php echo $member['user_id']; ?>"

                                            <?php
                                            if (
                                                $member['user_id'] == $user_id
                                            ) {
                                                echo "selected";
                                            }
                                            ?>

                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $member['full_name']
                                            );
                                            ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                                <i class="fa-solid fa-chevron-down"></i>

                            </div>

                        </div>


                        <!-- Submit -->
                        <button
                            type="submit"
                            class="deposit-button">

                            <i class="fa-solid fa-bangladeshi-taka-sign"></i>

                            টাকা জমা করুন

                        </button>

                      </form>

                </div>

            </div>

        </main>

    </div>

</div>

<?php else: ?>

<div style="text-align:center; padding:50px;">

    <h2>আপনি এখনো কোনো মেসে যুক্ত হননি।</h2>

    <br>

    <a href="dashboard.php">
        Dashboard এ ফিরে যান
    </a>

</div>

<?php endif; ?>

</body>
</html>