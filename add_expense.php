<?php
session_start();
include __DIR__ . "/db.php";
include __DIR__ . "/includes/auth.php";

require_login();

$user_id = get_logged_in_user_id();
$user_name = get_logged_in_user_name();

$has_mess = false;
$mess_name = "";
$month_name = "";
$current_mess_id = "";
$expense_error = "";

$active_mess = get_active_mess($conn, $user_id);

if ($active_mess) {
    $has_mess = true;
    $current_mess_id = (int) $active_mess["mess_id"];
    $mess_name = $active_mess["mess_name"];
    $month_name = $active_mess["month_name"];
}

if (isset($_POST["meal_expense_submit"]) && $has_mess) {
    $expense_date = $_POST["meal_expense_date"] ?? "";
    $amount = $_POST["meal_expense_amount"] ?? "";
    $market_list = trim($_POST["market_list"] ?? "");
    $market_user_id = (int) ($_POST["market_user_id"] ?? 0);

    if (
        empty($expense_date) ||
        !is_numeric($amount) ||
        $amount <= 0 ||
        $market_user_id <= 0
    ) {
        $expense_error = "সব প্রয়োজনীয় তথ্য সঠিকভাবে পূরণ করুন।";
    } elseif (!user_belongs_to_mess($conn, $market_user_id, $current_mess_id)) {
        $expense_error = "নির্বাচিত মেম্বার এই মেসের সদস্য নন।";
    } else {
        $month_id = get_active_month_id($conn, $current_mess_id);

        if ($month_id) {
            $insert_stmt = $conn->prepare(
                "INSERT INTO meal_expenses
                (mess_id, month_id, expense_date, amount, market_list, market_user_id)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $insert_stmt->bind_param(
                "iisdsi",
                $current_mess_id,
                $month_id,
                $expense_date,
                $amount,
                $market_list,
                $market_user_id
            );

            if ($insert_stmt->execute()) {
                header("Location: add_expense.php?success=1");
                exit();
            }

            $expense_error = "খরচ যুক্ত করতে সমস্যা হয়েছে। আবার চেষ্টা করুন।";
            $insert_stmt->close();
        } else {
            $expense_error = "সক্রিয় মাস পাওয়া যায়নি।";
        }
    }
}

$members = $has_mess ? get_mess_members($conn, $current_mess_id) : [];
$expense_success = isset($_GET["success"]) && $_GET["success"] === "1";
?>

<!DOCTYPE html>

<html lang="bn">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">


<title>খরচ যুক্ত | Mess Manager</title>


<!-- GOOGLE FONTS -->
<link
    rel="preconnect"
    href="https://fonts.googleapis.com">


<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin>


<link
    href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&family=Poppins:wght@400;500;600&display=swap"
    rel="stylesheet">


<!-- FONT AWESOME -->
<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


<!-- DASHBOARD CSS -->
<link
    rel="stylesheet"
    href="css/dashboard.css">


<!-- EXPENSE CSS -->
<link
    rel="stylesheet"
    href="css/expense.css">

</head>

<body>

<?php if ($has_mess): ?>

<div class="app-container">

<!-- ========================================= -->
<!-- SIDEBAR -->
<!-- ========================================= -->
<aside class="sidebar">


    <!-- LOGO -->
    <div class="sidebar-logo">

        <a
            href="home.php"
            class="logo-box">

            <img
                src="src/img/home_img/logo.png"
                alt="Mess Manager Logo">

        </a>

    </div>


    <!-- SIDEBAR MENU -->
    <ul class="sidebar-menu">


        <!-- HOME -->
        <li>

            <a href="dashboard.php">

                <i class="fa-solid fa-border-all"></i>

                হোম পেজ

            </a>

        </li>


        <!-- DEPOSIT -->
        <li>

            <a href="deposit.php">

                <i class="fa-solid fa-bangladeshi-taka-sign"></i>

                টাকা জমা

            </a>

        </li>


        <!-- MEAL -->
        <li>

            <a href="meals.php">

                <i class="fa-solid fa-bowl-rice"></i>

                মিল যুক্ত

            </a>

        </li>


        <!-- EXPENSE -->
        <li class="active">

            <a href="add_expense.php">

                <i class="fa-solid fa-bag-shopping"></i>

                খরচ যুক্ত

            </a>

        </li>


        <!-- MONTH DETAILS -->
        <li>

            <a href="month_details.php">

                <i class="fa-regular fa-calendar-check"></i>

                মাসের বিস্তারিত হিসাব

            </a>

        </li>


        <!-- ALL MONTHS -->
        <li>

            <a href="#">

                <i class="fa-regular fa-calendar"></i>

                সকল মাসের হিসাব

            </a>

        </li>


        <!-- NEW MONTH -->
        <li>

            <a href="#">

                <i class="fa-solid fa-folder-plus"></i>

                নতুন মাস শুরু করুন

            </a>

        </li>


        <!-- MESS MEMBERS -->
        <li>

            <a href="#">

                <i class="fa-solid fa-users"></i>

                মেস মেম্বার

            </a>

        </li>


        <!-- CHANGE MANAGER -->
        <li>

            <a href="#">

                <i class="fa-solid fa-user-tie"></i>

                ম্যানেজার পরিবর্তন

            </a>

        </li>


        <!-- SETTINGS -->
        <li>

            <a href="#">

                <i class="fa-solid fa-gear"></i>

                মেস সেটিংস

            </a>

        </li>


        <!-- DELETE MESS -->
        <li>

            <a href="#">

                <i class="fa-solid fa-trash"></i>

                মেস ডিলিট

            </a>

        </li>


    </ul>


    <!-- SIDEBAR FOOTER -->
    <div class="sidebar-footer">


        <div class="user-info">

            <img
                src="src/img/dashboard_img/profile.png"
                alt="Profile">


            <div>

                <span class="role">

                    <?php
                    echo htmlspecialchars($user_name);
                    ?>

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


    <!-- ========================================= -->
    <!-- TOP NAV -->
    <!-- ========================================= -->
    <header class="top-nav">


        <div class="nav-icons">


            <a
                href="dashboard.php"
                class="nav-icon active">

                <i class="fa-solid fa-table-cells-large"></i>

            </a>


            <a
                href="home.php"
                class="nav-icon">

                <i class="fa-solid fa-house"></i>

            </a>


            <a
                href="#"
                class="nav-icon">

                <i class="fa-regular fa-circle-question"></i>

            </a>


            <a
                href="#"
                class="nav-icon">

                <i class="fa-solid fa-bell"></i>

            </a>


            <a
                href="#"
                class="nav-icon">

                <i class="fa-regular fa-user"></i>

            </a>


        </div>


        <!-- PROFILE -->
        <div class="nav-profile">


            <span class="user-name">

                <?php
                echo htmlspecialchars($user_name);
                ?>

            </span>


            <img
                src="src/img/dashboard_img/profile.png"
                alt="Profile">


        </div>


    </header>


    <!-- ========================================= -->
    <!-- EXPENSE CONTENT -->
    <!-- ========================================= -->
    <main class="expense-page">


        <div class="expense-container">


            <!-- ========================================= -->
            <!-- EXPENSE TABS -->
            <!-- ========================================= -->
            <div class="expense-tabs">


                <!-- MEAL EXPENSE TAB -->
                <button
                    type="button"
                    class="expense-tab active"
                    id="mealExpenseTab"
                    onclick="showMealExpense()">

                    মিল খরচ

                </button>


                <!-- OTHER EXPENSE TAB -->
                <button
                    type="button"
                    class="expense-tab"
                    id="otherExpenseTab"
                    onclick="showOtherExpense()">

                    অন্যান্য খরচ

                </button>


            </div>


            <!-- ========================================= -->
            <!-- MEAL EXPENSE FORM -->
            <!-- DEFAULT ACTIVE -->
            <!-- ========================================= -->
            <div
                id="mealExpenseForm"
                class="expense-form-section active-form">


                <div class="expense-form-card">


                    <!-- TITLE -->
                    <h2>
                        দৈনিক বাজারের খরচ যুক্ত করুন
                    </h2>


                    <!-- DESCRIPTION -->
                    <p class="expense-description">

                        যার সাথে মিল রেট সম্পর্কিত।

                    </p>

                    <?php if ($expense_success): ?>
                        <div class="expense-alert success">
                            খরচ সফলভাবে যুক্ত হয়েছে।
                        </div>
                    <?php endif; ?>

                    <?php if ($expense_error !== ""): ?>
                        <div class="expense-alert error">
                            <?php echo htmlspecialchars($expense_error); ?>
                        </div>
                    <?php endif; ?>


                    <form
                        method="POST"
                        action="">


                        <!-- ================================= -->
                        <!-- DATE -->
                        <!-- ================================= -->
                        <div class="expense-form-group">


                            <label>
                                তারিখ
                            </label>


                            <input
                                type="date"
                                name="meal_expense_date"
                                value="<?php echo date('Y-m-d'); ?>"
                                required>


                        </div>


                        <!-- ================================= -->
                        <!-- MARKET EXPENSE AMOUNT -->
                        <!-- ================================= -->
                        <div class="expense-form-group">


                            <label>
                                বাজার খরচের পরিমাণ
                            </label>


                            <input
                                type="number"
                                name="meal_expense_amount"
                                placeholder="যেমন: 500"
                                step="0.01"
                                min="0.01"
                                required>


                        </div>


                        <!-- ================================= -->
                        <!-- MARKET LIST -->
                        <!-- ================================= -->
                        <div class="expense-form-group">


                            <label>

                                বাজার তালিকা

                                <span>
                                    (বাধ্যতামূলক নয়)
                                </span>

                            </label>


                            <textarea
                                name="market_list"
                                placeholder="বাজারের তালিকা লিখুন"></textarea>


                        </div>


                        <!-- ================================= -->
                        <!-- BUYER MEMBER -->
                        <!-- ================================= -->
                        <div class="expense-form-group">


                            <label>
                                বাজারকারীর নাম সিলেক্ট করুন
                            </label>


                            <select
                                name="market_user_id"
                                required>


                                <option value="">

                                    মেম্বার নির্বাচন করুন

                                </option>


                                <?php foreach ($members as $member): ?>


                                    <option
                                        value="<?php echo $member['user_id']; ?>">


                                        <?php
                                        echo htmlspecialchars(
                                            $member['full_name']
                                        );
                                        ?>


                                    </option>


                                <?php endforeach; ?>


                            </select>


                        </div>


                        <!-- ================================= -->
                        <!-- AUTO DEPOSIT CHECKBOX -->
                        <!-- ================================= -->
                        <label class="checkbox-row">


                            <input
                                type="checkbox"
                                name="add_as_deposit"
                                value="1">


                            <span>

                                সমপরিমাণ টাকা বাজারকারীর নামে জমা করুন?

                            </span>


                        </label>


                        <!-- ================================= -->
                        <!-- SUBMIT -->
                        <!-- ================================= -->
                        <button
                            type="submit"
                            name="meal_expense_submit"
                            class="expense-submit-button">

                            খরচ যুক্ত করুন

                        </button>


                    </form>


                </div>


            </div>


            <!-- ========================================= -->
            <!-- OTHER EXPENSE FORM -->
            <!-- HIDDEN BY DEFAULT -->
            <!-- ========================================= -->
            <div
                id="otherExpenseForm"
                class="expense-form-section">


                <div class="expense-form-card">


                    <!-- TITLE -->
                    <h2>
                        অন্যান্য খরচের সাথে মিল হিসাবের কোন সম্পর্ক নেই
                    </h2>


                    <form
                        method="POST"
                        action="">


                        <!-- ================================= -->
                        <!-- DATE -->
                        <!-- ================================= -->
                        <div class="expense-form-group">


                            <label>
                                তারিখ
                            </label>


                            <input
                                type="date"
                                name="other_expense_date"
                                value="<?php echo date('Y-m-d'); ?>"
                                required>


                        </div>


                        <!-- ================================= -->
                        <!-- EXPENSE LIST -->
                        <!-- ================================= -->
                        <div class="expense-form-group">


                            <label>
                                খরচ তালিকা
                            </label>


                            <textarea
                                name="other_expense_list"
                                placeholder="খরচের বিস্তারিত লিখুন"
                                required></textarea>


                        </div>


                        <!-- ================================= -->
                        <!-- EXPENSE TYPE -->
                        <!-- ================================= -->
                        <div class="expense-form-group">


                            <label>
                                খরচের ধরন
                            </label>


                            <select
                                id="expenseType"
                                name="expense_type">


                                <option value="joint">
                                    যৌথ
                                </option>


                                <option value="individual">
                                    ব্যক্তিগত
                                </option>


                            </select>


                        </div>


                        <!-- ================================= -->
                        <!-- TOTAL AMOUNT -->
                        <!-- ================================= -->
                        <div class="expense-form-group">


                            <label>
                                সর্বমোট খরচের পরিমান
                            </label>


                            <input
                                type="number"
                                id="otherExpenseAmount"
                                name="other_expense_amount"
                                placeholder="যেমন: 500"
                                step="0.01"
                                min="0.01"
                                oninput="updateOtherExpenseAmount()"
                                required>


                        </div>


                        <!-- ================================= -->
                        <!-- MEMBER AMOUNT TEXT -->
                        <!-- ================================= -->
                        <p class="selected-expense-text">


                            সিলেক্ট করা মেম্বারদের নামে


                            <strong
                                id="selectedExpenseAmount">

                                0.00 ৳

                            </strong>


                            অন্যান্য খরচ উঠবে


                        </p>


                        <!-- ================================= -->
                        <!-- MEMBER LIST -->
                        <!-- ================================= -->
                        <div class="expense-members-list">


                            <?php foreach ($members as $member): ?>


                        <label class="expense-member">

                            <!-- AVATAR -->
                            <div class="member-avatar">

                                <?php
                                $first_letter = mb_substr(
                                    $member['full_name'],
                                    0,
                                    1,
                                    "UTF-8"
                                );

                                echo htmlspecialchars($first_letter);
                                ?>

                            </div>


                            <!-- NAME -->
                            <span class="member-name">

                                <?php
                                echo htmlspecialchars(
                                    $member['full_name']
                                );
                                ?>

                            </span>


                            <!-- CHECKBOX -->
                            <input
                                type="checkbox"
                                name="selected_members[]"
                                class="expense-member-checkbox"
                                value="<?php echo $member['user_id']; ?>"
                            >

                        </label>


                            <?php endforeach; ?>


                        </div>


                        <!-- ================================= -->
                        <!-- SUBMIT -->
                        <!-- ================================= -->
                        <button
                            type="submit"
                            name="other_expense_submit"
                            class="expense-submit-button">

                            খরচ যুক্ত করুন

                        </button>


                    </form>


                </div>


            </div>


        </div>


    </main>


</div>

</div>

<?php else: ?>

<!-- ========================================= -->

<!-- NO MESS -->

<!-- ========================================= -->

<div class="no-mess-container">

<h2>
    আপনি এখনো কোনো মেসে যুক্ত হননি।
</h2>


<br>


<a href="dashboard.php">

    Dashboard এ ফিরে যান

</a>

</div>

<?php endif; ?>

<!-- ========================================= -->

<!-- EXPENSE JAVASCRIPT -->

<!-- ========================================= -->

<script src="js/expense.js"></script>

</body>

</html>
