<?php
if (empty($includeFunction) || !$includeFunction){
    exit();
}

if (isset($MYSQL_Hostname) && isset($MYSQL_User) && isset($MYSQL_Password) && isset($MYSQL_Database)){
        $mysqli = new mysqli($MYSQL_Hostname, $MYSQL_User, $MYSQL_Password, $MYSQL_Database);
        /* check connection */
        if ($mysqli->connect_errno) {
                printf("Connect failed: %s\n", $mysqli->connect_error);
                exit();
        }

        mysqli_query($mysqli,"SET CHARACTER SET UTF8");
}
else{
        echo "<script>alert('無法取得資料庫連線設定檔');</script>";
        exit();
}

?>