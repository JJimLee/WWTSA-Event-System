<?php
$includeFunction = true;
include_once("function.php");

if (empty($_POST['action'])){
    $API_ToJSON();
    exit();
}

switch($_POST['action']){
    case "getOrderItems":
        if(isset($_POST['PaymentMethod']) && isset($_POST['PromoCode']) && $_POST['PromoCode'] != ""){
            echo API_getOrderItems($_POST['PaymentMethod'], $_POST['PromoCode']);
        }
        else if(isset($_POST['PaymentMethod'])){
            echo API_getOrderItems($_POST['PaymentMethod']);
        }
        else{
            echo API_getOrderItems();
        }
        break;
    case "createOrder":
        // process data(Record to database)
        if(isset($_POST['PaymentMethod']) && isset($_POST['PromoCode']) && $_POST['PromoCode'] != ""){
            echo API_processOrder($_POST['PaymentMethod'], $_POST['PromoCode']);
        }
        else if (isset($_POST['PaymentMethod'])){
            echo API_processOrder($_POST['PaymentMethod']);
        }
        // Send out confirmation email
        
        // Redirect to paymentLink generator
        
        
        break;
    case "getPaymentInfo":
        // process data(Record to database)
        if(isset($_POST['orderId'])){
            echo API_getPaymentInfo($_POST['orderId']);
        }
        break;
    case "GeneratePaymentLink":
        // Generate payment link
        // Record to database
        // POST Redirect
        break;
    default:
        $API_ToJSON();
        exit();
        break;
}

?>
