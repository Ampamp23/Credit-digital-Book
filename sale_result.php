<?php
session_start();

require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: owner_login.php");
    exit;
}


/* =========================
   ตรวจสอบข้อมูลรายการขาย
========================= */

if (
    !isset($_SESSION["last_sale_id"]) ||
    !isset($_SESSION["last_sale_type"]) ||
    !isset($_SESSION["last_sale_total"])
) {
    header("Location: owner_home.php");
    exit;
}

$sale_id = $_SESSION["last_sale_id"];
$sale_type = $_SESSION["last_sale_type"];
$total_amount = $_SESSION["last_sale_total"];

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>ผลการบันทึกรายการขาย</title>

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

            --success: #38A169;
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

            margin-bottom: 28px;
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
   Result Card
========================= */

        .result-card {
            background: var(--primary-light);

            border: 1px solid var(--border);

            border-radius: 22px;

            padding: 22px 20px;

            box-shadow:
                0 4px 12px rgba(67, 143, 184, .08);
        }


        /* =========================
   Success
========================= */

        .success-message {
            display: flex;

            align-items: center;

            gap: 8px;

            margin-bottom: 24px;

            font-size: 14px;

            font-weight: 500;

            color: var(--success);
        }

        .success-icon {
            width: 22px;
            height: 22px;

            display: flex;

            align-items: center;
            justify-content: center;

            border: 1.5px solid var(--success);

            border-radius: 50%;

            font-size: 13px;
        }


        /* =========================
   Result Row
========================= */

        .result-row {
            display: flex;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 15px;

            font-size: 14px;
        }

        .result-label {
            font-weight: 500;
        }

        .result-value {
            text-align: right;
        }

        .total {
            font-weight: 600;

            color: var(--primary-dark);
        }


        /* =========================
   Button
========================= */

        .primary-button {
            display: block;

            width: 100%;

            margin-top: 34px;

            padding: 13px;

            border: none;

            border-radius: 14px;

            background: var(--primary);

            color: var(--white);

            text-align: center;

            text-decoration: none;

            font-family: "Prompt", sans-serif;

            font-size: 15px;

            font-weight: 500;

            cursor: pointer;
        }

        .primary-button:hover {
            background: var(--primary-dark);
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
                    0 20px 50px rgba(0, 0, 0, .12);

                overflow-y: auto;
            }
        }
    </style>

</head>


<body>

    <div class="app">


        <!-- Header -->

        <div class="header">

            <a href="owner_home.php"
                class="back-button">

                ‹

            </a>

            <h1>
                รายการขาย
            </h1>

        </div>


        <!-- Result -->

        <div class="result-card">


            <div class="success-message">

                <span class="success-icon">
                    ✓
                </span>

                <span>
                    บันทึกรายการขายสำเร็จ
                </span>

            </div>


            <!-- Total -->

            <div class="result-row">

                <span class="result-label">
                    ยอดรวม
                </span>

                <span class="result-value total">

                    <?= number_format(
                        $total_amount
                    ) ?> บาท

                </span>

            </div>


            <!-- Sale Type -->

            <div class="result-row">

                <span class="result-label">
                    ประเภทการขาย
                </span>

                <span class="result-value">

                    <?= htmlspecialchars(
                        $sale_type
                    ) ?>

                </span>

            </div>


        </div>


        <?php if ($sale_type === "ซื้อเชื่อ"): ?>

            <!-- ซื้อเชื่อ -->

            <a
                href="credit_customers.php?sale_id=<?= $sale_id ?>"
                class="primary-button">
                ดำเนินการซื้อเชื่อ
            </a>

        <?php else: ?>

            <!-- เงินสด -->

            <a
                href="owner_home.php"
                class="primary-button">
                กลับหน้าหลัก
            </a>

        <?php endif; ?>


    </div>

</body>

</html>