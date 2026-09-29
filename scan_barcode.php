<?php

session_start();

require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: owner_login.php");
    exit;
}


/* =========================
   เริ่มต้นตะกร้า
========================= */

if (!isset($_SESSION["sale_cart"])) {
    $_SESSION["sale_cart"] = [];
}


$error = "";
$product = null;


/* =========================
   ตรวจสอบบาร์โค้ด
========================= */

if (isset($_POST["check_barcode"])) {

    $barcode = trim($_POST["barcode"] ?? "");

    if ($barcode === "") {

        $error = "กรุณาสแกนหรือกรอกรหัสบาร์โค้ด";

    } else {

        $stmt = $conn->prepare("
            SELECT
                product_id,
                barcode,
                product_name,
                size_quantity,
                unit,
                price
            FROM products
            WHERE barcode = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $barcode);
        $stmt->execute();

        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        $stmt->close();

        if (!$product) {
            $error = "ไม่พบข้อมูลสินค้าจากรหัสบาร์โค้ดนี้";
        }
    }
}


/* =========================
   เพิ่มสินค้าเข้าตะกร้า
========================= */

if (isset($_POST["add_product"])) {

    $product_id = (int)($_POST["product_id"] ?? 0);
    $quantity = (int)($_POST["quantity"] ?? 0);

    if ($product_id <= 0 || $quantity <= 0) {

        $error = "กรุณาระบุจำนวนสินค้าให้ถูกต้อง";

    } else {

        $stmt = $conn->prepare("
            SELECT
                product_id,
                barcode,
                product_name,
                size_quantity,
                unit,
                price
            FROM products
            WHERE product_id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $product_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        $stmt->close();

        if (!$product) {

            $error = "ไม่พบข้อมูลสินค้า";

        } else {

            $found = false;

            foreach ($_SESSION["sale_cart"] as &$item) {

                if (
                    (int)$item["product_id"]
                    === $product_id
                ) {

                    $item["quantity"] += $quantity;

                    $item["total_price"] =
                        $item["quantity"]
                        * $item["selling_price"];

                    $found = true;

                    break;
                }
            }

            unset($item);


            /* ถ้ายังไม่มีสินค้าในตะกร้า */
            if (!$found) {

                $_SESSION["sale_cart"][] = [

                    "product_id" =>
                        (int)$product["product_id"],

                    "barcode" =>
                        $product["barcode"],

                    "product_name" =>
                        $product["product_name"],

                    "size_quantity" =>
                        $product["size_quantity"],

                    "unit" =>
                        $product["unit"],

                    "quantity" =>
                        $quantity,

                    "selling_price" =>
                        (int)$product["price"],

                    "total_price" =>
                        $quantity
                        * (int)$product["price"]
                ];
            }


            /*
             * เพิ่มสินค้าแล้ว
             * กลับมาหน้าสแกนเพื่อเพิ่มสินค้าอื่นต่อ
             */
            header("Location: scan_barcode.php");
            exit;
        }
    }
}


/* =========================
   ล้างรายการขาย
========================= */

if (isset($_POST["clear_cart"])) {

    unset($_SESSION["sale_cart"]);

    header("Location: scan_barcode.php");
    exit;
}


/* จำนวนสินค้าในตะกร้า */
$cart_count = count($_SESSION["sale_cart"]);

?>

<!DOCTYPE html>
<html lang="th">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

<title>สแกนบาร์โค้ดสินค้า</title>

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

<!-- Barcode Scanner -->
<script src="https://unpkg.com/html5-qrcode"></script>


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

    margin-bottom: 22px;
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
   Scanner
========================= */

.scanner-card {
    background: var(--primary-light);

    border: 1px solid var(--border);

    border-radius: 22px;

    padding: 16px;

    box-shadow:
        0 4px 12px rgba(67,143,184,.08);
}

.scanner-title {
    text-align: center;

    font-size: 14px;

    font-weight: 500;

    margin-bottom: 12px;
}

#reader {
    width: 100%;

    overflow: hidden;

    border-radius: 16px;
}


/* =========================
   Manual barcode
========================= */

.manual-title {
    margin-top: 18px;

    margin-bottom: 8px;

    text-align: center;

    font-size: 12px;

    color: var(--text-light);
}

.barcode-form {
    display: flex;

    gap: 8px;
}

.barcode-input {
    flex: 1;

    min-width: 0;

    height: 44px;

    padding: 0 13px;

    border: 1px solid var(--border);

    border-radius: 12px;

    outline: none;

    font-family: "Prompt", sans-serif;

    font-size: 13px;
}

.barcode-input:focus {
    border-color: var(--primary);

    box-shadow:
        0 0 0 3px rgba(90,166,207,.12);
}

.check-button {
    width: 90px;

    border: none;

    border-radius: 12px;

    background: var(--primary);

    color: var(--white);

    font-family: "Prompt", sans-serif;

    font-size: 13px;

    cursor: pointer;
}


/* =========================
   Error
========================= */

.alert {
    margin-bottom: 14px;

    padding: 11px 13px;

    border-radius: 12px;

    text-align: center;

    font-size: 12px;

    background: #FDECEC;

    color: var(--danger);

    border: 1px solid #F5C8C6;
}


/* =========================
   Product
========================= */

.product-card {
    margin-top: 18px;

    background: var(--white);

    border: 1px solid var(--border);

    border-radius: 18px;

    padding: 17px;

    box-shadow:
        0 3px 10px rgba(67,143,184,.08);
}

.product-name {
    font-size: 16px;

    font-weight: 500;

    margin-bottom: 5px;
}

.product-detail {
    font-size: 12px;

    color: var(--text-light);

    margin-bottom: 14px;
}

.product-price {
    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 14px;
}

.product-price-label {
    font-size: 13px;
}

.product-price-value {
    font-size: 18px;

    font-weight: 600;

    color: var(--primary-dark);
}


/* =========================
   Quantity
========================= */

.quantity-title {
    font-size: 13px;

    margin-bottom: 8px;
}

.quantity-control {
    display: flex;

    align-items: center;

    justify-content: center;

    gap: 16px;

    margin-bottom: 14px;
}

.quantity-button {
    width: 36px;
    height: 36px;

    border: none;

    border-radius: 50%;

    background: var(--primary-light);

    color: var(--primary-dark);

    font-size: 20px;

    cursor: pointer;
}

.quantity-input {
    width: 55px;

    height: 36px;

    border: 1px solid var(--border);

    border-radius: 10px;

    text-align: center;

    font-family: "Prompt", sans-serif;

    font-size: 14px;
}


/* =========================
   Add
========================= */

.add-button {
    width: 100%;

    height: 44px;

    border: none;

    border-radius: 13px;

    background: var(--primary);

    color: var(--white);

    font-family: "Prompt", sans-serif;

    font-size: 14px;

    font-weight: 500;

    cursor: pointer;
}

.add-button:hover {
    background: var(--primary-dark);
}


/* =========================
   Cart
========================= */

.cart-section {
    margin-top: 22px;
}

.cart-header {
    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 10px;
}

.cart-title {
    font-size: 14px;

    font-weight: 500;
}

.cart-count {
    font-size: 12px;

    color: var(--text-light);
}

.cart-item {
    display: flex;

    justify-content: space-between;

    gap: 10px;

    padding: 10px 0;

    border-bottom: 1px solid var(--border);

    font-size: 12px;
}

.cart-item-name {
    min-width: 0;
}

.cart-item-price {
    white-space: nowrap;

    font-weight: 500;
}

.summary-button {
    width: 100%;

    height: 44px;

    margin-top: 16px;

    border: none;

    border-radius: 13px;

    background: var(--primary-dark);

    color: var(--white);

    font-family: "Prompt", sans-serif;

    font-size: 14px;

    font-weight: 500;

    cursor: pointer;
}

.clear-button {
    width: 100%;

    margin-top: 8px;

    padding: 10px;

    border: none;

    background: transparent;

    color: var(--danger);

    font-family: "Prompt", sans-serif;

    font-size: 12px;

    cursor: pointer;
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

        <a
            href="owner_home.php"
            class="back-button">

            ‹

        </a>

        <h1>
            สแกนบาร์โค้ดสินค้า
        </h1>

    </div>


    <?php if ($error): ?>

        <div class="alert">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <!-- Scanner -->

    <div class="scanner-card">

        <div class="scanner-title">
            สแกนบาร์โค้ดสินค้า
        </div>

        <div id="reader"></div>


        <div class="manual-title">
            หรือกรอกรหัสบาร์โค้ด
        </div>


        <form
            method="POST"
            class="barcode-form">

            <input
                type="text"
                name="barcode"
                class="barcode-input"
                placeholder="กรอกรหัสบาร์โค้ด"
                inputmode="numeric"
                autocomplete="off">

            <button
                type="submit"
                name="check_barcode"
                class="check-button">

                ค้นหา

            </button>

        </form>

    </div>


    <?php if ($product): ?>

        <!-- Product -->

        <div class="product-card">

            <div class="product-name">

                <?= htmlspecialchars(
                    $product["product_name"]
                ) ?>

            </div>


            <div class="product-detail">

                <?= htmlspecialchars(
                    $product["size_quantity"]
                ) ?>

                <?= htmlspecialchars(
                    $product["unit"]
                ) ?>

            </div>


            <div class="product-price">

                <span class="product-price-label">
                    ราคาขาย
                </span>

                <span class="product-price-value">

                    <?= number_format(
                        $product["price"]
                    ) ?>

                    บาท

                </span>

            </div>


            <form method="POST">

                <input
                    type="hidden"
                    name="product_id"
                    value="<?= (int)$product["product_id"] ?>">


                <div class="quantity-title">
                    จำนวนสินค้า
                </div>


                <div class="quantity-control">

                    <button
                        type="button"
                        class="quantity-button"
                        onclick="changeQuantity(-1)">

                        −

                    </button>


                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        class="quantity-input"
                        value="1"
                        min="1"
                        required>


                    <button
                        type="button"
                        class="quantity-button"
                        onclick="changeQuantity(1)">

                        +

                    </button>

                </div>


                <button
                    type="submit"
                    name="add_product"
                    class="add-button">

                    เพิ่มรายการสินค้า

                </button>

            </form>

        </div>

    <?php endif; ?>


    <?php if ($cart_count > 0): ?>

        <!-- Cart -->

        <div class="cart-section">

            <div class="cart-header">

                <span class="cart-title">
                    รายการสินค้าที่เพิ่ม
                </span>

                <span class="cart-count">
                    <?= $cart_count ?> รายการ
                </span>

            </div>


            <?php foreach ($_SESSION["sale_cart"] as $item): ?>

                <div class="cart-item">

                    <span class="cart-item-name">

                        <?= htmlspecialchars(
                            $item["product_name"]
                        ) ?>

                        ×
                        <?= $item["quantity"] ?>

                    </span>

                    <span class="cart-item-price">

                        <?= number_format(
                            $item["total_price"]
                        ) ?>

                        บาท

                    </span>

                </div>

            <?php endforeach; ?>


            <form
                method="POST"
                action="sale_summary.php">

                <button
                    type="submit"
                    class="summary-button">

                    สรุปรายการขาย

                </button>

            </form>


            <form method="POST">

                <button
                    type="submit"
                    name="clear_cart"
                    class="clear-button">

                    ล้างรายการทั้งหมด

                </button>

            </form>

        </div>

    <?php endif; ?>


</div>


<script>

/* =========================
   จำนวนสินค้า
========================= */

function changeQuantity(change) {

    const input =
        document.getElementById("quantity");

    if (!input) return;

    let value =
        parseInt(input.value) || 1;

    value += change;

    if (value < 1) {
        value = 1;
    }

    input.value = value;
}


/* =========================
   Barcode Scanner
========================= */

function onScanSuccess(decodedText) {

    const form =
        document.createElement("form");

    form.method = "POST";

    form.style.display = "none";


    const input =
        document.createElement("input");

    input.type = "hidden";

    input.name = "barcode";

    input.value = decodedText;


    const submit =
        document.createElement("input");

    submit.type = "hidden";

    submit.name = "check_barcode";

    submit.value = "1";


    form.appendChild(input);

    form.appendChild(submit);

    document.body.appendChild(form);

    form.submit();
}


function onScanFailure(error) {
    /* ไม่ต้องแสดงข้อความระหว่างกำลังสแกน */
}


const scanner =
    new Html5QrcodeScanner(
        "reader",
        {
            fps: 10,
            qrbox: {
                width: 250,
                height: 120
            }
        },
        false
    );


scanner.render(
    onScanSuccess,
    onScanFailure
);

</script>

</body>

</html>