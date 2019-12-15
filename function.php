<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';


if (empty($includeFunction) || !$includeFunction){
    exit();
}
include_once("config.php");
include_once("MYSQLConnect.php");

function curlPostJSON($url, $data){
    $header = array(
    );
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}

function Google_reCaptcha_check($GoogleToken){
    global $GoogleReCaptchaServerKey;
    $result = curlPostJSON("https://www.google.com/recaptcha/api/siteverify", 
        array(
            "remoteip" => $_SERVER['REMOTE_ADDR'],
            "secret" => $GoogleReCaptchaServerKey,
            "response" => $GoogleToken
        )
    );
    
    return isset($result['success']) && $result['success'] && isset($result['hostname']) && $result['hostname'] == $_SERVER['HTTP_HOST'];
}

function ECPay_merchantSort($a, $b){
    return strcasecmp($a, $b);
}
function ECPay_GetMacValue($arParameters) {
    global $ECPay_HashKey, $ECPay_HashIV;
    $sMacValue = '' ;
    if(isset($arParameters))
    {
        // arParameters 為傳出的參數，並且做字母 A-Z 排序
        unset($arParameters['CheckMacValue']);
        //uksort($arParameters, array('ECPay_CheckMacValue','merchantSort'));
        uksort($arParameters, 'ECPay_merchantSort');
        
        // 組合字串
        $sMacValue = 'HashKey=' . $ECPay_HashKey ;
        foreach($arParameters as $key => $value)
        {
            $sMacValue .= '&' . $key . '=' . $value ;
        }
        $sMacValue .= '&HashIV=' . $ECPay_HashIV ;
        
        // URL Encode 編碼
        $sMacValue = urlencode($sMacValue);
        
        // 轉成小寫
        $sMacValue = strtolower($sMacValue);
        
        // 取代為與 dotNet 相符的字元
        $sMacValue = str_replace('%2d', '-', $sMacValue);
        $sMacValue = str_replace('%5f', '_', $sMacValue);
        $sMacValue = str_replace('%2e', '.', $sMacValue);
        $sMacValue = str_replace('%21', '!', $sMacValue);
        $sMacValue = str_replace('%2a', '*', $sMacValue);
        $sMacValue = str_replace('%28', '(', $sMacValue);
        $sMacValue = str_replace('%29', ')', $sMacValue);
        
        // 編碼
        $sMacValue = hash('sha256', $sMacValue);
        $sMacValue = strtoupper($sMacValue);
    }
    return $sMacValue ;
}

function ECPay_NewOrder($PaymentName, $PaymentDescription, $PaymentAmount, $PaymentMethod=null){
    global $ECPay_MerchantID, $Clark_ReturnURL, $Clark_ClientBackURL, 
    $Clark_OrderResultURL, $Clark_PaymentInfoURL;
    
    switch($PaymentMethod){
        case "CVS":
            $ECPay_ChoosePayment = "CVS";
            $ECPay_IgnorePayment = "WebATM#ATM#Credit#BARCODE#GooglePay";
            break;
        case "BARCODE":
            $ECPay_ChoosePayment = "BARCODE";
            $ECPay_IgnorePayment = "WebATM#ATM#CVS#Credit#GooglePay";
            break;
        case "Credit":
        default:
            $ECPay_ChoosePayment = "Credit";
            $ECPay_IgnorePayment = "WebATM#ATM#CVS#BARCODE#GooglePay";
            break;
    }
    
    $data = array(
        "MerchantID" => $ECPay_MerchantID,
        //TradeNo: 20191020004143Ia6b6c (Date-MD5 hash until reach 20 character)
        "MerchantTradeNo" => substr(date("YmdHis")."I".md5(date("YmdHis")), 0, 20), 
        "MerchantTradeDate" => date("Y/m/d H:i:s"), //Format: 2012/03/21 15:40:18
        "PaymentType" => "aio",
        "TotalAmount" => $PaymentAmount,
        "TradeDesc" => $PaymentDescription,
        "ItemName" => $PaymentName,
        "ReturnURL" => $Clark_ReturnURL,
        "ChoosePayment" => $ECPay_ChoosePayment,
        "CheckMacValue" => "", // Need to work
        //"ClientBackURL" => $Clark_ClientBackURL,
        "OrderResultURL" => $Clark_OrderResultURL,
        "EncryptType" => "1",
        "NeedExtraPaidInfo" => "Y",
        //"IgnorePayment" => $ECPay_IgnorePayment
        "PaymentInfoURL" => $Clark_PaymentInfoURL
    );
    $data['CheckMacValue'] = ECPay_GetMacValue($data);
    return $data;
}

function ECPay_PayOrder($data){
    
    $data['MerchantTradeNo'] = substr(date("YmdHis")."I".md5(date("YmdHis")), 0, 20);
    $data['MerchantTradeDate'] = date("Y/m/d H:i:s");
    $data['CheckMacValue'] = ECPay_GetMacValue($data);
    return $data;
}

function ECPay_PrintReceipt($EmailCopy = false, $isServer = false){
    $ContactInfo = MYSQL_GetContactInfoByOrder($_POST['MerchantTradeNo']);
    if ($ContactInfo === false){
    	return "";
    }
    if ($isServer && isset($_POST['RtnCode']) && $_POST['RtnCode'] == "1"){
        Coupon_Used($ContactInfo['PromoCode']);
    }
    if ($_POST['PaymentType'] == "Credit_CreditCard"){
        $output = "
            姓名: ".$ContactInfo['Name']." (".$ContactInfo['EnglishName'].")<br>
            生日: ".$ContactInfo['DOB']."<br>
            Email: ".$ContactInfo['Email']."<br>
            訂單編號: ".$_POST['MerchantTradeNo']."<br>
            交易金額: ".$_POST['TradeAmt']."<br>
            付款方式: ".$_POST['PaymentType']."<br>
            付款日期: ".$_POST['PaymentDate']."<br>
            信用卡末四碼: ".$_POST['card4no']."<br>
            交易結果: ".$_POST['RtnMsg']."<br>
            授權碼: ".$_POST['auth_code']."<br>
            <br>
            感謝您對世學聯的支持，我們12/27見。<br>
            <br>
            世學聯<br>
        ";
    }
    else if ($_POST['PaymentType'] == "BARCODE_BARCODE"){
        $output = "
            姓名: ".$ContactInfo['Name']." (".$ContactInfo['EnglishName'].")<br>
            生日: ".$ContactInfo['DOB']."<br>
            Email: ".$ContactInfo['Email']."<br>
            訂單編號: ".$_POST['MerchantTradeNo']."<br>
            交易金額: ".$_POST['TradeAmt']."<br>
            付款方式: ".$_POST['PaymentType']."<br>
            付款日期: ".$_POST['PaymentDate']."<br>
            交易結果: ".$_POST['RtnMsg']."<br>
            <br>
            感謝您對世學聯的支持，我們12/27見。<br>
            <br>
            世學聯<br>
        ";
    }
    else if ($_POST['PaymentType'] == "CVS_CVS"){
        $output = "
            姓名: ".$ContactInfo['Name']." (".$ContactInfo['EnglishName'].")<br>
            生日: ".$ContactInfo['DOB']."<br>
            Email: ".$ContactInfo['Email']."<br>
            訂單編號: ".$_POST['MerchantTradeNo']."<br>
            交易金額: ".$_POST['TradeAmt']."<br>
            付款方式: ".$_POST['PaymentType']."<br>
            付款日期: ".$_POST['PaymentDate']."<br>
            交易結果: ".$_POST['RtnMsg']."<br>
            <br>
            感謝您對世學聯的支持，我們12/27見。<br>
            <br>
            世學聯<br>
        ";
    }
    if ($EmailCopy){
        SMTP_Sender(
            $ContactInfo['Name']." (".$ContactInfo['EnglishName'].")", 
            $ContactInfo['Email'],
            "世學聯 - 活動繳費結果",
            "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">".$output
        );
    }
    return $output;
}

function ECPay_PrintOrder($EmailCopy = false){
    /*
    {
        "RtnMsg": "Get CVS Code Succeeded.", 
        "RtnCode": "10100073",
        "StoreID": "",
        "TradeNo": "1911180549139282", 
        "Barcode1": "",
        "Barcode2": "", 
        "Barcode3": "",
        "TradeAmt": "530", 
        "PaymentNo": "LLL19322887800",
        "TradeDate": "2019/11/18 05:49:30", 
        "ExpireDate": "2019/11/25 05:49:29", 
        "MerchantID": "2000214",
        "PaymentType": "CVS_CVS",
        "CustomField1": "",
        "CustomField2": "",
        "CustomField3": "", 
        "CustomField4": "", 
        "CheckMacValue": "249A5DF26D5A8C4E18ADC9D4A2CBDED12D64E2A71D3D3E0896A48E7958C7D503", 
        "MerchantTradeNo": "20191118054910Id4224"}
    */
    /*
    {"RtnMsg": "Get CVS Code Succeeded.",
    "RtnCode": "10100073", 
    "StoreID": "",
    "TradeNo": "1911180805049310", 
    "Barcode1": "081125619",
    "Barcode2": "9409411252653102",
    "Barcode3": "112534000000415",
    "TradeAmt": "415",
    "PaymentNo": "", 
    "TradeDate": "2019/11/18 08:05:16",
    "ExpireDate": "2019/11/25 08:05:16", 
    "MerchantID": "2000214", 
    "PaymentType": "BARCODE_BARCODE",
    "CustomField1": "",
    "CustomField2": "",
    "CustomField3": "", 
    "CustomField4": "",
    "CheckMacValue": "04F18FC789E06EC00A0AF4383DFD1584E6173DF405A4BB234B2198FBDE25297F", 
    "MerchantTradeNo": "20191118080453I6e7a1"}
    */
    $ContactInfo = MYSQL_GetContactInfoByOrder($_POST['MerchantTradeNo']);
    if ($_POST['PaymentType'] == "CVS_CVS"){
        $output = "
            姓名: ".$ContactInfo['Name']." (".$ContactInfo['EnglishName'].")<br>
            生日: ".$ContactInfo['DOB']."<br>
            Email: ".$ContactInfo['Email']."<br>
            訂單編號: ".$_POST['MerchantTradeNo']."<br>
            交易金額: ".$_POST['TradeAmt']."<br>
            付款方式: ".$_POST['PaymentType']."<br>
            訂單日期: ".$_POST['TradeDate']."<br>
            <br>
            持以下資訊至任意 7-11/全家/萊爾富/OK 超商列印條碼繳費<br>
            繳費期限: ".$_POST['ExpireDate']."<br>
            繳費代碼: ".$_POST['PaymentNo']."<br>
            <br>
            感謝您對世學聯的支持，我們12/27見。<br>
            <br>
            世學聯<br>
        ";
    }
    if ($_POST['PaymentType'] == "BARCODE_BARCODE"){
        $output = "
            姓名: ".$ContactInfo['Name']." (".$ContactInfo['EnglishName'].")<br>
            生日: ".$ContactInfo['DOB']."<br>
            Email: ".$ContactInfo['Email']."<br>
            訂單編號: ".$_POST['MerchantTradeNo']."<br>
            交易金額: ".$_POST['TradeAmt']."<br>
            付款方式: ".$_POST['PaymentType']."<br>
            訂單日期: ".$_POST['TradeDate']."<br>
            <br>
            持以下資訊至任意 7-11/全家/萊爾富/OK 超商繳費<br>
            繳費期限: ".$_POST['ExpireDate']."<br>
            繳費條碼: ".$_POST['PaymentNo']."<br>
            <img src=\"https://pay.ecpay.com.tw/bank/tcbank/cnt/GenerateBarcode?barcode=".$_POST['Barcode1']."\"><br>
            <img src=\"https://pay.ecpay.com.tw/bank/tcbank/cnt/GenerateBarcode?barcode=".$_POST['Barcode2']."\"><br>
            <img src=\"https://pay.ecpay.com.tw/bank/tcbank/cnt/GenerateBarcode?barcode=".$_POST['Barcode3']."\"><br>
            <br>
            感謝您對世學聯的支持，我們12/27見。<br>
            <br>
            世學聯<br>
        ";
    }
    if ($EmailCopy){
        SMTP_Sender(
            $ContactInfo['Name']." (".$ContactInfo['EnglishName'].")", 
            $ContactInfo['Email'],
            "世學聯 - 待繳費",
            "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">".$output
        );
    }
    return $output;
}

function ECPay_ProcessPaymentClient(){
    /*
    {
        "eci": "0", 
        "gwsr": "10979225", 
        "staed": "0", "stage": "0", 
        "stast": "0", "RtnMsg": "Succeeded", 
        "amount": "515", 
        "PayFrom": "", 
        "RtnCode": "1", 
        "StoreID": "", 
        "TradeNo": "1911161207028671", 
        "card4no": "2222", 
        "card6no": "431195", 
        "red_dan": "0", 
        "red_yet": "0", 
        "ATMAccNo": "", 
        "AlipayID": "", 
        "TradeAmt": "515", 
        "ExecTimes": "", 
        "Frequency": "", 
        "PaymentNo": "", 
        "TradeDate": "2019/11/16 12:07:02", 
        "auth_code": "777777", 
        "ATMAccBank": "", 
        "MerchantID": "2000214", 
        "PeriodType": "", 
        "red_de_amt": "0", 
        "red_ok_amt": "0", 
        "PaymentDate": "2019/11/16 12:08:11",
        "PaymentType": "Credit_CreditCard", 
        "WebATMAccNo": "", 
        "CustomField1": "", 
        "CustomField2": "", 
        "CustomField3": "", 
        "CustomField4": "", 
        "PeriodAmount": "", 
        "SimulatePaid": "0", 
        "process_date": "2019/11/16 12:08:11", 
        "AlipayTradeNo": "", 
        "CheckMacValue": "502FB9EE0C24EB243819A1B28D7EB55FE6D0B6C57294586BF430F78F8B1E70AD", 
        "TenpayTradeNo": "", 
        "WebATMAccBank": "", 
        "WebATMBankName": "", 
        "MerchantTradeNo": "20191116120657I176d9", 
        "TotalSuccessTimes": "", 
        "TotalSuccessAmount": "", 
        "PaymentTypeChargeFee": "13"
    }
    
    */
    
    
    if (isset($_POST['CheckMacValue']) && $_POST['CheckMacValue'] == ECPay_GetMacValue($_POST)){
        MYSQL_AddPaymentResultClient($_POST['PaymentType'], $_POST['TradeAmt'], $_POST['RtnMsg'], json_encode($_POST, JSON_UNESCAPED_UNICODE), $_POST['MerchantTradeNo']);
        return ECPay_PrintReceipt(false);
    }
    return "Fail";
    
}

function ECPay_ProcessPaymentServer(){
    /*
    {
        "eci": "",
        "gwsr": "",
        "staed": "",
        "stage": "",
        "stast": "",
        "RtnMsg": "u4ed8u6b3eu6210u529f",
        "amount": "",
        "PayFrom": "",
        "RtnCode": "1",
        "StoreID": "",
        "TradeNo": "1911180505159280",
        "card4no": "",
        "card6no": "",
        "red_dan": "",
        "red_yet": "",
        "ATMAccNo": "",
        "AlipayID": "", 
        "TradeAmt": "415",
        "ExecTimes": "",
        "Frequency": "",
        "PaymentNo": "",
        "TradeDate": "2019/11/18 05:05:15",
        "auth_code": "",
        "ATMAccBank": "",
        "MerchantID": "2000214", 
        "PeriodType": "",
        "red_de_amt": "", 
        "red_ok_amt": "", 
        "PaymentDate": "2019/11/18 05:07:29",
        "PaymentType": "BARCODE_BARCODE",
        "WebATMAccNo": "",
        "CustomField1": "",
        "CustomField2": "", 
        "CustomField3": "",
        "CustomField4": "", 
        "PeriodAmount": "",
        "SimulatePaid": "1",
        "process_date": "", 
        "AlipayTradeNo": "", 
        "CheckMacValue": "1FE4E766D9E3741D87F1D9CE1811A73075ADBEF161BCA77677D8EC9B76D16A71", 
        "TenpayTradeNo": "",
        "WebATMAccBank": "", 
        "WebATMBankName": "",
        "MerchantTradeNo": "20191118050511Ie8af6",
        "TotalSuccessTimes": "",
        "TotalSuccessAmount": "", 
        "PaymentTypeChargeFee": "15"}
    */
    if (isset($_POST['CheckMacValue']) && $_POST['CheckMacValue'] == ECPay_GetMacValue($_POST)){
        MYSQL_AddPaymentResultServer($_POST['PaymentType'], $_POST['TradeAmt'], $_POST['RtnMsg'], json_encode($_POST, JSON_UNESCAPED_UNICODE), $_POST['MerchantTradeNo']);
        if (isset($_POST['SimulatePaid']) && $_POST['SimulatePaid'] != "1"){
	        ECPay_PrintReceipt(true, true);
	}
        return "1|OK";
    }
    return "Fail";
    
}

function ECPay_ProcessPaymentCreated(){
    if (isset($_POST['CheckMacValue']) && $_POST['CheckMacValue'] == ECPay_GetMacValue($_POST)){
        MYSQL_AddPaymentResultServer($_POST['PaymentType'], $_POST['TradeAmt'], $_POST['RtnMsg'], json_encode($_POST, JSON_UNESCAPED_UNICODE), $_POST['TradeNo']);
        ECPay_PrintOrder(true);
        return "1|OK";
    }
    return "Fail";
}

function ECPay_SubmitForm($data){
	if ($data['TotalAmount'] <= 0){
		MYSQL_AddPaymentResultServer("免付款", $data['TotalAmount'], "交易成功", "{\"Type\":\"免付款\"}", $data['MerchantTradeNo']);
		$price = MYSQL_GetOrderInfo($data['MerchantTradeNo']);
		Coupon_Used($data['promoCode']);
		$formHTML = "\t<button class=\"btn btn-success btn-lg btn-block\" type=\"submit\" disabled>免付款交易!</button>\n";
        return $formHTML;
	}
	
    global $ECPay_PaymentEndPoint;
    $formHTML = "<form method='post' action='$ECPay_PaymentEndPoint'>\n";
    foreach($data as $key => $value){
        $formHTML .= "\t<input type=\"hidden\" name=\"".$key."\" value=\"".$value."\" />\n";
    }
    $formHTML .= "\t<button class=\"btn btn-success btn-lg btn-block\" type=\"submit\">前往付款</button>\n";
    $formHTML .= "</form>";
    return $formHTML;
}

function API_ToJSON($success=false, $result=array("msg"=>"Invalid API Call")){
    $output = array(
        "success" => $success,
        "result" => $result,
        "dataCount" => count($result)
    );
    return json_encode($output, JSON_UNESCAPED_UNICODE);
}

function API_formItem($Name, $Description, $Price){
    return array(
        "Name" => $Name,
        "Description" => $Description,
        "Price" => $Price
    );
}

function API_getFeeItem($PaymentMethod, $originalPrice){
    global $Fee_List;
    if (empty($Fee_List[$PaymentMethod])){
        return array("msg"=>"\$PaymentMethod: $PaymentMethod not found");
    }
    if ($originalPrice == 0){
        $newFee = 0;
    }
    else{
        switch($Fee_List[$PaymentMethod]['Type']){
            case "%":
                $newFee = round($Fee_List[$PaymentMethod]['Val']*0.01*$originalPrice);
                break;
            case "$":
                
                $newFee = $Fee_List[$PaymentMethod]['Val'];
                break;
            default:
                $newFee = 0;
                break;
        }
    }
    return API_formItem(
        "手續費:".$Fee_List[$PaymentMethod]['Name'],
        $Fee_List[$PaymentMethod]['Description'],
        $newFee
    );
}

function API_getDiscountItem($discountCode, $originalPrice){
    global $Discount_List;
    if (empty($Discount_List[$discountCode])){
        //return array("msg"=>"\$discountCode: $discountCode not found", "Price"=>0);
        return API_formItem(
            "折扣:".$discountCode,
            "無效的優惠代碼",
            0
        );
    }
    
    if ($originalPrice == 0){
        $newDiscount = 0;
    }
    else{
        switch($Discount_List[$discountCode]['Type']){
            case "%":
                $newDiscount = min(round($Discount_List[$discountCode]['Val']*0.01*$originalPrice), $originalPrice)*-1;
                break;
            case "$":
            
                $newDiscount = min($Discount_List[$discountCode]['Val'], $originalPrice)*-1;
                break;
            default:
                $newDiscount = 0;
                break;
        }
    }
    return API_formItem(
        "折扣:".$Discount_List[$discountCode]['Name'],
        $Discount_List[$discountCode]['Description'],
        $newDiscount
    );
}
function API_getPackage($packageCode, $PaymentMethod){
    global $Package_List, $Ticket_List, $Discount_List, $Fee_List;
    
    $Coupon = Coupon_check($packageCode);
    if ($Coupon !== false){
        $Package_List[$Coupon['CouponCode']] = array(
            "Ticket" => "default",
            "Fee" => $PaymentMethod,
            "Discount" => $Coupon['CouponCode']
        );
        $Discount_List[$Coupon['CouponCode']] = $Coupon;
    }
    if (empty($Package_List[$packageCode])){
        return array(
            API_formItem(
                "無效的優惠代碼",
                "如果願意使用此代碼，我們也願意以這個價格賣給您!",
                100000
            )
        );
    }
    if (empty($Package_List[$packageCode]['Discount'])){
        return array(
            $Ticket_List[$Package_List[$packageCode]['Ticket']],
            API_getFeeItem($PaymentMethod, $Ticket_List[$Package_List[$packageCode]['Ticket']]['Price'])
        );
    }
    else{
        $discountItem = API_getDiscountItem($Package_List[$packageCode]['Discount'], $Ticket_List[$Package_List[$packageCode]['Ticket']]['Price']);
        
        return array(
            $Ticket_List[$Package_List[$packageCode]['Ticket']],
            $discountItem,
            API_getFeeItem($PaymentMethod, $Ticket_List[$Package_List[$packageCode]['Ticket']]['Price']+$discountItem['Price'])
        );
    }
}

function API_getOrderItems($PaymentMethod="Credit", $productCode="default"){
    header('Content-Type: application/json');
    if ($productCode == ""){
        $productCode = "default";
    }
    $result = API_getPackage($productCode, $PaymentMethod);
    if (isset($result['msg'])){
        return API_ToJSON(false, $result);
    }
    else{
        return API_ToJSON(true, $result);
    }
}

function API_getOrderSum($data){
    $sum = 0;
    foreach($data as $value){
        $sum += $value['Price'];
    }
    return $sum;
}

function API_EmailOrder($OrderId, $promoCode=""){
    if ($promoCode == "default"){
        $promoCode = "";
    }
    // Send out confirmation email
    $output = "
        姓名: ".$_POST['Name']."<br>
        訂單編號: ".$OrderId."<br>
        付款連結: <a href=\"https://event.worldwidetsa.org/?order=".$OrderId."&promo=".$promoCode."\">https://event.worldwidetsa.org/?order=".$OrderId."</a><br>
        <br>
        感謝您對世學聯的支持，我們12/27見。<br>
        <br>
        世學聯<br>
    ";
    SMTP_Sender(
        $_POST['Name']." (".$_POST['EnglishName'].")", 
        $_POST['Email'],
        "世學聯 - 訂單成立",
        "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">".$output
    );
}

function API_processOrder($PaymentMethod="Credit", $promoCode="default"){
    global $EVENT_ID;
    if (empty($_POST['Name']) || empty($_POST['EnglishName']) || empty($_POST['Phone']) || empty($_POST['Email']) || empty($_POST['PersonalId']) || empty($_POST['DOB']) || empty($_POST['Gender']) || 
        empty($_POST['EmerContactName']) || empty($_POST['EmerContactNum']) || empty($_POST['School']) || empty($_POST['TSAOfficerRole']) || empty($_POST['RepresentTSA'])){
        return API_ToJSON(false, array("msg" => "Missing field"));
    }
    
    if ($_POST['Gender'] == "NULL"){
        $_POST['Gender'] = "null";
    }
    else if ($_POST['Gender'] != "Male" && $_POST['Gender'] != "Female"){
        return API_ToJSON(false, array("msg" => "Invalid gender"));
    }
    else{
        $_POST['Gender'] = "'".$_POST['Gender']."'";
    }
    
    $ContactId = MYSQL_AddContactInfo($_POST['Name'], $_POST['EnglishName'], $_POST['Phone'], $_POST['Email'], $_POST['PersonalId'], $_POST['DOB'], $_POST['Gender'], 
        $_POST['EmerContactName'], $_POST['EmerContactNum'], $_POST['School'], $_POST['TSAOfficerRole'], $_POST['RepresentTSA']
    );
    if (!$ContactId){
        return API_ToJSON(false, array("msg" => "Error while insert ContactInfo"));
    }
    
    $result = API_getPackage($promoCode, $PaymentMethod);
    $sum = API_getOrderSum($result);
    $data = ECPay_NewOrder($result[0]['Name'], $result[0]['Description'], $sum, $PaymentMethod);
    if (isset($result[0]['Price']) && isset($result[1]['Price']) && $result[0]['Price'] + $result[1]['Price'] == 0){
        $orderResult = MYSQL_AddOrder($data['MerchantTradeNo'], $ContactId, $EVENT_ID, $result[0]['Price']+$result[1]['Price'], 0, $data, $promoCode);
    }
    else if (isset($result[0]['Price']) && isset($result[1]['Price']) && $result[0]['Price'] + $result[1]['Price'] == $sum){
        $orderResult = MYSQL_AddOrder($data['MerchantTradeNo'], $ContactId, $EVENT_ID, $result[0]['Price'], $result[1]['Price'], $data);
    }
    else if (isset($result[0]['Price']) && isset($result[1]['Price']) && isset($result[2]['Price']) && $result[0]['Price'] + $result[1]['Price'] + $result[2]['Price'] == $sum){
        $orderResult = MYSQL_AddOrder($data['MerchantTradeNo'], $ContactId, $EVENT_ID, $result[0]['Price']+$result[1]['Price'], $result[2]['Price'], $data, $promoCode);
    }
    else{
        $orderResult = MYSQL_AddOrder($data['MerchantTradeNo'], $ContactId, $EVENT_ID, $sum, 0, $data, $promoCode);
    }
    
    if ($orderResult === false){
        return API_ToJSON(false, array("msg" => "Error while insert Order"));
    }
    
	$formHTML = ECPay_SubmitForm($data);
	
    MYSQL_AddPayment($data['MerchantTradeNo'], $formHTML);
    
    API_EmailOrder($data['MerchantTradeNo'], $promoCode);
    
    return API_ToJSON(true, array("html" => "$formHTML", "msg" => "Order '". $data['MerchantTradeNo'] ."' Created"));
}

function MYSQL_IsPaid($OrderId){
    $query = "
        SELECT COUNT(*) AS amount
        FROM `ContactInfo`
        JOIN `Order` ON `ContactInfo`.id = `Order`.ContactId
        JOIN `Payment` ON `Order`.id = `Payment`.OrderId
        JOIN `PaymentResultServer` ON `Payment`.OrderId = `PaymentResultServer`.OrderId
        WHERE `PaymentResultServer`.Status = '交易成功' AND (`Order`.id = \"$OrderId\" OR `Order`.OriginalId = \"$OrderId\")
    ";
    $result = MYSQL_getData($query);
    return isset($result[0]["amount"]) && $result[0]["amount"] != 0;
}

function API_getPaymentInfo($OriginalOrderId){
    if (MYSQL_IsPaid($OriginalOrderId)){
        $formHTML = "\t<button class=\"btn btn-success btn-lg btn-block\" type=\"submit\" disabled>已完成付款!</button>\n";
        return API_ToJSON(true, array("html" => "$formHTML", "msg" => "Order '". $OriginalOrderId ."' has already paid"));
    }
    
    $data = ECPay_PayOrder(json_decode(MYSQL_GetReOrderInfo($OriginalOrderId), true));
    $OrderInfo = MYSQL_GetOrderInfo($OriginalOrderId);
    $orderResult = MYSQL_ReOrder($data['MerchantTradeNo'], $OrderInfo['ContactId'], $OrderInfo['EventId'], $OrderInfo['Price'], $OrderInfo['Fee'], $data, $OriginalOrderId, $OrderInfo['PromoCode']);

    
    if ($orderResult === false){
        return API_ToJSON(false, array("msg" => "Error while insert Order"));
    }
    
    $formHTML = ECPay_SubmitForm($data);
    MYSQL_AddPayment($data['MerchantTradeNo'], $formHTML);
    
    
    return API_ToJSON(true, array("html" => "$formHTML", "msg" => "Order '". $data['MerchantTradeNo'] ."' Created"));
}

function Coupon_add($name, $description, $type, $value, $totalCount, $remark="", $CouponCode=null, $active=true){
    if ($CouponCode == null){
        $CouponCode = strtoupper(bin2hex(random_bytes(3)));
    }
    if ($type != "$" && $type != "%"){
        echo "Invalid Coupon Type\n";
        return false;
    }
    if ($value < 0){
        echo "Invalid Coupon Value";
        return false;
    }
    if (MYSQL_AddCoupon(strtoupper($CouponCode), $type, $value, $totalCount, $name, $description, $remark, $active) === false){
        return false;
    }
    return $CouponCode;
}

function Coupon_check($code){
    global $mysqli;
    $query = "
        SELECT `CouponCode`, `Name`, `Description`, `Type`, `Val` 
        FROM `Coupon` WHERE `CouponCode` = '$code' AND `UsedCount` < `TotalCount` AND `Active`
    ";
    
    $result = mysqli_query($mysqli, $query);
    if (mysqli_num_rows($result) > 0){
        return $result->fetch_assoc();
    }
    else{
        return false;
    }
}

function Coupon_Used($code){
    if ($code == ""){
        return false;
    }
    
    global $mysqli;
    $query = "
        UPDATE `Coupon` SET `UsedCount` = `UsedCount` + 1 WHERE `CouponCode` = '$code'
    ";
    
    if ($mysqli->query($query) === TRUE) {
        return true;
    }else{            
        echo "Query: ".$query."\n";
        echo "Error: " . $mysqli->error; // Fail
        return false;
    }
}

function Coupon_enable(){
    //TODO
}

function Coupon_disable(){
    //TODO
}

function SMTP_Sender($Name, $Email, $Subject, $Content){
    global $SMTP_Name, $SMTP_Account, $SMTP_Password, $SMTP_Server, $SMTP_Disabled;
    if (isset($SMTP_Disabled) && $SMTP_Disabled){
        return;
    }
    
    //Create a new PHPMailer instance
    $mail = new PHPMailer;
    
    $mail->isSMTP();
    //Enable SMTP debugging
    // 0: SMTP::DEBUG_OFF = off (for production use)
    // 1: SMTP::DEBUG_CLIENT = client messages
    // 2: SMTP::DEBUG_SERVER = client and server messages
    $mail->SMTPDebug = 0;
    //Set the hostname of the mail server
    $mail->Host = $SMTP_Server;
    //Set the SMTP port number - likely to be 25, 465 or 587
    $mail->Port = 587;
    //Whether to use SMTP authentication
    $mail->SMTPAuth = true;
    //Username to use for SMTP authentication
    $mail->Username = $SMTP_Account;
    //Password to use for SMTP authentication
    $mail->Password = $SMTP_Password;
    
    //Set who the message is to be sent from
    $mail->setFrom($SMTP_Account, $SMTP_Name);
    //Set who the message is to be sent to
    $mail->addAddress($Email, $Name);
    //Set the subject line
    $mail->Subject = $Subject;
    //Read an HTML message body from an external file, convert referenced images to embedded,
    //convert HTML into a basic plain-text alternative body
    //$mail->msgHTML(file_get_contents('contents.html'), __DIR__);
    $mail->msgHTML($Content);
    //Replace the plain text body with one created manually
    //$mail->AltBody = 'This is a plain-text message body';
    
    $mail->CharSet = 'UTF-8';
    
    //Attach an image file
    //$mail->addAttachment('images/phpmailer_mini.png');
    //send the message, check for errors
    if (!$mail->send()) {
        echo 'Mailer Error: '. $mail->ErrorInfo;
    } else {
        //echo 'Message sent!';
    }
}

function MYSQL_getData($query){
    global $mysqli;
    $result = mysqli_query($mysqli, $query);
    
    $output = array();
    if ($result !== false && mysqli_num_rows($result) > 0){
        while($output[] = $result->fetch_assoc()){
            
        }
        return $output;
    }
    else{
        echo "Query: ".$query."\n";
        echo "Error: " . $mysqli->error; // Fail
        return false;
    }
}

function MYSQL_Insert($query){
    global $mysqli;
    if ($mysqli->query($query) === TRUE) {
        return $mysqli->insert_id;
    }else{            
        echo "Query: ".$query."\n";
        echo "Error: " . $mysqli->error; // Fail
        return 0;
    }
}

function MYSQL_GetContactInfoByOrder($OrderId){
    global $mysqli;
    $query = "
        SELECT `ContactInfo`.*, `Order`.PromoCode
        FROM `ContactInfo` 
        JOIN `Order` ON `ContactInfo`.id = `Order`.ContactId 
        WHERE `Order`.id = '$OrderId'
    ";
    $result = mysqli_query($mysqli, $query);
    if (mysqli_num_rows($result) > 0){
        return $result->fetch_assoc();
    }
    else{
        if (isset($debug) && $debug == true){
            echo "Query: ".$query."\n";
	    echo "Error: " . $mysqli->error; // Fail
        }
        return false;
    }
}

function MYSQL_GetReOrderInfo($OrderId){
    global $mysqli;
    $query = "
        SELECT data
        FROM `Order`
        WHERE `Order`.id = '$OrderId'
    ";
    $result = mysqli_query($mysqli, $query);
    if (mysqli_num_rows($result) > 0){
        $OrderInfo = $result->fetch_assoc();
        $data = $OrderInfo['data'];
        return $data;
    }
    else{
        echo "Query: ".$query."\n";
        echo "Error: " . $mysqli->error; // Fail
        return false;
    }
}

function MYSQL_GetOrderInfo($OrderId){
    global $mysqli;
    $query = "
        SELECT *
        FROM `Order`
        WHERE `Order`.id = '$OrderId'
    ";
    $result = mysqli_query($mysqli, $query);
    if (mysqli_num_rows($result) > 0){
        $OrderInfo = $result->fetch_assoc();
        return $OrderInfo;
    }
    else{
        echo "Query: ".$query."\n";
        echo "Error: " . $mysqli->error; // Fail
        return false;
    }
}

function MYSQL_AddOrder($id, $ContactId, $EventId, $Price, $Fee, $data, $PromoCode=null){
    $query = "
        INSERT INTO `Order` (id, ContactId, EventId, Price, Fee, data, PromoCode)
        VALUES ('$id', '$ContactId', '$EventId', '$Price', '$Fee', '".json_encode($data, JSON_UNESCAPED_UNICODE)."', '$PromoCode');
    ";
    return MYSQL_Insert($query);
    /*
        INSERT INTO table (name)
        OUTPUT Inserted.ID
        VALUES('');
        
        id VARCHAR(25) PRIMARY KEY,
        ContactId INT(6),
        EventId INT(6),
        Price INT(6),
        Fee INT(6),
        PromoCode VARCHAR(50) NOT NULL,
        data JSON,
        OriginalId VARCHAR(25),
        CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    */
}

function MYSQL_ChangeOrderFee($OrderId, $Fee){
    $query = "
        UPDATE `Order` SET `Fee` = $Fee WHERE `id` = '$OrderId'
    ";
    return MYSQL_Insert($query);
    /*
        INSERT INTO table (name)
        OUTPUT Inserted.ID
        VALUES('');
        
        id VARCHAR(25) PRIMARY KEY,
        ContactId INT(6),
        EventId INT(6),
        Price INT(6),
        Fee INT(6),
        PromoCode VARCHAR(50) NOT NULL,
        data JSON,
        OriginalId VARCHAR(25),
        CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    */
}

function MYSQL_ReOrder($id, $ContactId, $EventId, $Price, $Fee, $data, $OriginalId, $PromoCode=null){
    $query = "
        INSERT INTO `Order` (id, ContactId, EventId, Price, Fee, data, OriginalId, PromoCode)
        VALUES ('$id', '$ContactId', '$EventId', '$Price', '$Fee', 
        '".json_encode($data, JSON_UNESCAPED_UNICODE)."', 
        '$OriginalId', '$PromoCode');
    ";
    return MYSQL_Insert($query);
    /*
        INSERT INTO table (name)
        OUTPUT Inserted.ID
        VALUES('');
        
        id VARCHAR(25) PRIMARY KEY,
        ContactId INT(6),
        EventId INT(6),
        Price INT(6),
        Fee INT(6),
        PromoCode VARCHAR(50) NOT NULL,
        data JSON,
        OriginalId VARCHAR(25),
        CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    */
}

function MYSQL_AddEvent($EventName, $EventDate){
    
    /*TODO
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        EventName VARCHAR(200) NOT NULL,
        EventDate DATE
    */
}

function MYSQL_AddContactInfo($Name, $EnglishName, $Phone, $Email, $PersonalId, $DOB, $Gender, $EmerContactName, $EmerContactNum, $School, $TSAOfficerRole, $RepresentTSA){
    $query = "
        INSERT INTO `ContactInfo` (Name, EnglishName, Phone, Email, PersonalId, DOB, Gender, EmerContactName, EmerContactNum, School, TSAOfficerRole, RepresentTSA)
        VALUES ('$Name', '$EnglishName', '$Phone', '$Email', '$PersonalId', '$DOB', $Gender, '$EmerContactName', '$EmerContactNum', '$School', '$TSAOfficerRole', '$RepresentTSA');
    ";
    return MYSQL_Insert($query);
    /*
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        Name VARCHAR(200) NOT NULL,
        EnglishName VARCHAR(200) NOT NULL,
        Phone VARCHAR(20) NOT NULL,
        Email VARCHAR(200) NOT NULL,
        PersonalId VARCHAR(20) NOT NULL,
        DOB DATE,
        EmerContactName VARCHAR(200) NOT NULL,
        EmerContactNum VARCHAR(20) NOT NULL,
        School VARCHAR(200) NOT NULL,
        TSAOfficerRole VARCHAR(20) NOT NULL,
        RepresentTSA BOOL
    */
}

function MYSQL_AddPayment($OrderId, $html){
    $html = urlencode($html);
    $query = "
        INSERT INTO `Payment` (OrderId, html)
        VALUES ('$OrderId', '$html');
    ";
    return MYSQL_Insert($query);
    /*
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        PaymentMethod VARCHAR(10) NOT NULL,
        TotalAmount INT(6),
        Status INT(6),
        Data JSON,
        OrderId VARCHAR(25)
    */
}

function MYSQL_AddPaymentResultClient($PaymentMethod, $TotalAmount, $Status, $Data, $OrderId){
    $query = "
        INSERT INTO `PaymentResultClient` (PaymentMethod, TotalAmount, Status, Data, OrderId)
        VALUES ('$PaymentMethod', '$TotalAmount', '$Status', '$Data', '$OrderId');
    ";
    return MYSQL_Insert($query);
    /*
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        PaymentMethod VARCHAR(10) NOT NULL,
        TotalAmount INT(6),
        Status INT(6),
        Data JSON,
        OrderId VARCHAR(25)
    */
}

function MYSQL_AddPaymentResultServer($PaymentMethod, $TotalAmount, $Status, $Data, $OrderId){
    $query = "
        INSERT INTO `PaymentResultServer` (PaymentMethod, TotalAmount, Status, Data, OrderId)
        VALUES ('$PaymentMethod', '$TotalAmount', '$Status', '$Data', '$OrderId');
    ";
    return MYSQL_Insert($query);
    /*
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        PaymentMethod VARCHAR(10) NOT NULL,
        TotalAmount INT(6),
        Status INT(6),
        Data JSON,
        OrderId VARCHAR(25)
    */
}

function MYSQL_AddCoupon($CouponCode, $Type, $Val, $TotalCount, $Name, $Description, $Remark, $Active=false){
    $query = "
        INSERT INTO `Coupon` (CouponCode, Type, Val, TotalCount, Name, Description, Remark, Active)
        VALUES ('$CouponCode', '$Type', $Val, $TotalCount, '$Name', '$Description', '$Remark', $Active);
    ";
    return MYSQL_Insert($query);
    /*
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        CouponCode VARCHAR(200) NOT NULL,
        Type CHAR(1) NOT NULL,
        Val INT(6) NOT NULL,
        UsedCount INT(6) DEFAULT 0,
        TotalCount INT(6),
        Remark VARCHAR(200) NOT NULL,
        Active BOOL NOT NULL DEFAULT false,
        IssueDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    */
}

function MYSQL_Init(){
    global $mysqli;
    if (empty($mysqli)){
        echo "Error with MySQL during Init process";
        exit();
    }
    $query = "CREATE TABLE `ContactInfo` (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        Name VARCHAR(200) NOT NULL,
        EnglishName VARCHAR(200) NOT NULL,
        Phone VARCHAR(20) NOT NULL,
        Email VARCHAR(200) NOT NULL,
        PersonalId VARCHAR(20) NOT NULL,
        DOB DATE,
        Gender ENUM ('Male','Female'),
        EmerContactName VARCHAR(200) NOT NULL,
        EmerContactNum VARCHAR(20) NOT NULL,
        School VARCHAR(200) NOT NULL,
        TSAOfficerRole VARCHAR(20) NOT NULL,
        RepresentTSA BOOL,
        CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if ($mysqli->query($query) === TRUE) {
        echo "DB: ContactInfo init success\n"; // Success
    }else{
        echo "Error: " . $mysqli->error; // Fail
        return false;
    }
    
    $query = "CREATE TABLE `Event` (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        EventName VARCHAR(200) NOT NULL,
        EventDate DATE,
        CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if ($mysqli->query($query) === TRUE) {
        echo "DB: Event init success\n"; // Success
    }else{
        echo "Error: " . $mysqli->error; // Fail
        return false;
    }
    
    $query = "CREATE TABLE `Order` (
        id VARCHAR(25) PRIMARY KEY,
        ContactId INT(6),
        EventId INT(6),
        Price INT(6),
        Fee INT(6),
        PromoCode VARCHAR(50) NOT NULL,
        data JSON,
        OriginalId VARCHAR(25),
        CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if ($mysqli->query($query) === TRUE) {
        echo "DB: Order init success\n"; // Success
    }else{
        echo "Error: " . $mysqli->error; // Fail
        return false;
    }
    
    $query = "CREATE TABLE `Payment` (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        html TEXT,
        OrderId VARCHAR(25),
        CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if ($mysqli->query($query) === TRUE) {
        echo "DB: Payment init success\n"; // Success
    }else{
        echo "Error: " . $mysqli->error; // Fail
        return false;
    }
    
    $query = "CREATE TABLE `PaymentResultClient` (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        PaymentMethod VARCHAR(20) NOT NULL,
        TotalAmount INT(6),
        Status VARCHAR(200),
        Data JSON,
        OrderId VARCHAR(25),
        CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if ($mysqli->query($query) === TRUE) {
        echo "DB: PaymentResultClient init success\n"; // Success
    }else{
        echo "Error: " . $mysqli->error; // Fail
        return false;
    }
    
    $query = "CREATE TABLE `PaymentResultServer` (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        PaymentMethod VARCHAR(20) NOT NULL,
        TotalAmount INT(6),
        Status VARCHAR(200),
        Data JSON,
        OrderId VARCHAR(25),
        CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if ($mysqli->query($query) === TRUE) {
        echo "DB: PaymentResultServer init success\n"; // Success
    }else{
        echo "Error: " . $mysqli->error; // Fail
        return false;
    }
    
    $query = "CREATE TABLE `Ticket` (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ContactId INT(6),
        EventId INT(6),
        OrderId VARCHAR(25),
        PaymentId INT(6),
        IssueDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if ($mysqli->query($query) === TRUE) {
        echo "DB: Ticket init success\n"; // Success
    }else{
        echo "Error: " . $mysqli->error; // Fail
        return false;
    }
    
    $query = "CREATE TABLE `Coupon` (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        CouponCode VARCHAR(200) NOT NULL,
        Type CHAR(1) NOT NULL,
        Val INT(6) NOT NULL,
        UsedCount INT(6) DEFAULT 0,
        TotalCount INT(6) NOT NULL,
        Name VARCHAR(200) NOT NULL,
        Description VARCHAR(200) NOT NULL,
        Remark TEXT NOT NULL,
        Active BOOL NOT NULL DEFAULT false,
        IssueDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if ($mysqli->query($query) === TRUE) {
        echo "DB: Coupon init success\n"; // Success
    }else{
        echo "Error: " . $mysqli->error; // Fail
        return false;
    }
    return true;
}
?>
