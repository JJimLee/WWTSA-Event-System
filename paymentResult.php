<?php

$includeFunction = true;
include_once("function.php");

echo ECPay_ProcessPaymentClient();
echo "<br><button onclick=\"window.print();return false;\">列印此頁</button>";
?>
