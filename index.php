<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Digital Credit Book</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        /* =========================
           DESIGN SYSTEM
        ========================= */

        :root {
            --primary: #5AA6CF;
            --primary-dark: #438FB8;
            --primary-light: #E8F5FC;

            --background: #E6D2BC;
            --card: #FFFFFF;
            --text: #263238;
            --text-light: #7A858C;

            --border: #E8EDF0;

            --radius-card: 18px;
            --radius-button: 14px;
        }


        /* =========================
           RESET
        ========================= */

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {
            font-family: "Prompt", sans-serif;
            background: var(--background);

            min-height: 100vh;

            display: flex;
            justify-content: center;
            align-items: center;
        }


        /* =========================
           MOBILE APP CONTAINER
        ========================= */

        .app {
            width: 100%;
            max-width: 490px;
            min-height: 100vh;

            background: var(--card);

            padding: 55px 30px 40px;

            display: flex;
            flex-direction: column;
            align-items: center;
        }


        /* =========================
           LOGO
        ========================= */

        .logo {
            width: 330px;
            height: 150px;

            display: flex;
            justify-content: center;
            align-items: center;

            margin-bottom: 5px;

            overflow: hidden;
        }


        .logo img {
            width: 333px;
            height: auto;

            max-width: none;

            display: block;

            transform: translateY(-2px);
        }


        /* =========================
           SYSTEM NAME
        ========================= */

        .system-name {
            text-align: center;
            margin-top: -18px;
        }

        .system-name p {
            font-size: 14px;
            font-weight: 400;

            color: var(--text-light);

            letter-spacing: 0.2px;
        }


        /* =========================
           SELECT TITLE
        ========================= */

        .select-title {
            width: 100%;

            margin-top: 65px;
            margin-bottom: 20px;

            font-size: 17px;
            font-weight: 500;

            color: var(--text);
        }


        /* =========================
           ROLE CARD
        ========================= */

        .role-button {
            width: 100%;
            min-height: 94px;

            display: flex;
            align-items: center;

            padding: 15px 18px;
            margin-bottom: 16px;

            background: var(--card);

            border: 1px solid var(--border);
            border-radius: var(--radius-card);

            text-decoration: none;
            color: var(--text);

            box-shadow: 0 5px 16px rgba(40, 60, 70, 0.07);

            transition:
                transform 0.15s ease,
                box-shadow 0.15s ease,
                border-color 0.15s ease;
        }


        .role-button:hover {
            transform: translateY(-2px);

            border-color: #C9E5F3;

            box-shadow: 0 8px 20px rgba(40, 60, 70, 0.11);
        }


        .role-button:active {
            transform: scale(0.98);
        }


        /* =========================
           ROLE ICON
        ========================= */

        .role-icon {
            width: 60px;
            height: 60px;

            flex-shrink: 0;

            border-radius: 16px;

            background: var(--primary-light);

            display: flex;
            justify-content: center;
            align-items: center;

            font-size: 29px;

            margin-right: 16px;
        }


        /* =========================
           ROLE TEXT
        ========================= */

        .role-content {
            flex: 1;
            min-width: 0;
        }


        .role-content h2 {
            font-size: 16px;
            font-weight: 500;

            margin-bottom: 4px;
        }


        .role-content p {
            font-size: 12px;
            font-weight: 300;

            color: var(--text-light);

            line-height: 1.6;
        }


        /* =========================
           ARROW
        ========================= */

        .arrow {
            width: 36px;
            height: 36px;

            flex-shrink: 0;

            border-radius: 50%;

            background: #F3F8FB;

            display: flex;
            justify-content: center;
            align-items: center;

            color: var(--primary-dark);

            font-size: 25px;

            margin-left: 10px;
        }


        /* =========================
           FOOTER
        ========================= */

        .footer {
            margin-top: auto;

            padding-top: 40px;

            font-size: 10px;
            font-weight: 300;

            color: #A7AFB4;
        }


        /* =========================
           DESKTOP / NOTEBOOK
        ========================= */

        @media (min-width: 600px) {

            body {
                padding: 20px;
            }

            .app {
                min-height: 760px;
                height: 850px;

                border-radius: 34px;

                box-shadow:
                    0 15px 45px rgba(60, 45, 30, 0.16);

                padding: 55px 30px 40px;
            }
        }


        /* =========================
           SMALL MOBILE
        ========================= */

        @media (max-width: 360px) {

            .app {
                padding-left: 20px;
                padding-right: 20px;
            }

            .logo {
                width: 220px;
                height: 105px;
            }

            .select-title {
                margin-top: 50px;
            }

            .role-button {
                padding: 13px;
            }

            .role-icon {
                width: 52px;
                height: 52px;

                font-size: 25px;

                margin-right: 12px;
            }

            .role-content h2 {
                font-size: 14px;
            }

            .role-content p {
                font-size: 10px;
            }
        }
    </style>
</head>


<body>

    <main class="app">

        <!-- Logo -->
        <div class="logo">
            <img
                src="assets/logo.png"
                alt="CHEUJAI Digital Credit Book">
        </div>


        <!-- System subtitle -->
        <div class="system-name">
            <p>ระบบบริหารการซื้อเชื่อ</p>
        </div>


        <!-- Select user -->
        <h2 class="select-title">
            เลือกประเภทผู้ใช้งาน
        </h2>


        <!-- Owner -->
        <a href="owner_login.php" class="role-button">

            <div class="role-icon">
                🏪
            </div>

            <div class="role-content">

                <h2>
                    เจ้าของร้าน
                </h2>

                <p>
                    จัดการข้อมูลลูกค้า รายการขาย และการซื้อเชื่อ
                </p>

            </div>

            <div class="arrow">
                ›
            </div>

        </a>


        <!-- Customer -->
        <a href="customer_login.php" class="role-button">

            <div class="role-icon">
                👤
            </div>

            <div class="role-content">

                <h2>
                    ลูกค้า
                </h2>

                <p>
                    ตรวจสอบวงเงิน ประวัติการซื้อเชื่อ และการชำระเงิน
                </p>

            </div>

            <div class="arrow">
                ›
            </div>

        </a>


        <!-- Footer -->
        <div class="footer">
            CHEUJAI Digital Credit Book
        </div>

    </main>

</body>

</html>