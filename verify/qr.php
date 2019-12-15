<?php

include_once("config.php");

if (empty($_GET['content']) || empty($_GET['hash']) || $_GET['hash'] != md5($QR_Hash.$_GET['content'])){
    exit;
}

require_once 'vendor/autoload.php';

use Endroid\QrCode\QrCode;

$qrCode = new QrCode($_GET['content']);

header('Content-Type: '.$qrCode->getContentType());
echo $qrCode->writeString();


?>

