<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: owner_login.php");
    exit;
}

require_once "config/database.php";

$sale_id = (int)($_GET["sale_id"] ?? 0);

/* ดึงข้อมูลลูกค้า */
$sql = "
    SELECT
        customer_id,
        customer_name,
        phone_number
    FROM customers
    ORDER BY customer_id ASC
";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>เลือกลูกค้า</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>

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

            width: 42px;

            height: 42px;

            border: none;

            border-radius: 14px;

            background: var(--primary-light);

            color: var(--primary);

            font-size: 28px;

            line-height: 1;

            cursor: pointer;
        }

        .header-title {
            font-size: 21px;

            font-weight: 600;

            color: var(--primary-dark);
        }


        /* Search */

        .search-box {
            position: relative;

            margin-bottom: 14px;
        }

        .search-input {
            width: 100%;

            height: 44px;

            padding: 0 16px 0 44px;

            border: 1px solid var(--primary);

            border-radius: 22px;

            outline: none;

            font-family: "Prompt", sans-serif;

            font-size: 13px;

            color: var(--text);

            background: var(--white);
        }

        .search-input::placeholder {
            color: var(--text-light);
        }

        .search-input:focus {
            border-color: var(--primary-dark);
        }

        .search-icon {
            position: absolute;

            left: 15px;

            top: 50%;

            transform: translateY(-50%);

            font-size: 20px;

            color: var(--text-light);

            pointer-events: none;
        }


        /* Customer list */

        .customer-list {
            display: flex;

            flex-direction: column;

            gap: 10px;
        }

        .customer-card {
            display: block;

            padding: 14px 16px;

            background: var(--primary-light);

            border: 1px solid transparent;

            border-radius: 14px;

            text-decoration: none;

            color: var(--text);

            transition: 0.15s;
        }

        .customer-card:hover {
            border-color: var(--primary);

            transform: translateY(-1px);
        }

        .customer-name {
            font-size: 14px;

            font-weight: 500;

            margin-bottom: 4px;
        }

        .customer-phone {
            font-size: 12px;

            color: var(--text);
        }


        /* Empty */

        .empty-message {
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


        <!-- Header -->

        <div class="header">

            <button
                type="button"
                class="back-button"
                onclick="history.back()"
                aria-label="ย้อนกลับ">
                ‹
            </button>

            <div class="header-title">
                เลือกลูกค้า
            </div>

        </div>


        <!-- Search -->

        <div class="search-box">

            <span class="search-icon">
                🔍
            </span>

            <input
                type="text"
                id="searchCustomer"
                class="search-input"
                placeholder="ค้นหาชื่อหรือลูกค้า"
                autocomplete="off">

        </div>


        <!-- Customer list -->

        <div
            class="customer-list"
            id="customerList">

            <?php if ($result->num_rows > 0): ?>

                <?php while ($customer = $result->fetch_assoc()): ?>

                    <a
                        href="<?php
                                if ($sale_id > 0) {
                                    echo "credit_customer_check.php?sale_id=" . $sale_id . "&customer_id=" . (int)$customer["customer_id"];
                                } else {
                                    echo "credit_customer_list.php?customer_id=" . (int)$customer["customer_id"];
                                }
                                ?>"
                        class="customer-card"
                        data-search="<?php
                                        echo htmlspecialchars(
                                            $customer["customer_name"] . " " . $customer["phone_number"]
                                        );
                                        ?>">

                        <div class="customer-name">

                            <?php
                            echo htmlspecialchars(
                                $customer["customer_name"]
                            );
                            ?>

                        </div>

                        <div class="customer-phone">

                            <?php
                            echo htmlspecialchars(
                                $customer["phone_number"]
                            );
                            ?>

                        </div>

                    </a>

                <?php endwhile; ?>

                <div
                    id="noSearchResult"
                    class="empty-message"
                    style="display:none;">
                    ไม่พบข้อมูลลูกค้า
                </div>

            <?php else: ?>

                <div class="empty-message">
                    ยังไม่มีข้อมูลลูกค้า
                </div>

            <?php endif; ?>

        </div>


    </div>


    <script>
        const searchInput =
            document.getElementById("searchCustomer");

        const customerCards =
            document.querySelectorAll(".customer-card");

        const noSearchResult =
            document.getElementById("noSearchResult");


        searchInput.addEventListener(
            "input",
            function() {

                const keyword =
                    this.value
                    .trim()
                    .toLowerCase();

                let found = false;


                customerCards.forEach(
                    function(card) {

                        const text =
                            card.dataset.search
                            .toLowerCase();

                        if (text.includes(keyword)) {

                            card.style.display = "block";

                            found = true;

                        } else {

                            card.style.display = "none";

                        }

                    }
                );


                if (noSearchResult) {

                    noSearchResult.style.display =
                        found ? "none" : "block";

                }

            }
        );
    </script>

</body>

</html>