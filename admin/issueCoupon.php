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

if (isset($argv[1]) && isset($argv[2])){
    $name = $argv[1];
    $email = $argv[2];
    
    if (empty($argv[3])){
        $couponCode = Coupon_add(
                "第一階段售票優惠", 
                "折價$200NTD", 
                "$", 
                "200", 
                1, 
                "For ".$name." sent to ".$email);
    }
    else{
        $couponCode = $argv[3];
        Coupon_add(
                "第一階段售票優惠", 
                "折價$200NTD", 
                "$", 
                "200", 
                1, 
                "For ".$name." sent to ".$email, 
                $couponCode
                );
    }
    if ($couponCode != false){
        echo $couponCode."\n";
        
        SMTP_Sender($name, $email, "世學聯-第一波售票優惠代碼與付款說明", 
            "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
            $name 您好，<br>
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
            <br>
            活動詳情：<br>
            地點：Fiesta Restaurant & Live House, Taipei, Taiwan<br>
            時間：12/27(五) 19:00-21:30<br>
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