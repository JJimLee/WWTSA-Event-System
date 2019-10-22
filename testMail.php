<?php
$includeFunction = true;
include_once("function.php");


SMTP_Sender("Clark Chen", "clark@clark-chen.com", "世學聯活動訂單-測試", 
"<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
姓名<br>
付款連結: <a href=\"https://event.worldwidetsa.org/\">https://event.worldwidetsa.org/</a><br>
<br>
感謝您對世學聯的支持，我們12/XX見。<br>
<br>
世學聯活動籌備委員會<br>
"
);

?>