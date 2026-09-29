```php
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
$customer = $result->fetch_assoc();

$stmt->close();


if (!$customer) {
    session_destroy();
    header("Location: customer_login.php");
    exit();
}


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

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$outstanding_amount = (int)$row["outstanding_amount"];

$stmt->close();


/* =========================
   วงเงินคงเหลือ
========================= */

$credit_limit = (int)$customer["credit_limit"];

$remaining_credit = $credit_limit - $outstanding_amount;

if ($remaining_credit < 0) {
    $remaining_credit = 0;
}


/* =========================================================
   ระบบแจ้งเตือน
========================================================= */

$notifications = [];

$today = new DateTime();
$today->setTime(0, 0, 0);


/* =========================
   ดึงรอบเครดิต
========================= */

$stmt = $conn->prepare("
    SELECT
        credit_round_id,
        due_date,
        credit_limit,
        outstanding_amount,
        payment_status
    FROM credit_rounds
    WHERE customer_id = ?
    ORDER BY credit_round_id DESC
");

$stmt->bind_param("i", $customer_id);
$stmt->execute();

$round_result = $stmt->get_result();


while ($round = $round_result->fetch_assoc()) {

    $outstanding = (int)$round["outstanding_amount"];

    if ($outstanding <= 0) {
        continue;
    }


    /* =========================
       วันครบกำหนด
    ========================= */

    $due_date = new DateTime($round["due_date"]);
    $due_date->setTime(0, 0, 0);


    $days_difference =
        (int)$today->diff($due_date)->format("%r%a");


    /* =========================
       เลยกำหนดชำระ
    ========================= */

    if ($due_date < $today) {

        $notifications[] = [

            "type" => "overdue",

            "icon" => "⚠️",

            "title" => "เลยกำหนดชำระ",

            "message" =>
                "รอบเครดิตของคุณเลยกำหนดชำระแล้ว " .
                number_format($outstanding) .
                " บาท"

        ];

        continue;
    }


    /* =========================
       ใกล้ถึงกำหนดชำระ
       ภายใน 3 วัน
    ========================= */

    if ($days_difference >= 0 && $days_difference <= 3) {

        if ($days_difference == 0) {

            $message =
                "วันนี้เป็นวันครบกำหนดชำระ " .
                number_format($outstanding) .
                " บาท";

        } elseif ($days_difference == 1) {

            $message =
                "รอบเครดิตของคุณครบกำหนดชำระในวันพรุ่งนี้ " .
                number_format($outstanding) .
                " บาท";

        } else {

            $message =
                "รอบเครดิตของคุณครบกำหนดชำระในอีก " .
                $days_difference .
                " วัน";

        }


        $notifications[] = [

            "type" => "due",

            "icon" => "🔔",

            "title" => "ใกล้ถึงวันครบกำหนดชำระ",

            "message" => $message

        ];

    }

}

$stmt->close();


/* =========================
   วงเงินเครดิตเต็ม
========================= */

if (
    $credit_limit > 0 &&
    $outstanding_amount >= $credit_limit
) {

    $notifications[] = [

        "type" => "limit",

        "icon" => "💳",

        "title" => "วงเงินเครดิตเต็ม",

        "message" =>
            "คุณใช้วงเงินเครดิตครบ " .
            number_format($credit_limit) .
            " บาทแล้ว"

    ];

}


/* =========================================================
   ตรวจสอบรอบที่ชำระครบแล้ว
========================================================= */

$stmt = $conn->prepare("
    SELECT
        credit_round_id,
        due_date
    FROM credit_rounds
    WHERE customer_id = ?
      AND payment_status = 'ชำระครบ'
    ORDER BY credit_round_id DESC
    LIMIT 1
");

$stmt->bind_param("i", $customer_id);
$stmt->execute();

$paid_result = $stmt->get_result();

$paid_round = $paid_result->fetch_assoc();

$stmt->close();


if ($paid_round) {

    $notifications[] = [

        "type" => "paid",

        "icon" => "✅",

        "title" => "ชำระเงินครบแล้ว",

        "message" =>
            "คุณชำระยอดค้างชำระของรอบเครดิตครบแล้ว"

    ];

}


/* =========================================================
   ระบบอ่านแจ้งเตือน
========================================================= */

$notification_count = count($notifications);

if (
    !isset($_SESSION["notification_viewed"])
) {

    $_SESSION["notification_viewed"] = false;

}


$show_badge =
    ($notification_count > 0 &&
     $_SESSION["notification_viewed"] === false);


/* =========================================================
   รายการล่าสุด
========================================================= */

$stmt = $conn->prepare("
    SELECT
        cp.credit_purchase_id AS transaction_id,
        cp.credit_purchase_date AS transaction_date,
        cp.credit_purchase_amount AS amount,
        'ซื้อเชื่อ' AS transaction_type
    FROM credit_purchases cp
    INNER JOIN credit_rounds cr
        ON cp.credit_round_id = cr.credit_round_id
    WHERE cr.customer_id = ?

    UNION ALL

    SELECT
        p.payment_id AS transaction_id,
        p.payment_date AS transaction_date,
        p.payment_amount AS amount,
        'ชำระเงิน' AS transaction_type
    FROM payments p
    INNER JOIN credit_purchases cp
        ON p.credit_purchase_id = cp.credit_purchase_id
    INNER JOIN credit_rounds cr
        ON cp.credit_round_id = cr.credit_round_id
    WHERE cr.customer_id = ?

    ORDER BY transaction_date DESC, transaction_id DESC
    LIMIT 5
");

$stmt->bind_param(
    "ii",
    $customer_id,
    $customer_id
);

$stmt->execute();

$latest_result = $stmt->get_result();

$latest_items = [];

while ($item = $latest_result->fetch_assoc()) {
    $latest_items[] = $item;
}

$stmt->close();

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>หน้าหลัก | เชื่อใจ</title>


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

            padding-bottom: 95px;
        }


        /* =========================
           Header
        ========================= */

        .header {

            background: var(--white);

            padding: 22px 20px 18px;

            border-bottom: 1px solid var(--border);

            display: flex;

            align-items: center;

            justify-content: space-between;
        }


        .header-text h1 {

            font-size: 22px;

            font-weight: 600;
        }


        .header-text p {

            font-size: 12px;

            color: var(--text-light);

            margin-top: 2px;
        }


        /* =========================
           Notification Button
        ========================= */

        .notification-wrap {

            position: relative;
        }


        .notification-button {

            width: 50px;

            height: 50px;

            border: none;

            border-radius: 16px;

            background: var(--primary-light);

            color: var(--primary-dark);

            display: flex;

            align-items: center;

            justify-content: center;

            cursor: pointer;

            position: relative;
        }


        .notification-button svg {

            width: 29px;

            height: 29px;

            fill: none;

            stroke: currentColor;

            stroke-width: 1.7;

            stroke-linecap: round;

            stroke-linejoin: round;
        }


        .notification-badge {

            position: absolute;

            top: -3px;

            right: -3px;

            min-width: 22px;

            height: 22px;

            padding: 0 5px;

            border-radius: 99px;

            background: #D9534F;

            color: #FFFFFF;

            border: 2px solid #FFFFFF;

            font-size: 10px;

            font-weight: 600;

            display: flex;

            align-items: center;

            justify-content: center;
        }


        /* =========================
           Notification Popup
        ========================= */

        .notification-overlay {

            display: none;

            position: fixed;

            inset: 0;

            background: rgba(38, 50, 56, 0.30);

            z-index: 300;

            align-items: flex-start;

            justify-content: center;

            padding-top: 75px;
        }


        .notification-overlay.show {

            display: flex;
        }


        .notification-box {

            width: calc(100% - 30px);

            max-width: 460px;

            max-height: calc(100vh - 105px);

            background: #FFFFFF;

            border-radius: 24px;

            overflow: hidden;

            box-shadow:
                0 20px 45px rgba(38, 50, 56, 0.18);

            animation: notificationOpen 0.18s ease-out;
        }


        @keyframes notificationOpen {

            from {

                opacity: 0;

                transform: translateY(-8px);
            }

            to {

                opacity: 1;

                transform: translateY(0);
            }
        }


        .notification-header {

            padding: 18px 18px 15px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            border-bottom: 1px solid var(--border);
        }


        .notification-header h2 {

            font-size: 18px;

            font-weight: 600;
        }


        .notification-close {

            width: 38px;

            height: 38px;

            border: none;

            border-radius: 12px;

            background: #F4F6F7;

            color: var(--text-light);

            font-size: 22px;

            cursor: pointer;
        }


        .notification-list {

            padding: 10px;

            overflow-y: auto;

            max-height: calc(100vh - 180px);
        }


        .notification-item {

            display: flex;

            gap: 12px;

            padding: 14px;

            border-radius: 16px;

            margin-bottom: 7px;

            background: #F8FAFB;
        }


        .notification-item:last-child {

            margin-bottom: 0;
        }


        .notification-icon {

            width: 42px;

            height: 42px;

            min-width: 42px;

            border-radius: 13px;

            background: var(--primary-light);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 20px;
        }


        .notification-title {

            font-size: 13px;

            font-weight: 600;

            margin-bottom: 3px;
        }


        .notification-message {

            font-size: 11px;

            line-height: 1.55;

            color: var(--text-light);
        }


        .notification-empty {

            padding: 45px 20px;

            text-align: center;

            color: var(--text-light);

            font-size: 13px;
        }


        .notification-empty-icon {

            font-size: 34px;

            margin-bottom: 8px;
        }


        /* =========================
           Content
        ========================= */

        .content {

            padding: 20px;
        }


        /* =========================
           Welcome Card
        ========================= */

        .welcome-card {

            background:
                linear-gradient(
                    135deg,
                    #5AA6CF,
                    #438FB8
                );

            color: #FFFFFF;

            border-radius: 21px;

            padding: 20px;

            margin-bottom: 18px;

            box-shadow:
                0 8px 20px rgba(67, 143, 184, 0.16);
        }


        .welcome-label {

            font-size: 12px;

            font-weight: 400;

            opacity: 0.9;

            margin-bottom: 4px;
        }


        .welcome-name {

            font-size: 21px;

            font-weight: 600;

            line-height: 1.4;
        }


        /* =========================
           Credit Cards
        ========================= */

        .credit-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 12px;

            margin-bottom: 22px;
        }


        .credit-card {

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 19px;

            padding: 17px;
        }


        .credit-label {

            font-size: 11px;

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
        }


        /* =========================
           Latest
        ========================= */

        .section-title {

            font-size: 17px;

            font-weight: 600;

            margin-bottom: 12px;
        }


        .latest-card {

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 19px;

            overflow: hidden;
        }


        .latest-item {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 14px 16px;

            border-bottom: 1px solid var(--border);
        }


        .latest-item:last-child {

            border-bottom: none;
        }


        .latest-icon {

            width: 40px;

            height: 40px;

            min-width: 40px;

            border-radius: 12px;

            background: var(--primary-light);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 18px;
        }


        .latest-info {

            flex: 1;

            min-width: 0;
        }


        .latest-title {

            font-size: 13px;

            font-weight: 500;
        }


        .latest-date {

            font-size: 10px;

            color: var(--text-light);

            margin-top: 2px;
        }


        .latest-amount {

            font-size: 13px;

            font-weight: 600;

            white-space: nowrap;
        }


        .amount-credit {

            color: var(--primary-dark);
        }


        .amount-payment {

            color: #4E9A6E;
        }


        .empty-latest {

            padding: 30px 15px;

            text-align: center;

            color: var(--text-light);

            font-size: 12px;
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


            .notification-overlay {

                position: absolute;

                border-radius: 34px;
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

        <div class="header-text">

            <h1>
                หน้าหลัก
            </h1>

            <p>
                ข้อมูลการซื้อเชื่อของคุณ
            </p>

        </div>


        <!-- Notification -->

        <div class="notification-wrap">

            <button
                type="button"
                class="notification-button"
                id="notificationButton"
                aria-label="การแจ้งเตือน">

                <svg viewBox="0 0 24 24">

                    <path
                        d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9">
                    </path>

                    <path
                        d="M10 21h4">
                    </path>

                </svg>


                <?php if ($show_badge): ?>

                    <span
                        class="notification-badge"
                        id="notificationBadge">

                        <?= $notification_count > 99
                            ? "99+"
                            : $notification_count ?>

                    </span>

                <?php endif; ?>

            </button>

        </div>

    </header>


    <!-- =========================
         Content
    ========================= -->

    <main class="content">


        <!-- Welcome -->

        <section class="welcome-card">

            <div class="welcome-label">
                ยินดีต้อนรับ
            </div>

            <div class="welcome-name">
                <?= htmlspecialchars($customer["customer_name"]) ?>
            </div>

        </section>


        <!-- Credit -->

        <section class="credit-grid">


            <div class="credit-card">

                <div class="credit-label">
                    วงเงินเครดิต
                </div>

                <div class="credit-value">

                    <?= number_format($credit_limit) ?>

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

                    <?= number_format($outstanding_amount) ?>

                    <span class="credit-unit">
                        บาท
                    </span>

                </div>

            </div>


        </section>


        <!-- Latest -->

        <section>

            <div class="section-title">
                รายการล่าสุด
            </div>


            <div class="latest-card">


                <?php if (count($latest_items) > 0): ?>


                    <?php foreach ($latest_items as $item): ?>


                        <?php

                        $is_payment =
                            $item["transaction_type"] === "ชำระเงิน";

                        ?>


                        <div class="latest-item">


                            <div class="latest-icon">

                                <?= $is_payment
                                    ? "💳"
                                    : "🛒" ?>

                            </div>


                            <div class="latest-info">

                                <div class="latest-title">

                                    <?= htmlspecialchars(
                                        $item["transaction_type"]
                                    ) ?>

                                </div>


                                <div class="latest-date">

                                    <?= date(
                                        "d/m/Y",
                                        strtotime(
                                            $item["transaction_date"]
                                        )
                                    ) ?>

                                </div>

                            </div>


                            <div
                                class="latest-amount
                                <?= $is_payment
                                    ? "amount-payment"
                                    : "amount-credit" ?>">

                                <?= $is_payment ? "-" : "+" ?>

                                <?= number_format(
                                    $item["amount"]
                                ) ?>

                                บาท

                            </div>


                        </div>


                    <?php endforeach; ?>


                <?php else: ?>


                    <div class="empty-latest">

                        ยังไม่มีรายการ

                    </div>


                <?php endif; ?>


            </div>

        </section>


    </main>


    <!-- =========================
         Notification Overlay
    ========================= -->

    <div
        class="notification-overlay"
        id="notificationOverlay">


        <div class="notification-box">


            <div class="notification-header">

                <h2>
                    การแจ้งเตือน
                </h2>


                <button
                    type="button"
                    class="notification-close"
                    id="notificationClose"
                    aria-label="ปิด">

                    ×

                </button>

            </div>


            <div class="notification-list">


                <?php if (count($notifications) > 0): ?>


                    <?php foreach ($notifications as $notification): ?>


                        <div class="notification-item">


                            <div class="notification-icon">

                                <?= $notification["icon"] ?>

                            </div>


                            <div>

                                <div class="notification-title">

                                    <?= htmlspecialchars(
                                        $notification["title"]
                                    ) ?>

                                </div>


                                <div class="notification-message">

                                    <?= htmlspecialchars(
                                        $notification["message"]
                                    ) ?>

                                </div>

                            </div>


                        </div>


                    <?php endforeach; ?>


                <?php else: ?>


                    <div class="notification-empty">

                        <div class="notification-empty-icon">
                            🔔
                        </div>

                        ไม่มีการแจ้งเตือน

                    </div>


                <?php endif; ?>


            </div>


        </div>


    </div>


    <!-- =========================
         Bottom Navigation
    ========================= -->

    <nav class="bottom-nav">


        <!-- Home -->

        <a
            href="customer_home.php"
            class="nav-item active">

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


        <!-- History -->

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


        <!-- Profile -->

        <a
            href="customer_profile.php"
            class="nav-item">

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


<script>

/* =========================
   Notification
========================= */

const notificationButton =
    document.getElementById("notificationButton");

const notificationOverlay =
    document.getElementById("notificationOverlay");

const notificationClose =
    document.getElementById("notificationClose");


/* เปิดแจ้งเตือน */

notificationButton.addEventListener("click", function () {

    notificationOverlay.classList.add("show");


    const badge =
        document.getElementById("notificationBadge");

    if (badge) {
        badge.remove();
    }


    fetch("customer_notification_read.php", {
        method: "POST"
    });

});


/* ปิดแจ้งเตือน */

notificationClose.addEventListener("click", function () {

    notificationOverlay.classList.remove("show");

});


/* กดพื้นที่ด้านนอกเพื่อปิด */

notificationOverlay.addEventListener("click", function (event) {

    if (event.target === notificationOverlay) {

        notificationOverlay.classList.remove("show");

    }

});

</script>


</body>

</html>
```
