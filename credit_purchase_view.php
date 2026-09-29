<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: owner_login.php");
    exit;
}

require_once "config/database.php";

$credit_purchase_id = (int)($_GET["credit_purchase_id"] ?? 0);

if ($credit_purchase_id <= 0) {
    die("ไม่พบข้อมูลรายการซื้อเชื่อ");
}


/* =========================
   ดึงข้อมูลรายการซื้อเชื่อ
========================= */

$sql = "
    SELECT
        cp.credit_purchase_id,
        cp.sale_id,
        cp.credit_round_id,
        cp.credit_purchase_date,
        cp.credit_purchase_amount,

        c.customer_name,
        c.phone_number,

        s.sale_date,
        s.total_amount

    FROM credit_purchases cp

    INNER JOIN credit_rounds cr
        ON cp.credit_round_id = cr.credit_round_id

    INNER JOIN customers c
        ON cr.customer_id = c.customer_id

    INNER JOIN sales s
        ON cp.sale_id = s.sale_id

    WHERE cp.credit_purchase_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $credit_purchase_id);
$stmt->execute();

$result = $stmt->get_result();
$purchase = $result->fetch_assoc();

if (!$purchase) {
    die("ไม่พบข้อมูลรายการซื้อเชื่อ");
}


/* =========================
   ดึงรายละเอียดสินค้า
========================= */

$sql_details = "
    SELECT
        sd.quantity,
        sd.selling_price,
        sd.total_price,

        p.product_name,
        p.size_quantity,
        p.unit

    FROM sale_details sd

    INNER JOIN products p
        ON sd.product_id = p.product_id

    WHERE sd.sale_id = ?

    ORDER BY sd.sale_detail_id ASC
";

$stmt_details = $conn->prepare($sql_details);
$stmt_details->bind_param("i", $purchase["sale_id"]);
$stmt_details->execute();

$result_details = $stmt_details->get_result();


/* =========================
   แปลงวันที่
========================= */

function thaiDate($date)
{
    if (!$date) {
        return "-";
    }

    $timestamp = strtotime($date);

    $day = date("d", $timestamp);
    $month = date("m", $timestamp);
    $year = date("Y", $timestamp) + 543;

    return $day . "/" . $month . "/" . $year;
}

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>รายละเอียดรายการซื้อเชื่อ</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

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

            margin-bottom: 18px;
        }


        .back-button {
            position: absolute;

            left: 0;

            top: 50%;

            transform: translateY(-50%);

            width: 42px;

            height: 42px;

            border: none;

            border-radius: 14px;

            background: var(--primary-light);

            color: var(--primary);

            font-size: 28px;

            line-height: 1;

            cursor: pointer;
        }


        .header-title {
            font-size: 20px;

            font-weight: 600;

            color: var(--primary-dark);

            text-align: center;
        }


        /* Customer */

        .customer-card {
            background: var(--primary-light);

            border: 1px solid var(--border);

            border-radius: 18px;

            padding: 16px;

            margin-bottom: 18px;
        }


        .customer-name {
            font-size: 16px;

            font-weight: 500;

            margin-bottom: 3px;
        }


        .customer-phone {
            font-size: 12px;

            color: var(--text-light);
        }


        /* Information */

        .info-card {
            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 16px;

            padding: 15px;

            margin-bottom: 18px;
        }


        .info-row {
            display: flex;

            justify-content: space-between;

            gap: 15px;

            padding: 7px 0;

            font-size: 13px;
        }


        .info-label {
            color: var(--text-light);
        }


        .info-value {
            font-weight: 500;

            text-align: right;
        }


        /* Section */

        .section-title {
            margin: 0 0 12px;

            font-size: 16px;

            font-weight: 500;

            color: var(--primary-dark);
        }


        /* Product */

        .product-list {
            display: flex;

            flex-direction: column;

            gap: 10px;
        }


        .product-card {
            background: var(--primary-light);

            border-radius: 16px;

            padding: 15px;
        }


        .product-top {
            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 12px;
        }


        .product-name {
            font-size: 14px;

            font-weight: 500;

            margin-bottom: 4px;
        }


        .product-size {
            font-size: 11px;

            color: var(--text-light);
        }


        .product-total {
            font-size: 15px;

            font-weight: 600;

            color: var(--primary-dark);

            white-space: nowrap;
        }


        .product-detail {
            margin-top: 10px;

            padding-top: 9px;

            border-top: 1px solid var(--border);

            font-size: 12px;

            color: var(--text);
        }


        /* Total */

        .total-card {
            margin-top: 18px;

            padding: 16px;

            background: var(--primary);

            border-radius: 16px;

            color: var(--white);

            display: flex;

            align-items: center;

            justify-content: space-between;
        }


        .total-label {
            font-size: 14px;
        }


        .total-value {
            font-size: 20px;

            font-weight: 600;
        }


        /* Empty */

        .empty-message {
            padding: 25px 10px;

            text-align: center;

            color: var(--text-light);

            font-size: 13px;
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
            รายละเอียดรายการซื้อเชื่อ
        </div>

    </div>


    <!-- Customer -->

    <div class="customer-card">

        <div class="customer-name">
            <?php
            echo htmlspecialchars(
                $purchase["customer_name"]
            );
            ?>
        </div>

        <div class="customer-phone">
            <?php
            echo htmlspecialchars(
                $purchase["phone_number"]
            );
            ?>
        </div>

    </div>


    <!-- Purchase information -->

    <div class="info-card">

        <div class="info-row">

            <div class="info-label">
                วันที่ซื้อเชื่อ
            </div>

            <div class="info-value">
                <?php
                echo thaiDate(
                    $purchase["credit_purchase_date"]
                );
                ?>
            </div>

        </div>


        <div class="info-row">

            <div class="info-label">
                รอบเครดิต
            </div>

            <div class="info-value">
                <?php
                echo (int)$purchase["credit_round_id"];
                ?>
            </div>

        </div>


        <div class="info-row">

            <div class="info-label">
                รหัสรายการขาย
            </div>

            <div class="info-value">
                <?php
                echo (int)$purchase["sale_id"];
                ?>
            </div>

        </div>

    </div>


    <!-- Products -->

    <h2 class="section-title">
        รายการสินค้า
    </h2>


    <div class="product-list">

        <?php if ($result_details->num_rows > 0): ?>

            <?php while ($detail = $result_details->fetch_assoc()): ?>

                <div class="product-card">

                    <div class="product-top">

                        <div>

                            <div class="product-name">

                                <?php
                                echo htmlspecialchars(
                                    $detail["product_name"]
                                );
                                ?>

                            </div>


                            <div class="product-size">

                                <?php
                                echo htmlspecialchars(
                                    $detail["size_quantity"]
                                );
                                ?>

                                <?php
                                echo " " . htmlspecialchars(
                                    $detail["unit"]
                                );
                                ?>

                            </div>

                        </div>


                        <div class="product-total">

                            <?php
                            echo number_format(
                                $detail["total_price"]
                            );
                            ?>

                            บาท

                        </div>

                    </div>


                    <div class="product-detail">

                        จำนวน
                        <?php
                        echo number_format(
                            $detail["quantity"]
                        );
                        ?>

                        ×

                        <?php
                        echo number_format(
                            $detail["selling_price"]
                        );
                        ?>

                        บาท

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty-message">
                ไม่พบรายละเอียดสินค้า
            </div>

        <?php endif; ?>

    </div>


    <!-- Total -->

    <div class="total-card">

        <div class="total-label">
            ยอดซื้อเชื่อ
        </div>

        <div class="total-value">

            <?php
            echo number_format(
                $purchase["credit_purchase_amount"]
            );
            ?>

            บาท

        </div>

    </div>


</div>

</body>

</html>