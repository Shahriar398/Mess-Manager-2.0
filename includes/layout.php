<?php

function render_app_sidebar(string $active, string $user_name, bool $is_manager): void
{
    $item = function (string $key, string $href, string $icon, string $label) use ($active) {
        $cls = $active === $key ? ' class="active"' : "";
        echo "<li{$cls}><a href=\"" . htmlspecialchars($href) . "\"><i class=\"{$icon}\"></i> {$label}</a></li>";
    };
    ?>
    <aside class="sidebar">
        <div class="sidebar-logo">
            <a href="home.php" class="logo-box">
                <img src="src/img/home_img/logo.png" alt="Mess Manager Logo">
            </a>
        </div>
        <ul class="sidebar-menu">
            <?php
            $item("home", "dashboard.php", "fa-solid fa-border-all", "হোম পেজ");
            $item("deposit", "deposit.php", "fa-solid fa-bangladeshi-taka-sign", "টাকা জমা");
            $item("meals", "meals.php", "fa-solid fa-bowl-rice", "মিল যুক্ত");
            $item("expense", "add_expense.php", "fa-solid fa-bag-shopping", "খরচ যুক্ত");
            $item("this_month", "this_month.php", "fa-regular fa-calendar-check", "মাসের বিস্তারিত হিসাব");
            $item("all_months", "all_months.php", "fa-regular fa-calendar", "সকল মাসের হিসাব");
            if ($is_manager) {
                $item("open_month", "open_new_month.php", "fa-solid fa-folder-plus", "নতুন মাস শুরু করুন");
            }
            $item("members", "members.php", "fa-solid fa-users", "মেস মেম্বার");
            if ($is_manager) {
                $item("manager", "change_manager.php", "fa-solid fa-user-tie", "ম্যানেজার পরিবর্তন");
            }
            $item("settings", "mess_settings.php", "fa-solid fa-gear", "মেস সেটিংস");
            if ($is_manager) {
                $item("delete", "delete_mess.php", "fa-solid fa-trash", "মেস ডিলিট");
            }
            ?>
        </ul>
        <div class="sidebar-footer">
            <div class="user-info">
                <img src="src/img/dashboard_img/profile.png" alt="Profile">
                <div><span class="role"><?php echo htmlspecialchars($user_name); ?></span></div>
            </div>
            <a href="logout.php" class="logout-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
        </div>
    </aside>
    <?php
}

function render_app_topnav(string $user_name): void
{
    ?>
    <header class="top-nav">
        <div class="nav-icons">
            <a href="dashboard.php" class="nav-icon active"><i class="fa-solid fa-table-cells-large"></i></a>
            <a href="home.php" class="nav-icon"><i class="fa-solid fa-house"></i></a>
            <a href="profile.php" class="nav-icon"><i class="fa-regular fa-user"></i></a>
        </div>
        <a href="profile.php" class="nav-profile">
            <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
            <img src="src/img/dashboard_img/profile.png" alt="Profile">
        </a>
    </header>
    <?php
}
