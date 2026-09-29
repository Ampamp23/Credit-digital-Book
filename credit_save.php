<?php

session_start();

require_once "config/database.php";

if (
    !isset($_GET["sale_id"]) ||
    !is_numeric($_GET["sale_id"]) ||
    !isset($_GET["customer_id"]) ||
    !is_numeric($_GET["customer_id"]) ||
    !isset($_GET["round_type"])
) {
    die("ข้อมูลไม่ครบถ้วน");
}

$sale_id = (int)$_GET["sale_id"];
$customer_id = (int)$_GET["customer_id"];
$round_type = $_GET["round_type"];

if (!in_array($round_type, ["existing", "new"], true)) {
    die("ประเภทของรอบเครดิตไม่ถูกต้อง");
}


/* ตรวจสอบรายการขาย */
$sql_sale = "SELECT sale_id, sale_type, total_amount
             FROM sales
             WHERE sale_id = ?";

$stmt = $conn->prepare($sql_sale);
$stmt->bind_param("i", $sale_id);
$stmt->execute();

$result = $stmt->get_result();
$sale = $result->fetch_assoc();

$stmt->close();

if (!$sale) {
    die("ไม่พบรายการขาย");
}

if ($sale["sale_type"] !== "ซื้อเชื่อ") {
    die("รายการนี้ไม่ใช่การซื้อเชื่อ");
}

$purchase_amount = (int)$sale["total_amount"];


/* ตรวจสอบลูกค้า */
$sql_customer = "SELECT customer_id, customer_name, credit_limit
                 FROM customers
                 WHERE customer_id = ?";

$stmt = $conn->prepare($sql_customer);
$stmt->bind_param("i", $customer_id);
$stmt->execute();

$result = $stmt->get_result();
$customer = $result->fetch_assoc();

$stmt->close();

if (!$customer) {
    die("ไม่พบข้อมูลลูกค้า");
}


/* เริ่ม Transaction */
$conn->begin_transaction();

try {

    if ($round_type === "existing") {

        /*
         * หารอบเครดิตเดิมที่ยังมียอดค้าง
         */
        $sql_round = "SELECT
                        credit_round_id,
                        due_date,
                        credit_limit,
                        outstanding_amount
                      FROM credit_rounds
                      WHERE customer_id = ?
                      AND outstanding_amount > 0
                      ORDER BY credit_round_id DESC
                      LIMIT 1
                      FOR UPDATE";

        $stmt = $conn->prepare($sql_round);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $round = $result->fetch_assoc();

        $stmt->close();

        if (!$round) {
            throw new Exception("ไม่พบรอบเครดิตเดิม");
        }

        $credit_round_id = (int)$round["credit_round_id"];
        $credit_limit = (int)$round["credit_limit"];
        $old_outstanding = (int)$round["outstanding_amount"];

        $new_outstanding = $old_outstanding + $purchase_amount;


        /* ตรวจสอบวงเงิน */
        if ($new_outstanding > $credit_limit) {
            throw new Exception("วงเงินเครดิตไม่เพียงพอ");
        }


        /*
         * บันทึกรายการซื้อเชื่อ
         */
        $sql_purchase = "INSERT INTO credit_purchases
                        (
                            sale_id,
                            credit_round_id,
                            credit_purchase_date,
                            credit_purchase_amount
                        )
                        VALUES (?, ?, CURDATE(), ?)";

        $stmt = $conn->prepare($sql_purchase);
        $stmt->bind_param(
            "iii",
            $sale_id,
            $credit_round_id,
            $purchase_amount
        );

        if (!$stmt->execute()) {
            throw new Exception("ไม่สามารถบันทึกรายการซื้อเชื่อได้");
        }

        $credit_purchase_id = $stmt->insert_id;

        $stmt->close();


        /*
         * ปรับปรุงยอดค้างชำระของรอบเดิม
         */
        $payment_status = "ยังไม่ชำระ";

        $sql_update = "UPDATE credit_rounds
                       SET outstanding_amount = ?,
                           payment_status = ?
                       WHERE credit_round_id = ?";

        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param(
            "isi",
            $new_outstanding,
            $payment_status,
            $credit_round_id
        );

        if (!$stmt->execute()) {
            throw new Exception("ไม่สามารถปรับปรุงรอบเครดิตได้");
        }

        $stmt->close();

    } else {

        /*
         * สร้างรอบเครดิตใหม่
         */
        $credit_limit = (int)$customer["credit_limit"];

        if ($purchase_amount > $credit_limit) {
            throw new Exception("วงเงินเครดิตไม่เพียงพอ");
        }

        $new_outstanding = $purchase_amount;
        $payment_status = "ยังไม่ชำระ";


        /*
         * สร้างรอบเครดิต
         */
        $sql_round = "INSERT INTO credit_rounds
                      (
                          customer_id,
                          start_date,
                          due_date,
                          credit_limit,
                          outstanding_amount,
                          payment_status
                      )
                      VALUES
                      (?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY),
                       ?, ?, ?)";

        $stmt = $conn->prepare($sql_round);
        $stmt->bind_param(
            "iiis",
            $customer_id,
            $credit_limit,
            $new_outstanding,
            $payment_status
        );

        if (!$stmt->execute()) {
            throw new Exception("ไม่สามารถสร้างรอบเครดิตได้");
        }

        $credit_round_id = $stmt->insert_id;

        $stmt->close();


        /*
         * บันทึกรายการซื้อเชื่อ
         */
        $sql_purchase = "INSERT INTO credit_purchases
                        (
                            sale_id,
                            credit_round_id,
                            credit_purchase_date,
                            credit_purchase_amount
                        )
                        VALUES (?, ?, CURDATE(), ?)";

        $stmt = $conn->prepare($sql_purchase);
        $stmt->bind_param(
            "iii",
            $sale_id,
            $credit_round_id,
            $purchase_amount
        );

        if (!$stmt->execute()) {
            throw new Exception("ไม่สามารถบันทึกรายการซื้อเชื่อได้");
        }

        $credit_purchase_id = $stmt->insert_id;

        $stmt->close();
    }


    /* ยืนยัน Transaction */
    $conn->commit();


    /* เก็บข้อมูลไว้สำหรับหน้าแสดงผล */
    $_SESSION["credit_purchase_id"] = $credit_purchase_id;
    $_SESSION["credit_round_id"] = $credit_round_id;
    $_SESSION["credit_customer_id"] = $customer_id;
    $_SESSION["credit_sale_id"] = $sale_id;
    $_SESSION["credit_purchase_amount"] = $purchase_amount;
    $_SESSION["credit_new_outstanding"] = $new_outstanding;


    header("Location: credit_result.php");
    exit;


} catch (Exception $e) {

    $conn->rollback();

    die(
        "ไม่สามารถบันทึกข้อมูลได้: "
        . htmlspecialchars($e->getMessage())
    );
}