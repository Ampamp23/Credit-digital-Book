<?php
session_start();

require_once "config/database.php";

/* =========================
   ตรวจสอบการเข้าสู่ระบบ
========================= */

if (!isset($_SESSION["customer_id"])) {
    header("Location: customer_login.php");
    exit();
}

$customer_id = $_SESSION["customer_id"];


/* =========================
   ข้อมูลลูกค้า
========================= */

$stmt = $conn->prepare("
    SELECT
        customer_id,
        customer_name,
        phone_number,
        credit_limit
    FROM customers
    WHERE customer_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $customer_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    session_destroy();
    header("Location: customer_login.php");
    exit();
}

$customer = $result->fetch_assoc();

$stmt->close();


/* =========================
   ยอดค้างชำระ
========================= */

$stmt = $conn->prepare("
    SELECT
        COALESCE(SUM(outstanding_amount), 0) AS outstanding_amount
    FROM credit_rounds
    WHERE customer_id = ?
      AND payment_status <> 'ชำระครบ'
");

$stmt->bind_param("i", $customer_id);
$stmt->execute();

$outstanding_result = $stmt->get_result();
$outstanding_data = $outstanding_result->fetch_assoc();

$outstanding_amount =
    (int)$outstanding_data["outstanding_amount"];

$stmt->close();


/* =========================
   วงเงินคงเหลือ
========================= */

$remaining_credit =
    (int)$customer["credit_limit"] - $outstanding_amount;

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>โปรไฟล์ | เชื่อใจ</title>


    <link
        rel="preconnect"
        href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
        crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap"
        rel="stylesheet">


    <style>

        :root {

            --primary: #5AA6CF;
            --primary-dark: #438FB8;
            --primary-light: #E8F5FC;

            --background: #F7FAFC;
            --white: #FFFFFF;

            --text: #263238;
            --text-light: #7A858C;

            --border: #E8EDF0;

            --danger: #D9534F;
        }


        * {

            box-sizing: border-box;

            margin: 0;

            padding: 0;
        }


        body {

            font-family: "Prompt", sans-serif;

            background: #E6D2BC;

            color: var(--text);

            min-height: 100vh;
        }


        .app {

            width: 100%;

            max-width: 490px;

            min-height: 100vh;

            margin: 0 auto;

            background: var(--background);

            position: relative;

            overflow-x: hidden;

            padding-bottom: 90px;
        }


        /* =========================
           Header
        ========================= */

        .header {

            background: var(--white);

            padding: 22px 22px 18px;

            border-bottom: 1px solid var(--border);
        }


        .header h1 {

            font-size: 22px;

            font-weight: 600;

            color: var(--text);
        }


        .header p {

            font-size: 12px;

            color: var(--text-light);

            margin-top: 2px;
        }


        /* =========================
           Content
        ========================= */

        .content {

            padding: 20px;
        }


        /* =========================
           Profile Header
        ========================= */

        .profile-card {

            background: linear-gradient(
                135deg,
                #5AA6CF,
                #438FB8
            );

            color: white;

            border-radius: 22px;

            padding: 24px 20px;

            margin-bottom: 18px;

            text-align: center;

            box-shadow:
                0 8px 20px rgba(90, 166, 207, 0.18);
        }


        .profile-icon {

            width: 70px;

            height: 70px;

            margin: 0 auto 12px;

            border-radius: 50%;

            background: rgba(255,255,255,0.20);

            display: flex;

            align-items: center;

            justify-content: center;
        }


        .profile-icon svg {

            width: 38px;

            height: 38px;

            fill: none;

            stroke: white;

            stroke-width: 1.5;

            stroke-linecap: round;

            stroke-linejoin: round;
        }


        .profile-name {

            font-size: 21px;

            font-weight: 600;
        }


        .profile-role {

            font-size: 12px;

            opacity: 0.9;

            margin-top: 3px;
        }


        /* =========================
           Information
        ========================= */

        .section-title {

            font-size: 17px;

            font-weight: 600;

            margin-bottom: 12px;
        }


        .info-card {

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 20px;

            overflow: hidden;

            margin-bottom: 20px;
        }


        .info-row {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 16px 18px;

            border-bottom: 1px solid var(--border);
        }


        .info-row:last-child {

            border-bottom: none;
        }


        .info-left {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .info-icon {

            width: 38px;

            height: 38px;

            border-radius: 11px;

            background: var(--primary-light);

            color: var(--primary-dark);

            display: flex;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;
        }


        .info-icon svg {

            width: 20px;

            height: 20px;

            fill: none;

            stroke: currentColor;

            stroke-width: 1.7;

            stroke-linecap: round;

            stroke-linejoin: round;
        }


        .info-label {

            font-size: 11px;

            color: var(--text-light);
        }


        .info-value {

            font-size: 14px;

            font-weight: 500;

            margin-top: 2px;
        }


        /* =========================
           Credit Summary
        ========================= */

        .credit-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 12px;

            margin-bottom: 20px;
        }


        .credit-card {

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 18px;

            padding: 17px;
        }


        .credit-label {

            font-size: 12px;

            color: var(--text-light);

            margin-bottom: 8px;
        }


        .credit-value {

            font-size: 21px;

            font-weight: 600;

            color: var(--primary-dark);
        }


        .credit-unit {

            font-size: 11px;

            font-weight: 400;

            margin-left: 2px;
        }


        /* =========================
           Logout
        ========================= */

        .logout-button {

            width: 100%;

            height: 50px;

            border: 1px solid #F0D8D7;

            border-radius: 15px;

            background: #FFF7F7;

            color: var(--danger);

            font-family: "Prompt", sans-serif;

            font-size: 14px;

            font-weight: 500;

            cursor: pointer;
        }


        .logout-button:hover {

            background: #FFF0F0;
        }


        /* =========================
           Bottom Navigation
        ========================= */

        .bottom-nav {

            position: fixed;

            bottom: 0;

            left: 50%;

            transform: translateX(-50%);

            width: 100%;

            max-width: 490px;

            height: 78px;

            background: #FFFFFF;

            border-top: 1px solid var(--border);

            display: flex;

            align-items: center;

            justify-content: space-around;

            z-index: 100;
        }


        .nav-item {

            flex: 1;

            height: 100%;

            text-decoration: none;

            color: #A0A9AE;

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            gap: 5px;

            font-family: "Prompt", sans-serif;

            font-size: 11px;

            font-weight: 400;
        }


        .nav-item.active {

            color: var(--primary-dark);

            font-weight: 500;
        }


        .nav-icon {

            width: 23px;

            height: 23px;

            fill: none;

            stroke: currentColor;

            stroke-width: 1.7;

            stroke-linecap: round;

            stroke-linejoin: round;
        }


        /* =========================
           Desktop
        ========================= */

        @media (min-width: 768px) {

            body {

                display: flex;

                justify-content: center;

                padding: 30px 0;
            }


            .app {

                min-height: 850px;

                border-radius: 34px;

                box-shadow:
                    0 20px 50px rgba(38, 50, 56, 0.15);
            }


            .bottom-nav {

                position: absolute;

                bottom: 0;

                border-radius:
                    0 0 34px 34px;
            }

        }

    </style>

</head>


<body>


<div class="app">


    <!-- =========================
         Header
    ========================= -->

    <header class="header">

        <h1>
            โปรไฟล์
        </h1>

        <p>
            ข้อมูลบัญชีของคุณ
        </p>

    </header>


    <!-- =========================
         Content
    ========================= -->

    <main class="content">


        <!-- Profile -->

        <section class="profile-card">


            <div class="profile-icon">

                <svg viewBox="0 0 24 24">

                    <circle
                        cx="12"
                        cy="8"
                        r="3.5">
                    </circle>

                    <path
                        d="M5 21c.8-3.7 3.2-5.5 7-5.5s6.2 1.8 7 5.5">
                    </path>

                </svg>

            </div>


            <div class="profile-name">

                <?= htmlspecialchars(
                    $customer["customer_name"]
                ) ?>

            </div>


            <div class="profile-role">

                ลูกค้า

            </div>


        </section>


        <!-- Personal Information -->

        <section>

            <div class="section-title">

                ข้อมูลส่วนตัว

            </div>


            <div class="info-card">


                <!-- Name -->

                <div class="info-row">

                    <div class="info-left">

                        <div class="info-icon">

                            <svg viewBox="0 0 24 24">

                                <circle
                                    cx="12"
                                    cy="8"
                                    r="3.5">
                                </circle>

                                <path
                                    d="M5 21c.8-3.7 3.2-5.5 7-5.5s6.2 1.8 7 5.5">
                                </path>

                            </svg>

                        </div>


                        <div>

                            <div class="info-label">
                                ชื่อ-นามสกุล
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $customer["customer_name"]
                                ) ?>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Phone -->

                <div class="info-row">

                    <div class="info-left">

                        <div class="info-icon">

                            <svg viewBox="0 0 24 24">

                                <path
                                    d="M6.5 3.5h3l1.5 4-2 1.5c1 2.1 2.4 3.5 4.5 4.5l1.5-2 4 1.5v3c0 1-0.8 1.5-1.8 1.5C11 17.5 6.5 13 6.5 7.3c0-1 .5-1.8 1.5-1.8">
                                </path>

                            </svg>

                        </div>


                        <div>

                            <div class="info-label">
                                เบอร์โทรศัพท์
                            </div>

                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $customer["phone_number"]
                                ) ?>

                            </div>

                        </div>

                    </div>

                </div>


            </div>

        </section>


        <!-- Credit Information -->

        <section>

            <div class="section-title">

                ข้อมูลเครดิต

            </div>


            <div class="credit-grid">


                <div class="credit-card">

                    <div class="credit-label">
                        วงเงินเครดิต
                    </div>

                    <div class="credit-value">

                        <?= number_format(
                            $customer["credit_limit"]
                        ) ?>

                        <span class="credit-unit">
                            บาท
                        </span>

                    </div>

                </div>


                <div class="credit-card">

                    <div class="credit-label">
                        ยอดค้างชำระ
                    </div>

                    <div class="credit-value">

                        <?= number_format(
                            $outstanding_amount
                        ) ?>

                        <span class="credit-unit">
                            บาท
                        </span>

                    </div>

                </div>


            </div>

        </section>


        <!-- Logout -->

        <form
            action="customer_logout.php"
            method="post">

            <button
                type="submit"
                class="logout-button">

                ออกจากระบบ

            </button>

        </form>


    </main>


    <!-- =========================
         Bottom Navigation
    ========================= -->

    <nav class="bottom-nav">


        <!-- หน้าหลัก -->

        <a
            href="customer_home.php"
            class="nav-item">

            <svg
                viewBox="0 0 24 24"
                class="nav-icon">

                <path d="M3 10.5L12 3l9 7.5"></path>

                <path d="M5.5 9.5V21h13V9.5"></path>

                <path d="M9.5 21v-6h5v6"></path>

            </svg>

            <span>
                หน้าหลัก
            </span>

        </a>


        <!-- ประวัติ -->

        <a
            href="customer_history.php"
            class="nav-item">

            <svg
                viewBox="0 0 24 24"
                class="nav-icon">

                <circle
                    cx="12"
                    cy="12"
                    r="8.5">
                </circle>

                <path d="M12 7v5l3 2"></path>

            </svg>

            <span>
                ประวัติ
            </span>

        </a>


        <!-- โปรไฟล์ -->

        <a
            href="customer_profile.php"
            class="nav-item active">

            <svg
                viewBox="0 0 24 24"
                class="nav-icon">

                <circle
                    cx="12"
                    cy="8"
                    r="3.5">
                </circle>

                <path
                    d="M5 21c.8-3.7 3.2-5.5 7-5.5s6.2 1.8 7 5.5">
                </path>

            </svg>

            <span>
                โปรไฟล์
            </span>

        </a>


    </nav>


</div>


</body>

</html>