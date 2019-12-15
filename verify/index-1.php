<?php
include_once("config.php");

function getQRHash($content){
    global $QR_Hash;
    return md5($QR_Hash.$content);
}

$url = "https://event.worldwidetsa.org/verify/";
?>
<html>
<head>
    <style>
    html, body {
      overflow-x: hidden;
    }
    body {
      position: relative;
    }
    img {
        display:block;
        margin-left: auto;
        margin-right: auto;
        width: 60%
    }
    .center {
        display:block;
        margin-left: auto;
        margin-right: auto;
        width: 50%
    }
    .fullwidth {
        display:block;
        margin-left: auto;
        margin-right: auto;
        width: 60%
    }
    </style>
    <title>世學聯門票系統</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="icon" href="https://www.worldwidetsa.org/wp-content/uploads/2019/08/cropped-WWTSA-LOGO-new-32x32.png" sizes="32x32">
</head>

<body>
    <img src='https://www.worldwidetsa.org/wp-content/uploads/2019/08/WWTSA-LOGO-new.png'>
    <div class='fullwidth'>
        活動: 世學聯-歲末倒流年終晚會<br>
        日期: 2019/12/27 19:00~21:30<br>
        地點: Fiesta Resturant & Live House (Taipei, Taiwan)<br>
    </div>
    <div class='fullwidth'>
        門票序號: XXX<br>
        姓名: XXX<br>
        生日: 1999/2/30<br>
        <br>
    </div>
    <img src='qr.php?content=<?=$url?>&hash=<?=getQRHash($url)?>'>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <script>
        function horiMode(on){
            if (on){
                console.log("isHori")
            }
            else{
                console.log("isVerti")
            }
        }
        $(document).ready(function () {
            horiMode(window.orientation == 90 || window.orientation == -90);
            $( window ).on( "orientationchange", function( event ) {
                horiMode(window.orientation == 90 || window.orientation == -90);
            });
        });
    </script>
</body>

</html>


