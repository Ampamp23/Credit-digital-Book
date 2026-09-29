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
        customer_name,
        phone_number,
        credit_limit
    FROM customers
    WHERE customer_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $customer_id);
$stmt->execute();

$customer = $stmt->get_result()->fetch_assoc();

$stmt->close();


if (!$customer) {
    session_destroy();
    header("Location: customer_login.php");
    exit();
}


/* =========================
   ดึงรายการซื้อเชื่อ
========================= */

$stmt = $conn->prepare("
    SELECT
        cp.credit_purchase_id,
        cp.credit_purchase_date,
        cp.credit_purchase_amount,
        p.product_name,
        sd.quantity,
        sd.selling_price,
        sd.total_price,
        cr.payment_status
    FROM credit_purchases cp

    INNER JOIN sales s
        ON cp.sale_id = s.sale_id

    INNER JOIN sale_details sd
        ON s.sale_id = sd.sale_id

    INNER JOIN products p
        ON sd.product_id = p.product_id

    INNER JOIN credit_rounds cr
        ON cp.credit_round_id = cr.credit_round_id

    WHERE cr.customer_id = ?

    ORDER BY
        cp.credit_purchase_date DESC,
        cp.credit_purchase_id DESC
");

$stmt->bind_param("i", $customer_id);
$stmt->execute();

$credit_result = $stmt->get_result();

$credit_purchases = [];


while ($row = $credit_result->fetch_assoc()) {

    $id = $row["credit_purchase_id"];


    if (!isset($credit_purchases[$id])) {

        $credit_purchases[$id] = [

            "credit_purchase_id" => $id,

            "date" => $row["credit_purchase_date"],

            "amount" => $row["credit_purchase_amount"],

            "payment_status" => $row["payment_status"],

            "products" => []

        ];
    }


    $credit_purchases[$id]["products"][] = [

        "product_name" => $row["product_name"],

        "quantity" => $row["quantity"],

        "selling_price" => $row["selling_price"],

        "total_price" => $row["total_price"]

    ];
}

$stmt->close();


/* =========================
   ดึงรายการชำระเงิน
========================= */

$stmt = $conn->prepare("
    SELECT
        p.payment_id,
        p.payment_date,
        p.payment_amount

    FROM payments p

    INNER JOIN credit_purchases cp
        ON p.credit_purchase_id = cp.credit_purchase_id

    INNER JOIN credit_rounds cr
        ON cp.credit_round_id = cr.credit_round_id

    WHERE cr.customer_id = ?

    ORDER BY
        p.payment_date DESC,
        p.payment_id DESC
");

$stmt->bind_param("i", $customer_id);
$stmt->execute();

$payment_result = $stmt->get_result();

$payments = [];


while ($row = $payment_result->fetch_assoc()) {

    $payments[] = [

        "payment_id" => $row["payment_id"],

        "date" => $row["payment_date"],

        "amount" => $row["payment_amount"]

    ];
}

$stmt->close();


/* =========================
   รวม Timeline
========================= */

$timeline = [];


foreach ($credit_purchases as $item) {

    $timeline[] = [

        "type" => "credit",

        "id" => $item["credit_purchase_id"],

        "date" => $item["date"],

        "amount" => $item["amount"],

        "payment_status" => $item["payment_status"],

        "products" => $item["products"]

    ];
}


foreach ($payments as $item) {

    $timeline[] = [

        "type" => "payment",

        "id" => $item["payment_id"],

        "date" => $item["date"],

        "amount" => $item["amount"]

    ];
}


/* ใหม่สุดอยู่บน */

usort($timeline, function ($a, $b) {

    $dateCompare =
        strtotime($b["date"]) <=> strtotime($a["date"]);


    if ($dateCompare !== 0) {

        return $dateCompare;
    }


    return $b["id"] <=> $a["id"];

});


/* =========================
   แปลงวันที่เป็น พ.ศ.
========================= */

function thaiDate($date)
{
    $timestamp = strtotime($date);

    $day = date("d", $timestamp);

    $month = date("m", $timestamp);

    $year = date("Y", $timestamp) + 543;


    $months = [

        "01" => "ม.ค.",
        "02" => "ก.พ.",
        "03" => "มี.ค.",
        "04" => "เม.ย.",
        "05" => "พ.ค.",
        "06" => "มิ.ย.",
        "07" => "ก.ค.",
        "08" => "ส.ค.",
        "09" => "ก.ย.",
        "10" => "ต.ค.",
        "11" => "พ.ย.",
        "12" => "ธ.ค."

    ];


    return
        $day . " " .
        $months[$month] . " " .
        $year;
}


/* =========================
   สถานะรายการซื้อเชื่อ
========================= */

function statusClass($status)
{
    if ($status === "ชำระครบ") {

        return "paid";
    }


    if ($status === "ชำระบางส่วน") {

        return "partial";
    }


    return "unpaid";
}


function statusText($status)
{
    if ($status === "ชำระครบ") {

        return "ชำระครบ";
    }


    if ($status === "ชำระบางส่วน") {

        return "ชำระบางส่วน";
    }


    return "ยังไม่ชำระ";
}

?>

<!DOCTYPE html>

<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>ประวัติ | เชื่อใจ</title>


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

            --green: #4E9A6E;

            --green-bg: #EAF7EC;

            --orange: #F59E0B;

            --orange-bg: #FFF5DF;
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

            overflow-x: hidden;
        }


        /* =========================
           App
        ========================= */

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
           ใช้ขนาดเดียวกับ Home
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
           Content
           ใช้ระยะเดียวกับ Home
        ========================= */

        .content {

            padding: 20px;
        }


        /* =========================
           History List
        ========================= */

        .history-list {

            display: flex;

            flex-direction: column;

            gap: 12px;

            height: auto;
        }


        /* =========================
           History Card
        ========================= */

        .history-card {

            width: 100%;

            height: auto;

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 19px;

            padding: 16px;
        }


        .history-header {

            display: flex;

            align-items: flex-start;

            justify-content: space-between;

            gap: 10px;

            margin-bottom: 12px;
        }


        .history-title {

            display: flex;

            align-items: center;

            gap: 8px;

            font-size: 14px;

            font-weight: 500;
        }


        .history-date {

            font-size: 10px;

            color: var(--text-light);

            margin-top: 2px;
        }


        /* =========================
           Status
        ========================= */

        .status {

            display: inline-flex;

            align-items: center;

            padding: 4px 9px;

            border-radius: 99px;

            font-size: 10px;

            font-weight: 400;

            white-space: nowrap;
        }


        .status.paid {

            color: #388E3C;

            background: var(--green-bg);
        }


        .status.partial {

            color: var(--primary-dark);

            background: var(--primary-light);
        }


        .status.unpaid {

            color: #D97706;

            background: var(--orange-bg);
        }


        /* =========================
           Product List
        ========================= */

        .product-list {

            display: flex;

            flex-direction: column;

            gap: 8px;

            margin-bottom: 12px;
        }


        .product-row {

            display: flex;

            align-items: flex-start;

            justify-content: space-between;

            gap: 12px;

            padding-bottom: 8px;

            border-bottom: 1px solid #F0F2F3;
        }


        .product-row:last-child {

            padding-bottom: 0;

            border-bottom: none;
        }


        .product-info {

            flex: 1;

            min-width: 0;
        }


        .product-name {

            font-size: 13px;

            font-weight: 400;

            line-height: 1.5;

            word-break: break-word;
        }


        .product-detail {

            margin-top: 1px;

            color: var(--text-light);

            font-size: 10px;

            font-weight: 300;
        }


        .product-price {

            font-size: 13px;

            font-weight: 500;

            white-space: nowrap;
        }


        /* =========================
           Total
        ========================= */

        .history-total {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding-top: 10px;

            border-top: 1px solid var(--border);
        }


        .history-total span:first-child {

            font-size: 11px;

            color: var(--text-light);
        }


        .history-total span:last-child {

            font-size: 15px;

            font-weight: 600;

            color: var(--primary-dark);
        }


        /* =========================
           Payment Card
        ========================= */

        .payment-card {

            width: 100%;

            height: auto;

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 19px;

            padding: 16px;
        }


        .payment-main {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 12px;
        }


        .payment-info {

            display: flex;

            align-items: center;

            gap: 10px;

            min-width: 0;
        }


        .payment-icon {

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


        .payment-title {

            font-size: 13px;

            font-weight: 500;
        }


        .payment-date {

            font-size: 10px;

            color: var(--text-light);

            margin-top: 2px;
        }


        .payment-amount {

            font-size: 13px;

            font-weight: 600;

            color: var(--green);

            white-space: nowrap;
        }


        /* =========================
           Empty
        ========================= */

        .empty {

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 19px;

            padding: 30px 20px;

            text-align: center;

            color: var(--text-light);

            font-size: 12px;
        }


        /* =========================
           Bottom Navigation
           ใช้ชุดเดียวกับ Home
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

        <div class="header-text">

            <h1>
                ประวัติ
            </h1>

            <p>
                รายการซื้อเชื่อและการชำระเงินของคุณ
            </p>

        </div>

    </header>


    <!-- =========================
         Content
    ========================= -->

    <main class="content">


        <div class="history-list">


            <?php if (empty($timeline)): ?>


                <div class="empty">

                    ยังไม่มีประวัติการซื้อเชื่อหรือการชำระเงิน

                </div>


            <?php else: ?>


                <?php foreach ($timeline as $item): ?>


                    <?php if ($item["type"] === "credit"): ?>


                        <!-- =========================
                             Credit Purchase
                        ========================= -->

                        <div class="history-card">


                            <div class="history-header">


                                <div>

                                    <div class="history-title">

                                        <span>🛒</span>

                                        <span>
                                            ซื้อเชื่อ
                                        </span>

                                    </div>


                                    <div class="history-date">

                                        <?= thaiDate($item["date"]) ?>

                                    </div>

                                </div>


                                <div
                                    class="status <?= statusClass($item["payment_status"]) ?>">

                                    <?= statusText($item["payment_status"]) ?>

                                </div>


                            </div>


                            <div class="product-list">


                                <?php foreach ($item["products"] as $product): ?>


                                    <div class="product-row">


                                        <div class="product-info">


                                            <div class="product-name">

                                                <?= htmlspecialchars(
                                                    $product["product_name"]
                                                ) ?>

                                            </div>


                                            <div class="product-detail">

                                                <?= $product["quantity"] ?>

                                                ×

                                                <?= number_format(
                                                    $product["selling_price"],
                                                    0
                                                ) ?>

                                                บาท

                                            </div>


                                        </div>


                                        <div class="product-price">

                                            <?= number_format(
                                                $product["total_price"],
                                                0
                                            ) ?>

                                            บาท

                                        </div>


                                    </div>


                                <?php endforeach; ?>


                            </div>


                            <div class="history-total">


                                <span>
                                    ยอดซื้อเชื่อ
                                </span>


                                <span>

                                    <?= number_format(
                                        $item["amount"],
                                        0
                                    ) ?>

                                    บาท

                                </span>


                            </div>


                        </div>


                    <?php else: ?>


                        <!-- =========================
                             Payment
                        ========================= -->

                        <div class="payment-card">


                            <div class="payment-main">


                                <div class="payment-info">


                                    <div class="payment-icon">

                                        💳

                                    </div>


                                    <div>


                                        <div class="payment-title">

                                            ชำระเงิน

                                        </div>


                                        <div class="payment-date">

                                            <?= thaiDate($item["date"]) ?>

                                        </div>


                                    </div>


                                </div>


                                <div class="payment-amount">

                                    -<?= number_format(
                                        $item["amount"],
                                        0
                                    ) ?>

                                    บาท

                                </div>


                            </div>


                        </div>


                    <?php endif; ?>


                <?php endforeach; ?>


            <?php endif; ?>


        </div>


    </main>


    <!-- =========================
         Bottom Navigation
    ========================= -->

    <nav class="bottom-nav">


        <!-- Home -->

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


        <!-- History -->

        <a
            href="customer_history.php"
            class="nav-item active">


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
                    d="M5 21c.8-4 3.1-6 7-6s6.2 2 7 6">
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
