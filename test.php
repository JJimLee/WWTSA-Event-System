<?php
$includeFunction = true;
include_once("function.php");
$data = ECPay_NewOrder("測試活動訂票", "測試內容", 100);
echo ECPay_SubmitForm($data);

?>