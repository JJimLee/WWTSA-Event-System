<?php
if (empty($includeFunction) || !$includeFunction){
    exit();
}
include_once("config.php");


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

function API_processOrder($productCode="default", $paymentMethod="Credit"){
    $result = API_getPackage($productCode, $paymentMethod);
    $sum = API_getOrderSum($result);
    $data = ECPay_NewOrder($result[0]['Name'], $result[0]['Description'], $sum, $paymentMethod);
    ECPay_SubmitForm($data);
}
?>