<?php
$includeFunction = true;
include_once("function.php");
$data = ECPay_NewOrder("測試活動訂票", 100, "測試內容");
ECPay_SubmitForm($data);

?>