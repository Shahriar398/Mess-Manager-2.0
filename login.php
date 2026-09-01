<?php

session_start();

include("db.php");

$email = "";
$password = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";

    if (empty($email)) {
        $error = "Enter Email";
    } else if (empty($password)) {
        $error = "Enter Password";
    } else {
        /*
         * users table:
         * user_id, full_name, email, phone, password_hash,
         * profile_image, status, created_at, updated_at
         *
         * The password is stored as a password HASH, so we must
         * use password_verify() instead of comparing:
         * password='$password'
         */

        $sql = "SELECT user_id, full_name, email, password_hash, status
                FROM users
                WHERE email = ?";

        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);

                // Do not allow inactive or blocked users to log in.
                if ($row["status"] !== "active") {
                    $error = "Your account is " . $row["status"] . ". Please contact the administrator.";
                } else if (password_verify($password, $row["password_hash"])) {
                    // Login successful
                    $_SESSION["user_id"] = $row["user_id"];
                    $_SESSION["name"] = $row["full_name"];
                    $_SESSION["email"] = $row["email"];

                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "Invalid Email or Password";
                }
            } else {
                $error = "Invalid Email or Password";
            }

            mysqli_stmt_close($stmt);
        } else {
            $error = "Database query failed.";
        }
    }
}

?>

<!DOCTYPE html>

<html>

<head>

    <title>Login - Mess Manager</title>

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="css/login.css">


</head>

<body>


    <!-- LANGUAGE BUTTON -->

    <button class="language" onclick="changeLanguage()">

        বং

    </button>


    <!-- MAIN -->

    <div class="main">


        <!-- LEFT SIDE -->

        <div class="left">


            <!-- PHONE -->

            <img
                src="src/img/login_img/login.webp"
                class="phones">


            <!-- BOTTOM LINKS -->

            <div class="bottom-links">


                <div class="bottom-item">

                    <a href="https://www.facebook.com/MessManagerM27Lab/?_rdr" target="_blank">

                        <i class="fa-brands fa-facebook"></i>

                        Our Facebook Page

                    </a>

                </div>


                <div class="bottom-item">

                    <a href="https://www.youtube.com/watch?v=dyEZAiEaQ1o" target="_blank">

                        <i class="fa-brands fa-youtube"></i>

                        How to use the App

                    </a>

                </div>


                <div class="bottom-item">

                    <a href="https://play.google.com/store/apps/details?id=com.m27lab.messmanager.app&pli=1" target="_blank">

                        <i class="fa-solid fa-mobile-screen-button"></i>

                        Mobile App

                    </a>

                </div>


            </div>


        </div>


        <!-- LOGIN CARD -->

        <div class="login-card">


            <!-- LOGO -->

            <div class="logo">

                <img src="src/img/login_img/logo.png" alt="">

            </div>


            <!-- TITLE -->

            <div class="title" id="title">

                Log in to your account

                <br>

                with Email

            </div>


            <!-- FORM -->

            <form method="post">


                <label class="form-label" id="email-label">

                    Your Email

                </label>


                <input
                    type="email"
                    name="email"
                    class="input-box"
                    id="email"
                    placeholder="Your Email"
                    value="<?php echo htmlspecialchars($email); ?>">


                <label class="form-label" id="password-label">

                    Enter Your Password

                </label>


                <input
                    type="password"
                    name="password"
                    class="input-box"
                    id="password"
                    placeholder="minimum 6 characters">


                <a href="forgot_password.php" class="forgot" id="forgot">

                    Forgot Password?

                </a>


                <?php

                if ($error != "") {

                    echo '<div class="error">' . htmlspecialchars($error) . '</div>';
                }

                ?>


                <input
                    type="submit"
                    value="Login"
                    class="login-button"
                    id="login-button">


            </form>


            <!-- DIVIDER -->

            <div class="divider">

                or

            </div>


            <!-- REGISTER -->

            <div class="register-text" id="register-text">

                Don't have an account in

                <span>Mess Manager?</span>

                <br>

                Create your account with Email

            </div>


            <a href="registration.php" class="create-button" id="create-button">

                Create Account

            </a>


        </div>


    </div>



    <script src="js/login.js"></script>

</body>

</html>