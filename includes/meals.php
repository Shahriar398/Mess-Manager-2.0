<?php

/**
 * Meal management helpers.
 * Requires includes/auth.php and db connection.
 */

function normalize_meal_count($value): float
{
    if (!is_numeric($value)) {
        return 0.0;
    }

    $count = (float) $value;

    if ($count < 0) {
        return 0.0;
    }

    if ($count > 9) {
        return 9.0;
    }

    return round($count, 1);
}

function meal_day_total(float $breakfast, float $lunch, float $dinner): float
{
    return round($breakfast + $lunch + $dinner, 1);
}

function can_manage_member_meal(mysqli $conn, int $logged_user_id, int $mess_id, int $target_user_id): bool
{
    if ($logged_user_id === $target_user_id) {
        return user_belongs_to_mess($conn, $logged_user_id, $mess_id);
    }

    return user_is_manager($conn, $logged_user_id, $mess_id);
}

function get_meals_for_date(mysqli $conn, int $mess_id, int $month_id, string $meal_date): array
{
    $meals = [];

    $stmt = $conn->prepare(
        "SELECT meal_id, user_id, breakfast, lunch, dinner
         FROM meals
         WHERE mess_id = ? AND month_id = ? AND meal_date = ?"
    );

    if (!$stmt) {
        return $meals;
    }

    $stmt->bind_param("iis", $mess_id, $month_id, $meal_date);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $meals[(int) $row["user_id"]] = $row;
    }

    $stmt->close();

    return $meals;
}

function get_meal_by_id(mysqli $conn, int $meal_id, int $mess_id): ?array
{
    $stmt = $conn->prepare(
        "SELECT meal_id, mess_id, month_id, user_id, meal_date, breakfast, lunch, dinner
         FROM meals
         WHERE meal_id = ? AND mess_id = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("ii", $meal_id, $mess_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function save_member_meal(
    mysqli $conn,
    int $mess_id,
    int $month_id,
    int $target_user_id,
    string $meal_date,
    float $breakfast,
    float $lunch,
    float $dinner
): bool {
    $total = meal_day_total($breakfast, $lunch, $dinner);

    if ($total <= 0) {
        $delete = $conn->prepare(
            "DELETE FROM meals
             WHERE mess_id = ? AND month_id = ? AND user_id = ? AND meal_date = ?"
        );

        if (!$delete) {
            return false;
        }

        $delete->bind_param("iiis", $mess_id, $month_id, $target_user_id, $meal_date);
        $ok = $delete->execute();
        $delete->close();

        return $ok;
    }

    $upsert = $conn->prepare(
        "INSERT INTO meals (mess_id, month_id, user_id, meal_date, breakfast, lunch, dinner)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            breakfast = VALUES(breakfast),
            lunch = VALUES(lunch),
            dinner = VALUES(dinner),
            updated_at = CURRENT_TIMESTAMP"
    );

    if (!$upsert) {
        return false;
    }

    $upsert->bind_param(
        "iiisddd",
        $mess_id,
        $month_id,
        $target_user_id,
        $meal_date,
        $breakfast,
        $lunch,
        $dinner
    );

    $ok = $upsert->execute();
    $upsert->close();

    return $ok;
}

function get_mess_total_meals(mysqli $conn, int $mess_id, int $month_id): float
{
    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(breakfast + lunch + dinner), 0) AS total_meals
         FROM meals
         WHERE mess_id = ? AND month_id = ?"
    );

    if (!$stmt) {
        return 0.0;
    }

    $stmt->bind_param("ii", $mess_id, $month_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return round((float) ($row["total_meals"] ?? 0), 1);
}

function get_member_total_meals(mysqli $conn, int $mess_id, int $month_id, int $user_id): float
{
    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(breakfast + lunch + dinner), 0) AS total_meals
         FROM meals
         WHERE mess_id = ? AND month_id = ? AND user_id = ?"
    );

    if (!$stmt) {
        return 0.0;
    }

    $stmt->bind_param("iii", $mess_id, $month_id, $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return round((float) ($row["total_meals"] ?? 0), 1);
}

function get_member_monthly_summary(mysqli $conn, int $mess_id, int $month_id): array
{
    $summary = [];

    $stmt = $conn->prepare(
        "SELECT u.user_id, u.full_name,
                COALESCE(SUM(m.breakfast), 0) AS breakfast_total,
                COALESCE(SUM(m.lunch), 0) AS lunch_total,
                COALESCE(SUM(m.dinner), 0) AS dinner_total,
                COALESCE(SUM(m.breakfast + m.lunch + m.dinner), 0) AS meal_total
         FROM mess_members mem
         JOIN users u ON mem.user_id = u.user_id
         LEFT JOIN meals m
            ON m.user_id = u.user_id
           AND m.mess_id = mem.mess_id
           AND m.month_id = ?
         WHERE mem.mess_id = ?
         GROUP BY u.user_id, u.full_name
         ORDER BY u.full_name ASC"
    );

    if (!$stmt) {
        return $summary;
    }

    $stmt->bind_param("ii", $month_id, $mess_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $summary[] = $row;
    }

    $stmt->close();

    return $summary;
}

function get_date_monthly_summary(mysqli $conn, int $mess_id, int $month_id): array
{
    $summary = [];

    $stmt = $conn->prepare(
        "SELECT meal_date,
                COALESCE(SUM(breakfast), 0) AS breakfast_total,
                COALESCE(SUM(lunch), 0) AS lunch_total,
                COALESCE(SUM(dinner), 0) AS dinner_total,
                COALESCE(SUM(breakfast + lunch + dinner), 0) AS meal_total
         FROM meals
         WHERE mess_id = ? AND month_id = ?
         GROUP BY meal_date
         ORDER BY meal_date DESC"
    );

    if (!$stmt) {
        return $summary;
    }

    $stmt->bind_param("ii", $mess_id, $month_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $summary[] = $row;
    }

    $stmt->close();

    return $summary;
}

function delete_meal_record(mysqli $conn, int $meal_id, int $mess_id): bool
{
    $stmt = $conn->prepare("DELETE FROM meals WHERE meal_id = ? AND mess_id = ?");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ii", $meal_id, $mess_id);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function meals_table_exists(mysqli $conn): bool
{
    $result = $conn->query("SHOW TABLES LIKE 'meals'");

    return $result && $result->num_rows > 0;
}

function ensure_meals_table(mysqli $conn): bool
{
    if (meals_table_exists($conn)) {
        return true;
    }

    $sql = "CREATE TABLE IF NOT EXISTS `meals` (
      `meal_id` int(11) NOT NULL AUTO_INCREMENT,
      `mess_id` int(11) NOT NULL,
      `month_id` int(11) NOT NULL,
      `user_id` int(11) NOT NULL,
      `meal_date` date NOT NULL,
      `breakfast` decimal(4,1) NOT NULL DEFAULT 0.0,
      `lunch` decimal(4,1) NOT NULL DEFAULT 0.0,
      `dinner` decimal(4,1) NOT NULL DEFAULT 0.0,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`meal_id`),
      UNIQUE KEY `unique_member_meal_day` (`mess_id`, `month_id`, `user_id`, `meal_date`),
      KEY `idx_meals_mess_month` (`mess_id`, `month_id`),
      KEY `idx_meals_date` (`meal_date`),
      KEY `idx_meals_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    return (bool) $conn->query($sql);
}
