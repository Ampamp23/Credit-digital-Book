<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: owner_login.php");
    exit;
}

require_once "config/database.php";


/* ตรวจสอบ customer_id */

if (!isset($_GET["customer_id"]) || !is_numeric($_GET["customer_id"])) {
    header("Location: customers.php");
    exit;
}

$customer_id = (int)$_GET["customer_id"];


/* ดึงข้อมูลลูกค้า */

$stmt = $conn->prepare("
    SELECT
        customer_id,
        customer_name,
        phone_number,
        credit_limit
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


/* ดึงข้อมูลรอบเครดิตล่าสุด */

$stmt = $conn->prepare("
    SELECT
        credit_round_id,
        start_date,
        due_date,
        credit_limit,
        outstanding_amount,
        payment_status
    FROM credit_rounds
    WHERE customer_id = ?
    ORDER BY credit_round_id DESC
    LIMIT 1
");

$stmt->bind_param("i", $customer_id);
$stmt->execute();

$round_result = $stmt->get_result();

$credit_round = null;

if ($round_result->num_rows > 0) {
    $credit_round = $round_result->fetch_assoc();
}

$stmt->close();


/* กำหนดสถานะ */

if ($credit_round === null) {

    $status_text = "ชำระครบ";
    $status_class = "status-paid";

} elseif ($credit_round["payment_status"] === "ชำระครบ") {

    $status_text = "ชำระครบ";
    $status_class = "status-paid";

} elseif ($credit_round["payment_status"] === "ชำระบางส่วน") {

    $status_text = "ชำระบางส่วน";
    $status_class = "status-partial";

} else {

    $status_text = "ยังไม่ชำระ";
    $status_class = "status-unpaid";

}


/* ข้อมูลเครดิต */

if ($credit_round !== null) {

    $due_date = date(
        "d/m/Y",
        strtotime($credit_round["due_date"])
    );

    $outstanding = (int)$credit_round["outstanding_amount"];

} else {

    $due_date = "-";
    $outstanding = 0;

}

?>

<!DOCTYPE html>
<html lang="th">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>ข้อมูลลูกค้า</title>

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

    --green: #35A56A;
    --green-light: #EAF7F0;

    --yellow: #D99A00;
    --yellow-light: #FFF7E3;

    --red: #D9534F;
    --red-light: #FDECEC;
}

* {
    box-sizing: border-box;
}

html,
body {
    margin: 0;
    min-height: 100%;
}

body {
    font-family: "Prompt", sans-serif;
    background: var(--background);
    color: var(--text);
    overflow-x: hidden;
}

.app {
    width: 100%;
    max-width: 490px;
    min-height: 100vh;

    margin: auto;

    background: #fff;

    padding: 24px 18px 30px;
}


/* =========================================================
   TOP BAR
   ========================================================= */

.top-bar {
    position: relative;

    display: flex;
    align-items: center;
    justify-content: center;

    margin-bottom: 24px;
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


/* =========================================================
   CUSTOMER PROFILE
   ========================================================= */

.profile-card {
    background: #fff;

    border: 1px solid var(--border);
    border-radius: 20px;

    padding: 20px;

    box-shadow: 0 4px 12px rgba(0,0,0,0.05);

    margin-bottom: 14px;
}

.profile-icon {
    width: 64px;
    height: 64px;

    margin: 0 auto 14px;

    border-radius: 50%;

    background: var(--primary-light);

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 30px;
}

.profile-name {
    text-align: center;

    font-size: 20px;
    font-weight: 600;

    margin-bottom: 5px;
}

.profile-phone {
    text-align: center;

    font-size: 13px;

    color: var(--text-light);
}


/* =========================================================
   SECTION
   ========================================================= */

.section-card {
    background: #fff;

    border: 1px solid var(--border);
    border-radius: 18px;

    padding: 17px;

    margin-bottom: 12px;

    box-shadow: 0 4px 12px rgba(0,0,0,0.04);
}

.section-title {
    font-size: 15px;
    font-weight: 600;

    color: var(--primary-dark);

    margin-bottom: 14px;
}


/* =========================================================
   DATA ROW
   ========================================================= */

.data-row {
    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 15px;

    padding: 10px 0;

    border-bottom: 1px solid #F1F3F4;
}

.data-row:last-child {
    border-bottom: none;
}

.data-label {
    font-size: 13px;
    color: var(--text-light);
}

.data-value {
    font-size: 14px;
    font-weight: 500;

    text-align: right;
}


/* =========================================================
   STATUS
   ========================================================= */

.status {
    display: inline-flex;

    padding: 5px 10px;

    border-radius: 20px;

    font-size: 12px;
    font-weight: 400;
}

.status-paid {
    background: var(--green-light);
    color: var(--green);
}

.status-partial {
    background: var(--yellow-light);
    color: var(--yellow);
}

.status-unpaid {
    background: var(--red-light);
    color: var(--red);
}


/* =========================================================
   BACK BUTTON
   ========================================================= */

.bottom-button {
    width: 100%;
    height: 46px;

    border-radius: 13px;

    background: var(--primary);

    color: #fff;

    text-decoration: none;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 14px;
    font-weight: 500;

    margin-top: 6px;
}

.bottom-button:hover {
    background: var(--primary-dark);
}


/* =========================================================
   DESKTOP
   ========================================================= */

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

        overflow-y: auto;
    }

}


/* =========================================================
   MOBILE
   ========================================================= */

@media (max-width: 767px) {

    .app {
        min-height: 100vh;

        height: auto;

        overflow: visible;
    }

}

</style>

</head>

<body>

<div class="app">


    <!-- TOP BAR -->

    <div class="top-bar">

        <a
            href="customers.php"
            class="back-button"
        >
            ‹
        </a>

        <h1>
            ข้อมูลลูกค้า
        </h1>

    </div>


    <!-- CUSTOMER PROFILE -->

    <div class="profile-card">

        <div class="profile-icon">
            👤
        </div>

        <div class="profile-name">

            <?php
            echo htmlspecialchars(
                $customer["customer_name"]
            );
            ?>

        </div>

        <div class="profile-phone">

            <?php
            echo htmlspecialchars(
                $customer["phone_number"]
            );
            ?>

        </div>

    </div>


    <!-- CREDIT INFORMATION -->

    <div class="section-card">

        <div class="section-title">
            ข้อมูลเครดิต
        </div>


        <div class="data-row">

            <div class="data-label">
                วงเงินเครดิต
            </div>

            <div class="data-value">

                <?php
                echo number_format(
                    $customer["credit_limit"]
                );
                ?>

                บาท

            </div>

        </div>


        <div class="data-row">

            <div class="data-label">
                ยอดค้างชำระ
            </div>

            <div class="data-value">

                <?php
                echo number_format($outstanding);
                ?>

                บาท

            </div>

        </div>


        <div class="data-row">

            <div class="data-label">
                วันครบกำหนดชำระ
            </div>

            <div class="data-value">

                <?php echo $due_date; ?>

            </div>

        </div>


        <div class="data-row">

            <div class="data-label">
                สถานะการชำระ
            </div>

            <div class="data-value">

                <span class="status <?php echo $status_class; ?>">

                    <?php
                    echo $status_text;
                    ?>

                </span>

            </div>

        </div>

    </div>


    <!-- CREDIT ROUND -->

    <?php if ($credit_round !== null): ?>

    <div class="section-card">

        <div class="section-title">
            ข้อมูลรอบเครดิตล่าสุด
        </div>


        <div class="data-row">

            <div class="data-label">
                วันที่เริ่มรอบ
            </div>

            <div class="data-value">

                <?php

                echo date(
                    "d/m/Y",
                    strtotime(
                        $credit_round["start_date"]
                    )
                );

                ?>

            </div>

        </div>


        <div class="data-row">

            <div class="data-label">
                วงเงินของรอบ
            </div>

            <div class="data-value">

                <?php
                echo number_format(
                    $credit_round["credit_limit"]
                );
                ?>

                บาท

            </div>

        </div>

    </div>

    <?php endif; ?>


    <!-- BACK -->

    <a
        href="customers.php"
        class="bottom-button"
    >
        กลับหน้าข้อมูลลูกค้า
    </a>


</div>

</body>

</html>