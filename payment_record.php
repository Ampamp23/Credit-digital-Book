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

$error = "";


/* =========================
   ดึงข้อมูลลูกค้าและรอบเครดิต
========================= */

$sql = "
    SELECT
        c.customer_id,
        c.customer_name,
        c.phone_number,
        cr.credit_round_id,
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

if (!$stmt) {
    die("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL");
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


/* =========================
   เมื่อกดบันทึกการชำระเงิน
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $payment_amount = (int) ($_POST["payment_amount"] ?? 0);


    /* ตรวจสอบจำนวนเงิน */

    if ($payment_amount <= 0) {

        $error = "กรุณากรอกจำนวนเงินที่ชำระ";

    } elseif (
        $payment_amount >
        (int) $data["outstanding_amount"]
    ) {

        $error = "จำนวนเงินที่ชำระต้องไม่เกินยอดหนี้คงค้าง";

    } else {


        /* เริ่ม Transaction */

        $conn->begin_transaction();


        try {


            /* =========================
               ตรวจสอบยอดหนี้ล่าสุด
            ========================= */

            $round_sql = "
                SELECT
                    outstanding_amount
                FROM credit_rounds
                WHERE credit_round_id = ?
                  AND customer_id = ?
                FOR UPDATE
            ";

            $round_stmt = $conn->prepare($round_sql);

            if (!$round_stmt) {
                throw new Exception(
                    "ไม่สามารถตรวจสอบรอบเครดิตได้"
                );
            }

            $round_stmt->bind_param(
                "ii",
                $credit_round_id,
                $customer_id
            );

            $round_stmt->execute();

            $round_result =
                $round_stmt->get_result();


            if ($round_result->num_rows === 0) {
                throw new Exception(
                    "ไม่พบข้อมูลรอบเครดิต"
                );
            }


            $round = $round_result->fetch_assoc();


            $current_outstanding =
                (int) $round["outstanding_amount"];


            /* ตรวจสอบอีกครั้ง */

            if ($payment_amount > $current_outstanding) {

                throw new Exception(
                    "จำนวนเงินที่ชำระเกินยอดหนี้คงค้าง"
                );
            }


            /* =========================
               ดึงรายการซื้อเชื่อ
               เรียงจากเก่าไปใหม่
            ========================= */

            $purchase_sql = "
                SELECT
                    cp.credit_purchase_id,
                    cp.credit_purchase_amount,

                    COALESCE(
                        (
                            SELECT SUM(p.payment_amount)
                            FROM payments p
                            WHERE p.credit_purchase_id =
                                  cp.credit_purchase_id
                        ),
                        0
                    ) AS paid_amount

                FROM credit_purchases cp

                WHERE cp.credit_round_id = ?

                ORDER BY
                    cp.credit_purchase_date ASC,
                    cp.credit_purchase_id ASC
            ";


            $purchase_stmt =
                $conn->prepare($purchase_sql);


            if (!$purchase_stmt) {
                throw new Exception(
                    "ไม่สามารถตรวจสอบรายการซื้อเชื่อได้"
                );
            }


            $purchase_stmt->bind_param(
                "i",
                $credit_round_id
            );


            $purchase_stmt->execute();


            $purchase_result =
                $purchase_stmt->get_result();


            /* เงินที่ยังต้องนำไปตัดยอด */

            $remaining_payment =
                $payment_amount;


            /* =========================
               กระจายยอดชำระ
               จากรายการเก่า → รายการใหม่
            ========================= */

            while (
                $purchase =
                $purchase_result->fetch_assoc()
            ) {


                /* ถ้าเงินถูกใช้หมดแล้ว ให้หยุด */

                if ($remaining_payment <= 0) {
                    break;
                }


                $purchase_amount =
                    (int) $purchase[
                        "credit_purchase_amount"
                    ];


                $paid_amount =
                    (int) $purchase[
                        "paid_amount"
                    ];


                /* ยอดที่ยังเหลือของรายการนี้ */

                $purchase_remaining =
                    $purchase_amount -
                    $paid_amount;


                /* ถ้ารายการนี้ชำระครบแล้ว
                   ให้ข้ามไป */
                if ($purchase_remaining <= 0) {
                    continue;
                }


                /* จำนวนเงินที่จะตัดรายการนี้ */

                $amount_for_purchase =
                    min(
                        $remaining_payment,
                        $purchase_remaining
                    );


                /* =========================
                   บันทึกข้อมูลการชำระเงิน
                ========================= */

                $insert_sql = "
                    INSERT INTO payments
                    (
                        credit_purchase_id,
                        payment_date,
                        payment_amount
                    )
                    VALUES
                    (
                        ?,
                        CURDATE(),
                        ?
                    )
                ";


                $insert_stmt =
                    $conn->prepare($insert_sql);


                if (!$insert_stmt) {
                    throw new Exception(
                        "ไม่สามารถบันทึกข้อมูลการชำระเงินได้"
                    );
                }


                $credit_purchase_id =
                    (int) $purchase[
                        "credit_purchase_id"
                    ];


                $insert_stmt->bind_param(
                    "ii",
                    $credit_purchase_id,
                    $amount_for_purchase
                );


                $insert_stmt->execute();


                /* หักเงินที่ใช้ไป */

                $remaining_payment -=
                    $amount_for_purchase;
            }


            /* =========================
               ตรวจสอบว่ากระจายเงินครบหรือไม่
            ========================= */

            if ($remaining_payment > 0) {

                throw new Exception(
                    "ไม่สามารถจัดสรรยอดชำระเงินได้"
                );
            }


            /* =========================
               คำนวณยอดคงค้างใหม่
            ========================= */

            $new_outstanding =
                $current_outstanding -
                $payment_amount;


            /* =========================
               กำหนดสถานะ
            ========================= */

            if ($new_outstanding <= 0) {

                $new_outstanding = 0;

                $new_status = "ชำระครบ";

            } else {

                $new_status = "ชำระบางส่วน";
            }


            /* =========================
               ปรับปรุงรอบเครดิต
            ========================= */

            $update_sql = "
                UPDATE credit_rounds
                SET
                    outstanding_amount = ?,
                    payment_status = ?
                WHERE credit_round_id = ?
            ";


            $update_stmt =
                $conn->prepare($update_sql);


            if (!$update_stmt) {
                throw new Exception(
                    "ไม่สามารถปรับปรุงยอดหนี้ได้"
                );
            }


            $update_stmt->bind_param(
                "isi",
                $new_outstanding,
                $new_status,
                $credit_round_id
            );


            $update_stmt->execute();


            /* =========================
               ยืนยัน Transaction
            ========================= */

            $conn->commit();


            /* =========================
               เก็บข้อมูลไปหน้า Result
            ========================= */

            $_SESSION["payment_result"] = [

                "customer_name" =>
                    $data["customer_name"],

                "payment_amount" =>
                    $payment_amount,

                "old_outstanding" =>
                    $current_outstanding,

                "new_outstanding" =>
                    $new_outstanding,

                "payment_status" =>
                    $new_status,

                "due_date" =>
                    $data["due_date"]

            ];


            header(
                "Location: payment_result.php"
            );

            exit;


        } catch (Exception $e) {

            /* ถ้าเกิดข้อผิดพลาด
               ยกเลิกการบันทึกทั้งหมด */

            $conn->rollback();

            $error = $e->getMessage();
        }
    }
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

    <title>บันทึกการชำระเงิน</title>


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


        .subtitle {

            text-align: center;

            color: var(--text-light);

            font-size: 13px;

            margin: -8px 0 22px;
        }


        /* =========================
           CUSTOMER CARD
        ========================= */

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


        /* =========================
           SUMMARY
        ========================= */

        .summary-box {

            background: var(--white);

            border-radius: 15px;

            padding: 17px;

            margin-top: 18px;
        }


        .summary-row {

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding: 10px 0;

            border-bottom: 1px solid var(--border);
        }


        .summary-row:last-child {

            border-bottom: none;
        }


        .summary-label {

            font-size: 13px;

            color: var(--text-light);
        }


        .summary-value {

            font-size: 15px;

            font-weight: 500;
        }


        /* =========================
           DEBT
        ========================= */

        .debt-box {

            border: 2px solid var(--red);

            border-radius: 18px;

            padding: 18px;

            text-align: center;

            margin-bottom: 14px;
        }


        .debt-label {

            font-size: 13px;

            color: var(--text-light);
        }


        .debt-amount {

            font-size: 28px;

            font-weight: 600;

            color: var(--red);

            margin-top: 3px;
        }


        .debt-unit {

            font-size: 13px;

            font-weight: 400;
        }


        /* =========================
           FORM
        ========================= */

        .form-card {

            background: var(--primary-light);

            border-radius: 18px;

            padding: 18px;

            margin-bottom: 14px;
        }


        .form-label {

            display: block;

            font-size: 13px;

            color: var(--text);

            margin-bottom: 8px;
        }


        .input-wrapper {

            position: relative;
        }


        .payment-input {

            width: 100%;

            border: 1px solid var(--border);

            border-radius: 14px;

            padding: 13px 50px 13px 15px;

            font-family: "Prompt", sans-serif;

            font-size: 16px;

            outline: none;

            background: var(--white);
        }


        .payment-input:focus {

            border-color: var(--primary);
        }


        .input-unit {

            position: absolute;

            right: 16px;

            top: 50%;

            transform: translateY(-50%);

            font-size: 13px;

            color: var(--text-light);
        }


        .error {

            background: var(--red-light);

            color: var(--red);

            border-radius: 12px;

            padding: 10px 12px;

            font-size: 12px;

            margin-top: 10px;
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

            margin-top: 14px;
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
            href="payment_detail.php?customer_id=<?php echo $customer_id; ?>&credit_round_id=<?php echo $credit_round_id; ?>"
            class="back-button"
        >
            ‹
        </a>


        <div class="page-title">
            บันทึกการชำระเงิน
        </div>

    </div>


    <div class="subtitle">
        บันทึกจำนวนเงินที่ลูกค้าชำระ
    </div>



    <!-- CUSTOMER -->

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



        <div class="summary-box">


            <div class="summary-row">

                <div class="summary-label">
                    วงเงินเครดิต
                </div>


                <div class="summary-value">

                    <?php

                    echo number_format(
                        $data["credit_limit"]
                    );

                    ?>

                    บาท

                </div>

            </div>



            <div class="summary-row">

                <div class="summary-label">
                    วันครบกำหนดชำระ
                </div>


                <div class="summary-value">

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


        </div>

    </div>



    <!-- OUTSTANDING -->

    <div class="debt-box">

        <div class="debt-label">
            ยอดหนี้คงค้าง
        </div>


        <div class="debt-amount">

            <?php

            echo number_format(
                $data["outstanding_amount"]
            );

            ?>

            <span class="debt-unit">
                บาท
            </span>

        </div>

    </div>



    <!-- PAYMENT FORM -->

    <div class="form-card">

        <form method="POST">


            <label class="form-label">
                จำนวนเงินที่ชำระ
            </label>


            <div class="input-wrapper">

                <input
                    type="number"
                    name="payment_amount"
                    class="payment-input"
                    min="1"
                    max="<?php echo (int) $data["outstanding_amount"]; ?>"
                    placeholder="กรอกจำนวนเงิน"
                    required
                >


                <span class="input-unit">
                    บาท
                </span>

            </div>


            <?php if ($error !== ""): ?>

                <div class="error">

                    <?php

                    echo htmlspecialchars(
                        $error
                    );

                    ?>

                </div>

            <?php endif; ?>


            <button
                type="submit"
                class="button button-primary"
            >
                บันทึกการชำระเงิน
            </button>


        </form>

    </div>


</div>


</body>

</html>