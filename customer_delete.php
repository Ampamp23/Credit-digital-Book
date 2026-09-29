<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: owner_login.php");
    exit;
}

require_once "config/database.php";

if (!isset($_GET["customer_id"])) {
    header("Location: customers.php");
    exit;
}

$customer_id = (int)$_GET["customer_id"];

$sql = "DELETE FROM customers WHERE customer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);

if ($stmt->execute()) {
    header("Location: customers.php");
    exit;
}

$stmt->close();
$conn->close();

header("Location: customers.php");
exit;
?>

$customer_id = (int)$_GET["customer_id"];

/* ดึงข้อมูลลูกค้า */
$stmt = $conn->prepare("
SELECT customer_name, phone_number
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


/* เมื่อกดยืนยันการลบ */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

$stmt = $conn->prepare("
DELETE FROM customers
WHERE customer_id = ?
");

$stmt->bind_param("i", $customer_id);

if ($stmt->execute()) {

$stmt->close();

header("Location: customers.php");
exit;

} else {

$error = "ไม่สามารถลบข้อมูลลูกค้าได้";
}

$stmt->close();
}

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ลบลูกค้า</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --primary: #5AA6CF;
            --primary-dark: #438FB8;
            --primary-light: #E8F5FC;
            --background: #E6D2BC;
            --text: #263238;
            --text-light: #7A858C;
            --border: #E8EDF0;
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
            margin: auto;
            background: #fff;
            padding: 24px 18px 30px;
        }

        .top-bar {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
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

        .delete-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 25px 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .delete-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: var(--red-light);
            color: var(--red);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .delete-card h2 {
            margin: 0 0 8px;
            font-size: 20px;
            font-weight: 600;
        }

        .delete-card p {
            margin: 0 0 8px;
            font-size: 14px;
            color: var(--text-light);
        }

        .customer-name {
            color: var(--text);
            font-weight: 500;
            margin-bottom: 25px !important;
        }

        .error {
            background: var(--red-light);
            color: var(--red);
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 18px;
            font-size: 13px;
        }

        .button-group {
            display: flex;
            gap: 10px;
        }

        .cancel-button,
        .delete-button {
            flex: 1;
            height: 48px;
            border-radius: 13px;
            font-family: "Prompt", sans-serif;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cancel-button {
            background: #fff;
            color: var(--text);
            border: 1px solid var(--border);
        }

        .delete-button {
            background: var(--red);
            color: #fff;
            border: none;
        }

        .delete-button:hover {
            opacity: 0.9;
        }

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
                box-shadow: 0 20px 50px rgba(0, 0, 0, 0.12);
            }

        }
    </style>

</head>

<body>

    <div class="app">

        <div class="top-bar">

            <a href="customers.php" class="back-button">‹</a>

            <h1>ลบลูกค้า</h1>

        </div>

        <div class="delete-card">

            <div class="delete-icon">
                🗑️
            </div>

            <h2>ยืนยันการลบข้อมูล</h2>

            <p>
                คุณต้องการลบข้อมูลลูกค้ารายนี้หรือไม่
            </p>

            <p class="customer-name">
                <?php echo htmlspecialchars($customer["customer_name"]); ?>
            </p>

            <?php if (isset($error)): ?>

                <div class="error">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <div class="button-group">

                    <a
                        href="customers.php"
                        class="cancel-button">
                        ยกเลิก
                    </a>

                    <button
                        type="submit"
                        class="delete-button">
                        ยืนยันการลบ
                    </button>

                </div>

            </form>

        </div>

    </div>

</body>

</html>