<?php

/**
 * Shared authentication and mess-access helpers.
 * Include after session_start() and db.php.
 */

function require_login(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit();
    }
}

function get_logged_in_user_id(): int
{
    return (int) $_SESSION["user_id"];
}

function get_logged_in_user_name(): string
{
    return isset($_SESSION["name"]) ? $_SESSION["name"] : "User";
}

/**
 * Returns the user's first active mess, or null if none.
 */
function get_active_mess(mysqli $conn, int $user_id): ?array
{
    $query = "SELECT m.mess_id, m.mess_name, mm.month_id, mm.month_name
              FROM mess_members mem
              JOIN messes m ON mem.mess_id = m.mess_id
              JOIN mess_months mm ON m.mess_id = mm.mess_id
              WHERE mem.user_id = ? AND mm.status = 'active'";

    if (mess_members_has_status($conn)) {
        $query .= " AND mem.status = 'active'";
    }

    $query .= " LIMIT 1";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function user_belongs_to_mess(mysqli $conn, int $user_id, int $mess_id): bool
{
    $stmt = $conn->prepare(
        "SELECT member_id FROM mess_members WHERE mess_id = ? AND user_id = ? LIMIT 1"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ii", $mess_id, $user_id);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $exists;
}

function mess_members_has_status(mysqli $conn): bool
{
    $result = $conn->query("SHOW COLUMNS FROM mess_members LIKE 'status'");

    return $result && $result->num_rows > 0;
}

function user_is_active_member(mysqli $conn, int $user_id, int $mess_id): bool
{
    if (!mess_members_has_status($conn)) {
        return user_belongs_to_mess($conn, $user_id, $mess_id);
    }

    $stmt = $conn->prepare(
        "SELECT member_id FROM mess_members
         WHERE mess_id = ? AND user_id = ? AND status = 'active'
         LIMIT 1"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ii", $mess_id, $user_id);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $exists;
}

function user_is_manager(mysqli $conn, int $user_id, int $mess_id): bool
{
    $status_sql = mess_members_has_status($conn) ? " AND status = 'active'" : "";

    $stmt = $conn->prepare(
        "SELECT member_id FROM mess_members
         WHERE mess_id = ? AND user_id = ? AND role = 'manager'{$status_sql}
         LIMIT 1"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ii", $mess_id, $user_id);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $exists;
}

function get_mess_members(mysqli $conn, int $mess_id): array
{
    $members = [];
    $status_sql = mess_members_has_status($conn) ? " AND mem.status = 'active'" : "";

    $stmt = $conn->prepare(
        "SELECT u.user_id, u.full_name
         FROM mess_members mem
         JOIN users u ON mem.user_id = u.user_id
         WHERE mem.mess_id = ?{$status_sql}
         ORDER BY u.full_name ASC"
    );

    if (!$stmt) {
        return $members;
    }

    $stmt->bind_param("i", $mess_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }

    $stmt->close();

    return $members;
}

function get_active_month_id(mysqli $conn, int $mess_id): ?int
{
    $stmt = $conn->prepare(
        "SELECT month_id FROM mess_months
         WHERE mess_id = ? AND status = 'active'
         LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $mess_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? (int) $row["month_id"] : null;
}

function csrf_token(): string
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

function csrf_verify(): bool
{
    $posted = $_POST["csrf_token"] ?? "";
    $session_token = $_SESSION["csrf_token"] ?? "";

    return $posted !== ""
        && $session_token !== ""
        && hash_equals($session_token, $posted);
}

