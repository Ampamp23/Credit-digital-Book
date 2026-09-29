<?php

session_start();

if (!isset($_SESSION["customer_id"])) {
    http_response_code(403);
    exit();
}

$_SESSION["notification_viewed"] = true;

http_response_code(200);

?>