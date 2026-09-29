<?php

session_start();

require_once "config/database.php";


/* ตรวจสอบข้อมูล */
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
$sql_sale = "SELECT
                sale_id,
                sale_type,
                total_amount
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
 * ตรวจสอบรอบเครดิตที่ยังมียอดค้าง
 * ใช้รอบล่าสุดที่ยังเปิดอยู่
 */
$sql_round = "SELECT
                credit_round_id,
                start_date,
                due_date,
                credit_limit,
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


/* ยอดซื้อเชื่อครั้งนี้ */
$purchase_amount = (int)$sale["total_amount"];


/*
 * กรณีมีรอบเครดิตเดิม
 */
if ($round) {

    $has_round = true;

    $credit_round_id = (int)$round["credit_round_id"];

    $start_date = $round["start_date"];
    $due_date = $round["due_date"];

    $credit_limit = (int)$round["credit_limit"];

    $old_outstanding = (int)$round["outstanding_amount"];

    $remaining_before = $credit_limit - $old_outstanding;

    $new_outstanding = $old_outstanding + $purchase_amount;

    $remaining_after = $credit_limit - $new_outstanding;
} else {

    /*
     * กรณีไม่มีรอบเครดิตเดิม
     */

    $has_round = false;

    $credit_round_id = null;

    $start_date = date("Y-m-d");

    $due_date = date(
        "Y-m-d",
        strtotime("+30 days")
    );

    $credit_limit = (int)$customer["credit_limit"];

    $old_outstanding = 0;

    $remaining_before = $credit_limit;

    $new_outstanding = $purchase_amount;

    $remaining_after = $credit_limit - $purchase_amount;
}


/*
 * ตรวจสอบว่าวงเงินเพียงพอหรือไม่
 */
$credit_available = $purchase_amount <= $remaining_before;


/*
 * เมื่อกดยืนยัน
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!$credit_available) {

        die("วงเงินเครดิตไม่เพียงพอสำหรับรายการนี้");
    }


    /*
     * ส่งข้อมูลไปหน้าบันทึกจริง
     *
     * ยังไม่บันทึก credit_rounds / credit_purchases ตรงนี้
     * เพราะหน้านี้เป็นหน้าตรวจสอบ
     */

    header(
        "Location: credit_save.php?sale_id="
            . $sale_id
            . "&customer_id="
            . $customer_id
            . "&round_type="
            . ($has_round ? "existing" : "new")
    );

    exit;
}


/* แปลงวันที่เป็นรูปแบบไทย */
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
        content="width=device-width, initial-scale=1.0">

    <title>ตรวจสอบรอบเครดิต</title>

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

            --border: #D7ECF7;

            --success: #2ECC71;
            --danger: #D9534F;
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


        /* Main card */

        .round-card {
            background: var(--primary-light);

            border: 1px solid var(--border);

            border-radius: 16px;

            padding: 14px 16px;

            box-shadow:
                0 3px 8px rgba(67, 143, 184, 0.08);
        }


        /* Status */

        .status {
            display: flex;

            align-items: center;

            gap: 6px;

            margin-bottom: 12px;

            font-size: 12px;

            font-weight: 500;
        }

        .status-icon {
            display: inline-flex;

            align-items: center;

            justify-content: center;

            width: 17px;

            height: 17px;

            border-radius: 50%;

            color: var(--white);

            font-size: 11px;
        }

        .status-success .status-icon {
            background: var(--success);
        }

        .status-info .status-icon {
            background: #D9534F;
            color: var(--white);
            border: none;
        }


        .sub-message {
            font-size: 11px;

            margin-top: -7px;

            margin-bottom: 13px;

            color: var(--text);
        }


        /* Information */

        .info-row {
            margin-bottom: 13px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-size: 11px;

            margin-bottom: 2px;
        }

        .info-value {
            font-size: 12px;

            font-weight: 400;
        }


        /* Credit warning */

        .warning {
            margin-top: 12px;

            padding: 10px;

            border-radius: 10px;

            background: #FFF1F0;

            color: var(--danger);

            font-size: 11px;

            line-height: 1.5;
        }


        /* Button */

        .confirm-button {
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

        .confirm-button:hover {
            background: var(--primary-dark);
        }

        .confirm-button:disabled {
            background: #B8C2C7;

            cursor: not-allowed;
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
                aria-label="ย้อนกลับ">
                ‹
            </button>

            <div class="header-title">
                รายการซื้อเชื่อ
            </div>

        </div>


        <div class="page-title">
            ตรวจสอบรอบเครดิต
        </div>


        <div class="round-card">


            <?php if ($has_round): ?>

                <!-- มีรอบเครดิตเดิม -->

                <div class="status status-success">

                    <span class="status-icon">
                        ✓
                    </span>

                    พบรอบเครดิตปัจจุบัน

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
                        วงเงินคงเหลือ
                    </div>

                    <div class="info-value">
                        <?php
                        echo number_format(
                            $remaining_before
                        );
                        ?> บาท
                    </div>

                </div>


                <div class="info-row">

                    <div class="info-label">
                        ยอดค้างชำระเดิม
                    </div>

                    <div class="info-value">
                        <?php
                        echo number_format(
                            $old_outstanding
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
                        echo thaiDate($due_date);
                        ?>
                    </div>

                </div>


                <div class="info-row">

                    <div class="info-label">
                        ยอดซื้อเชื่อครั้งนี้
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
                        ยอดค้างชำระใหม่
                    </div>

                    <div class="info-value">
                        <?php
                        echo number_format(
                            $new_outstanding
                        );
                        ?> บาท
                    </div>

                </div>


                <?php if (!$credit_available): ?>

                    <div class="warning">
                        วงเงินเครดิตคงเหลือไม่เพียงพอสำหรับรายการนี้
                    </div>

                <?php endif; ?>


            <?php else: ?>

                <!-- ไม่มีรอบเครดิตเดิม -->

                <div class="status status-info">

                    <span class="status-icon">
                        i
                    </span>

                    ไม่พบรอบเครดิตปัจจุบัน

                </div>


                <div class="sub-message">
                    ระบบจะสร้างรอบเครดิตใหม่
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
                        วันเริ่มรอบ
                    </div>

                    <div class="info-value">
                        <?php
                        echo thaiDate($start_date);
                        ?>
                    </div>

                </div>


                <div class="info-row">

                    <div class="info-label">
                        วันครบกำหนด
                    </div>

                    <div class="info-value">
                        <?php
                        echo thaiDate($due_date);
                        ?>
                    </div>

                </div>


                <div class="info-row">

                    <div class="info-label">
                        วงเงินเครดิต
                    </div>

                    <div class="info-value">
                        <?php
                        echo number_format(
                            $credit_limit
                        );
                        ?> บาท
                    </div>

                </div>


                <div class="info-row">

                    <div class="info-label">
                        ยอดค้างชำระเดิม
                    </div>

                    <div class="info-value">
                        0 บาท
                    </div>

                </div>


                <div class="info-row">

                    <div class="info-label">
                        ยอดซื้อเชื่อครั้งนี้
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
                        ยอดค้างชำระใหม่
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
                        วงเงินคงเหลือ
                    </div>

                    <div class="info-value">
                        <?php
                        echo number_format(
                            $remaining_after
                        );
                        ?> บาท
                    </div>

                </div>


            <?php endif; ?>


        </div>


        <!-- Confirm -->

        <form method="POST">

            <button
                type="submit"
                class="confirm-button"
                <?php
                echo !$credit_available
                    ? "disabled"
                    : "";
                ?>>

                <?php if ($has_round): ?>

                    ใช้เครดิตรอบเดิม

                <?php else: ?>

                    สร้างรอบเครดิตใหม่

                <?php endif; ?>

            </button>

        </form>


    </div>

</body>

</html>