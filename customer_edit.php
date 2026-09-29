<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: owner_login.php");
    exit;
}

require_once "config/database.php";

if (!isset($_GET["customer_id"]) || !is_numeric($_GET["customer_id"])) {
    header("Location: customers.php");
    exit;
}

$customer_id = (int)$_GET["customer_id"];

$error = "";

/* ดึงข้อมูลลูกค้า */
$stmt = $conn->prepare("
    SELECT customer_name, phone_number, credit_limit
    FROM customers
    WHERE customer_id = ?
");

$stmt->bind_param("i", $customer_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: customers.php");
    exit;
}

$customer = $result->fetch_assoc();
$stmt->close();


/* บันทึกการแก้ไข */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $customer_name = trim($_POST["customer_name"]);
    $phone_number = trim($_POST["phone_number"]);
    $credit_limit = trim($_POST["credit_limit"]);

    if ($customer_name === "") {

        $error = "กรุณากรอกชื่อลูกค้า";

    } elseif ($phone_number === "") {

        $error = "กรุณากรอกเบอร์โทรศัพท์";

    } elseif (!preg_match("/^[0-9]{10}$/", $phone_number)) {

        $error = "กรุณากรอกเบอร์โทรศัพท์ 10 หลัก";

    } elseif ($credit_limit === "") {

        $error = "กรุณากรอกวงเงินเครดิต";

    } elseif (!is_numeric($credit_limit) || $credit_limit < 0) {

        $error = "กรุณากรอกวงเงินเครดิตให้ถูกต้อง";

    } else {

        $credit_limit = (int)$credit_limit;

        $stmt = $conn->prepare("
            UPDATE customers
            SET customer_name = ?,
                phone_number = ?,
                credit_limit = ?
            WHERE customer_id = ?
        ");

        $stmt->bind_param(
            "ssii",
            $customer_name,
            $phone_number,
            $credit_limit,
            $customer_id
        );

        if ($stmt->execute()) {

            $stmt->close();

            header("Location: customers.php");
            exit;

        } else {

            $error = "ไม่สามารถแก้ไขข้อมูลลูกค้าได้";
        }

        $stmt->close();
    }

    /* แสดงค่าที่ผู้ใช้เพิ่งกรอกกลับมา */
    $customer["customer_name"] = $customer_name;
    $customer["phone_number"] = $phone_number;
    $customer["credit_limit"] = $credit_limit;
}

?>

<!DOCTYPE html>
<html lang="th">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>แก้ไขลูกค้า</title>

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
    --text: #263238;
    --text-light: #7A858C;
    --border: #E8EDF0;
    --red: #D9534F;
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
    background: #fff;
    padding: 24px 18px 30px;
}

.top-bar {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 30px;
}

.back-button {
    position: absolute;
    left: 0;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: var(--primary-light);
    color: var(--primary-dark);
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    line-height: 1;
}

.top-bar h1 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
    color: var(--primary-dark);
}

.form-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 22px 18px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 7px;
}

.form-group input {
    width: 100%;
    height: 48px;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 0 14px;
    font-family: "Prompt", sans-serif;
    font-size: 14px;
    color: var(--text);
    outline: none;
}

.form-group input:focus {
    border-color: var(--primary);
}

.error {
    background: #FDECEC;
    color: var(--red);
    border-radius: 10px;
    padding: 10px 12px;
    margin-bottom: 18px;
    font-size: 13px;
}

.save-button {
    width: 100%;
    height: 48px;
    border: none;
    border-radius: 14px;
    background: var(--primary);
    color: white;
    font-family: "Prompt", sans-serif;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    margin-top: 5px;
}

.save-button:hover {
    background: var(--primary-dark);
}

@media (min-width: 768px) {

    body {
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .app {
        min-height: 760px;
        height: 850px;
        border-radius: 34px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.12);
    }

}

</style>

</head>

<body>

<div class="app">

    <div class="top-bar">

        <a href="customers.php" class="back-button">‹</a>

        <h1>แก้ไขลูกค้า</h1>

    </div>

    <div class="form-card">

        <?php if ($error !== ""): ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST" novalidate>

            <div class="form-group">

                <label for="customer_name">
                    ชื่อลูกค้า
                </label>

                <input
                    type="text"
                    id="customer_name"
                    name="customer_name"
                    placeholder="กรอกชื่อลูกค้า"
                    value="<?php echo htmlspecialchars($customer["customer_name"]); ?>"
                >

            </div>

            <div class="form-group">

                <label for="phone_number">
                    เบอร์โทรศัพท์
                </label>

                <input
                    type="tel"
                    id="phone_number"
                    name="phone_number"
                    placeholder="กรอกเบอร์โทรศัพท์ 10 หลัก"
                    maxlength="10"
                    value="<?php echo htmlspecialchars($customer["phone_number"]); ?>"
                >

            </div>

            <div class="form-group">

                <label for="credit_limit">
                    วงเงินเครดิต
                </label>

                <input
                    type="number"
                    id="credit_limit"
                    name="credit_limit"
                    placeholder="กรอกวงเงินเครดิต"
                    min="0"
                    step="1"
                    value="<?php echo htmlspecialchars($customer["credit_limit"]); ?>"
                >

            </div>

            <button
                type="submit"
                class="save-button"
            >
                บันทึกการแก้ไข
            </button>

        </form>

    </div>

</div>

</body>

</html>