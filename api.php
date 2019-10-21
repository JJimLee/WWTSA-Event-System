<?php
$includeFunction = true;
include_once("function.php");

if (empty($_POST['action'])){
    $API_ToJSON();
    exit();
}

switch($_POST['action']){
    case "getOrderItems":
        if(isset($_POST['paymentMethod']) && isset($_POST['promo'])){
            echo API_getOrderItems($_POST['promo'], $_POST['paymentMethod']);
        }
        else if(isset($_POST['promo'])){
            echo API_getOrderItems($_POST['promo']);
        }
        else{
            echo API_getOrderItems();
        }
        break;
    default:
        $API_ToJSON();
        exit();
        break;
}

?>