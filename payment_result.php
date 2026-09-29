<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: owner_login.php");
    exit;
}

if (!isset($_SESSION["payment_result"])) {
    header("Location: payments.php");
    exit;
}

$result = $_SESSION["payment_result"];

/* ดึงข้อมูลจาก Session */
$customer_name = $result["customer_name"];
$payment_amount = $result["payment_amount"];
$old_outstanding = $result["old_outstanding"];
$new_outstanding = $result["new_outstanding"];
$payment_status = $result["payment_status"];
$due_date = $result["due_date"];

/* ล้างข้อมูลหลังนำมาใช้ */
unset($_SESSION["payment_result"]);

?>


<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>บันทึกการชำระเงินสำเร็จ</title>


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

            --green-light: #E8F5E9;

            --yellow: #F0B429;

            --yellow-light: #FFF4D6;

            --red: #D9534F;

            --red-light: #FDE7E7;
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

            padding: 28px 18px 30px;

            display: flex;

            flex-direction: column;
        }


        /* =========================
           SUCCESS ICON
        ========================= */

        .success-icon {

            width: 76px;

            height: 76px;

            border-radius: 50%;

            background: var(--green-light);

            color: var(--green);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 38px;

            margin: 15px auto 16px;
        }


        .page-title {

            text-align: center;

            font-size: 24px;

            font-weight: 600;

            color: var(--primary-dark);

            margin-bottom: 7px;
        }


        .subtitle {

            text-align: center;

            color: var(--text-light);

            font-size: 13px;

            margin-bottom: 28px;
        }


        /* =========================
           RESULT CARD
        ========================= */

        .result-card {

            background: var(--primary-light);

            border-radius: 20px;

            padding: 20px;

            margin-bottom: 14px;
        }


        .result-row {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            padding: 13px 0;

            border-bottom: 1px solid var(--border);
        }


        .result-row:last-child {

            border-bottom: none;
        }


        .result-label {

            font-size: 13px;

            color: var(--text-light);
        }


        .result-value {

            font-size: 15px;

            font-weight: 500;

            text-align: right;
        }


        .payment-value {

            color: var(--green);

            font-size: 18px;

            font-weight: 600;
        }


        .remaining-value {

            color: var(--red);

            font-size: 18px;

            font-weight: 600;
        }


        /* =========================
           STATUS
        ========================= */

        .status-box {

            text-align: center;

            margin: 10px 0 20px;
        }


        .status {

            display: inline-block;

            padding: 7px 18px;

            border-radius: 20px;

            font-size: 13px;

            font-weight: 500;
        }


        .status-partial {

            background: var(--yellow-light);

            color: var(--yellow);
        }


        .status-paid {

            background: var(--green-light);

            color: var(--green);
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

            cursor: pointer;

            text-decoration: none;

            display: block;

            text-align: center;
        }


        .button-primary {

            background: var(--primary);

            color: var(--white);

            box-shadow:
                0 5px 12px
                rgba(90, 166, 207, 0.22);
        }


        .button-secondary {

            background: var(--primary-light);

            color: var(--primary-dark);

            margin-top: 10px;
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


    <div class="success-icon">
        ✓
    </div>


    <div class="page-title">
        บันทึกการชำระเงินสำเร็จ
    </div>


    <div class="subtitle">
        ระบบบันทึกข้อมูลการชำระเงินเรียบร้อยแล้ว
    </div>



    <!-- RESULT -->

    <div class="result-card">


        <div class="result-row">

            <div class="result-label">
                ลูกค้า
            </div>

            <div class="result-value">

                <?php

                echo htmlspecialchars(
                    $customer_name
                );

                ?>

            </div>

        </div>



        <div class="result-row">

            <div class="result-label">
                ยอดก่อนชำระ
            </div>

            <div class="result-value">

                <?php

                echo number_format(
                    $old_outstanding
                );

                ?>

                บาท

            </div>

        </div>



        <div class="result-row">

            <div class="result-label">
                จำนวนเงินที่ชำระ
            </div>

            <div class="result-value payment-value">

                <?php

                echo number_format(
                    $payment_amount
                );

                ?>

                บาท

            </div>

        </div>



        <div class="result-row">

            <div class="result-label">
                ยอดค้างชำระ
            </div>

            <div class="result-value remaining-value">

                <?php

                echo number_format(
                    $new_outstanding
                );

                ?>

                บาท

            </div>

        </div>



        <div class="result-row">

            <div class="result-label">
                วันครบกำหนดชำระ
            </div>

            <div class="result-value">

                <?php

                echo date(
                    "d/m/Y",
                    strtotime($due_date)
                );

                ?>

            </div>

        </div>

    </div>



    <!-- STATUS -->

    <div class="status-box">

        <?php if ($payment_status === "ชำระครบ"): ?>

            <span class="status status-paid">
                ชำระครบ
            </span>

        <?php else: ?>

            <span class="status status-partial">
                ชำระบางส่วน
            </span>

        <?php endif; ?>

    </div>



    <!-- BUTTON -->

    <a
        href="payments.php"
        class="button button-primary"
    >
        กลับไปหน้าการชำระเงิน
    </a>


    <a
        href="owner_home.php"
        class="button button-secondary"
    >
        กลับหน้าหลัก
    </a>


</div>


</body>

</html>