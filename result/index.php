<?php
$includeFunction = true;
include_once("../config.php");
include_once("../MYSQLConnect.php");

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

function displayAllDetail(){
    
    $query = "
        SELECT 
            #`ContactInfo`.id, 
            `ContactInfo`.Name, 
            `ContactInfo`.EnglishName, 
            `ContactInfo`.Phone, 
            `ContactInfo`.Email, 
            `ContactInfo`.PersonalId, 
            `ContactInfo`.DOB, 
            `ContactInfo`.Gender AS 性別, 
            `ContactInfo`.EmerContactName AS 緊急人, 
            `ContactInfo`.EmerContactNum AS 緊急電話, 
            `ContactInfo`.School, 
            `ContactInfo`.TSAOfficerRole AS TSA職稱, 
            `ContactInfo`.RepresentTSA AS 代表TSA, 
            `ContactInfo`.CreatedAt AS 下單時間, 
            `Order`.id AS 訂單號碼,
            `Order`.OriginalId AS 原始訂單號碼, 
            `Order`.Price, 
            `Order`.Fee, 
            `Order`.PromoCode, 
            `PaymentResultServer`.PaymentMethod, 
            `PaymentResultServer`.TotalAmount, 
            `PaymentResultServer`.Status AS 交易結果 
        FROM `ContactInfo`
        JOIN `Order` ON `ContactInfo`.id = `Order`.ContactId
        JOIN `Payment` ON `Order`.id = `Payment`.OrderId
        JOIN `PaymentResultServer` ON `Payment`.OrderId = `PaymentResultServer`.OrderId
    ";
    $result = MYSQL_getData($query);
    if ($result === false){
        return false;
    }
    
    echo "<table border='1'>";
    echo "<tr>";
    foreach($result[0] as $key => $value){
        echo "<th>$key</th>";
    }
    echo "</tr>";
    
    foreach($result as $row){
        if (!is_array($row)){
            continue;
        }
        echo "<tr>";
        foreach($row as $key => $value){
            echo "<td>$value</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    return true;
}

function displayAll(){
    
    $query = "
        SELECT 
            #`ContactInfo`.id, 
            `ContactInfo`.Name, 
            `ContactInfo`.EnglishName, 
            `ContactInfo`.Phone, 
            `ContactInfo`.Email, 
            #`ContactInfo`.PersonalId, 
            #`ContactInfo`.DOB, 
            #`ContactInfo`.Gender AS 性別, 
            #`ContactInfo`.EmerContactName AS 緊急人, 
            #`ContactInfo`.EmerContactNum AS 緊急電話, 
            `ContactInfo`.School, 
            `ContactInfo`.TSAOfficerRole AS TSA職稱, 
            `ContactInfo`.RepresentTSA AS 代表TSA, 
            `ContactInfo`.CreatedAt AS 下單時間, 
            #`Order`.id AS 訂單號碼,
            #`Order`.OriginalId AS 原始訂單號碼, 
            `Order`.Price, 
            `Order`.Fee, 
            `Order`.PromoCode, 
            `PaymentResultServer`.PaymentMethod, 
            `PaymentResultServer`.TotalAmount, 
            `PaymentResultServer`.Status AS 交易結果 
        FROM `ContactInfo`
        JOIN `Order` ON `ContactInfo`.id = `Order`.ContactId
        JOIN `Payment` ON `Order`.id = `Payment`.OrderId
        JOIN `PaymentResultServer` ON `Payment`.OrderId = `PaymentResultServer`.OrderId
    ";
    $result = MYSQL_getData($query);
    if ($result === false){
        return false;
    }
    
    echo "<table border='1'>";
    echo "<tr>";
    foreach($result[0] as $key => $value){
        echo "<th>$key</th>";
    }
    echo "</tr>";
    
    foreach($result as $row){
        if (!is_array($row)){
            continue;
        }
        echo "<tr>";
        foreach($row as $key => $value){
            echo "<td>$value</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    return true;
}

function displayAllDetailSuccess(){
    
    $query = "
        SELECT 
            #`ContactInfo`.id, 
            `ContactInfo`.Name, 
            `ContactInfo`.EnglishName, 
            `ContactInfo`.Phone, 
            `ContactInfo`.Email, 
            `ContactInfo`.PersonalId, 
            `ContactInfo`.DOB, 
            `ContactInfo`.Gender AS 性別, 
            `ContactInfo`.EmerContactName AS 緊急人, 
            `ContactInfo`.EmerContactNum AS 緊急電話, 
            `ContactInfo`.School, 
            `ContactInfo`.TSAOfficerRole AS TSA職稱, 
            `ContactInfo`.RepresentTSA AS 代表TSA, 
            `ContactInfo`.CreatedAt AS 下單時間, 
            `Order`.id AS 訂單號碼,
            `Order`.OriginalId AS 原始訂單號碼, 
            `Order`.Price, 
            `Order`.Fee, 
            `Order`.PromoCode, 
            `PaymentResultServer`.PaymentMethod, 
            `PaymentResultServer`.TotalAmount, 
            `PaymentResultServer`.Status AS 交易結果 
        FROM `ContactInfo`
        JOIN `Order` ON `ContactInfo`.id = `Order`.ContactId
        JOIN `Payment` ON `Order`.id = `Payment`.OrderId
        JOIN `PaymentResultServer` ON `Payment`.OrderId = `PaymentResultServer`.OrderId
        WHERE `PaymentResultServer`.Status = '交易成功'
    ";
    $result = MYSQL_getData($query);
    if ($result === false){
        return false;
    }
    
    echo "<table border='1'>";
    echo "<tr>";
    foreach($result[0] as $key => $value){
        echo "<th>$key</th>";
    }
    echo "</tr>";
    
    foreach($result as $row){
        if (!is_array($row)){
            continue;
        }
        echo "<tr>";
        foreach($row as $key => $value){
            echo "<td>$value</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    return true;
}

function displayAllSuccess(){
    
    $query = "
        SELECT 
            #`ContactInfo`.id, 
            `ContactInfo`.Name, 
            `ContactInfo`.EnglishName, 
            `ContactInfo`.Phone, 
            `ContactInfo`.Email, 
            #`ContactInfo`.PersonalId, 
            #`ContactInfo`.DOB, 
            #`ContactInfo`.Gender AS 性別, 
            #`ContactInfo`.EmerContactName AS 緊急人, 
            #`ContactInfo`.EmerContactNum AS 緊急電話, 
            `ContactInfo`.School, 
            `ContactInfo`.TSAOfficerRole AS TSA職稱, 
            `ContactInfo`.RepresentTSA AS 代表TSA, 
            `ContactInfo`.CreatedAt AS 下單時間, 
            #`Order`.id AS 訂單號碼,
            #`Order`.OriginalId AS 原始訂單號碼, 
            `Order`.Price, 
            `Order`.Fee, 
            `Order`.PromoCode, 
            `PaymentResultServer`.PaymentMethod, 
            `PaymentResultServer`.TotalAmount, 
            `PaymentResultServer`.Status AS 交易結果 
        FROM `ContactInfo`
        JOIN `Order` ON `ContactInfo`.id = `Order`.ContactId
        JOIN `Payment` ON `Order`.id = `Payment`.OrderId
        JOIN `PaymentResultServer` ON `Payment`.OrderId = `PaymentResultServer`.OrderId
        WHERE `PaymentResultServer`.Status = '交易成功'
    ";
    $result = MYSQL_getData($query);
    if ($result === false){
        return false;
    }
    
    echo "<table border='1'>";
    echo "<tr>";
    foreach($result[0] as $key => $value){
        echo "<th>$key</th>";
    }
    echo "</tr>";
    
    foreach($result as $row){
        if (!is_array($row)){
            continue;
        }
        echo "<tr>";
        foreach($row as $key => $value){
            echo "<td>$value</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    return true;
}
if (isset($_POST['page'])){
    switch($_POST['page']){
        case "general":
            displayAll();
            break;
        case "detail":
            displayAllDetail();
            break;
        case "generalSuccess":
            displayAllSuccess();
            break;
        case "detailSuccess":
            displayAllDetailSuccess();
            break;
        default:
            echo "Invalid POST";
    }
    
}
else{
    ?>
<!doctype html>
<html>
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" integrity="sha256-rByPlHULObEjJ6XQxW/flG2r+22R5dKiAoef+aXWfik=" crossorigin="anonymous" />
    <link rel="icon" href="https://www.worldwidetsa.org/wp-content/uploads/2019/08/cropped-WWTSA-LOGO-new-32x32.png" sizes="32x32">
</head>
<body>
    <button class="pageChange" value="general">一般</button>
    <button class="pageChange" value="detail">詳細</button>
    <button class="pageChange" value="generalSuccess">一般(僅顯示交易成功)</button>
    <button class="pageChange" value="detailSuccess">詳細(僅顯示交易成功)</button>
    <div id="mainData">
    <?php
    displayAll();
    ?>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js" integrity="sha256-KM512VNnjElC30ehFwehXjx1YCHPiQkOPmqnrWtpccM=" crossorigin="anonymous"></script>
    <script>
    $(".pageChange").click(function(){
        var postData = {page:$( this ).val()};
        $.ajax({
            url: "index.php",
            method: "POST",
            data: postData
        }).done(function( data ) {
            $("#mainData").html(data);
        });
    });
    </script>
<body>
</html>
<?php
}
?>