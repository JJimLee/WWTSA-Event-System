<?php
$includeFunction = true;
include_once("../config.php");
include_once("../MYSQLConnect.php");
include_once("../function.php");

if (isset($argv[1]) && isset($argv[2])){
    $orderId = $argv[1];
    $processBy = $argv[2];
    
    $price = MYSQL_GetOrderInfo($orderId);
    if ($price !== false){
        $CashPrice = $price['Price'];
        MYSQL_AddPaymentResultServer("Cash-$processBy", $CashPrice, "交易成功", "{\"ProcessBy\":\"$processBy\"}", $orderId);
		MYSQL_ChangeOrderFee($orderId, 0);
		echo "$orderId: \$$CashPrice 現金付款成功!\n";
		Coupon_Used($price['PromoCode']);
    }
    else{
        echo "Error: OrderID not found\n";
    }
}
else{
    echo "Usage: php ".$argv[0]." [OrderID] [Process By]\n";
}

?>
