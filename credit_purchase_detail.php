<?php

session_start();

require_once "config/database.php";

/* ตรวจสอบข้อมูลที่ส่งมา */
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
                total_amount,
                sale_date
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


/* ตรวจสอบลูกค้า */
$sql_customer = "SELECT
                    customer_id,
                    customer_name
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


/* ดึงรายละเอียดสินค้าที่ขาย */
$sql_details = "SELECT
                    sd.product_id,
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
                ORDER BY sd.sale_detail_id ASC";

$stmt_details = $conn->prepare($sql_details);
$stmt_details->bind_param("i", $sale_id);
$stmt_details->execute();

$result_details = $stmt_details->get_result();

$stmt_details->close();


/* กดตรวจสอบรอบเครดิต */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    header(
        "Location: credit_round.php?sale_id="
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

    <title>ข้อมูลการซื้อเชื่อ</title>

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


        /* Customer */

        .customer-card {
            background: var(--primary-light);

            border: 1px solid var(--border);

            border-radius: 16px;

            padding: 13px 14px;

            margin-bottom: 12px;
        }

        .customer-label {
            font-size: 12px;

            color: var(--text-light);

            margin-bottom: 3px;
        }

        .customer-name {
            font-size: 13px;

            font-weight: 500;
        }


        /* Product list */

        .section-title {
            font-size: 13px;

            font-weight: 500;

            margin: 10px 0 8px;
        }

        .product-list {
            background: var(--primary-light);

            border: 1px solid var(--border);

            border-radius: 16px;

            padding: 12px 14px;

            box-shadow:
                0 3px 8px rgba(67, 143, 184, 0.08);
        }

        .product-item {
            padding: 11px 0;

            border-bottom: 1px solid rgba(67, 143, 184, 0.12);
        }

        .product-item:first-child {
            padding-top: 2px;
        }

        .product-item:last-child {
            border-bottom: none;

            padding-bottom: 2px;
        }

        .product-top {
            display: flex;

            justify-content: space-between;

            gap: 10px;
        }

        .product-name {
            font-size: 13px;

            font-weight: 500;

            flex: 1;
        }

        .product-total {
            font-size: 13px;

            font-weight: 500;

            white-space: nowrap;
        }

        .product-detail {
            margin-top: 4px;

            font-size: 11px;

            color: var(--text-light);
        }


        /* Total */

        .total-box {
            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-top: 12px;

            padding: 14px 14px;

            background: var(--primary-light);

            border: 1px solid var(--border);

            border-radius: 16px;
        }

        .total-label {
            font-size: 14px;

            font-weight: 500;
        }

        .total-amount {
            font-size: 16px;

            font-weight: 600;

            color: var(--primary-dark);
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


        /* Empty */

        .empty {
            padding: 20px;

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
            aria-label="ย้อนกลับ"
        >
            ‹
        </button>

        <div class="header-title">
            รายการซื้อเชื่อ
        </div>

    </div>


    <div class="page-title">
        ข้อมูลการซื้อเชื่อ
    </div>


    <!-- Customer -->

    <div class="customer-card">

        <div class="customer-label">
            ลูกค้า
        </div>

        <div class="customer-name">
            <?php
            echo htmlspecialchars($customer["customer_name"]);
            ?>
        </div>

    </div>


    <!-- Products -->

    <div class="section-title">
        รายการสินค้า
    </div>


    <div class="product-list">

        <?php if ($result_details->num_rows > 0): ?>

            <?php while ($item = $result_details->fetch_assoc()): ?>

                <div class="product-item">

                    <div class="product-top">

                        <div class="product-name">

                            <?php
                            echo htmlspecialchars(
                                $item["product_name"]
                            );
                            ?>

                        </div>

                        <div class="product-total">

                            <?php
                            echo number_format(
                                $item["total_price"]
                            );
                            ?> บาท

                        </div>

                    </div>


                    <div class="product-detail">

                        <?php
                        echo htmlspecialchars(
                            $item["size_quantity"]
                        );
                        ?>

                        <?php
                        echo " ";
                        ?>

                        <?php
                        echo htmlspecialchars(
                            $item["unit"]
                        );
                        ?>

                        &nbsp;|&nbsp;

                        จำนวน
                        <?php
                        echo number_format(
                            $item["quantity"]
                        );
                        ?>

                        &nbsp;×&nbsp;

                        <?php
                        echo number_format(
                            $item["selling_price"]
                        );
                        ?>

                        บาท

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty">
                ไม่พบรายละเอียดรายการสินค้า
            </div>

        <?php endif; ?>

    </div>


    <!-- Total -->

    <div class="total-box">

        <div class="total-label">
            ยอดรวม
        </div>

        <div class="total-amount">

            <?php
            echo number_format(
                $sale["total_amount"]
            );
            ?>

            บาท

        </div>

    </div>


    <!-- Continue -->

    <form method="POST">

        <button
            type="submit"
            class="continue-button"
        >
            ตรวจสอบรอบเครดิต
        </button>

    </form>


</div>

</body>

</html>