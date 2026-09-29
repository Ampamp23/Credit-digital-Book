<?php

session_start();

/* ล้างข้อมูล Session ของลูกค้า */
unset($_SESSION["customer_id"]);
unset($_SESSION["customer_name"]);
unset($_SESSION["customer_phone"]);

/* กลับไปหน้าเข้าสู่ระบบ */
header("Location: customer_login.php");
exit();

?>