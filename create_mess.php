<?php

session_start();
include __DIR__ . "/db.php";

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["status" => "error", "message" => "Please login first."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = $_SESSION["user_id"];
    $mess_name = trim($_POST["mess_name"] ?? "");
    $month_name = trim($_POST["month_name"] ?? "");
    $role = $_POST["role"] ?? "manager";

    if (empty($mess_name) || empty($month_name)) {
        echo json_encode(["status" => "error", "message" => "সব তথ্য পূরণ করুন।"]);
        exit;
    }

    $conn->begin_transaction();

    try {
        $stmt1 = $conn->prepare("INSERT INTO messes (mess_name, created_by) VALUES (?, ?)");
        $stmt1->bind_param("si", $mess_name, $user_id);
        $stmt1->execute();
        $mess_id = $stmt1->insert_id;

        $stmt2 = $conn->prepare(
            "INSERT INTO mess_months (mess_id, month_name, status) VALUES (?, ?, 'active')"
        );
        $stmt2->bind_param("is", $mess_id, $month_name);
        $stmt2->execute();

        $stmt3 = $conn->prepare(
            "INSERT INTO mess_members (mess_id, user_id, role) VALUES (?, ?, ?)"
        );
        $stmt3->bind_param("iis", $mess_id, $user_id, $role);
        $stmt3->execute();

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Mess created successfully"]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
}
