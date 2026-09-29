<?php
session_start();

require_once "config/database.php";

$error = "";
$phone = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $phone = trim($_POST["phone"] ?? "");

    // ตรวจสอบข้อมูลฝั่ง PHP อีกครั้ง
    if ($phone === "") {

        $error = "กรุณากรอกเบอร์โทรศัพท์";

    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {

        $error = "กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง";

    } else {

        $stmt = $conn->prepare(
            "SELECT customer_id, customer_name, phone_number
             FROM customers
             WHERE phone_number = ?"
        );

        $stmt->bind_param("s", $phone);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $customer = $result->fetch_assoc();

            $_SESSION["customer_id"] = $customer["customer_id"];
            $_SESSION["customer_name"] = $customer["customer_name"];
            $_SESSION["customer_phone"] = $customer["phone_number"];

            header("Location: customer_home.php");
            exit();

        } else {

            $error = "ไม่พบเบอร์โทรศัพท์นี้ในระบบ";
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

    <title>เข้าสู่ระบบลูกค้า | เชื่อใจ</title>

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
                    สำหรับลูกค้า
                </p>

            </div>


            <!-- Login Form -->

            <form
                class="login-form"
                method="POST"
                action=""
                id="loginForm"
                novalidate>


                <!-- Phone Number -->

                <div class="form-group">

                    <label
                        for="phone"
                        class="form-label">
                        เบอร์โทรศัพท์
                    </label>

                    <div class="input-wrapper">

                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            class="form-input"
                            placeholder="กรอกเบอร์โทรศัพท์"
                            value="<?php echo htmlspecialchars($phone); ?>"
                            maxlength="10"
                            inputmode="numeric"
                            autocomplete="tel">

                    </div>

                    <p
                        class="field-error"
                        id="phoneError">
                        กรุณากรอกเบอร์โทรศัพท์
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

        const phone = document.getElementById("phone");

        const phoneError = document.getElementById("phoneError");


        /* =========================
           Validate Form
        ========================= */

        form.addEventListener("submit", function(event) {

            let valid = true;

            const phoneValue = phone.value.trim();


            if (phoneValue === "") {

                phoneError.textContent =
                    "กรุณากรอกเบอร์โทรศัพท์";

                phoneError.classList.add("show");

                phone.classList.add("input-error");

                valid = false;

            } else if (!/^[0-9]{10}$/.test(phoneValue)) {

                phoneError.textContent =
                    "กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง";

                phoneError.classList.add("show");

                phone.classList.add("input-error");

                valid = false;

            } else {

                phoneError.classList.remove("show");

                phone.classList.remove("input-error");

            }


            if (!valid) {

                event.preventDefault();

            }

        });


        /* =========================
           Remove Error While Typing
        ========================= */

        phone.addEventListener("input", function() {

            // อนุญาตเฉพาะตัวเลข
            this.value = this.value.replace(/\D/g, "");


            if (/^[0-9]{10}$/.test(this.value)) {

                phoneError.classList.remove("show");

                phone.classList.remove("input-error");

            }

        });

    </script>

</body>

</html>