<?php

session_start();

require_once "config/database.php";

/* ตรวจสอบ sale_id และ customer_id */
if (
    !isset($_GET["sale_id"]) ||
    !is_numeric($_GET["sale_id"]) ||
    !isset($_GET["customer_id"]) ||
    !is_numeric($_GET["customer_id"])
) {
    die("ไม่พบข้อมูลรายการขายหรือลูกค้า");
}

$sale_id = (int)$_GET["sale_id"];
$customer_id = (int)$_GET["customer_id"];


/* ตรวจสอบรายการขาย */
$sql_sale = "SELECT sale_id, sale_type, total_amount
             FROM sales
             WHERE sale_id = ?";

$stmt_sale = $conn->prepare($sql_sale);
$stmt_sale->bind_param("i", $sale_id);
$stmt_sale->execute();

$result_sale = $stmt_sale->get_result();
$sale = $result_sale->fetch_assoc();

$stmt_sale->close();

if (!$sale) {
    die("ไม่พบข้อมูลรายการขาย");
}


/* ต้องเป็นรายการซื้อเชื่อ */
if ($sale["sale_type"] !== "ซื้อเชื่อ") {
    die("รายการนี้ไม่ใช่การซื้อเชื่อ");
}


/* ดึงข้อมูลลูกค้า */
$sql_customer = "SELECT
                    customer_id,
                    customer_name,
                    phone_number,
                    credit_limit
                 FROM customers
                 WHERE customer_id = ?";

$stmt_customer = $conn->prepare($sql_customer);
$stmt_customer->bind_param("i", $customer_id);
$stmt_customer->execute();

$result_customer = $stmt_customer->get_result();
$customer = $result_customer->fetch_assoc();

$stmt_customer->close();

if (!$customer) {
    die("ไม่พบข้อมูลลูกค้า");
}


/*
 * ดึงรอบเครดิตที่ยังมียอดค้างชำระ
 * ถ้ามีหลายรอบ ให้ใช้รอบที่ยังเปิดล่าสุด
 */
$sql_round = "SELECT
                credit_round_id,
                due_date,
                outstanding_amount,
                payment_status
              FROM credit_rounds
              WHERE customer_id = ?
              AND outstanding_amount > 0
              ORDER BY credit_round_id DESC
              LIMIT 1";

$stmt_round = $conn->prepare($sql_round);
$stmt_round->bind_param("i", $customer_id);
$stmt_round->execute();

$result_round = $stmt_round->get_result();
$round = $result_round->fetch_assoc();

$stmt_round->close();


/* ถ้ามีรอบเครดิต ให้ใช้ยอดค้างจากรอบนั้น */
if ($round) {
    $outstanding_amount = (int)$round["outstanding_amount"];
} else {
    $outstanding_amount = 0;
}


/* คำนวณวงเงินคงเหลือ */
$credit_limit = (int)$customer["credit_limit"];

$remaining_credit = $credit_limit - $outstanding_amount;


/* ถ้าค่าติดลบ ให้แสดงเป็น 0 */
if ($remaining_credit < 0) {
    $remaining_credit = 0;
}


/* กดดำเนินการต่อ */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    header(
        "Location: credit_purchase_detail.php?sale_id="
        . $sale_id
        . "&customer_id="
        . $customer_id
    );

    exit;
}

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>ข้อมูลลูกค้า</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap"
        rel="stylesheet"
    >

    <style>

        :root {
            --primary: #5AA6CF;
            --primary-dark: #438FB8;
            --primary-light: #E8F5FC;

            --background: #E6D2BC;
            --white: #FFFFFF;

            --text: #263238;
            --text-light: #7A858C;

            --border: #D7ECF7;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;

            font-family: "Prompt", sans-serif;

            background: var(--background);

            color: var(--text);
        }

        .app {
            width: 100%;
            max-width: 490px;

            min-height: 100vh;

            margin: 0 auto;

            background: var(--white);

            padding: 18px 20px 30px;
        }


        /* Header */

        .header {
            position: relative;

            display: flex;

            align-items: center;
            justify-content: center;

            height: 42px;

            margin-bottom: 8px;
        }

        .back-button {
            position: absolute;

            left: 0;
            top: 50%;

            transform: translateY(-50%);

            width: 32px;
            height: 32px;

            border: none;

            border-radius: 50%;

            background: var(--primary-light);

            color: var(--primary);

            font-size: 25px;

            line-height: 1;

            cursor: pointer;
        }

        .header-title {
            font-size: 18px;

            font-weight: 500;
        }


        /* Page title */

        .page-title {
            margin: 4px 0 12px;

            font-size: 16px;

            font-weight: 500;
        }


        /* Customer card */

        .customer-card {
            background: var(--primary-light);

            border: 1px solid var(--border);

            border-radius: 16px;

            padding: 18px 16px;

            box-shadow:
                0 3px 8px rgba(67, 143, 184, 0.08);
        }

        .info-row {
            margin-bottom: 18px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-size: 12px;

            font-weight: 400;

            margin-bottom: 4px;
        }

        .info-value {
            font-size: 13px;

            font-weight: 400;
        }


        /* Button */

        .continue-button {
            width: 100%;

            height: 44px;

            margin-top: 14px;

            border: none;

            border-radius: 10px;

            background: var(--primary);

            color: var(--white);

            font-family: "Prompt", sans-serif;

            font-size: 13px;

            font-weight: 500;

            cursor: pointer;
        }

        .continue-button:hover {
            background: var(--primary-dark);
        }


        @media (min-width: 700px) {

            body {
                min-height: 100vh;

                display: flex;

                align-items: center;
                justify-content: center;
            }

            .app {
                min-height: 760px;

                height: 850px;

                border-radius: 34px;

                box-shadow:
                    0 20px 45px rgba(0, 0, 0, 0.12);

                overflow-y: auto;
            }
        }

    </style>

</head>

<body>

<div class="app">


    <!-- Header -->

    <div class="header">

        <button
            type="button"
            class="back-button"
            onclick="history.back()"
            aria-label="ย้อนกลับ"
        >
            ‹
        </button>

        <div class="header-title">
            ข้อมูลลูกค้า
        </div>

    </div>


    <div class="page-title">
        รายการซื้อเชื่อ
    </div>


    <!-- Customer information -->

    <div class="customer-card">


        <div class="info-row">

            <div class="info-label">
                ชื่อ - สกุล
            </div>

            <div class="info-value">
                <?php
                echo htmlspecialchars($customer["customer_name"]);
                ?>
            </div>

        </div>


        <div class="info-row">

            <div class="info-label">
                เบอร์โทรศัพท์
            </div>

            <div class="info-value">
                <?php
                echo htmlspecialchars($customer["phone_number"]);
                ?>
            </div>

        </div>


        <div class="info-row">

            <div class="info-label">
                วงเงินเครดิต
            </div>

            <div class="info-value">
                <?php
                echo number_format($credit_limit);
                ?> บาท
            </div>

        </div>


        <div class="info-row">

            <div class="info-label">
                ยอดค้างชำระ
            </div>

            <div class="info-value">
                <?php
                echo number_format($outstanding_amount);
                ?> บาท
            </div>

        </div>


        <div class="info-row">

            <div class="info-label">
                วงเงินคงเหลือ
            </div>

            <div class="info-value">
                <?php
                echo number_format($remaining_credit);
                ?> บาท
            </div>

        </div>


    </div>


    <!-- Continue -->

    <form method="POST">

        <button
            type="submit"
            class="continue-button"
        >
            ดำเนินการต่อ
        </button>

    </form>


</div>

</body>

</html>