<?php

session_start();

require_once "config/database.php";


if (
    !isset($_SESSION["credit_purchase_id"]) ||
    !isset($_SESSION["credit_customer_id"])
) {
    die("ไม่พบข้อมูลการซื้อเชื่อ");
}

$credit_purchase_id = (int)$_SESSION["credit_purchase_id"];
$customer_id = (int)$_SESSION["credit_customer_id"];
$purchase_amount = (int)$_SESSION["credit_purchase_amount"];
$new_outstanding = (int)$_SESSION["credit_new_outstanding"];


/* ดึงข้อมูลลูกค้า */
$sql_customer = "SELECT
                    customer_name
                 FROM customers
                 WHERE customer_id = ?";

$stmt = $conn->prepare($sql_customer);
$stmt->bind_param("i", $customer_id);
$stmt->execute();

$result = $stmt->get_result();
$customer = $result->fetch_assoc();

$stmt->close();


/* ดึงข้อมูลรอบเครดิต */
$sql_round = "SELECT
                due_date,
                payment_status
              FROM credit_rounds cr
              INNER JOIN credit_purchases cp
                  ON cr.credit_round_id = cp.credit_round_id
              WHERE cp.credit_purchase_id = ?";

$stmt = $conn->prepare($sql_round);
$stmt->bind_param("i", $credit_purchase_id);
$stmt->execute();

$result = $stmt->get_result();
$round = $result->fetch_assoc();

$stmt->close();


function thaiDate($date)
{
    $timestamp = strtotime($date);

    return date("d/m/", $timestamp)
        . (date("Y", $timestamp) + 543);
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

    <title>ผลการบันทึกซื้อเชื่อ</title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

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

            --success: #2ECC71;
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


        /* Result */

        .result-card {
            background: var(--primary-light);

            border: 1px solid var(--border);

            border-radius: 16px;

            padding: 18px 16px;

            box-shadow:
                0 3px 8px rgba(67, 143, 184, 0.08);
        }

        .success-icon {
            width: 40px;

            height: 40px;

            margin: 0 auto 14px;

            display: flex;

            align-items: center;

            justify-content: center;

            border: 2px solid var(--success);

            border-radius: 50%;

            color: var(--success);

            font-size: 22px;
        }

        .success-message {
            text-align: center;

            font-size: 13px;

            font-weight: 500;

            margin-bottom: 18px;
        }

        .info-row {
            margin-bottom: 13px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-size: 11px;

            margin-bottom: 3px;
        }

        .info-value {
            font-size: 13px;

            font-weight: 400;
        }


        /* Button */

        .home-button {
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

            text-decoration: none;

            display: flex;

            align-items: center;

            justify-content: center;
        }

        .home-button:hover {
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
            รายการซื้อเชื่อ
        </div>

    </div>


    <div class="page-title">
        ผลการบันทึกซื้อเชื่อ
    </div>


    <div class="result-card">


        <div class="success-icon">
            ✓
        </div>


        <div class="success-message">
            บันทึกรายการซื้อเชื่อสำเร็จแล้ว
        </div>


        <div class="info-row">

            <div class="info-label">
                ลูกค้า
            </div>

            <div class="info-value">
                <?php
                echo htmlspecialchars(
                    $customer["customer_name"]
                );
                ?>
            </div>

        </div>


        <div class="info-row">

            <div class="info-label">
                ยอดซื้อเชื่อ
            </div>

            <div class="info-value">
                <?php
                echo number_format(
                    $purchase_amount
                );
                ?> บาท
            </div>

        </div>


        <div class="info-row">

            <div class="info-label">
                วันครบกำหนด
            </div>

            <div class="info-value">
                <?php
                echo thaiDate(
                    $round["due_date"]
                );
                ?>
            </div>

        </div>


        <div class="info-row">

            <div class="info-label">
                ยอดค้างชำระ
            </div>

            <div class="info-value">
                <?php
                echo number_format(
                    $new_outstanding
                );
                ?> บาท
            </div>

        </div>


        <div class="info-row">

            <div class="info-label">
                สถานะ
            </div>

            <div class="info-value">
                <?php
                echo htmlspecialchars(
                    $round["payment_status"]
                );
                ?>
            </div>

        </div>


    </div>


    <a
        href="owner_home.php"
        class="home-button"
    >
        กลับหน้าหลัก
    </a>


</div>

</body>

</html>