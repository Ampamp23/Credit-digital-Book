<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: owner_login.php");
    exit;
}

require_once "config/database.php";

$customer_id = (int)($_GET["customer_id"] ?? 0);

if ($customer_id <= 0) {
    die("ไม่พบข้อมูลลูกค้า");
}


/* =========================
   ดึงข้อมูลลูกค้า
========================= */

$sql_customer = "
    SELECT
        customer_id,
        customer_name,
        phone_number,
        credit_limit
    FROM customers
    WHERE customer_id = ?
";

$stmt_customer = $conn->prepare($sql_customer);
$stmt_customer->bind_param("i", $customer_id);
$stmt_customer->execute();

$result_customer = $stmt_customer->get_result();
$customer = $result_customer->fetch_assoc();

if (!$customer) {
    die("ไม่พบข้อมูลลูกค้า");
}


/* =========================
   ดึงรอบเครดิตล่าสุด
========================= */

$sql_round = "
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
";

$stmt_round = $conn->prepare($sql_round);
$stmt_round->bind_param("i", $customer_id);
$stmt_round->execute();

$result_round = $stmt_round->get_result();
$round = $result_round->fetch_assoc();


/* =========================
   กำหนดข้อมูลเครดิต
========================= */

$credit_limit = (int)$customer["credit_limit"];

$outstanding = $round
    ? (int)$round["outstanding_amount"]
    : 0;

$due_date = $round
    ? $round["due_date"]
    : null;

$payment_status = $round
    ? $round["payment_status"]
    : "ชำระครบ";

$remaining_credit = $credit_limit - $outstanding;

if ($remaining_credit < 0) {
    $remaining_credit = 0;
}


/* =========================
   ดึงรายการซื้อเชื่อทั้งหมด
========================= */

$sql_purchases = "
    SELECT
        cp.credit_purchase_id,
        cp.credit_purchase_date,
        cp.credit_purchase_amount,
        cr.credit_round_id
    FROM credit_purchases cp
    INNER JOIN credit_rounds cr
        ON cp.credit_round_id = cr.credit_round_id
    WHERE cr.customer_id = ?
    ORDER BY cp.credit_purchase_date DESC,
             cp.credit_purchase_id DESC
";

$stmt_purchases = $conn->prepare($sql_purchases);
$stmt_purchases->bind_param("i", $customer_id);
$stmt_purchases->execute();

$result_purchases = $stmt_purchases->get_result();


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


/* =========================
   สถานะ
========================= */

$status_text = $payment_status;

if ($payment_status === "ชำระครบ") {
    $status_class = "status-paid";
} elseif ($payment_status === "ชำระบางส่วน") {
    $status_class = "status-partial";
} else {
    $status_class = "status-unpaid";
}

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>รายการซื้อเชื่อ</title>

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

            --green: #4CAF50;
            --green-light: #EAF7EA;

            --yellow: #D99A00;
            --yellow-light: #FFF6DD;

            --red: #D9534F;
            --red-light: #FDECEC;
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
            font-size: 21px;

            font-weight: 600;

            color: var(--primary-dark);
        }


        /* Customer information */

        .customer-card {
            padding: 18px;

            background: var(--primary-light);

            border: 1px solid var(--border);

            border-radius: 18px;

            margin-bottom: 18px;
        }


        .customer-header {
            display: flex;

            align-items: center;

            gap: 12px;

            margin-bottom: 16px;
        }


        .customer-icon {
            width: 46px;

            height: 46px;

            border-radius: 50%;

            background: var(--white);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 22px;

            flex-shrink: 0;
        }


        .customer-name {
            font-size: 17px;

            font-weight: 500;

            margin-bottom: 3px;
        }


        .customer-phone {
            font-size: 12px;

            color: var(--text-light);
        }


        .credit-grid {
            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 10px;
        }


        .credit-item {
            background: var(--white);

            border-radius: 12px;

            padding: 11px 12px;
        }


        .credit-label {
            font-size: 11px;

            color: var(--text-light);

            margin-bottom: 3px;
        }


        .credit-value {
            font-size: 14px;

            font-weight: 500;
        }


        .status {
            display: inline-flex;

            padding: 4px 9px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 400;
        }


        .status-paid {
            color: var(--green);

            background: var(--green-light);
        }


        .status-partial {
            color: var(--yellow);

            background: var(--yellow-light);
        }


        .status-unpaid {
            color: var(--red);

            background: var(--red-light);
        }


        /* Section title */

        .section-title {
            font-size: 16px;

            font-weight: 500;

            color: var(--primary-dark);

            margin: 0 0 12px;
        }


        /* Purchase list */

        .purchase-list {
            display: flex;

            flex-direction: column;

            gap: 10px;
        }


        .purchase-card {
            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 16px;

            padding: 15px;

            box-shadow: 0 3px 10px rgba(90, 166, 207, 0.08);
        }


        .purchase-top {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 10px;

            margin-bottom: 8px;
        }


        .purchase-date {
            font-size: 12px;

            color: var(--text-light);
        }


        .purchase-amount {
            font-size: 16px;

            font-weight: 600;

            color: var(--primary-dark);
        }


        .purchase-round {
            font-size: 11px;

            color: var(--text-light);

            margin-bottom: 10px;
        }


        .detail-button {
            display: block;

            width: 100%;

            padding: 9px;

            border: none;

            border-radius: 10px;

            background: var(--primary-light);

            color: var(--primary-dark);

            text-align: center;

            text-decoration: none;

            font-family: "Prompt", sans-serif;

            font-size: 12px;

            cursor: pointer;
        }


        .detail-button:hover {
            background: #DDF0FA;
        }


        /* Empty */

        .empty-message {
            padding: 30px 10px;

            text-align: center;

            color: var(--text-light);

            font-size: 13px;
        }


        /* Desktop */

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


    <!-- Customer information -->

    <div class="customer-card">

        <div class="customer-header">

            <div class="customer-icon">
                👤
            </div>

            <div>

                <div class="customer-name">
                    <?php
                    echo htmlspecialchars(
                        $customer["customer_name"]
                    );
                    ?>
                </div>

                <div class="customer-phone">
                    <?php
                    echo htmlspecialchars(
                        $customer["phone_number"]
                    );
                    ?>
                </div>

            </div>

        </div>


        <div class="credit-grid">

            <div class="credit-item">

                <div class="credit-label">
                    วงเงินเครดิต
                </div>

                <div class="credit-value">
                    <?php echo number_format($credit_limit); ?>
                    บาท
                </div>

            </div>


            <div class="credit-item">

                <div class="credit-label">
                    ยอดค้างชำระ
                </div>

                <div class="credit-value">
                    <?php echo number_format($outstanding); ?>
                    บาท
                </div>

            </div>


            <div class="credit-item">

                <div class="credit-label">
                    วงเงินคงเหลือ
                </div>

                <div class="credit-value">
                    <?php echo number_format($remaining_credit); ?>
                    บาท
                </div>

            </div>


            <div class="credit-item">

                <div class="credit-label">
                    วันครบกำหนด
                </div>

                <div class="credit-value">
                    <?php
                    echo $due_date
                        ? thaiDate($due_date)
                        : "-";
                    ?>
                </div>

            </div>

        </div>


        <div style="margin-top: 12px;">

            <span class="status <?php echo $status_class; ?>">
                <?php echo htmlspecialchars($status_text); ?>
            </span>

        </div>

    </div>


    <!-- Purchase list -->

    <h2 class="section-title">
        รายการซื้อเชื่อ
    </h2>


    <div class="purchase-list">

        <?php if ($result_purchases->num_rows > 0): ?>

            <?php while ($purchase = $result_purchases->fetch_assoc()): ?>

                <div class="purchase-card">

                    <div class="purchase-top">

                        <div class="purchase-date">
                            <?php
                            echo thaiDate(
                                $purchase["credit_purchase_date"]
                            );
                            ?>
                        </div>

                        <div class="purchase-amount">
                            <?php
                            echo number_format(
                                $purchase["credit_purchase_amount"]
                            );
                            ?>
                            บาท
                        </div>

                    </div>


                    <div class="purchase-round">
                        รอบเครดิต
                        <?php
                        echo (int)$purchase["credit_round_id"];
                        ?>
                    </div>


                    <a
                        href="credit_purchase_view.php?credit_purchase_id=<?php echo (int)$purchase["credit_purchase_id"]; ?>"
                        class="detail-button">
                        ดูรายละเอียด
                    </a>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty-message">
                ยังไม่มีรายการซื้อเชื่อ
            </div>

        <?php endif; ?>

    </div>


</div>

</body>

</html>