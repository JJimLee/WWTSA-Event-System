<?php
include_once("config.php");

function getQRHash($content){
    global $QR_Hash;
    return md5($QR_Hash.$content);
}

$url = "https://event.worldwidetsa.org/verify/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" href="https://www.worldwidetsa.org/wp-content/uploads/2019/08/cropped-WWTSA-LOGO-new-32x32.png" sizes="32x32">
    <title>世學聯門票系統</title>
    <link rel="stylesheet" href="./style.css">
    <link href="https://fonts.googleapis.com/css?family=Noto+Sans+TC:100|Noto+Serif+TC:700&display=swap" rel="stylesheet">
</head>
<body>
    <div>
        <img src="Background.png" alt="Background" class="background">
        <header>歲末年終 年度晚會</header>
        <img src="qr.php?content=<?=$url?>&hash=<?=getQRHash($url)?>" alt="QR CODE" class="QR">
        <p>
            日期: 2019/12/27 19:00~21:30<br>
            地點: Fiesta Restaurant & Live House (Taipei, Taiwan)<br>
            門票序號: XXX<br>
            姓名: XXX<br>
            生日 : 1999/2/30</p>
        <img src="titleLogo.png" alt="LOGO" class="logo">
        <h1>Get Connection Make Evolution.</h1>
    </div>
</body>
</html>