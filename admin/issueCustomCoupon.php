<?php
$includeFunction = true;
include_once("../config.php");
include_once("../MYSQLConnect.php");
include_once("../function.php");

/*
$result = Coupon_add(
                $name, 
                $description, 
                $type, 
                $value, 
                $totalCount, 
                $remark="", 
                $CouponCode=null, 
                $active=true);
*/

if (isset($argv[6])){
    $name = $argv[1];
    $description = $argv[2];
    $type = $argv[3];
    $value = $argv[4];
    $count = $argv[5];
    $remark = $argv[6];
    
    if (empty($argv[7])){
        $couponCode = Coupon_add(
                $name,
                $description, 
                $type, 
                $value, 
                $count, 
                $remark);
    }
    else{
        $couponCode = $argv[7];
	Coupon_add(
		$name,
                $description,
                $type,
                $value,
                $count,
		$remark,
	        $couponCode
	);
    }
    if ($couponCode != false){
        echo $couponCode."\n";    
    }
}
else{
    echo "Usage: php ".$argv[0]." [Name] [Description] [Type $ or %] [value] [Count] [Remark] [Option: CouponCode]\n";
}
?>
