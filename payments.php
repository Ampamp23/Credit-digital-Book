<?php

session_start();

require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: owner_login.php");
    exit;
}

$sql = "
    SELECT
        c.customer_id,
        c.customer_name,
        c.phone_number,
        cr.credit_round_id,
        cr.due_date,
        cr.outstanding_amount,
        cr.payment_status
    FROM customers c
    INNER JOIN credit_rounds cr
        ON c.customer_id = cr.customer_id
    WHERE cr.outstanding_amount > 0
    ORDER BY cr.due_date ASC, c.customer_name ASC
";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>การชำระเงิน</title>

    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>

        :root {
            --primary: #5AA6CF;
            --primary-dark: #438FB8;
            --primary-light: #E8F5FC;
            --background: #E6D2BC;
            --white: #FFFFFF;
            --text: #263238;
            --text-light: #7A858C;
            --border: #E8EDF0;
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
            padding: 22px 18px 30px;
        }

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
            font-size: 22px;
            font-weight: 600;
            color: var(--primary-dark);
            white-space: nowrap;
        }

        .subtitle {
            text-align: center;
            color: var(--text-light);
            font-size: 13px;
            margin: -10px 0 22px;
        }

        .customer-card {
            display: block;
            text-decoration: none;
            color: inherit;
            background: var(--primary-light);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 17px;
            margin-bottom: 12px;
            transition: 0.2s;
        }

        .customer-card:active {
            transform: scale(0.98);
        }

        .customer-top {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .customer-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .customer-info {
            flex: 1;
            min-width: 0;
        }

        .customer-name {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 2px;
        }

        .customer-phone {
            font-size: 12px;
            color: var(--text-light);
        }

        .arrow {
            font-size: 24px;
            color: var(--primary-dark);
        }

        .debt-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid rgba(90, 166, 207, 0.2);
        }

        .debt-label {
            font-size: 12px;
            color: var(--text-light);
        }

        .debt-amount {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .due-date {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 3px;
        }

        .empty {
            text-align: center;
            padding: 55px 20px;
            color: var(--text-light);
        }

        .empty-icon {
            font-size: 45px;
            margin-bottom: 12px;
        }

        .empty-title {
            font-size: 16px;
            font-weight: 500;
            color: var(--text);
            margin-bottom: 5px;
        }

        .empty-text {
            font-size: 13px;
        }

        @media (min-width: 700px) {

            body {
                padding: 25px 0;
            }

            .app {
                min-height: 760px;
                height: 850px;
                border-radius: 34px;
                box-shadow: 0 15px 45px rgba(0, 0, 0, 0.12);
                overflow-y: auto;
            }

        }

    </style>
</head>

<body>

<div class="app">

    <div class="top-bar">

        <a href="owner_home.php" class="back-button">
            ‹
        </a>

        <div class="page-title">
            การชำระเงิน
        </div>

    </div>

    <div class="subtitle">
        เลือกลูกค้าที่ต้องการบันทึกการชำระเงิน
    </div>

    <?php if ($result && $result->num_rows > 0): ?>

        <?php while ($row = $result->fetch_assoc()): ?>

            <a
                href="payment_check.php?customer_id=<?php echo $row["customer_id"]; ?>&credit_round_id=<?php echo $row["credit_round_id"]; ?>"
                class="customer-card"
            >

                <div class="customer-top">

                    <div class="customer-icon">
                        👤
                    </div>

                    <div class="customer-info">

                        <div class="customer-name">
                            <?php echo htmlspecialchars($row["customer_name"]); ?>
                        </div>

                        <div class="customer-phone">
                            <?php echo htmlspecialchars($row["phone_number"]); ?>
                        </div>

                    </div>

                    <div class="arrow">
                        ›
                    </div>

                </div>

                <div class="debt-info">

                    <div>
                        <div class="debt-label">
                            ยอดค้างชำระ
                        </div>

                        <div class="due-date">
                            ครบกำหนด <?php echo date("d/m/Y", strtotime($row["due_date"])); ?>
                        </div>
                    </div>

                    <div class="debt-amount">
                        <?php echo number_format($row["outstanding_amount"]); ?> บาท
                    </div>

                </div>

            </a>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="empty">

            <div class="empty-icon">
                ✅
            </div>

            <div class="empty-title">
                ไม่มียอดค้างชำระ
            </div>

            <div class="empty-text">
                ขณะนี้ไม่มีรายการที่ต้องรับชำระเงิน
            </div>

        </div>

    <?php endif; ?>

</div>

</body>
</html>