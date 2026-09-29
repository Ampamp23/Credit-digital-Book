<?php
session_start();

require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: owner_login.php");
    exit;
}

/* ถ้าไม่มีสินค้าในรายการ */
if (
    !isset($_SESSION["sale_cart"]) ||
    count($_SESSION["sale_cart"]) === 0
) {
    header("Location: scan_barcode.php");
    exit;
}

$cart = $_SESSION["sale_cart"];

$total_amount = 0;

foreach ($cart as $item) {
    $total_amount += $item["total_price"];
}

$error = "";


/* =========================
   บันทึกรายการขาย
========================= */

if (isset($_POST["save_sale"])) {

    $sale_type = $_POST["sale_type"] ?? "";

    if (
        $sale_type !== "เงินสด" &&
        $sale_type !== "ซื้อเชื่อ"
    ) {

        $error = "กรุณาเลือกประเภทการขาย";

    } else {

        $conn->begin_transaction();

        try {

            /* =========================
               1. บันทึกรายการขาย
            ========================= */

            $stmt_sale = $conn->prepare("
                INSERT INTO sales
                (
                    user_id,
                    sale_date,
                    sale_type,
                    total_amount
                )
                VALUES (?, CURDATE(), ?, ?)
            ");

            $stmt_sale->bind_param(
                "isi",
                $_SESSION["user_id"],
                $sale_type,
                $total_amount
            );

            if (!$stmt_sale->execute()) {
                throw new Exception(
                    "ไม่สามารถบันทึกรายการขายได้"
                );
            }

            $sale_id = $conn->insert_id;

            $stmt_sale->close();


            /* =========================
               2. บันทึกรายละเอียดการขาย
            ========================= */

            $stmt_detail = $conn->prepare("
                INSERT INTO sale_details
                (
                    sale_id,
                    product_id,
                    quantity,
                    selling_price,
                    total_price
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($cart as $item) {

                $stmt_detail->bind_param(
                    "iiiii",
                    $sale_id,
                    $item["product_id"],
                    $item["quantity"],
                    $item["selling_price"],
                    $item["total_price"]
                );

                if (!$stmt_detail->execute()) {
                    throw new Exception(
                        "ไม่สามารถบันทึกรายละเอียดการขายได้"
                    );
                }
            }

            $stmt_detail->close();


            /* ยืนยันข้อมูลทั้งหมด */
            $conn->commit();


            /* เก็บ sale_id ไว้สำหรับหน้าถัดไป */
            $_SESSION["last_sale_id"] = $sale_id;
            $_SESSION["last_sale_type"] = $sale_type;
            $_SESSION["last_sale_total"] = $total_amount;


            /* ล้างตะกร้า */
            unset($_SESSION["sale_cart"]);


            /* ไปหน้าผลการบันทึก */
            header(
                "Location: sale_result.php"
            );

            exit;


        } catch (Exception $e) {

            $conn->rollback();

            $error =
                "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>สรุปรายการขาย</title>

<link rel="preconnect"
      href="https://fonts.googleapis.com">

<link rel="preconnect"
      href="https://fonts.gstatic.com"
      crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap"
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

    margin: auto;

    background: var(--white);

    padding: 22px 20px 30px;
}


/* =========================
   Header
========================= */

.header {
    position: relative;

    display: flex;

    align-items: center;
    justify-content: center;

    margin-bottom: 24px;
}

.back-button {
    position: absolute;

    left: 0;

    width: 42px;
    height: 42px;

    display: flex;

    align-items: center;
    justify-content: center;

    text-decoration: none;

    font-size: 30px;
    font-weight: 300;

    color: var(--primary-dark);

    border-radius: 14px;

    background: var(--primary-light);
}

.header h1 {
    margin: 0;

    font-size: 23px;

    font-weight: 600;

    color: var(--primary-dark);
}


/* =========================
   Summary Card
========================= */

.summary-card {
    background: var(--primary-light);

    border: 1px solid var(--border);

    border-radius: 22px;

    padding: 18px;

    box-shadow:
        0 4px 12px rgba(67,143,184,.08);
}


/* =========================
   Product Item
========================= */

.product-item {
    padding: 12px 0;

    border-bottom: 1px solid rgba(90,166,207,.25);
}

.product-name-row {
    display: flex;

    justify-content: space-between;

    gap: 10px;

    font-size: 14px;

    font-weight: 500;
}

.product-detail-row {
    display: flex;

    justify-content: space-between;

    margin-top: 5px;

    font-size: 13px;

    color: var(--text-light);
}


/* =========================
   Total
========================= */

.total-row {
    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-top: 15px;

    padding-top: 13px;

    border-top: 1px solid rgba(90,166,207,.35);

    font-size: 14px;
}

.total-price {
    font-size: 18px;

    font-weight: 600;

    color: var(--primary-dark);
}


/* =========================
   Sale Type
========================= */

.sale-type {
    margin-top: 28px;

    text-align: center;
}

.sale-type-title {
    margin-bottom: 12px;

    font-size: 14px;

    font-weight: 500;
}

.radio-option {
    display: flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    margin-bottom: 10px;

    font-size: 14px;

    cursor: pointer;
}

.radio-option input {
    width: 16px;
    height: 16px;

    accent-color: var(--primary);
}


/* =========================
   Button
========================= */

.primary-button {
    width: 100%;

    margin-top: 20px;

    padding: 13px;

    border: none;

    border-radius: 14px;

    background: var(--primary);

    color: var(--white);

    font-family: "Prompt", sans-serif;

    font-size: 15px;

    font-weight: 500;

    cursor: pointer;
}

.primary-button:hover {
    background: var(--primary-dark);
}


/* =========================
   Error
========================= */

.alert {
    margin-bottom: 18px;

    padding: 12px 14px;

    border-radius: 13px;

    text-align: center;

    font-size: 13px;

    background: #FDECEC;

    color: var(--danger);

    border: 1px solid #F5C8C6;
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
            0 20px 50px rgba(0,0,0,.12);

        overflow-y: auto;
    }
}

</style>

</head>


<body>

<div class="app">


    <!-- Header -->

    <div class="header">

        <a href="scan_barcode.php"
           class="back-button">

            ‹

        </a>

        <h1>
            สรุปรายการขาย
        </h1>

    </div>


    <!-- Error -->

    <?php if ($error): ?>

        <div class="alert">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <!-- Summary -->

    <div class="summary-card">


        <?php foreach ($cart as $item): ?>

            <div class="product-item">

                <div class="product-name-row">

                    <span>
                        <?= htmlspecialchars(
                            $item["product_name"]
                        ) ?>
                        <?= htmlspecialchars(
                            $item["size_quantity"]
                        ) ?>
                        <?= htmlspecialchars(
                            $item["unit"]
                        ) ?>
                    </span>

                    <span>
                        <?= $item["quantity"] ?> ชิ้น
                    </span>

                </div>


                <div class="product-detail-row">

                    <span>
                        <?= number_format(
                            $item["selling_price"]
                        ) ?> บาท ×
                        <?= $item["quantity"] ?>
                    </span>

                    <span>
                        <?= number_format(
                            $item["total_price"]
                        ) ?> บาท
                    </span>

                </div>

            </div>

        <?php endforeach; ?>


        <!-- Total -->

        <div class="total-row">

            <span>
                ยอดรวม
            </span>

            <span class="total-price">

                <?= number_format(
                    $total_amount
                ) ?> บาท

            </span>

        </div>

    </div>


    <!-- Sale Type -->

    <form method="POST">

        <div class="sale-type">

            <div class="sale-type-title">
                ประเภทการขาย
            </div>


            <label class="radio-option">

                <input
                    type="radio"
                    name="sale_type"
                    value="เงินสด"
                    required
                >

                <span>
                    เงินสด
                </span>

            </label>


            <label class="radio-option">

                <input
                    type="radio"
                    name="sale_type"
                    value="ซื้อเชื่อ"
                    required
                >

                <span>
                    ซื้อเชื่อ
                </span>

            </label>

        </div>


        <button
            type="submit"
            name="save_sale"
            class="primary-button">

            บันทึกรายการขาย

        </button>

    </form>


</div>

</body>

</html>