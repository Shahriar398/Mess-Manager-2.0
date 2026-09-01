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
    $mess_id = trim($_POST["mess_code"] ?? "");

    if (empty($mess_id)) {
        echo json_encode(["status" => "error", "message" => "মেস কোড প্রদান করুন।"]);
        exit;
    }

    try {
        // Check that this Mess ID exists.
        $check_mess = $conn->prepare("SELECT mess_id FROM messes WHERE mess_id = ?");
        $check_mess->bind_param("i", $mess_id);
        $check_mess->execute();
        $mess_result = $check_mess->get_result();

        if ($mess_result->num_rows === 0) {
            echo json_encode(["status" => "error", "message" => "ভুল মেস কোড! এই নামে কোনো মেস নেই।"]);
            exit;
        }

        // Check that the user is not already in this mess.
        $check_user = $conn->prepare(
            "SELECT member_id FROM mess_members WHERE user_id = ? AND mess_id = ?"
        );
        $check_user->bind_param("ii", $user_id, $mess_id);
        $check_user->execute();
        $user_result = $check_user->get_result();

        if ($user_result->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "আপনি আগে থেকেই এই মেসে যুক্ত আছেন।"]);
            exit;
        }

        $role = "member";
        $stmt = $conn->prepare(
            "INSERT INTO mess_members (mess_id, user_id, role) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iis", $mess_id, $user_id, $role);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "সফলভাবে মেসে যুক্ত হয়েছেন!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "যুক্ত হতে সমস্যা হয়েছে।"]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
}
