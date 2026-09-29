<?php

session_start();

require_once "config/database.php";

/* ตรวจสอบว่ามี sale_id ส่งมาหรือไม่ */
if (!isset($_GET["sale_id"]) || !is_numeric($_GET["sale_id"])) {
    die("ไม่พบข้อมูลรายการขาย");
}

$sale_id = (int)$_GET["sale_id"];

/* ตรวจสอบรายการขาย */
$sql_sale = "SELECT sale_id, sale_type, total_amount
             FROM sales
             WHERE sale_id = ?";

$stmt_sale = $conn->prepare($sql_sale);
$stmt_sale->bind_param("i", $sale_id);
$stmt_sale->execute();

$result_sale = $stmt_sale->get_result();
$sale = $result_sale->fetch_assoc();

$stmt_sale->close();

if (!$sale) {
    die("ไม่พบข้อมูลรายการขาย");
}

/* ต้องเป็นรายการซื้อเชื่อเท่านั้น */
if ($sale["sale_type"] !== "ซื้อเชื่อ") {
    die("รายการนี้ไม่ใช่การซื้อเชื่อ");
}

/* ดึงข้อมูลลูกค้าทั้งหมด */
$sql_customer = "SELECT customer_id, customer_name, phone_number
                 FROM customers
                 ORDER BY customer_id ASC";

$result_customer = $conn->query($sql_customer);

if (!$result_customer) {
    die("ไม่สามารถดึงข้อมูลลูกค้าได้");
}


/* เมื่อกดเลือกลูกค้า */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_POST["customer_id"]) || !is_numeric($_POST["customer_id"])) {
        die("ไม่พบข้อมูลลูกค้า");
    }

    $customer_id = (int)$_POST["customer_id"];

    /* ตรวจสอบว่ามีลูกค้าจริง */
    $sql_check = "SELECT customer_id
                  FROM customers
                  WHERE customer_id = ?";

    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $customer_id);
    $stmt_check->execute();

    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows === 0) {
        $stmt_check->close();
        die("ไม่พบข้อมูลลูกค้า");
    }

    $stmt_check->close();

    /* ส่ง sale_id และ customer_id ไปหน้าตรวจสอบข้อมูลลูกค้า */
    header(
        "Location: credit_customer_check.php?sale_id="
            . $sale_id
            . "&customer_id="
            . $customer_id
    );

    exit;
}

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>รายการซื้อเชื่อ</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

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
            margin-bottom: 12px;
        }

        .back-button {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);

            width: 32px;
            height: 32px;

            border: none;
            border-radius: 50%;

            background: var(--primary-light);
            color: var(--primary);

            font-size: 25px;
            line-height: 1;

            cursor: pointer;
        }

        .header-title {
            font-size: 18px;
            font-weight: 500;
            color: var(--text);
        }

        /* Page title */

        .page-title {
            margin: 4px 0 14px;
            font-size: 18px;
            font-weight: 500;
        }

        /* Search */

        .search-box {
            position: relative;
            margin-bottom: 16px;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);

            font-size: 20px;
        }

        .search-input {
            width: 100%;
            height: 44px;

            padding: 0 14px 0 42px;

            border: 1.5px solid var(--primary);
            border-radius: 24px;

            outline: none;

            font-family: "Prompt", sans-serif;
            font-size: 13px;

            background: var(--white);
            color: var(--text);
        }

        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(90, 166, 207, 0.15);
        }

        /* Customer list */

        .customer-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .customer-card {
            width: 100%;

            display: flex;
            align-items: center;

            padding: 14px 14px;

            background: var(--primary-light);
            border: 1px solid var(--border);
            border-radius: 16px;

            box-shadow: 0 3px 8px rgba(67, 143, 184, 0.08);

            cursor: pointer;
            font-family: "Prompt", sans-serif;
            text-align: left;

            transition: 0.15s;
        }

        .customer-card:hover {
            background: #DDF1FB;
        }

        .customer-card:active {
            transform: scale(0.98);
        }

        .customer-info {
            min-width: 0;
        }

        .customer-name {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .customer-phone {
            font-size: 12px;
            color: var(--text-light);
        }

        .no-customer {
            padding: 25px 10px;

            text-align: center;

            color: var(--text-light);
            font-size: 13px;
        }

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


        <div class="page-title">
            เลือกลูกค้า
        </div>


        <div class="search-box">

            <span class="search-icon">🔍</span>

            <input
                type="text"
                id="searchInput"
                class="search-input"
                placeholder="ค้นหารายชื่อลูกค้า"
                autocomplete="off">

        </div>


        <div class="customer-list" id="customerList">

            <?php if ($result_customer->num_rows > 0): ?>

                <?php while ($customer = $result_customer->fetch_assoc()): ?>

                    <form
                        method="POST"
                        class="customer-card"
                        onclick="this.submit();">

                        <input
                            type="hidden"
                            name="customer_id"
                            value="<?php echo (int)$customer["customer_id"]; ?>">

                        <div class="customer-info">

                            <div class="customer-name">
                                <?php echo htmlspecialchars($customer["customer_name"]); ?>
                            </div>

                            <div class="customer-phone">
                                <?php echo htmlspecialchars($customer["phone_number"]); ?>
                            </div>

                        </div>

                    </form>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="no-customer">
                    ยังไม่มีข้อมูลลูกค้า
                </div>

            <?php endif; ?>

        </div>

    </div>


    <script>
        const searchInput = document.getElementById("searchInput");
        const customerCards = document.querySelectorAll(".customer-card");

        searchInput.addEventListener("input", function() {

            const keyword = this.value.trim().toLowerCase();

            customerCards.forEach(function(card) {

                const name =
                    card.querySelector(".customer-name")
                    .textContent
                    .toLowerCase();

                const phone =
                    card.querySelector(".customer-phone")
                    .textContent
                    .toLowerCase();

                if (
                    name.includes(keyword) ||
                    phone.includes(keyword)
                ) {
                    card.style.display = "flex";
                } else {
                    card.style.display = "none";
                }

            });

        });
    </script>

</body>

</html>