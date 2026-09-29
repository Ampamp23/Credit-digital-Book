<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: owner_login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>หน้าหลักเจ้าของร้าน | เชื่อใจ</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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

            --border: #D7ECF7;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
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

            position: relative;
            overflow: hidden;
        }


        /* =========================
           Header
        ========================= */

        .header {
            height: 70px;

            display: flex;
            align-items: center;

            padding: 0 20px;

            border-bottom: 1px solid #E8EDF0;
        }

        .back-button {
            width: 40px;
            height: 40px;

            display: flex;
            align-items: center;
            justify-content: center;

            text-decoration: none;

            color: var(--text);

            font-size: 25px;

            border-radius: 12px;

            transition: 0.2s;
        }

        .back-button:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
        }

        .page-title {
            flex: 1;

            text-align: center;

            font-size: 20px;
            font-weight: 500;

            margin-right: 40px;
        }


        /* =========================
           Content
        ========================= */

        .content {
            padding: 22px 20px 30px;
        }


        /* =========================
           Welcome Card
        ========================= */

        .welcome-card {
            background: linear-gradient(135deg,
                    var(--primary),
                    var(--primary-dark));

            color: white;

            border-radius: 22px;

            padding: 22px;

            margin-bottom: 18px;

            box-shadow:
                0 8px 20px rgba(67, 143, 184, 0.18);
        }

        .welcome-small {
            font-size: 14px;
            font-weight: 300;

            margin-bottom: 3px;
        }

        .welcome-name {
            font-size: 24px;
            font-weight: 500;

            margin-bottom: 7px;
        }

        .welcome-description {
            font-size: 13px;
            font-weight: 300;

            line-height: 1.6;

            opacity: 0.95;
        }


        /* =========================
           Barcode Scan
        ========================= */

        .barcode-card {
            background: var(--primary-light);

            border: 1px solid var(--border);

            border-radius: 20px;

            padding: 20px;

            display: flex;
            align-items: center;

            gap: 15px;

            margin-bottom: 25px;

            text-decoration: none;

            color: var(--text);

            box-shadow:
                0 4px 12px rgba(38, 50, 56, 0.05);

            transition: 0.2s;
        }

        .barcode-card:hover {
            transform: translateY(-2px);

            box-shadow:
                0 7px 17px rgba(38, 50, 56, 0.08);
        }

        .barcode-icon {
            width: 55px;
            height: 55px;

            border-radius: 16px;

            background: var(--white);

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 28px;

            flex-shrink: 0;
        }

        .barcode-text {
            flex: 1;
        }

        .barcode-title {
            font-size: 17px;
            font-weight: 500;

            margin-bottom: 2px;
        }

        .barcode-description {
            font-size: 12px;

            color: var(--text-light);

            line-height: 1.5;
        }

        .arrow {
            font-size: 23px;

            color: var(--primary-dark);
        }


        /* =========================
           Menu Cards
        ========================= */

        .menu-grid {
            display: grid;

            grid-template-columns: repeat(2, 1fr);

            gap: 14px;
        }

        .menu-card {
            min-height: 145px;

            /* สีเดียวกับปุ่มสแกนบาร์โค้ด */
            background: var(--primary-light);

            border: 1px solid var(--border);

            border-radius: 20px;

            padding: 18px;

            text-decoration: none;

            color: var(--text);

            display: flex;
            flex-direction: column;
            justify-content: space-between;

            box-shadow:
                0 4px 12px rgba(38, 50, 56, 0.05);

            transition: 0.2s;
        }

        .menu-card:hover {
            transform: translateY(-3px);

            box-shadow:
                0 7px 17px rgba(38, 50, 56, 0.08);
        }

        .menu-icon {
            width: 50px;
            height: 50px;

            /* กล่อง Emoji สีขาว */
            background: var(--white);

            border-radius: 15px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 25px;
        }

        .menu-name {
            font-size: 15px;
            font-weight: 500;

            margin-top: 12px;
        }

        .menu-description {
            font-size: 11px;

            color: var(--text-light);

            margin-top: 2px;

            line-height: 1.4;
        }


        /* =========================
           Logout
        ========================= */

        .logout-wrapper {
            text-align: center;

            margin-top: 30px;
        }

        .logout-button {
            display: inline-block;

            color: var(--text-light);

            font-size: 13px;

            text-decoration: none;

            padding: 8px 16px;

            border-radius: 10px;

            transition: 0.2s;
        }

        .logout-button:hover {
            color: #D9534F;

            background: #FFF3F2;
        }


        /* =========================
           Desktop
        ========================= */

        @media (min-width: 768px) {

            body {
                min-height: 100vh;

                display: flex;
                align-items: center;
                justify-content: center;

                padding: 30px 0;
            }

            .app {
                min-height: 760px;

                height: 850px;

                border-radius: 34px;

                box-shadow:
                    0 20px 50px rgba(38, 50, 56, 0.15);
            }

            .content {
                padding-left: 25px;
                padding-right: 25px;
            }
        }
    </style>
</head>

<body>

    <div class="app">


        <!-- Header -->

        <header class="header">

            <a
                href="index.php"
                class="back-button"
                aria-label="ย้อนกลับ">
                ‹
            </a>

            <h1 class="page-title">
                หน้าหลัก
            </h1>

        </header>


        <main class="content">


            <!-- Welcome -->

            <section class="welcome-card">

                <p class="welcome-small">
                    ยินดีต้อนรับ
                </p>

                <h2 class="welcome-name">
                    เจ้าของร้าน
                </h2>

                <p class="welcome-description">
                    จัดการข้อมูลร้านค้าและรายการซื้อเชื่อ
                </p>

            </section>


            <!-- Barcode Scan -->

            <a
                href="scan_barcode.php"
                class="barcode-card">

                <div class="barcode-icon">
                    📷
                </div>

                <div class="barcode-text">

                    <div class="barcode-title">
                        สแกนบาร์โค้ดสินค้า
                    </div>

                    <div class="barcode-description">
                        สแกนเพื่อบันทึกรายการขายและค้นหาข้อมูลสินค้า
                    </div>

                </div>

                <div class="arrow">
                    ›
                </div>

            </a>


            <!-- Menu Cards -->

            <div class="menu-grid">


                <!-- Customer -->

                <a
                    href="customers.php"
                    class="menu-card">

                    <div class="menu-icon">
                        👥
                    </div>

                    <div>

                        <div class="menu-name">
                            ข้อมูลลูกค้า
                        </div>

                        <div class="menu-description">
                            เพิ่ม แก้ไข และลบข้อมูลลูกค้า
                        </div>

                    </div>

                </a>


                <!-- Credit Purchase -->

                <a href="credit_customers.php" class="menu-card">

                    <div class="menu-icon">
                        📝
                    </div>

                    <div>

                        <div class="menu-name">
                            ซื้อเชื่อ
                        </div>

                        <div class="menu-description">
                            จัดการรายการซื้อเชื่อและรอบเครดิต
                        </div>

                    </div>

                </a>


                <!-- Payment -->

                <a
                    href="payments.php"
                    class="menu-card">

                    <div class="menu-icon">
                        💰
                    </div>

                    <div>

                        <div class="menu-name">
                            การชำระเงิน
                        </div>

                        <div class="menu-description">
                            บันทึกและตรวจสอบการชำระเงิน
                        </div>

                    </div>

                </a>


                <!-- Reports -->

                <a
                    href="reports.php"
                    class="menu-card">

                    <div class="menu-icon">
                        📊
                    </div>

                    <div>

                        <div class="menu-name">
                            รายงานและตรวจสอบข้อมูล
                        </div>

                        <div class="menu-description">
                            ดูรายงานและวิเคราะห์ข้อมูล
                        </div>

                    </div>

                </a>


            </div>


            <!-- Logout -->

            <div class="logout-wrapper">

                <a
                    href="logout.php"
                    class="logout-button"
                    onclick="return confirm('ต้องการออกจากระบบหรือไม่?');">
                    ออกจากระบบ
                </a>

            </div>


        </main>

    </div>

</body>

</html>