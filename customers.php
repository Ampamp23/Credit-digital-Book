<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: owner_login.php");
    exit;
}

require_once "config/database.php";


/* ดึงข้อมูลลูกค้า พร้อมสถานะล่าสุด */

$sql = "
    SELECT
        c.customer_id,
        c.customer_name,
        c.phone_number,
        c.credit_limit,
        cr.payment_status
    FROM customers c
    LEFT JOIN credit_rounds cr
        ON cr.credit_round_id = (
            SELECT MAX(cr2.credit_round_id)
            FROM credit_rounds cr2
            WHERE cr2.customer_id = c.customer_id
        )
    ORDER BY c.customer_id ASC
";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ข้อมูลลูกค้า</title>

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

            --green: #35A56A;
            --green-light: #EAF7F0;

            --yellow: #D99A00;
            --yellow-light: #FFF7E3;

            --red: #D9534F;
            --red-light: #FDECEC;
        }


        * {
            box-sizing: border-box;
        }


        html,
        body {
            margin: 0;
            min-height: 100%;
        }


        body {
            font-family: "Prompt", sans-serif;
            background: var(--background);
            color: var(--text);

            overflow-x: hidden;
        }


        /* =========================================================
   APP
   ========================================================= */

        .app {
            width: 100%;
            max-width: 490px;

            min-height: 100vh;

            margin: auto;

            background: #fff;

            padding: 24px 18px 30px;
        }


        /* =========================================================
   TOP BAR
   ========================================================= */

        .top-bar {
            position: relative;

            display: flex;
            align-items: center;
            justify-content: center;

            margin-bottom: 24px;
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


        /* =========================================================
   SEARCH
   ========================================================= */

        .search-box {
            margin-bottom: 14px;
        }


        .search-box input {
            width: 100%;
            height: 46px;

            border: 1px solid var(--border);
            border-radius: 13px;

            padding: 0 15px;

            font-family: "Prompt", sans-serif;
            font-size: 14px;

            outline: none;
        }


        .search-box input:focus {
            border-color: var(--primary);
        }


        /* =========================================================
   ADD CUSTOMER
   ========================================================= */

        .add-button {
            width: 100%;
            height: 46px;

            border: none;
            border-radius: 13px;

            background: var(--primary);
            color: #fff;

            text-decoration: none;

            display: flex;
            align-items: center;
            justify-content: center;

            font-family: "Prompt", sans-serif;
            font-size: 14px;
            font-weight: 500;

            margin-bottom: 18px;
        }


        .add-button:hover {
            background: var(--primary-dark);
        }


        /* =========================================================
   CUSTOMER LIST
   ========================================================= */

        .customer-list {
            display: flex;
            flex-direction: column;

            gap: 12px;

            padding-bottom: 10px;
        }


        /* =========================================================
   CUSTOMER CARD
   ========================================================= */

        .customer-card {
            background: #fff;

            border: 1px solid var(--border);
            border-radius: 18px;

            padding: 16px;

            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }


        .customer-info {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 12px;

            margin-bottom: 12px;
        }

        .customer-main {
            min-width: 0;
        }

        .customer-status {
            flex-shrink: 0;
            display: flex;
            align-items: center;
        }


        .customer-name {
            font-size: 17px;
            font-weight: 500;

            margin-bottom: 3px;
        }


        .customer-phone {
            font-size: 13px;
            color: var(--text-light);
        }


        /* =========================================================
   STATUS
   ========================================================= */

        .status {
            display: inline-flex;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 12px;
            font-weight: 400;
        }


        .status-paid {
            background: var(--green-light);
            color: var(--green);
        }


        .status-partial {
            background: var(--yellow-light);
            color: var(--yellow);
        }


        .status-unpaid {
            background: var(--red-light);
            color: var(--red);
        }


        /* =========================================================
   CARD BUTTONS
   ========================================================= */

        .card-buttons {
            display: flex;

            gap: 8px;
        }


        .card-button {
            flex: 1;

            height: 38px;

            border-radius: 10px;

            text-decoration: none;

            display: flex;
            align-items: center;
            justify-content: center;

            font-family: "Prompt", sans-serif;
            font-size: 12px;
        }


        /* ดู */

        .view-button {
            background: var(--primary-light);
            color: var(--primary-dark);
        }


        /* แก้ไข */

        .edit-button {
            background: #F4F6F7;
            color: var(--text);
        }


        /* ลบ */

        .delete-button {
            flex: 1;

            height: 38px;

            border: none;
            border-radius: 10px;

            background: var(--red-light);
            color: var(--red);

            font-family: "Prompt", sans-serif;
            font-size: 12px;

            cursor: pointer;
        }


        .delete-button:hover {
            background: #F9DCDC;
        }


        /* =========================================================
   EMPTY
   ========================================================= */

        .empty-message {
            text-align: center;

            color: var(--text-light);

            font-size: 14px;

            padding: 35px 10px;
        }


        /* =========================================================
   DELETE MODAL
   ========================================================= */

        .modal-overlay {
            display: none;

            position: fixed;

            inset: 0;

            background: rgba(38, 50, 56, 0.45);

            z-index: 999;

            align-items: center;
            justify-content: center;

            padding: 20px;
        }


        .modal-overlay.show {
            display: flex;
        }


        .modal-box {
            width: 100%;
            max-width: 360px;

            background: #fff;

            border-radius: 24px;

            padding: 26px 20px 20px;

            text-align: center;

            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.18);

            animation: modalIn 0.2s ease;
        }


        .modal-icon {
            width: 64px;
            height: 64px;

            margin: 0 auto 16px;

            border-radius: 50%;

            background: var(--red-light);
            color: var(--red);

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 28px;
        }


        .modal-box h2 {
            margin: 0 0 8px;

            font-size: 19px;
            font-weight: 600;

            color: var(--text);
        }


        .modal-box p {
            margin: 0;

            font-size: 14px;

            color: var(--text-light);
        }


        .modal-customer-name {
            margin-top: 5px !important;
            margin-bottom: 22px !important;

            font-weight: 500;

            color: var(--text) !important;
        }


        .modal-buttons {
            display: flex;

            gap: 10px;
        }


        .modal-cancel,
        .modal-confirm {
            flex: 1;

            height: 46px;

            border-radius: 13px;

            font-family: "Prompt", sans-serif;
            font-size: 14px;
            font-weight: 500;

            cursor: pointer;
        }


        /* ยกเลิก */

        .modal-cancel {
            background: #fff;

            color: var(--text);

            border: 1px solid var(--border);
        }


        /* ยืนยัน */

        .modal-confirm {
            background: var(--red);

            color: #fff;

            border: none;
        }


        .modal-confirm:hover {
            opacity: 0.9;
        }


        /* =========================================================
   MODAL ANIMATION
   ========================================================= */

        @keyframes modalIn {

            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }

        }


        /* =========================================================
   DESKTOP
   ========================================================= */

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

                /*
         * ถ้าลูกค้ามีจำนวนมาก
         * ให้พื้นที่ในกรอบแอปสามารถเลื่อนขึ้นลงได้
         */
                overflow-y: auto;
            }

        }


        /* =========================================================
   MOBILE
   ========================================================= */

        @media (max-width: 767px) {

            /*
     * มือถือให้หน้าเว็บยาวตามจำนวนข้อมูล
     * และเลื่อนขึ้นลงตามปกติ
     */

            .app {
                min-height: 100vh;

                height: auto;

                overflow: visible;
            }

        }
    </style>

</head>


<body>

    <div class="app">


        <!-- =====================================================
         TOP BAR
         ===================================================== -->

        <div class="top-bar">

            <a
                href="owner_home.php"
                class="back-button">
                ‹
            </a>

            <h1>
                ข้อมูลลูกค้า
            </h1>

        </div>


        <!-- =====================================================
         SEARCH
         ===================================================== -->

        <div class="search-box">

            <input
                type="text"
                id="searchCustomer"
                placeholder="ค้นหาลูกค้า"
                autocomplete="off">

        </div>


        <!-- =====================================================
         ADD CUSTOMER
         ===================================================== -->

        <a
            href="customer_add.php"
            class="add-button">
            ＋ เพิ่มลูกค้า
        </a>


        <!-- =====================================================
         CUSTOMER LIST
         ===================================================== -->

        <div
            class="customer-list"
            id="customerList">

            <?php if ($result->num_rows > 0): ?>

                <?php while ($customer = $result->fetch_assoc()): ?>

                    <?php

                    $status = $customer["payment_status"];


                    if (
                        $status === null ||
                        $status === "ชำระครบ"
                    ) {

                        $status_text = "ชำระครบ";
                        $status_class = "status-paid";
                    } elseif (
                        $status === "ชำระบางส่วน"
                    ) {

                        $status_text = "ชำระบางส่วน";
                        $status_class = "status-partial";
                    } else {

                        $status_text = "ยังไม่ชำระ";
                        $status_class = "status-unpaid";
                    }

                    ?>


                    <div
                        class="customer-card"
                        data-search="<?php
                                        echo htmlspecialchars(
                                            $customer["customer_name"]
                                                . " "
                                                . $customer["phone_number"]
                                        );
                                        ?>">


                        <!-- ข้อมูลลูกค้า -->

                        <div class="customer-info">

                            <div class="customer-main">

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

                            </div>


                            <div class="customer-status">

                                <div class="status <?php echo $status_class; ?>">

                                    <?php echo $status_text; ?>

                                </div>

                            </div>

                        </div>


                        <!-- ปุ่ม -->

                        <div class="card-buttons">


                            <!-- ดู -->

                            <a
                                href="customer_view.php?customer_id=<?php
                                                                    echo $customer["customer_id"];
                                                                    ?>"
                                class="card-button view-button">
                                ดู
                            </a>


                            <!-- แก้ไข -->

                            <a
                                href="customer_edit.php?customer_id=<?php
                                                                    echo $customer["customer_id"];
                                                                    ?>"
                                class="card-button edit-button">
                                แก้ไข
                            </a>


                            <!-- ลบ -->

                            <button
                                type="button"
                                class="delete-button"
                                onclick="openDeleteModal(
                                <?php
                                echo $customer["customer_id"];
                                ?>,
                                '<?php
                                    echo htmlspecialchars(
                                        $customer["customer_name"],
                                        ENT_QUOTES
                                    );
                                    ?>'
                            )">
                                ลบ
                            </button>


                        </div>

                    </div>

                <?php endwhile; ?>


                <!-- ไม่พบผลการค้นหา -->

                <div
                    id="noSearchResult"
                    class="empty-message"
                    style="display: none;">
                    ไม่พบข้อมูลลูกค้า
                </div>


            <?php else: ?>


                <div class="empty-message">

                    ยังไม่มีข้อมูลลูกค้า

                </div>


            <?php endif; ?>

        </div>


    </div>


    <!-- =========================================================
     DELETE MODAL
     ========================================================= -->

    <div
        id="deleteModal"
        class="modal-overlay">

        <div class="modal-box">


            <div class="modal-icon">
                🗑️
            </div>


            <h2>
                ยืนยันการลบลูกค้า
            </h2>


            <p>
                ต้องการลบข้อมูลลูกค้ารายนี้หรือไม่
            </p>


            <p
                class="modal-customer-name"
                id="deleteCustomerName">
                -
            </p>


            <div class="modal-buttons">


                <button
                    type="button"
                    class="modal-cancel"
                    onclick="closeDeleteModal()">
                    ยกเลิก
                </button>


                <button
                    type="button"
                    class="modal-confirm"
                    onclick="confirmDelete()">
                    ยืนยันการลบ
                </button>


            </div>

        </div>

    </div>


    <script>
        /* =========================================================
   SEARCH CUSTOMER
   ========================================================= */

        const searchInput =
            document.getElementById("searchCustomer");

        const customerCards =
            document.querySelectorAll(".customer-card");

        const noSearchResult =
            document.getElementById("noSearchResult");


        searchInput.addEventListener("input", function() {

            const keyword =
                this.value.trim().toLowerCase();

            let found = false;


            customerCards.forEach(function(card) {

                const searchText =
                    card.dataset.search.toLowerCase();


                if (searchText.includes(keyword)) {

                    card.style.display = "";

                    found = true;

                } else {

                    card.style.display = "none";

                }

            });


            if (noSearchResult) {

                noSearchResult.style.display =
                    found ? "none" : "block";

            }

        });


        /* =========================================================
           DELETE MODAL
           ========================================================= */

        let deleteCustomerId = null;


        function openDeleteModal(
            customerId,
            customerName
        ) {

            deleteCustomerId = customerId;


            document.getElementById(
                "deleteCustomerName"
            ).textContent = customerName;


            document.getElementById(
                "deleteModal"
            ).classList.add("show");

        }


        function closeDeleteModal() {

            deleteCustomerId = null;


            document.getElementById(
                "deleteModal"
            ).classList.remove("show");

        }


        function confirmDelete() {

            if (deleteCustomerId !== null) {

                window.location.href =
                    "customer_delete.php?customer_id=" +
                    deleteCustomerId;

            }

        }


        /* ปิด Popup เมื่อกดพื้นที่ด้านนอก */

        document.getElementById("deleteModal")
            .addEventListener("click", function(event) {

                if (event.target === this) {

                    closeDeleteModal();

                }

            });
    </script>

</body>

</html>