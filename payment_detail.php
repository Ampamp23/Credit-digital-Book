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

$sql = "
    SELECT
        c.customer_id,
        c.customer_name,
        c.phone_number,
        cr.credit_round_id,
        cr.start_date,
        cr.due_date,
        cr.credit_limit,
        cr.outstanding_amount,
        cr.payment_status
    FROM customers c
    INNER JOIN credit_rounds cr
        ON c.customer_id = cr.customer_id
    WHERE c.customer_id = ?
      AND cr.credit_round_id = ?
      AND cr.outstanding_amount > 0
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $customer_id, $credit_round_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: payments.php");
    exit;
}

$data = $result->fetch_assoc();

$remaining_credit =
    $data["credit_limit"] - $data["outstanding_amount"];


/* กำหนดสีตามสถานะ */

if ($data["outstanding_amount"] <= 0) {

    $amount_class = "amount-paid";
    $status_class = "status-paid";

} elseif ($data["payment_status"] === "ชำระบางส่วน") {

    $amount_class = "amount-partial";
    $status_class = "status-partial";

} else {

    $amount_class = "amount-unpaid";
    $status_class = "status-unpaid";

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

    <title>ตรวจสอบยอดค้างชำระ</title>

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

            --red: #D9534F;
            --red-light: #FDE7E7;

            --yellow: #A47700;
            --yellow-light: #FFF4D6;

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
        }


        /* -------------------------
           TOP BAR
        ------------------------- */

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


        .subtitle {
            text-align: center;

            color: var(--text-light);

            font-size: 13px;

            margin: -8px 0 22px;
        }


        /* -------------------------
           CUSTOMER CARD
        ------------------------- */

        .customer-card {
            background: var(--primary-light);

            border: 1px solid var(--border);

            border-radius: 20px;

            padding: 20px;

            margin-bottom: 14px;
        }


        .customer-header {
            display: flex;

            align-items: center;

            gap: 13px;
        }


        .customer-icon {
            width: 52px;
            height: 52px;

            border-radius: 15px;

            background: var(--white);

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 26px;

            flex-shrink: 0;
        }


        .customer-name {
            font-size: 18px;

            font-weight: 500;
        }


        .customer-phone {
            font-size: 12px;

            color: var(--text-light);

            margin-top: 2px;
        }


        /* -------------------------
           INFORMATION BOX
        ------------------------- */

        .info-box {
            margin-top: 18px;

            background: var(--white);

            border-radius: 15px;

            padding: 15px;
        }


        .info-row {
            display: flex;

            justify-content: space-between;

            align-items: center;

            padding: 9px 0;

            border-bottom: 1px solid var(--border);

            gap: 15px;
        }


        .info-row:last-child {
            border-bottom: none;
        }


        .info-label {
            font-size: 13px;

            color: var(--text-light);
        }


        .info-value {
            font-size: 14px;

            font-weight: 500;

            text-align: right;
        }


        /* -------------------------
           OUTSTANDING BOX
        ------------------------- */

        .outstanding-box {
            background: var(--white);

            border: 2px solid var(--primary);

            border-radius: 18px;

            padding: 18px;

            text-align: center;

            /*
             * เว้นระยะจากกล่องยอดหนี้
             * กับปุ่มด้านล่างให้ชัดเจน
             */
            margin-bottom: 22px;
        }


        .outstanding-label {
            font-size: 13px;

            color: var(--text-light);

            margin-bottom: 4px;
        }


        .outstanding-amount {
            font-size: 30px;

            font-weight: 600;
        }


        .outstanding-unit {
            font-size: 13px;

            font-weight: 400;
        }


        /* -------------------------
           AMOUNT COLORS
        ------------------------- */

        .amount-unpaid {
            color: var(--red);
        }


        .amount-partial {
            color: var(--yellow);
        }


        .amount-paid {
            color: var(--green);
        }


        /* -------------------------
           STATUS COLORS
        ------------------------- */

        .status {
            display: inline-block;

            margin-top: 10px;

            padding: 5px 13px;

            border-radius: 20px;

            font-size: 12px;
        }


        .status-unpaid {
            background: var(--red-light);

            color: var(--red);
        }


        .status-partial {
            background: var(--yellow-light);

            color: var(--yellow);
        }


        .status-paid {
            background: var(--green-light);

            color: var(--green);
        }


        /* -------------------------
           BUTTON
        ------------------------- */

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
                0 5px 12px rgba(90, 166, 207, 0.22);

            margin-top: 0;
        }


        .button-primary:active {
            transform: scale(0.98);
        }


        /* -------------------------
           DESKTOP
        ------------------------- */

        @media (min-width: 700px) {

            body {
                padding: 25px 0;
            }


            .app {
                min-height: 760px;

                height: 850px;

                border-radius: 34px;

                box-shadow:
                    0 15px 45px rgba(0, 0, 0, 0.12);

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
            ตรวจสอบยอดค้างชำระ
        </div>

    </div>


    <div class="subtitle">
        ตรวจสอบข้อมูลก่อนบันทึกการชำระเงิน
    </div>



    <!-- CUSTOMER INFORMATION -->

    <div class="customer-card">


        <div class="customer-header">


            <div class="customer-icon">
                👤
            </div>


            <div>

                <div class="customer-name">
                    <?php
                    echo htmlspecialchars(
                        $data["customer_name"]
                    );
                    ?>
                </div>


                <div class="customer-phone">
                    <?php
                    echo htmlspecialchars(
                        $data["phone_number"]
                    );
                    ?>
                </div>

            </div>

        </div>



        <div class="info-box">


            <!-- CREDIT LIMIT -->

            <div class="info-row">

                <div class="info-label">
                    วงเงินเครดิต
                </div>

                <div class="info-value">
                    <?php
                    echo number_format(
                        $data["credit_limit"]
                    );
                    ?>
                    บาท
                </div>

            </div>



            <!-- START DATE -->

            <div class="info-row">

                <div class="info-label">
                    วันที่เริ่มรอบ
                </div>

                <div class="info-value">
                    <?php
                    echo date(
                        "d/m/Y",
                        strtotime(
                            $data["start_date"]
                        )
                    );
                    ?>
                </div>

            </div>



            <!-- DUE DATE -->

            <div class="info-row">

                <div class="info-label">
                    วันครบกำหนดชำระ
                </div>

                <div class="info-value">
                    <?php
                    echo date(
                        "d/m/Y",
                        strtotime(
                            $data["due_date"]
                        )
                    );
                    ?>
                </div>

            </div>



            <!-- REMAINING CREDIT -->

            <div class="info-row">

                <div class="info-label">
                    วงเงินคงเหลือ
                </div>

                <div class="info-value">
                    <?php
                    echo number_format(
                        $remaining_credit
                    );
                    ?>
                    บาท
                </div>

            </div>


        </div>

    </div>



    <!-- OUTSTANDING -->

    <div class="outstanding-box">


        <div class="outstanding-label">
            ยอดหนี้คงค้าง
        </div>


        <div
            class="outstanding-amount
            <?php echo $amount_class; ?>"
        >

            <?php
            echo number_format(
                $data["outstanding_amount"]
            );
            ?>

            <span class="outstanding-unit">
                บาท
            </span>

        </div>


        <div
            class="status
            <?php echo $status_class; ?>"
        >

            <?php
            echo htmlspecialchars(
                $data["payment_status"]
            );
            ?>

        </div>


    </div>



    <!-- NEXT BUTTON -->

    <a
        href="payment_record.php?customer_id=<?php echo $customer_id; ?>&credit_round_id=<?php echo $credit_round_id; ?>"
        class="button button-primary"
    >
        ดำเนินการต่อ
    </a>


</div>


</body>

</html>