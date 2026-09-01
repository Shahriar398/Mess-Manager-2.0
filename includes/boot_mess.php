<?php

require_login();
ensure_extended_schema($conn);

if (function_exists("ensure_meals_table")) {
    ensure_meals_table($conn);
}

$user_id = get_logged_in_user_id();
$user_name = get_logged_in_user_name();
$active_mess = get_active_mess($conn, $user_id);
$has_mess = (bool) $active_mess;
$current_mess_id = $has_mess ? (int) $active_mess["mess_id"] : 0;
$current_month_id = $has_mess ? (int) $active_mess["month_id"] : 0;
$mess_name = $has_mess ? $active_mess["mess_name"] : "";
$month_name = $has_mess ? $active_mess["month_name"] : "";
$is_manager = $has_mess && user_is_manager($conn, $user_id, $current_mess_id);

function require_mess_page(bool $has_mess): void
{
    if ($has_mess) {
        return;
    }

    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Mess Manager</title>
    <link rel="stylesheet" href="css/dashboard.css"></head><body>
    <div class="no-mess-container" style="text-align:center;padding:50px;font-family:Noto Sans Bengali,sans-serif">
    <h2>আপনি এখনো কোনো মেসে যুক্ত হননি।</h2><br>
    <a href="dashboard.php">Dashboard এ ফিরে যান</a></div></body></html>';
    exit();
}

function require_manager_page(bool $is_manager): void
{
    if ($is_manager) {
        return;
    }

    http_response_code(403);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Forbidden</title>
    <link rel="stylesheet" href="css/dashboard.css"></head><body>
    <div style="text-align:center;padding:50px;font-family:Noto Sans Bengali,sans-serif">
    <h2>Only the mess manager can perform this action.</h2><br>
    <a href="dashboard.php">Back to dashboard</a></div></body></html>';
    exit();
}
