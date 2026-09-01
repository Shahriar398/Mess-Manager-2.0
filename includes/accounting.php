<?php

/**
 * Month-scoped accounting. Always pass mess_id + month_id from mess_months.
 * Never use MONTH(CURDATE()).
 */

function get_month_row(mysqli $conn, int $mess_id, int $month_id): ?array
{
    $stmt = $conn->prepare(
        "SELECT month_id, mess_id, month_name, status, started_at, closed_at
         FROM mess_months
         WHERE mess_id = ? AND month_id = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("ii", $mess_id, $month_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function get_mess_months(mysqli $conn, int $mess_id): array
{
    $months = [];
    $stmt = $conn->prepare(
        "SELECT month_id, mess_id, month_name, status, started_at, closed_at
         FROM mess_months
         WHERE mess_id = ?
         ORDER BY started_at DESC, month_id DESC"
    );

    if (!$stmt) {
        return $months;
    }

    $stmt->bind_param("i", $mess_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $months[] = $row;
    }

    $stmt->close();

    return $months;
}

function sql_sum(mysqli $conn, string $sql, string $types, array $params): float
{
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return 0.0;
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return money_round($row["total"] ?? 0);
}

function month_meal_totals(mysqli $conn, int $mess_id, int $month_id): array
{
    $totals = [
        "breakfast" => 0.0,
        "lunch" => 0.0,
        "dinner" => 0.0,
        "meals" => 0.0,
    ];

    if (!function_exists("meals_table_exists") || !meals_table_exists($conn)) {
        return $totals;
    }

    $stmt = $conn->prepare(
        "SELECT
            COALESCE(SUM(breakfast), 0) AS breakfast,
            COALESCE(SUM(lunch), 0) AS lunch,
            COALESCE(SUM(dinner), 0) AS dinner,
            COALESCE(SUM(breakfast + lunch + dinner), 0) AS meals
         FROM meals
         WHERE mess_id = ? AND month_id = ?"
    );

    if (!$stmt) {
        return $totals;
    }

    $stmt->bind_param("ii", $mess_id, $month_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        $totals["breakfast"] = round((float) $row["breakfast"], 1);
        $totals["lunch"] = round((float) $row["lunch"], 1);
        $totals["dinner"] = round((float) $row["dinner"], 1);
        $totals["meals"] = round((float) $row["meals"], 1);
    }

    return $totals;
}

function month_meal_cost(mysqli $conn, int $mess_id, int $month_id): float
{
    return sql_sum(
        $conn,
        "SELECT COALESCE(SUM(amount), 0) AS total
         FROM meal_expenses
         WHERE mess_id = ? AND month_id = ?",
        "ii",
        [$mess_id, $month_id]
    );
}

function month_other_cost(mysqli $conn, int $mess_id, int $month_id, string $type): float
{
    return sql_sum(
        $conn,
        "SELECT COALESCE(SUM(total_amount), 0) AS total
         FROM other_expenses
         WHERE mess_id = ? AND month_id = ? AND expense_type = ?",
        "iis",
        [$mess_id, $month_id, $type]
    );
}

function month_deposits_total(mysqli $conn, int $mess_id, int $month_id, array $month_row): float
{
    $has_month_id = table_has_column($conn, "deposits", "month_id");
    $start = substr((string) $month_row["started_at"], 0, 10);
    $end = !empty($month_row["closed_at"])
        ? substr((string) $month_row["closed_at"], 0, 10)
        : date("Y-m-d");

    if ($has_month_id) {
        return sql_sum(
            $conn,
            "SELECT COALESCE(SUM(amount), 0) AS total
             FROM deposits
             WHERE mess_id = ?
               AND (
                    month_id = ?
                    OR (month_id IS NULL AND deposit_date >= ? AND deposit_date <= ?)
               )",
            "iiss",
            [$mess_id, $month_id, $start, $end]
        );
    }

    return sql_sum(
        $conn,
        "SELECT COALESCE(SUM(amount), 0) AS total
         FROM deposits
         WHERE mess_id = ? AND deposit_date >= ? AND deposit_date <= ?",
        "iss",
        [$mess_id, $start, $end]
    );
}

function member_month_meals(mysqli $conn, int $mess_id, int $month_id, int $user_id): float
{
    if (!function_exists("meals_table_exists") || !meals_table_exists($conn)) {
        return 0.0;
    }

    return round(sql_sum(
        $conn,
        "SELECT COALESCE(SUM(breakfast + lunch + dinner), 0) AS total
         FROM meals
         WHERE mess_id = ? AND month_id = ? AND user_id = ?",
        "iii",
        [$mess_id, $month_id, $user_id]
    ), 1);
}

function member_month_deposit(mysqli $conn, int $mess_id, int $month_id, int $user_id, array $month_row): float
{
    $has_month_id = table_has_column($conn, "deposits", "month_id");
    $start = substr((string) $month_row["started_at"], 0, 10);
    $end = !empty($month_row["closed_at"])
        ? substr((string) $month_row["closed_at"], 0, 10)
        : date("Y-m-d");

    if ($has_month_id) {
        return sql_sum(
            $conn,
            "SELECT COALESCE(SUM(amount), 0) AS total
             FROM deposits
             WHERE mess_id = ? AND user_id = ?
               AND (
                    month_id = ?
                    OR (month_id IS NULL AND deposit_date >= ? AND deposit_date <= ?)
               )",
            "iiiss",
            [$mess_id, $user_id, $month_id, $start, $end]
        );
    }

    return sql_sum(
        $conn,
        "SELECT COALESCE(SUM(amount), 0) AS total
         FROM deposits
         WHERE mess_id = ? AND user_id = ? AND deposit_date >= ? AND deposit_date <= ?",
        "iiss",
        [$mess_id, $user_id, $start, $end]
    );
}

function member_individual_cost(mysqli $conn, int $mess_id, int $month_id, int $user_id): float
{
    return sql_sum(
        $conn,
        "SELECT COALESCE(SUM(oem.amount), 0) AS total
         FROM other_expense_members oem
         JOIN other_expenses oe ON oem.other_expense_id = oe.other_expense_id
         WHERE oe.mess_id = ? AND oe.month_id = ? AND oem.user_id = ?
           AND oe.expense_type = 'individual'",
        "iii",
        [$mess_id, $month_id, $user_id]
    );
}

function accounting_members_for_month(mysqli $conn, int $mess_id, int $month_id): array
{
    $members = [];
    $seen = [];

    $stmt = $conn->prepare(
        "SELECT u.user_id, u.full_name, u.email, u.phone, mem.role, mem.joined_at
         FROM mess_members mem
         JOIN users u ON mem.user_id = u.user_id
         WHERE mem.mess_id = ?
         ORDER BY u.full_name ASC"
    );

    if ($stmt) {
        $stmt->bind_param("i", $mess_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $uid = (int) $row["user_id"];
            $seen[$uid] = true;
            $members[] = $row;
        }

        $stmt->close();
    }

    if (function_exists("meals_table_exists") && meals_table_exists($conn)) {
        $extra = $conn->prepare(
            "SELECT DISTINCT u.user_id, u.full_name, u.email, u.phone
             FROM meals m
             JOIN users u ON m.user_id = u.user_id
             WHERE m.mess_id = ? AND m.month_id = ?"
        );

        if ($extra) {
            $extra->bind_param("ii", $mess_id, $month_id);
            $extra->execute();
            $result = $extra->get_result();

            while ($row = $result->fetch_assoc()) {
                $uid = (int) $row["user_id"];

                if (!isset($seen[$uid])) {
                    $row["role"] = "member";
                    $row["joined_at"] = null;
                    $members[] = $row;
                    $seen[$uid] = true;
                }
            }

            $extra->close();
        }
    }

    return $members;
}

function calculate_month_accounts(mysqli $conn, int $mess_id, int $month_id): ?array
{
    $month_row = get_month_row($conn, $mess_id, $month_id);

    if (!$month_row) {
        return null;
    }

    $meal_totals = month_meal_totals($conn, $mess_id, $month_id);
    $total_meals = $meal_totals["meals"];
    $total_meal_cost = month_meal_cost($conn, $mess_id, $month_id);
    $shared_cost = month_other_cost($conn, $mess_id, $month_id, "joint");
    $individual_cost = month_other_cost($conn, $mess_id, $month_id, "individual");
    $total_cost = money_round($total_meal_cost + $shared_cost + $individual_cost);
    $total_deposit = month_deposits_total($conn, $mess_id, $month_id, $month_row);
    $meal_rate = ($total_meals > 0) ? money_round($total_meal_cost / $total_meals) : 0.0;
    $mess_balance = money_round($total_deposit - $total_cost);

    $people = accounting_members_for_month($conn, $mess_id, $month_id);
    $share_count = max(count($people), 1);
    $shared_each = money_round($shared_cost / $share_count);

    $rows = [];

    foreach ($people as $person) {
        $uid = (int) $person["user_id"];
        $meals = member_month_meals($conn, $mess_id, $month_id, $uid);
        $deposit = member_month_deposit($conn, $mess_id, $month_id, $uid, $month_row);
        $meal_cost = money_round($meals * $meal_rate);
        $indiv = member_individual_cost($conn, $mess_id, $month_id, $uid);
        $member_cost = money_round($meal_cost + $shared_each + $indiv);
        $balance = money_round($deposit - $member_cost);

        $rows[] = [
            "user_id" => $uid,
            "full_name" => $person["full_name"],
            "meals" => $meals,
            "deposit" => $deposit,
            "meal_cost" => $meal_cost,
            "shared" => $shared_each,
            "individual" => $indiv,
            "total_cost" => $member_cost,
            "balance" => $balance,
        ];
    }

    return [
        "month" => $month_row,
        "breakfast" => $meal_totals["breakfast"],
        "lunch" => $meal_totals["lunch"],
        "dinner" => $meal_totals["dinner"],
        "total_meals" => $total_meals,
        "total_meal_cost" => $total_meal_cost,
        "meal_rate" => $meal_rate,
        "shared_cost" => $shared_cost,
        "individual_cost" => $individual_cost,
        "total_expenses" => $total_cost,
        "total_cost" => $total_cost,
        "total_deposits" => $total_deposit,
        "mess_balance" => $mess_balance,
        "members" => $rows,
    ];
}
