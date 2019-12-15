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

if (isset($argv[1]) && isset($argv[2]) && isset($argv[3])){
    $name = $argv[1];
    $email = $argv[2];
    $couponCode = $argv[3];
	
    if ($couponCode != false){
		if (Coupon_check($couponCode) === false){
			echo $couponCode." is used\n";
			exit;
		}
        
		echo $couponCode."\n";
        SMTP_Sender($name, $email, "世學聯晚會早鳥票付款提醒", 
            "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
			$name 您好，，<br>
			僅此代表世界台灣學生會聯合總會，感謝您購買世學聯年終晚會門票-早鳥優惠票，<br>
			期待與您於年底的晚會見面。<br>
            <br>
            以下是付款方式，您可以選擇：<br>
            1. 信用卡<br>
            2. 超商代碼付款<br>
            3. 銀行轉帳<br>
            <br>
            早鳥優惠代碼: $couponCode<br>
            優惠連結: <a href=\"https://event.worldwidetsa.org/?promo=$couponCode\">https://event.worldwidetsa.org/</a><br>
            僅限一次使用 付款後失效<br>
			請輸入此優惠代碼<br>
			《請於12/15前完成付款以確保購買早鳥票票價》<br>
            <br>
            活動詳情：<br>
            地點：Fiesta Restaurant & Live House, Taipei, Taiwan<br>
            時間：12/27(五) 17:00-21:30<br>
            服裝：Formal Attire<br>
            <br>
            趕快收拾行囊，帶著愉快心情。<br>
            「揮別今年，回家吧！」我們期待當天與您相見。<br>
            <br>
            世學聯<br>
            "
        );
    }
}
else{
    echo "Usage: php ".$argv[0]." [Name] [Email] [Option: CouponCode]\n";
}
?>