<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: owner_login.php");
    exit;
}

require_once "config/database.php";


/* =========================
   1. ยอดขายทั้งหมด
========================= */

$sql_sales = "
    SELECT COALESCE(SUM(total_amount), 0) AS total_sales
    FROM sales
";

$result_sales = $conn->query($sql_sales);
$sales_data = $result_sales->fetch_assoc();

$total_sales = (int)$sales_data["total_sales"];


/* =========================
   2. ยอดชำระเงินทั้งหมด
========================= */

$sql_payment = "
    SELECT COALESCE(SUM(payment_amount), 0) AS total_payment
    FROM payments
";

$result_payment = $conn->query($sql_payment);
$payment_data = $result_payment->fetch_assoc();

$total_payment = (int)$payment_data["total_payment"];


/* =========================
   3. ยอดค้างชำระทั้งหมด
========================= */

$sql_outstanding = "
    SELECT COALESCE(SUM(outstanding_amount), 0) AS total_outstanding
    FROM credit_rounds
    WHERE payment_status != 'ชำระครบ'
";

$result_outstanding = $conn->query($sql_outstanding);
$outstanding_data = $result_outstanding->fetch_assoc();

$total_outstanding = (int)$outstanding_data["total_outstanding"];


/* =========================
   4. จำนวนลูกค้าที่มีการซื้อเชื่อ
========================= */

$sql_credit_customers = "
    SELECT COUNT(DISTINCT customer_id) AS credit_customers
    FROM credit_rounds
";

$result_credit_customers = $conn->query($sql_credit_customers);
$credit_customer_data = $result_credit_customers->fetch_assoc();

$credit_customers = (int)$credit_customer_data["credit_customers"];


/* =========================
   5. สรุปยอดขาย
========================= */

$sql_sale_summary = "
    SELECT
        sale_type,
        COUNT(*) AS sale_count,
        COALESCE(SUM(total_amount), 0) AS sale_total
    FROM sales
    GROUP BY sale_type
    ORDER BY sale_total DESC
";

$result_sale_summary = $conn->query($sql_sale_summary);


/* =========================
   6. ลูกค้าที่มียอดค้างชำระ
========================= */

$sql_customer_debt = "
    SELECT
        c.customer_name,
        c.phone_number,
        COALESCE(SUM(cr.outstanding_amount), 0) AS outstanding_amount
    FROM customers c

    INNER JOIN credit_rounds cr
        ON c.customer_id = cr.customer_id

    WHERE cr.outstanding_amount > 0

    GROUP BY
        c.customer_id,
        c.customer_name,
        c.phone_number

    ORDER BY outstanding_amount DESC
";

$result_customer_debt = $conn->query($sql_customer_debt);


/* =========================
   7. สินค้าขายดี
========================= */

$sql_best_products = "
    SELECT
        p.product_name,
        p.size_quantity,
        p.unit,
        SUM(sd.quantity) AS total_quantity
    FROM sale_details sd

    INNER JOIN products p
        ON sd.product_id = p.product_id

    GROUP BY
        p.product_id,
        p.product_name,
        p.size_quantity,
        p.unit

    ORDER BY total_quantity DESC

    LIMIT 5
";

$result_best_products = $conn->query($sql_best_products);

?>

<!DOCTYPE html>
<html lang="th">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

<title>รายงานและตรวจสอบข้อมูล</title>


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

/* =========================
   Color
========================= */

:root {

    --primary: #5AA6CF;
    --primary-dark: #438FB8;
    --primary-light: #E8F5FC;

    --background: #E6D2BC;

    --white: #FFFFFF;

    --text: #263238;
    --text-light: #7A858C;

    --border: #E7EDF1;

    --green: #4CAF7A;
    --green-light: #EAF7F0;

    --orange: #E59A45;
    --orange-light: #FFF4E6;

    --red: #D96B6B;
    --red-light: #FDEEEE;

}


/* =========================
   Reset
========================= */

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    font-family: "Prompt", sans-serif;

    background: var(--background);

    color: var(--text);

}


/* =========================
   App
========================= */

.app {

    width: 100%;

    max-width: 490px;

    min-height: 100vh;

    margin: 0 auto;

    background: var(--white);

    padding: 18px 20px 34px;

}


/* =========================
   Header
========================= */

.header {

    position: relative;

    height: 44px;

    display: flex;

    align-items: center;

    justify-content: center;

    margin-bottom: 3px;

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

    color: var(--primary-dark);

    font-size: 28px;

    line-height: 1;

    cursor: pointer;

}


.header-title {

    font-size: 20px;

    font-weight: 600;

    color: var(--primary-dark);

}


.subtitle {

    text-align: center;

    font-size: 12px;

    font-weight: 300;

    color: var(--text-light);

    margin-bottom: 22px;

}


/* =========================
   Summary
========================= */

.summary-grid {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 11px;

    margin-bottom: 27px;

}


.summary-card {

    position: relative;

    overflow: hidden;

    min-height: 108px;

    padding: 15px 15px 14px;

    background: var(--primary-light);

    border: 1px solid #DCEEF7;

    border-radius: 18px;

}


.summary-card::after {

    content: "";

    position: absolute;

    width: 48px;

    height: 48px;

    right: -18px;

    bottom: -18px;

    border-radius: 50%;

    background: rgba(255,255,255,0.45);

}


.summary-icon {

    width: 34px;

    height: 34px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 11px;

    background: rgba(255,255,255,0.8);

    font-size: 17px;

    margin-bottom: 9px;

}


.summary-label {

    font-size: 11px;

    color: var(--text-light);

    margin-bottom: 2px;

}


.summary-value {

    font-size: 20px;

    font-weight: 600;

    color: var(--primary-dark);

    line-height: 1.3;

}


.summary-unit {

    font-size: 10px;

    font-weight: 400;

    color: var(--text-light);

}


/* =========================
   Section
========================= */

.section {

    margin-bottom: 27px;

}


.section-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 11px;

}


.section-title {

    margin: 0;

    font-size: 16px;

    font-weight: 500;

    color: var(--text);

}


.section-line {

    flex: 1;

    height: 1px;

    background: var(--border);

    margin-left: 12px;

}


/* =========================
   Sale Summary
========================= */

.sale-card {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12px;

    padding: 14px 15px;

    margin-bottom: 8px;

    background: #FFFFFF;

    border: 1px solid var(--border);

    border-radius: 16px;

}


.sale-left {

    display: flex;

    align-items: center;

    gap: 11px;

    min-width: 0;

}


.sale-icon {

    width: 38px;

    height: 38px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 12px;

    background: var(--primary-light);

    font-size: 16px;

}


.sale-name {

    font-size: 13px;

    font-weight: 500;

}


.sale-count {

    margin-top: 2px;

    font-size: 11px;

    color: var(--text-light);

}


.sale-total {

    font-size: 14px;

    font-weight: 600;

    color: var(--primary-dark);

    white-space: nowrap;

}


/* =========================
   Best Products
========================= */

.product-card {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 13px 14px;

    margin-bottom: 8px;

    background: #FFFFFF;

    border: 1px solid var(--border);

    border-radius: 16px;

}


.product-rank {

    width: 31px;

    height: 31px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 10px;

    background: var(--primary-light);

    color: var(--primary-dark);

    font-size: 13px;

    font-weight: 600;

}


.product-info {

    flex: 1;

    min-width: 0;

}


.product-name {

    font-size: 13px;

    font-weight: 500;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;

}


.product-size {

    margin-top: 2px;

    font-size: 11px;

    color: var(--text-light);

}


.product-quantity {

    font-size: 13px;

    font-weight: 600;

    color: var(--primary-dark);

    white-space: nowrap;

}


/* =========================
   Debt
========================= */

.debt-card {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12px;

    padding: 13px 14px;

    margin-bottom: 8px;

    background: var(--red-light);

    border: 1px solid #F5DCDC;

    border-radius: 16px;

}


.debt-left {

    display: flex;

    align-items: center;

    gap: 11px;

    min-width: 0;

}


.debt-icon {

    width: 38px;

    height: 38px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 12px;

    background: #FFFFFF;

    font-size: 16px;

}


.debt-info {

    min-width: 0;

}


.debt-name {

    font-size: 13px;

    font-weight: 500;

}


.debt-phone {

    margin-top: 2px;

    font-size: 11px;

    color: var(--text-light);

}


.debt-amount {

    font-size: 14px;

    font-weight: 600;

    color: var(--red);

    white-space: nowrap;

}


/* =========================
   Empty
========================= */

.empty {

    padding: 20px 15px;

    border: 1px dashed var(--border);

    border-radius: 16px;

    text-align: center;

    color: var(--text-light);

    font-size: 12px;

}


/* =========================
   Desktop
========================= */

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

            รายงานและตรวจสอบข้อมูล

        </div>

    </div>


    <div class="subtitle">

        สรุปข้อมูลการขายและการซื้อเชื่อ

    </div>



    <!-- =========================
         Summary Cards
    ========================= -->

    <div class="summary-grid">


        <!-- Sales -->

        <div class="summary-card">

            <div class="summary-icon">
                💰
            </div>

            <div class="summary-label">
                ยอดขายทั้งหมด
            </div>

            <div class="summary-value">

                <?= number_format($total_sales) ?>

                <span class="summary-unit">
                    บาท
                </span>

            </div>

        </div>


        <!-- Payment -->

        <div class="summary-card">

            <div class="summary-icon">
                💵
            </div>

            <div class="summary-label">
                ยอดชำระเงิน
            </div>

            <div class="summary-value">

                <?= number_format($total_payment) ?>

                <span class="summary-unit">
                    บาท
                </span>

            </div>

        </div>


        <!-- Outstanding -->

        <div class="summary-card">

            <div class="summary-icon">
                📌
            </div>

            <div class="summary-label">
                ยอดค้างชำระ
            </div>

            <div class="summary-value">

                <?= number_format($total_outstanding) ?>

                <span class="summary-unit">
                    บาท
                </span>

            </div>

        </div>


        <!-- Credit Customers -->

        <div class="summary-card">

            <div class="summary-icon">
                👥
            </div>

            <div class="summary-label">
                ลูกค้าที่ซื้อเชื่อ
            </div>

            <div class="summary-value">

                <?= number_format($credit_customers) ?>

                <span class="summary-unit">
                    คน
                </span>

            </div>

        </div>

    </div>



    <!-- =========================
         Sale Summary
    ========================= -->

    <div class="section">


        <div class="section-header">

            <h2 class="section-title">
                สรุปยอดขาย
            </h2>

            <div class="section-line"></div>

        </div>


        <?php if ($result_sale_summary->num_rows > 0): ?>


            <?php while ($sale = $result_sale_summary->fetch_assoc()): ?>


                <div class="sale-card">


                    <div class="sale-left">


                        <div class="sale-icon">

                            <?= $sale["sale_type"] === "เงินสด" ? "💵" : "📝" ?>

                        </div>


                        <div>


                            <div class="sale-name">

                                <?= htmlspecialchars($sale["sale_type"]) ?>

                            </div>


                            <div class="sale-count">

                                <?= number_format($sale["sale_count"]) ?>

                                รายการ

                            </div>


                        </div>


                    </div>


                    <div class="sale-total">

                        <?= number_format($sale["sale_total"]) ?>

                        บาท

                    </div>


                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <div class="empty">

                ยังไม่มีข้อมูลการขาย

            </div>


        <?php endif; ?>


    </div>



    <!-- =========================
         Best Selling Products
    ========================= -->

    <div class="section">


        <div class="section-header">

            <h2 class="section-title">
                สินค้าขายดี
            </h2>

            <div class="section-line"></div>

        </div>


        <?php if ($result_best_products->num_rows > 0): ?>


            <?php
            $rank = 1;
            ?>


            <?php while ($product = $result_best_products->fetch_assoc()): ?>


                <div class="product-card">


                    <div class="product-rank">

                        <?= $rank ?>

                    </div>


                    <div class="product-info">


                        <div class="product-name">

                            <?= htmlspecialchars(
                                $product["product_name"]
                            ) ?>

                        </div>


                        <div class="product-size">

                            <?= htmlspecialchars(
                                $product["size_quantity"]
                            ) ?>

                            <?= htmlspecialchars(
                                $product["unit"]
                            ) ?>

                        </div>


                    </div>


                    <div class="product-quantity">

                        <?= number_format(
                            $product["total_quantity"]
                        ) ?>

                        ชิ้น

                    </div>


                </div>


                <?php
                $rank++;
                ?>


            <?php endwhile; ?>


        <?php else: ?>


            <div class="empty">

                ยังไม่มีข้อมูลการขายสินค้า

            </div>


        <?php endif; ?>


    </div>



    <!-- =========================
         Customers With Debt
    ========================= -->

    <div class="section">


        <div class="section-header">

            <h2 class="section-title">
                ลูกค้าที่มียอดค้างชำระ
            </h2>

            <div class="section-line"></div>

        </div>


        <?php if ($result_customer_debt->num_rows > 0): ?>


            <?php while ($customer = $result_customer_debt->fetch_assoc()): ?>


                <div class="debt-card">


                    <div class="debt-left">


                        <div class="debt-icon">

                            👤

                        </div>


                        <div class="debt-info">


                            <div class="debt-name">

                                <?= htmlspecialchars(
                                    $customer["customer_name"]
                                ) ?>

                            </div>


                            <div class="debt-phone">

                                <?= htmlspecialchars(
                                    $customer["phone_number"]
                                ) ?>

                            </div>


                        </div>


                    </div>


                    <div class="debt-amount">

                        <?= number_format(
                            $customer["outstanding_amount"]
                        ) ?>

                        บาท

                    </div>


                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <div class="empty">

                ไม่มีลูกค้าที่มียอดค้างชำระ

            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>