<?php

function table_has_column(mysqli $conn, string $table, string $column): bool
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

    $sql = "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'";
    $result = $conn->query($sql);

    return $result && $result->num_rows > 0;
}

function ensure_extended_schema(mysqli $conn): void
{
    if (!table_has_column($conn, "messes", "description")) {
        $conn->query("ALTER TABLE `messes` ADD COLUMN IF NOT EXISTS `description` TEXT NULL");
    }

    if (!table_has_column($conn, "messes", "address")) {
        $conn->query("ALTER TABLE `messes` ADD COLUMN IF NOT EXISTS `address` VARCHAR(255) NULL");
    }

    if (!table_has_column($conn, "mess_members", "status")) {
        $conn->query("ALTER TABLE `mess_members` ADD COLUMN IF NOT EXISTS `status` ENUM('active','left') NOT NULL DEFAULT 'active'");
    }

    if (!table_has_column($conn, "mess_members", "left_at")) {
        $conn->query("ALTER TABLE `mess_members` ADD COLUMN IF NOT EXISTS `left_at` TIMESTAMP NULL DEFAULT NULL");
    }

    if (!table_has_column($conn, "deposits", "month_id")) {
        $conn->query("ALTER TABLE `deposits` ADD COLUMN IF NOT EXISTS `month_id` INT NULL DEFAULT NULL AFTER `mess_id`");
    }
}

function money_round($value): float
{
    return round((float) $value, 2);
}

function money_fmt($value): string
{
    $amount = money_round($value);

    if (abs($amount) < 0.005) {
        $amount = 0.0;
    }

    return number_format($amount, 2);
}
