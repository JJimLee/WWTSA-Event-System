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


function ECPay_GetMacValue($arParameters) {
    function merchantSort($a, $b){
        return strcasecmp($a, $b);
    }
    global $ECPay_HashKey, $ECPay_HashIV;
    $sMacValue = '' ;
    if(isset($arParameters))
    {
        // arParameters 為傳出的參數，並且做字母 A-Z 排序
        unset($arParameters['CheckMacValue']);
        //uksort($arParameters, array('ECPay_CheckMacValue','merchantSort'));
        ksort($arParameters, SORT_STRING);
        
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
    global $ECPay_MerchantID, $Clark_ReturnURL, $Clark_ClientBackURL;
    
    switch($PaymentMethod){
        case "CVS":
            $ECPay_ChoosePayment = "WebATM#ATM#Credit#BARCODE#GooglePay";
            break;
        case "BARCODE":
            $ECPay_ChoosePayment = "WebATM#ATM#CVS#Credit#GooglePay";
            break;
        case "Credit":
        default:
            $ECPay_ChoosePayment = "WebATM#ATM#CVS#BARCODE#GooglePay";
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
        "ChoosePayment" => "Credit",
        "CheckMacValue" => "", // Need to work
        "ClientBackURL" => $Clark_ClientBackURL,
        "EncryptType" => "1",
        "NeedExtraPaidInfo" => "Y",
        "IgnorePayment" => "WebATM#ATM#CVS#BARCODE#GooglePay"
    );
    $data['CheckMacValue'] = ECPay_GetMacValue($data);
    return $data;
}

function ECPay_SubmitForm($data){
    global $ECPay_PaymentEndPoint;
    echo "<form method='post' action='$ECPay_PaymentEndPoint'>\n";
    foreach($data as $key => $value){
        echo "\t<input type=\"hidden\" name=\"".$key."\" value=\"".$value."\" />\n";
    }
    echo "\t<input type=\"submit\" />\n";
    echo "</form>";
}

function API_ToJSON($success=false, $result=array("msg"=>"Invalid API Call")){
    $output = array(
        "success" => $success,
        "result" => $result,
        "dataCount" => count($result)
    );
    return json_encode($output);
}

function API_formItem($Name, $Description, $Price){
    return array(
        "Name" => $Name,
        "Description" => $Description,
        "Price" => $Price
    );
}

function API_getFeeItem($paymentMethod, $originalPrice){
    global $Fee_List;
    if (empty($Fee_List[$paymentMethod])){
        return array("msg"=>"\$paymentMethod: $paymentMethod not found");
    }
    if ($originalPrice == 0){
        $newFee = 0;
    }
    else{
        switch($Fee_List[$paymentMethod]['Type']){
            case "%":
                $newFee = round($Fee_List[$paymentMethod]['Value']*0.01*$originalPrice);
                break;
            case "$":
                
                $newFee = $Fee_List[$paymentMethod]['Value'];
                break;
            default:
                $newFee = 0;
                break;
        }
    }
    return API_formItem(
        "手續費:".$Fee_List[$paymentMethod]['Name'],
        $Fee_List[$paymentMethod]['Description'],
        $newFee
    );
}

function API_getDiscountItem($discountCode, $originalPrice){
    global $Discount_List;
    if (empty($Discount_List[$discountCode])){
        return array("msg"=>"\$discountCode: $discountCode not found");
    }
    if ($originalPrice == 0){
        $newDiscount = 0;
    }
    else{
        switch($Discount_List[$discountCode]['Type']){
            case "%":
                $newDiscount = min(round($Discount_List[$discountCode]['Value']*0.01*$originalPrice), $originalPrice)*-1;
                break;
            case "$":
            
                $newDiscount = min($Discount_List[$discountCode]['Value'], $originalPrice)*-1;
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
function API_getPackage($packageCode, $paymentMethod){
    global $Package_List, $Ticket_List, $Fee_List;
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
            API_getFeeItem($paymentMethod, $Ticket_List[$Package_List[$packageCode]['Ticket']]['Price'])
        );
    }
    else{
        $discountItem = API_getDiscountItem($Package_List[$packageCode]['Discount'], $Ticket_List[$Package_List[$packageCode]['Ticket']]['Price']);
        
        return array(
            $Ticket_List[$Package_List[$packageCode]['Ticket']],
            $discountItem,
            API_getFeeItem($paymentMethod, $Ticket_List[$Package_List[$packageCode]['Ticket']]['Price']+$discountItem['Price'])
        );
    }
}

function API_getOrderItems($productCode="default", $paymentMethod="Credit"){
    header('Content-Type: application/json');
    if ($productCode == ""){
        $productCode = "default";
    }
    $result = API_getPackage($productCode, $paymentMethod);
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

function API_processOrder($promoCode="default", $paymentMethod="Credit"){
    global $EVENT_ID;
    if (empty($_POST['Name']) || empty($_POST['EnglishName']) || empty($_POST['Phone']) || empty($_POST['Email']) || empty($_POST['PersonalId']) || empty($_POST['DOB']) || 
        empty($_POST['EmerContactName']) || empty($_POST['EmerContactNum']) || empty($_POST['School']) || empty($_POST['TSAOfficerRole']) || empty($_POST['RepresentTSA'])){
        return API_ToJSON(false, array("msg" => "Missing field"));
    }
    $ContactId = MYSQL_AddContactInfo($_POST['Name'], $_POST['EnglishName'], $_POST['Phone'], $_POST['Email'], $_POST['PersonalId'], $_POST['DOB'], 
        $_POST['EmerContactName'], $_POST['EmerContactNum'], $_POST['School'], $_POST['TSAOfficerRole'], $_POST['RepresentTSA']
    );
    if (!$ContactId){
        return API_ToJSON(false, array("msg" => "Error while insert ContactInfo"));
    }
    
    $result = API_getPackage($promoCode, $paymentMethod);
    $sum = API_getOrderSum($result);
    $data = ECPay_NewOrder($result[0]['Name'], $result[0]['Description'], $sum, $paymentMethod);
    if ($result[0]['Price'] + $result[1]['Price'] == $sum){
        $orderId = MYSQL_AddOrder($data['MerchantTradeNo'], $result[0]['Price'], $result[1]['Price']);
    }
    else{
        $orderId = MYSQL_AddOrder($data['MerchantTradeNo'], $ContactId, $EVENT_ID, $sum, 0, $promoCode);
    }
    
    if (!$orderId){
        return API_ToJSON(false, array("msg" => "Error while insert Order"));
    }
    ECPay_SubmitForm($data);
    return API_ToJSON(false, array("msg" => "Order '"+ $data['MerchantTradeNo'] +"' Created"));
}


function SMTP_Sender($Name, $Email, $Subject, $Content){
    global $SMTP_Name, $SMTP_Account, $SMTP_Password, $SMTP_Server;
    //Create a new PHPMailer instance
    $mail = new PHPMailer;
    
    $mail->isSMTP();
    //Enable SMTP debugging
    // SMTP::DEBUG_OFF = off (for production use)
    // SMTP::DEBUG_CLIENT = client messages
    // SMTP::DEBUG_SERVER = client and server messages
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
        echo 'Message sent!';
    }
}

function MYSQL_Run($query){
    if ($mysqli->query($query) === TRUE) {
        return $mysqli->insert_id;
    }else{
        echo "Error: " . $mysqli->error; // Fail
        return 0;
    }
}

function MYSQL_AddOrder($id, $ContactId, $EventId, $Price, $Fee, $PromoCode){
    $query = "
        INSERT INTO Order (id, ContactId, EventId, Price, Fee, PromoCode)
        OUTPUT Inserted.ID
        VALUES ('$id', '$ContactId', '$EventId', '$Price', '$Fee', '$PromoCode');
    ";
    $query = "
        INSERT INTO `ContactInfo` (Name, EnglishName, Phone, Email, PersonalId, DOB, EmerContactName, EmerContactNum, School, TSAOfficerRole, RepresentTSA)
        VALUES ('$Name', '$EnglishName', '$Phone', '$Email', '$PersonalId', '$DOB', '$EmerContactName', '$EmerContactNum', '$School', '$TSAOfficerRole', '$RepresentTSA');
    ";
    return MYSQL_Run($query);
    /*
        INSERT INTO table (name)
        OUTPUT Inserted.ID
        VALUES('');
        
        ContactId INT(6),
        EventId INT(6),
        Price INT(6),
        Fee INT(6),
        PromoCode VARCHAR(50) NOT NULL
    */
}

function MYSQL_AddEvent($EventName, $EventDate){
    
    /*
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        EventName VARCHAR(200) NOT NULL,
        EventDate DATE
    */
}

function MYSQL_AddContactInfo($Name, $EnglishName, $Phone, $Email, $PersonalId, $DOB, $EmerContactName, $EmerContactNum, $School, $TSAOfficerRole, $RepresentTSA){
    $query = "
        INSERT INTO `ContactInfo` (Name, EnglishName, Phone, Email, PersonalId, DOB, EmerContactName, EmerContactNum, School, TSAOfficerRole, RepresentTSA)
        VALUES ('$Name', '$EnglishName', '$Phone', '$Email', '$PersonalId', '$DOB', '$EmerContactName', '$EmerContactNum', '$School', '$TSAOfficerRole', '$RepresentTSA');
    ";
    return MYSQL_Run($query);
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
        EmerContactName VARCHAR(200) NOT NULL,
        EmerContactNum VARCHAR(20) NOT NULL,
        School VARCHAR(200) NOT NULL,
        TSAOfficerRole VARCHAR(20) NOT NULL,
        RepresentTSA BOOL
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
        EventDate DATE
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
        PromoCode VARCHAR(50) NOT NULL
    )";
    if ($mysqli->query($query) === TRUE) {
        echo "DB: Order init success\n"; // Success
    }else{
        echo "Error: " . $mysqli->error; // Fail
        return false;
    }
    
    $query = "CREATE TABLE `Payment` (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        PaymentMethod VARCHAR(10) NOT NULL,
        TotalAmount INT(6),
        Status INT(6),
        Data JSON,
        OrderId INT(6)
    )";
    if ($mysqli->query($query) === TRUE) {
        echo "DB: Payment init success\n"; // Success
    }else{
        echo "Error: " . $mysqli->error; // Fail
        return false;
    }
    
    $query = "CREATE TABLE `Ticket` (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ContactId INT(6),
        EventId INT(6),
        OrderId INT(6),
        PaymentId INT(6)
    )";
    if ($mysqli->query($query) === TRUE) {
        echo "DB: Ticket init success\n"; // Success
        return true;
    }else{
        echo "Error: " . $mysqli->error; // Fail
        return false;
    }
    
    $query = "CREATE TABLE `Coupon` (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        CouponCode VARCHAR(200) NOT NULL,
        UsedCount INT(6) DEFAULT 0,
        TotalCount INT(6),
        Remark VARCHAR(200) NOT NULL,
        IssueDate DATE DEFAULT GETDATE()
    )";
    if ($mysqli->query($query) === TRUE) {
        echo "DB: Coupon init success\n"; // Success
        return true;
    }else{
        echo "Error: " . $mysqli->error; // Fail
        return false;
    }
    
}
?>