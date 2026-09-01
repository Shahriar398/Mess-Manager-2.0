<?php

// Connect to the Mess Manager MySQL database (XAMPP).
$conn = mysqli_connect(
    "127.0.0.1",
    "root",
    "",
    "mess manager 2.0",
);

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}
