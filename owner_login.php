<?php
session_start();

require_once "config/database.php";

$error = "";
$username = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    // ตรวจสอบข้อมูลฝั่ง PHP อีกครั้ง
    if ($username === "") {
        $error = "กรุณากรอกชื่อผู้ใช้";
    } elseif ($password === "") {
        $error = "กรุณากรอกรหัสผ่าน";
    } else {

        $stmt = $conn->prepare(
            "SELECT user_id, username, password
             FROM users
             WHERE username = ?"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            // ปัจจุบันฐานข้อมูลใช้รหัสผ่านแบบธรรมดา
            if ($password === $user["password"]) {

                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["username"] = $user["username"];

                header("Location: owner_home.php");
                exit();
            } else {
                $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
            }
        } else {
            $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>เข้าสู่ระบบเจ้าของร้าน | เชื่อใจ</title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --primary: #5AA6CF;
            --primary-dark: #438FB8;
            --primary-light: #E8F5FC;

            --background: #E6D2BC;
            --white: #FFFFFF;

            --text: #263238;
            --text-light: #7A858C;

            --border: #E8EDF0;
            --error: #D9534F;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Prompt", sans-serif;

            background: var(--background);

            color: var(--text);

            min-height: 100vh;
        }

        .app {
            width: 100%;
            max-width: 490px;

            min-height: 100vh;

            margin: 0 auto;

            background: var(--white);

            position: relative;

            overflow: hidden;
        }


        /* =========================
           Back Button
        ========================= */

        .back-button {
            position: absolute;

            top: 18px;
            left: 30px;

            width: 40px;
            height: 40px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: var(--primary-light);

            color: var(--primary-dark);

            border-radius: 50%;

            text-decoration: none;

            font-size: 25px;

            transition: 0.2s;
        }

        .back-button:hover {
            background: #DCEFF8;
        }


        /* =========================
           Login Container
        ========================= */

        .login-container {
            padding: 95px 30px 30px;
        }


        /* =========================
           Logo
        ========================= */

        .logo {
            width: 220px;
            height: 100px;

            margin: 0 auto;

            display: flex;
            align-items: center;
            justify-content: center;

            overflow: hidden;
        }

        .logo img {
            width: 220px;
            height: auto;

            max-width: 100%;

            display: block;
        }


        /* =========================
           Title
        ========================= */

        .login-title {
            text-align: center;
            margin-top: 4px;
            margin-bottom: 0;
        }

        .login-title h1 {
            display: inline-block;

            font-size: 26px;
            font-weight: 600;

            color: var(--primary-dark);

            letter-spacing: 0.3px;

            margin-bottom: 7px;

            position: relative;
        }

        /* เส้นตกแต่งใต้หัวข้อ */
        .login-title h1::after {
            content: "";

            display: block;

            width: 38px;
            height: 3px;

            margin: 8px auto 0;

            background: var(--primary);

            border-radius: 10px;

            box-shadow:
                0 2px 5px rgba(90, 166, 207, 0.25);
        }

        .login-title p {
            font-size: 13px;

            font-weight: 400;

            color: var(--text-light);

            letter-spacing: 0.2px;

            margin-top: 5px;
        }


        /* =========================
           Form
        ========================= */

        .login-form {
            margin-top: 68px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;

            font-size: 14px;

            font-weight: 400;

            margin-bottom: 7px;
        }


        /* =========================
           Input
        ========================= */

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;

            height: 56px;

            border: 1px solid var(--border);

            border-radius: 15px;

            padding: 0 16px;

            font-family: "Prompt", sans-serif;

            font-size: 14px;

            color: var(--text);

            outline: none;

            transition: 0.2s;

            background: var(--white);
        }

        .form-input:focus {
            border-color: var(--primary);

            box-shadow:
                0 0 0 2px rgba(90, 166, 207, 0.12);
        }

        .form-input::placeholder {
            color: #AAB3B8;

            font-weight: 300;
        }

        .password-input {
            padding-right: 52px;
        }


        /* =========================
           Eye Button
        ========================= */

        .toggle-password {
            position: absolute;

            right: 15px;
            top: 50%;

            transform: translateY(-50%);

            width: 30px;
            height: 30px;

            border: none;

            background: transparent;

            cursor: pointer;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 0;

            color: var(--text-light);
        }

        .toggle-password:hover {
            color: var(--primary-dark);
        }

        .toggle-password svg {
            width: 20px;
            height: 20px;

            fill: none;

            stroke: currentColor;

            stroke-width: 1.8;

            stroke-linecap: round;

            stroke-linejoin: round;
        }

        .toggle-password:hover {
            color: var(--primary-dark);
        }


        /* =========================
           Error Message
        ========================= */

        .field-error {
            display: none;

            margin-top: 6px;

            font-size: 12px;

            color: var(--error);
        }

        .field-error.show {
            display: block;
        }

        .form-input.input-error {
            border-color: var(--error);
        }


        /* =========================
           Login Error From PHP
        ========================= */

        .login-error {
            margin-top: -5px;

            margin-bottom: 15px;

            padding: 10px 12px;

            border-radius: 10px;

            background: #FFF3F2;

            color: var(--error);

            font-size: 12px;

            text-align: center;
        }


        /* =========================
           Login Button
        ========================= */

        .login-button {
            width: 100%;

            height: 52px;

            border: none;

            border-radius: 15px;

            background: var(--primary);

            color: var(--white);

            font-family: "Prompt", sans-serif;

            font-size: 16px;

            font-weight: 500;

            cursor: pointer;

            box-shadow:
                0 7px 15px rgba(90, 166, 207, 0.18);

            transition: 0.2s;
        }

        .login-button:hover {
            background: var(--primary-dark);

            transform: translateY(-1px);
        }

        .login-button:active {
            transform: translateY(0);
        }


        /* =========================
           Footer
        ========================= */

        .footer {
            position: absolute;

            bottom: 28px;

            left: 0;
            right: 0;

            text-align: center;

            font-size: 10px;

            font-weight: 300;

            color: #AAB3B8;
        }


        /* =========================
           Desktop
        ========================= */

        @media (min-width: 768px) {

            body {
                display: flex;

                align-items: center;
                justify-content: center;

                padding: 30px 0;
            }

            .app {
                min-height: 760px;

                height: 850px;

                border-radius: 34px;

                box-shadow:
                    0 20px 50px rgba(38, 50, 56, 0.15);
            }

        }
    </style>

</head>


<body>

    <div class="app">


        <!-- Back Button -->

        <a
            href="index.php"
            class="back-button"
            aria-label="ย้อนกลับ">
            ‹
        </a>


        <main class="login-container">


            <!-- Logo -->

            <div class="logo">

                <img
                    src="assets/logo.png"
                    alt="CHEUJAI Digital Credit Book">

            </div>


            <!-- Title -->

            <div class="login-title">

                <h1>
                    เข้าสู่ระบบ
                </h1>

                <p>
                    สำหรับเจ้าของร้าน
                </p>

            </div>


            <!-- Login Form -->

            <form
                class="login-form"
                method="POST"
                action=""
                id="loginForm"
                novalidate>


                <!-- Username -->

                <div class="form-group">

                    <label
                        for="username"
                        class="form-label">
                        ชื่อผู้ใช้
                    </label>

                    <div class="input-wrapper">

                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-input"
                            placeholder="กรอกชื่อผู้ใช้"
                            value="<?php echo htmlspecialchars($username); ?>"
                            autocomplete="username">

                    </div>

                    <p
                        class="field-error"
                        id="usernameError">
                        กรุณากรอกชื่อผู้ใช้
                    </p>

                </div>


                <!-- Password -->

                <div class="form-group">

                    <label
                        for="password"
                        class="form-label">
                        รหัสผ่าน
                    </label>

                    <div class="input-wrapper">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input password-input"
                            placeholder="กรอกรหัสผ่าน"
                            autocomplete="current-password">

                        <button
                            type="button"
                            class="toggle-password"
                            id="togglePassword"
                            aria-label="แสดงรหัสผ่าน">
                            <svg
                                id="eyeIcon"
                                viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>

                    </div>

                    <p
                        class="field-error"
                        id="passwordError">
                        กรุณากรอกรหัสผ่าน
                    </p>

                </div>


                <?php if ($error !== ""): ?>

                    <div class="login-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>

                <?php endif; ?>


                <!-- Login Button -->

                <button
                    type="submit"
                    class="login-button">
                    เข้าสู่ระบบ
                </button>


            </form>

        </main>


        <!-- Footer -->

        <div class="footer">
            CHEUJAI Digital Credit Book
        </div>


    </div>


    <script>
        const form = document.getElementById("loginForm");

        const username = document.getElementById("username");

        const password = document.getElementById("password");

        const usernameError = document.getElementById("usernameError");

        const passwordError = document.getElementById("passwordError");

        const togglePassword = document.getElementById("togglePassword");


        /* =========================
           Validate Form
        ========================= */

        form.addEventListener("submit", function(event) {

            let valid = true;


            // Username

            if (username.value.trim() === "") {

                usernameError.classList.add("show");

                username.classList.add("input-error");

                valid = false;

            } else {

                usernameError.classList.remove("show");

                username.classList.remove("input-error");

            }


            // Password

            if (password.value === "") {

                passwordError.classList.add("show");

                password.classList.add("input-error");

                valid = false;

            } else {

                passwordError.classList.remove("show");

                password.classList.remove("input-error");

            }


            if (!valid) {

                event.preventDefault();

            }

        });


        /* =========================
           Remove Error While Typing
        ========================= */

        username.addEventListener("input", function() {

            if (username.value.trim() !== "") {

                usernameError.classList.remove("show");

                username.classList.remove("input-error");

            }

        });


        password.addEventListener("input", function() {

            if (password.value !== "") {

                passwordError.classList.remove("show");

                password.classList.remove("input-error");

            }

        });


        /* =========================
           Show / Hide Password
        ========================= */

        togglePassword.addEventListener("click", function() {

            if (password.type === "password") {

                password.type = "text";

                togglePassword.innerHTML = `
            <svg
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path d="M3 3l18 18"></path>
                <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                <path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a17.8 17.8 0 0 1-3.1 3.8"></path>
                <path d="M6.2 6.2C3.6 8.1 2 12 2 12s3.5 7 10 7c1.3 0 2.5-.2 3.5-.6"></path>
            </svg>
        `;

                togglePassword.setAttribute(
                    "aria-label",
                    "ซ่อนรหัสผ่าน"
                );

            } else {

                password.type = "password";

                togglePassword.innerHTML = `
            <svg
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
        `;

                togglePassword.setAttribute(
                    "aria-label",
                    "แสดงรหัสผ่าน"
                );

            }

        });
    </script>

</body>

</html>