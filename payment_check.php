<?php

session_start();

require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: owner_login.php");
    exit;
}

if (!isset($_GET["customer_id"]) || !isset($_GET["credit_round_id"])) {
    header("Location: payments.php");
    exit;
}

$customer_id = (int) $_GET["customer_id"];
$credit_round_id = (int) $_GET["credit_round_id"];


/* =========================
   ตรวจสอบข้อมูลลูกค้าและรอบเครดิต
========================= */

$sql = "
    SELECT
        c.customer_name,
        cr.credit_round_id
    FROM customers c
    INNER JOIN credit_rounds cr
        ON c.customer_id = cr.customer_id
    WHERE c.customer_id = ?
      AND cr.credit_round_id = ?
      AND cr.outstanding_amount > 0
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("เกิดข้อผิดพลาดในการตรวจสอบข้อมูล");
}

$stmt->bind_param(
    "ii",
    $customer_id,
    $credit_round_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: payments.php");
    exit;
}

$data = $result->fetch_assoc();

?>


<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>ตรวจสอบยอดหนี้</title>


    <link
        href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap"
        rel="stylesheet"
    >


    <style>

        :root {

            --primary: #5AA6CF;

            --primary-dark: #438FB8;

            --primary-light: #E8F5FC;

            --white: #FFFFFF;

            --text: #263238;

            --text-light: #7A858C;

            --border: #E8EDF0;

            --green: #4CAF50;

            --green-light: #E8F6EA;
        }


        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family: "Prompt", sans-serif;

            background: #E6D2BC;

            color: var(--text);
        }


        .app {

            width: 100%;

            max-width: 490px;

            min-height: 100vh;

            margin: 0 auto;

            background: var(--white);

            padding: 22px 18px 30px;

            display: flex;

            flex-direction: column;
        }


        /* =========================
           TOP BAR
        ========================= */

        .top-bar {

            display: flex;

            align-items: center;

            position: relative;

            margin-bottom: 25px;
        }


        .back-button {

            width: 40px;

            height: 40px;

            border-radius: 12px;

            background: var(--primary-light);

            color: var(--primary-dark);

            display: flex;

            align-items: center;

            justify-content: center;

            text-decoration: none;

            font-size: 28px;

            line-height: 1;
        }


        .page-title {

            position: absolute;

            left: 50%;

            transform: translateX(-50%);

            font-size: 21px;

            font-weight: 600;

            color: var(--primary-dark);

            white-space: nowrap;
        }


        /* =========================
           CONTENT
        ========================= */

        .check-card {

            background: var(--primary-light);

            border: 1px solid var(--border);

            border-radius: 22px;

            padding: 28px 20px;

            text-align: center;

            margin-top: 35px;
        }


        .check-icon {

            width: 72px;

            height: 72px;

            border-radius: 50%;

            background: var(--white);

            color: var(--primary);

            display: flex;

            align-items: center;

            justify-content: center;

            margin: 0 auto 18px;

            font-size: 34px;

            box-shadow:
                0 5px 14px
                rgba(90, 166, 207, 0.12);
        }


        .check-title {

            font-size: 20px;

            font-weight: 600;

            color: var(--primary-dark);

            margin-bottom: 8px;
        }


        .check-text {

            font-size: 13px;

            color: var(--text-light);

            line-height: 1.7;

            margin: 0;
        }


        .customer-name {

            color: var(--text);

            font-weight: 500;
        }


        /* =========================
           CHECK STATUS
        ========================= */

        .check-status {

            display: flex;

            align-items: center;

            gap: 10px;

            background: var(--white);

            border-radius: 14px;

            padding: 13px 15px;

            margin-top: 22px;

            text-align: left;
        }


        .status-icon {

            width: 28px;

            height: 28px;

            border-radius: 50%;

            background: var(--green-light);

            color: var(--green);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 16px;

            flex-shrink: 0;
        }


        .status-text {

            font-size: 13px;

            color: var(--text);
        }


        /* =========================
           BUTTON
        ========================= */

        .button {

            width: 100%;

            border: none;

            border-radius: 15px;

            padding: 14px;

            font-family: "Prompt", sans-serif;

            font-size: 15px;

            font-weight: 500;

            text-decoration: none;

            display: block;

            text-align: center;

            margin-top: 20px;
        }


        .button-primary {

            background: var(--primary);

            color: var(--white);

            box-shadow:
                0 5px 12px
                rgba(90, 166, 207, 0.22);
        }


        .button-primary:active {

            transform: scale(0.98);
        }


        /* =========================
           DESKTOP
        ========================= */

        @media (min-width: 700px) {

            body {
                padding: 25px 0;
            }


            .app {

                min-height: 760px;

                height: 850px;

                border-radius: 34px;

                box-shadow:
                    0 15px 45px
                    rgba(0, 0, 0, 0.12);

                overflow-y: auto;
            }

        }

    </style>

</head>


<body>


<div class="app">


    <!-- TOP BAR -->

    <div class="top-bar">

        <a
            href="payments.php"
            class="back-button"
        >
            ‹
        </a>


        <div class="page-title">
            ตรวจสอบยอดหนี้
        </div>

    </div>



    <!-- CHECK CARD -->

    <div class="check-card">


        <div class="check-icon">
            ✓
        </div>


        <div class="check-title">
            ตรวจสอบยอดหนี้คงค้าง
        </div>


        <p class="check-text">

            ระบบตรวจสอบข้อมูลการซื้อเชื่อ<br>

            ของลูกค้า
            <span class="customer-name">

                <?php

                echo htmlspecialchars(
                    $data["customer_name"]
                );

                ?>

            </span>

            เรียบร้อยแล้ว

        </p>



        <div class="check-status">

            <div class="status-icon">
                ✓
            </div>


            <div class="status-text">
                ตรวจสอบข้อมูลเรียบร้อยแล้ว
            </div>

        </div>


        <a
            href="payment_detail.php?customer_id=<?php echo $customer_id; ?>&credit_round_id=<?php echo $credit_round_id; ?>"
            class="button button-primary"
        >
            ดูยอดหนี้คงค้าง
        </a>


    </div>


</div>


</body>

</html>