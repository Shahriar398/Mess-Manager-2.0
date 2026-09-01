<?php
session_start();
include("db.php");
include __DIR__ . "/includes/meals.php";

// ইউজার লগইন না থাকলে login.php তে পাঠিয়ে দেবে
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$user_email = $_SESSION["email"];
$user_name = isset($_SESSION["name"]) ? $_SESSION["name"] : "User";

// ডাটাবেস চেক করা হচ্ছে ইউজারের কোনো মেস আছে কি না
$has_mess = false;
$mess_name = "";
$month_name = "";
$current_mess_id = "";
$current_month_id = 0;
$total_meals = 0;
$user_total_meals = 0;
$meal_rate = 0;
$month_meal_expense = 0;

$query = "SELECT m.mess_id, m.mess_name, mm.month_id, mm.month_name 
          FROM mess_members mem
          JOIN messes m ON mem.mess_id = m.mess_id
          JOIN mess_months mm ON m.mess_id = mm.mess_id
          WHERE mem.user_id = ? AND mm.status = 'active' LIMIT 1";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $has_mess = true; // মেস পাওয়া গেছে
    $current_mess_id = $row['mess_id'];
    $current_month_id = (int) $row['month_id'];
    $mess_name = $row['mess_name'];
    $month_name = $row['month_name'];
}

$is_manager = false;
if ($has_mess) {
    $role_stmt = $conn->prepare(
        "SELECT role FROM mess_members WHERE mess_id = ? AND user_id = ? LIMIT 1"
    );
    $role_stmt->bind_param("ii", $current_mess_id, $user_id);
    $role_stmt->execute();
    $role_row = $role_stmt->get_result()->fetch_assoc();
    $role_stmt->close();
    $is_manager = $role_row && $role_row["role"] === "manager";
}

// ==========================================
// TOTAL DEPOSIT
// ==========================================

$total_deposit = 0;

if ($has_mess) {

    $deposit_query = "
        SELECT COALESCE(SUM(amount), 0) AS total_deposit
        FROM deposits
        WHERE mess_id = ?
    ";

    $deposit_stmt = $conn->prepare($deposit_query);

    $deposit_stmt->bind_param(
        "i",
        $current_mess_id
    );

    $deposit_stmt->execute();

    $deposit_result =
        $deposit_stmt->get_result();

    $deposit_row =
        $deposit_result->fetch_assoc();

    $total_deposit =
        $deposit_row["total_deposit"];

}

// ==========================================
// TOTAL EXPENSE
// ==========================================

$total_expense = 0;

if ($has_mess) {

    $expense_query = "
        SELECT COALESCE(SUM(amount), 0) AS total_expense
        FROM meal_expenses
        WHERE mess_id = ?
    ";

    $expense_stmt = $conn->prepare($expense_query);

    $expense_stmt->bind_param(
        "i",
        $current_mess_id
    );

    $expense_stmt->execute();

    $expense_result = $expense_stmt->get_result();

    $expense_row = $expense_result->fetch_assoc();

    $total_expense = $expense_row["total_expense"];
}

if ($has_mess && meals_table_exists($conn)) {
    $total_meals = get_mess_total_meals($conn, (int) $current_mess_id, $current_month_id);
    $user_total_meals = get_member_total_meals($conn, (int) $current_mess_id, $current_month_id, (int) $user_id);

    $month_expense_stmt = $conn->prepare(
        "SELECT COALESCE(SUM(amount), 0) AS month_meal_expense
         FROM meal_expenses
         WHERE mess_id = ? AND month_id = ?"
    );
    $month_expense_stmt->bind_param("ii", $current_mess_id, $current_month_id);
    $month_expense_stmt->execute();
    $month_expense_row = $month_expense_stmt->get_result()->fetch_assoc();
    $month_meal_expense = (float) ($month_expense_row["month_meal_expense"] ?? 0);
    $month_expense_stmt->close();

    if ($total_meals > 0) {
        $meal_rate = $month_meal_expense / $total_meals;
    }
}

$mess_balance = $total_deposit - $total_expense;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mess Manager | Meal Management</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>

<?php if ($has_mess): ?>
    <!-- ========================================== -->
    <!-- মেইন ড্যাশবোর্ড (মেস তৈরি করার পরের ভিউ) -->
    <!-- ========================================== -->
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <a href="home.php" class="logo-box">
                    <img src="src/img/home_img/logo.png" alt="Mess Manager Logo">
                </a>
            </div>
            
            <ul class="sidebar-menu">
                <li class="active"><a href="dashboard.php"><i class="fa-solid fa-border-all"></i> হোম পেজ</a></li>
                <li>
                    <a href="deposit.php">
                        <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                        টাকা জমা
                    </a>
                </li>
                <li><a href="meals.php"><i class="fa-solid fa-bowl-rice"></i> মিল যুক্ত</a></li>
                <li>
                    <a href="add_expense.php">
                        <i class="fa-solid fa-bag-shopping"></i>
                        খরচ যুক্ত
                    </a>
                </li>
                <li><a href="this_month.php"><i class="fa-regular fa-calendar-check"></i> মাসের বিস্তারিত হিসাব</a></li>
                <li><a href="all_months.php"><i class="fa-regular fa-calendar"></i> সকল মাসের হিসাব</a></li>
                <?php if ($is_manager): ?>
                <li><a href="open_new_month.php"><i class="fa-solid fa-folder-plus"></i> নতুন মাস শুরু করুন</a></li>
                <?php endif; ?>
                <li><a href="members.php"><i class="fa-solid fa-users"></i> মেস মেম্বার</a></li>
                <?php if ($is_manager): ?>
                <li><a href="change_manager.php"><i class="fa-solid fa-user-tie"></i> ম্যানেজার পরিবর্তন</a></li>
                <?php endif; ?>
                <li><a href="mess_settings.php"><i class="fa-solid fa-gear"></i> মেস সেটিংস</a></li>
                <?php if ($is_manager): ?>
                <li><a href="delete_mess.php"><i class="fa-solid fa-trash"></i> মেস ডিলিট</a></li>
                <?php endif; ?>
            </ul>

            <div class="sidebar-footer">
                <div class="user-info">
                    <img src="src/img/dashboard_img/profile.png" alt="Profile">
                    <div>
                        <span class="role"><?php echo htmlspecialchars($user_name); ?></span>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="main-area">
            <!-- Top Navbar -->
            <header class="top-nav">
                <div class="nav-icons">
                    <a href="#" class="nav-icon active"><i class="fa-solid fa-table-cells-large"></i></a>
                    <a href="#" class="nav-icon"><i class="fa-solid fa-house"></i></a>
                    <a href="#" class="nav-icon"><i class="fa-regular fa-circle-question"></i></a>
                    <a href="#" class="nav-icon"><i class="fa-solid fa-bell"></i></a>
                    <a href="profile.php" class="nav-icon">
                        <i class="fa-regular fa-user"></i>
                    </a>
                </div>
                    <a href="profile.php" class="nav-profile">
                        <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <img src="src/img/dashboard_img/profile.png" alt="Profile">
                    </a>
            </header>

            <!-- Dashboard Content -->
            <div class="content-wrapper">
                
                <!-- Progress Step Card -->
                <div class="step-card">
                    <div class="step-graphic">
                        <div class="step-line">
                            <div class="step-item completed"><i class="fa-solid fa-check"></i><p>মেস গঠন</p></div>
                            <div class="line completed-line"></div>
                            <div class="step-item active"><i class="fa-solid fa-user-plus"></i><p>মেম্বার যুক্ত</p></div>
                            <div class="line"></div>
                            <div class="step-item"><i class="fa-solid fa-bowl-rice"></i><p>মিল যুক্ত</p></div>
                            <div class="line"></div>
                            <div class="step-item"><i class="fa-solid fa-wallet"></i><p>টাকা জমা</p></div>
                            <div class="line"></div>
                            <div class="step-item"><i class="fa-solid fa-bag-shopping"></i><p>মিল খরচ যুক্ত</p></div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <!-- Left Column -->
                    <div class="left-col">
                        <div class="status-card">
                            <div class="status-header">
                                <div>
                                    <!-- মেস ও মাসের আসল নাম ডাটাবেস থেকে -->
                                    <h3><?php echo htmlspecialchars($mess_name . ', ' . $month_name); ?></h3>
                                    <p>চলতি মাসের হিসাব</p>
                                </div>
                                <span class="badge-running"><span class="dot"></span> Running</span>
                            </div>
                            <div class="status-list">

                                <!-- MESS BALANCE -->
                                <div class="list-item">
                                    <i class="fa-solid fa-scale-balanced icon-gray"></i>
                                    <span>মেস ব্যালেন্স</span>

                                    <strong>
                                        <?php echo number_format($mess_balance, 2); ?> ৳
                                    </strong>
                                </div>


                                <!-- TOTAL DEPOSIT -->
                                <div class="list-item">
                                    <i class="fa-solid fa-bangladeshi-taka-sign icon-green"></i>
                                    <span>মেসের মোট জমা</span>

                                    <strong>
                                        <?php echo number_format($total_deposit, 2); ?> ৳
                                    </strong>
                                </div>


                                <!-- TOTAL MEAL -->
                                <div class="list-item">
                                    <i class="fa-solid fa-bowl-rice icon-blue"></i>
                                    <span>মোট মিল</span>
                                    <strong><?php echo number_format($total_meals, 1); ?></strong>
                                </div>


                                <!-- TOTAL MEAL EXPENSE -->
                                <div class="list-item">
                                    <i class="fa-solid fa-bag-shopping icon-gray"></i>
                                    <span>মোট মিল খরচ</span>
                                    <strong>
                                        <?php echo number_format($total_expense, 2); ?> ৳
                                    </strong>
                                </div>


                                <!-- MEAL RATE -->
                                <div class="list-item">
                                    <i class="fa-solid fa-calculator icon-gray"></i>
                                    <span>মিল রেট</span>
                                    <strong>
                                        <?php echo $total_meals > 0 ? number_format($meal_rate, 2) . " ৳" : "--- ৳"; ?>
                                    </strong>
                                </div>

                            </div>
                        </div>

                        <!-- মেস কোড দেখানোর কার্ড -->
                        <div class="info-notice mt-10">
                            <div class="info-icon"><i class="fa-solid fa-circle-info"></i></div>
                            <p>অন্যদের যুক্ত করতে আপনার মেস কোডটি শেয়ার করুন: <strong><?php echo $current_mess_id; ?></strong></p>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="right-col">
                        <h3 class="section-title">আমার হিসাব</h3>
                        <div class="stats-grid">
                            <div class="stat-box box-blue">
                                <i class="fa-solid fa-bowl-rice"></i>
                                <h2><?php echo number_format($user_total_meals, 1); ?></h2>
                                <p>মোট মিল</p>
                            </div>
                            <div class="stat-box box-green">

                                <i class="fa-solid fa-bangladeshi-taka-sign"></i>

                                <h2>
                                    <?php echo number_format($total_deposit, 2); ?> ৳
                                </h2>

                                <p>মোট জমা</p>

                            </div>
                            <div class="stat-box box-pink">
                                <i class="fa-solid fa-cart-shopping"></i>

                                <h2>
                                    <?php echo number_format($total_expense, 2); ?> ৳
                                </h2>

                                <p>মোট খরচ</p>
                            </div>
                            <div class="stat-box box-yellow">
                                <i class="fa-solid fa-wallet"></i>

                                <h2>
                                    <?php echo number_format($mess_balance, 2); ?> ৳
                                </h2>

                                <p>ব্যালেন্স</p>
                            </div>
                        </div>

                        <div class="daily-meal-card mt-20">
                            <div class="card-header-flex">
                                <div>
                                    <h3 class="section-title-red">আমার প্রতিদিনের মিল <i class="fa-solid fa-arrow-up-right-from-square"></i></h3>
                                    <p class="subtitle">অটো মিল রিকোয়েস্ট বন্ধ আছে, চালু করুন</p>
                                </div>
                                <div class="toggle-switch"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ========================================== -->
    <!-- প্রাথমিক ভিউ (মেস তৈরি বা যুক্ত হওয়ার পেজ) -->
    <!-- ========================================== -->
    
    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="nav-left">
            <a href="home.php" class="logo-box">
                <img src="src/img/home_img/logo.png" alt="Mess Manager Logo">
            </a>
        </div>
        
        <div class="nav-center">
            <a href="#" class="nav-icon active"><i class="fa-solid fa-table-cells-large"></i></a>
            <a href="#" class="nav-icon"><i class="fa-solid fa-house"></i></a>
            <a href="#" class="nav-icon"><i class="fa-regular fa-circle-question"></i></a>
            <a href="#" class="nav-icon"><i class="fa-solid fa-bell"></i></a>
            <a href="profile.php" class="nav-icon">
                <i class="fa-regular fa-user"></i>
            </a>
        </div>

        <div class="nav-right">
            <div class="profile-pic">
                <img src="src/img/dashboard_img/profile.png" alt="Profile">
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        
        <!-- View 1: Initial Dashboard -->
        <div id="initial-view" class="view-container">
            <div class="welcome-header">
                <h2>মেস ম্যানেজার এ আপনাকে স্বাগতম <span class="emoji">🎉</span></h2>
                <p><?php echo htmlspecialchars($user_email); ?></p>
            </div>

            <div class="cards-container">
                <!-- Create Mess Button -->
                <div class="action-card create-card" id="btn-create-mess">
                    <div class="card-icon create-icon">
                        <i class="fa-regular fa-clipboard"></i>
                    </div>
                    <div class="card-text">
                        <h3>মেস তৈরি করুন</h3>
                        <p>ম্যানেজার হিসেবে নতুন মেস খুলুন</p>
                    </div>
                </div>

                <!-- Join Mess Button -->
                <div class="action-card join-card" id="btn-join-mess">
                    <div class="card-icon join-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="card-text">
                        <h3>মেসে যুক্ত হোন</h3>
                        <p>ইমেইল শেয়ার করুন, ম্যানেজার যুক্ত করবেন</p>
                    </div>
                </div>

                <div class="info-notice">
                    <div class="info-icon">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                    <p>অন্য কেউ মেস তৈরি করে থাকলে, নতুন মেস না খুলে সেই মেসে যুক্ত হোন</p>
                </div>
            </div>
        </div>

        <!-- View 2: Create Mess Form -->
        <div id="create-mess-view" class="view-container" style="display: none;">
            
            <button id="btn-back-create" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i> Back
            </button>

            <div class="form-header">
                <h2>আপনাদের মেসের হিসাব চালু করতে<br>প্রয়োজনীয় তথ্য গুলো প্রদান করুন</h2>
            </div>

            <form id="form-create-mess" class="mess-form">
                <div class="input-group">
                    <label>আপনাদের মেসের নাম দিন</label>
                    <input type="text" name="mess_name" placeholder="e.g. Our Mess" required>
                </div>
                <div class="input-group">
                    <label>মাসের নাম (যে মাসের হিসাব করবেন ওই মাসের নাম দিন)</label>
                    <input type="text" name="month_name" placeholder="e.g. March" required>
                </div>

                <ul class="form-rules">
                    <li>যে মাসের মিল হিসাব করবেন সেই মাসের নাম লিখুন।</li>
                    <li>আমাদের এপ এ মাসের কোন <span class="highlight">নির্দিষ্ট দিন নেই।</span></li>
                    <li>মাসের হিসাব অটো বন্ধ হবে নাহ, যেকোনদিন থেকেই হিসাব শুরু করে শেষ করতে পারবেন।</li>
                    <li><span class="highlight">হিসাব শেষ না করলে</span> ওই হিসাব চলতেই থাকবে। যেকোন দিন থেকে হিসাব শুরু করে যেকোন দিন হিসাব শেষ করা যাবে।</li>
                </ul>

                <div class="input-group">
                    <label class="role-label">আপনি মেসের ম্যানেজার নাকি মেম্বার?</label>
                    <div class="radio-options">
                        <label class="radio-container">
                            <input type="radio" name="role" value="manager" checked>
                            <span class="radio-text">ম্যানেজার</span>
                        </label>
                        <label class="radio-container">
                            <input type="radio" name="role" value="member">
                            <span class="radio-text">মেম্বার</span>
                        </label>
                    </div>
                </div>

                <button type="submit" id="submit-create-btn" class="submit-button">হিসাব শুরু করুন</button>
            </form>
        </div>

        <!-- View 3: Join Mess Form -->
        <div id="join-mess-view" class="view-container" style="display: none;">
            
            <button id="btn-back-join" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i> Back
            </button>

            <div class="form-header">
                <h2>মেসে যুক্ত হতে ম্যানেজারের দেওয়া<br>মেস কোড প্রদান করুন</h2>
            </div>

            <form id="form-join-mess" class="mess-form">
                
                <div class="input-group">
                    <label>মেস কোড (Mess Code)</label>
                    <input type="text" name="mess_code" placeholder="e.g. 5" required>
                </div>

                <div class="info-notice">
                    <div class="info-icon">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                    <p>আপনার ম্যানেজারের কাছ থেকে মেস কোডটি জেনে নিন।</p>
                </div>

                <button type="submit" id="submit-join-btn" class="submit-button">মেসে যুক্ত হোন</button>
            </form>
        </div>

    </main>

    <!-- Success Modal -->
    <div id="success-modal" class="modal-overlay" style="display: none;">
        <div class="modal-box">
            <!-- success image -->
            <img src="https://cdn-icons-png.flaticon.com/512/190/190411.png" alt="Success" class="modal-img">
            <h2 class="modal-title">আপনাদের মেস সফলভাবে খোলা হয়েছে!</h2>
            <p class="modal-subtitle">Mess created successfully</p>
            <button id="btn-ok-great" class="modal-btn">Ok, Great</button>
        </div>
    </div>
<?php endif; ?>

    <!-- Custom JS -->
    <script src="js/dashboard.js"></script>
</body>
</html>