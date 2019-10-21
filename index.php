<?php
$Event_Name = "世學聯年終大活動";
$Event_Organizer = "世學聯";
$Event_Description = "[活動簡介]";
$Event_Website_URL = "https://www.worldwidetsa.org/";
$Event_Email = "wwtsa2019@gmail.com";
$Event_Note = "[訂購前須知]";
$Event_FB_URL = "https://www.facebook.com/wwtsa2019/";
$Event_API_EndPoint = "https://event.worldwidetsa.org/api.php";
$Event_Logo_URL = "https://free.com.tw/blog/wp-content/uploads/2014/08/Placekitten480-g.jpg";
$Event_Logo_URL = "https://www.worldwidetsa.org/wp-content/uploads/2019/08/WWTSA-LOGO-new.png";
$Event_Agreement_URL = "#";

?>
<!doctype html>
<html lang="en">
  <head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" integrity="sha256-rByPlHULObEjJ6XQxW/flG2r+22R5dKiAoef+aXWfik=" crossorigin="anonymous" />
    <link rel="icon" href="https://www.worldwidetsa.org/wp-content/uploads/2019/08/cropped-WWTSA-LOGO-new-32x32.png" sizes="32x32">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js" integrity="sha256-KM512VNnjElC30ehFwehXjx1YCHPiQkOPmqnrWtpccM=" crossorigin="anonymous"></script>
    <script>
    var purchaseFormTop;
    function formOrderItem(item){
        return `
            <li class="list-group-item d-flex justify-content-between lh-condensed">
                <div>
                    <h6 class="my-0">${item.Name}</h6>
                    <small class="text-muted">${item.Description}</small>
                </div>
                <span class="text-muted">$${item.Price}NTD</span>
            </li>
        `;
    }
    function updatePrice(PromoCode=null){
        $("#orderItems").html(`
            <li class="list-group-item d-flex justify-content-between">
                <div class="spinner-border text-primary" role="status">
                <span class="sr-only">讀取中...</span>
                </div>
            </li>
        `);
        $("#itemCount").html("~");
        
        var postData = {action:"getOrderItems", promo:PromoCode, paymentMethod:$("#paymentMethod").val() };
        $.ajax({
            url: "<?=$Event_API_EndPoint?>",
            method: "POST",
            data: postData
        }).done(function( data ) {
            $("#itemCount").html(data.dataCount);
            var totalPrice = 0;
            $("#orderItems").html("");
            $.each(data.result, function(i, val) {
                totalPrice += val.Price;
                $("#orderItems").append(formOrderItem(val));
            });
            $("#orderItems").append(`
			<li class="list-group-item d-flex justify-content-between">
                <span>總額 (NTD)</span>
                <strong>$${totalPrice}</strong>
			</li>
            `);
        });
    }
    
    document.addEventListener("DOMContentLoaded", function(event) {
        var isMobile = window.matchMedia("only screen and (max-width: 760px)").matches;
        
        
        updatePrice();
        $( "#DOB" ).datepicker({
            dateFormat: "yy/mm/dd",
            maxDate: new Date("2005/12/31"),
            changeMonth: true,
            changeYear: true
        });
        $("#promoForm").submit(function( event ){
            updatePrice($("#promoCode").val());
            $("#promoCodeData").val($("#promoCode").val());
            event.preventDefault();
        });
        $("#purchaseForm").submit(function( event ){
            alert("我還沒做完這塊，但也不想讓你有機會玩壞");
            
            if ($("#RepresentTSA-YES").is(':checked')){
                $("#RepresentTSA").val("YES");
            }
            else if ($("#RepresentTSA-NO").is(':checked')){
                $("#RepresentTSA").val("NO");
            }
            
            console.log(`
                Name: ${$("#Name").val()}
                EnglishName: ${$("#EnglishName").val()}
                Phone: ${$("#Phone").val()}
                Email: ${$("#Email").val()}
                PersonalId: ${$("#PersonalId").val()}
                DOB: ${$("#DOB").val()}
                EmerContactName: ${$("#EmerContactName").val()}
                EmerContactNum: ${$("#EmerContactNum").val()}
                School: ${$("#School").val()}
                TSAOfficerRole: ${$("#TSAOfficerRole").val()}
                RepresentTSA: ${$("#RepresentTSA").val()}
                PaymentMethod: ${$("#PaymentMethod").val()}
                PromoCode: ${$("#promoCodeData").val()}
            `);
            event.preventDefault();
        });
        $("#paymentMethod").change(function( event ){
            updatePrice($("#promoCode").val());
        });
        
        if (isMobile == false) {
            $('.always-on-top').css('height', "0px");
            purchaseFormTop = $('.always-on-top').offset().top;
            $(document).scroll(function(){
                if (purchaseFormTop > $(document).scrollTop()){
                    $('.always-on-top').css('top', 0);
                }
                else{
                    $('.always-on-top').css('top',$(document).scrollTop()-purchaseFormTop);
                }
            });
        }
    });
    </script>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">

    <title><?=$Event_Organizer?>售票系統｜<?=$Event_Name?></title>
  </head>
  <body>
  <header>
	<div class="collapse bg-dark" id="navbarHeader">
		<div class="container">
		<div class="row">
			<div class="col-sm-8 col-md-7 py-4">
			<h4 class="text-white">關於<?=$Event_Name?></h4>
			<p class="text-muted"><?=$Event_Description?></p>
			</div>
			<div class="col-sm-4 offset-md-1 py-4">
			<h4 class="text-white">聯絡我們</h4>
			<ul class="list-unstyled">
				<li><a href="<?=$Event_Website_URL?>" class="text-white">官方網站</a></li>
				<li><a href="mailto:<?=$Event_Email?>" class="text-white">Email 電子郵件</a></li>
				<li><a href="<?=$Event_FB_URL?>" class="text-white">Facebook 粉絲專頁</a></li>
			</ul>
			</div>
		</div>
		</div>
	</div>
	<div class="navbar navbar-dark bg-dark shadow-sm">
		<div class="container d-flex justify-content-between">
		<a href="#" class="navbar-brand d-flex align-items-center">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2" focusable="false" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
			<strong><?=$Event_Organizer?>售票系統</strong>
		</a>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarHeader" aria-controls="navbarHeader" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		</div>
	</div>
	</header>
	
	<!-- 讀取狀態顯示
	<div class="spinner-border text-primary" role="status">
	<span class="sr-only">讀取中...</span>
	</div>
	-->
	
	<div class="container">
	<div class="py-5 text-center">
		<img class="d-block mx-auto mb-4" src="<?=$Event_Logo_URL?>" alt="" height="100">
		<h2><?=$Event_Name?></h2>
		<p class="lead"><?=$Event_Note?></p>
	</div>

	<div class="row">
		<div class="col-md-4 order-md-2 mb-4 always-on-top">
            <h4 class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted">訂購項目</span>
                <span class="badge badge-secondary badge-pill" id="itemCount">~</span>
            </h4>
            <ul class="list-group mb-3" id="orderItems">
                <!-- 訂單內容 -->
            </ul>

            <form class="card p-2" id="promoForm">
                <div class="input-group">
                    <input id="promoCode" type="text" class="form-control" placeholder="優惠折扣碼">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-secondary">使用</button>
                    </div>
                </div>
            </form>
		</div>
    <div class="col-md-8 order-md-1">
        <h4 class="mb-3">訂票資訊</h4>
        <form class="needs-validation" id="purchaseForm" novalidate>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="Name">中文姓名</label>
                    <input type="text" class="form-control" id="Name" placeholder="" value="" required>
                    <div class="invalid-feedback">
                    Valid first name is required.
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="EnglishName">英文姓名</label>
                    <input type="text" class="form-control" id="EnglishName" placeholder="" value="" required>
                    <div class="invalid-feedback">
                    Valid last name is required.
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="Phone">聯絡電話(含國碼)</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                    <span class="input-group-text">+</span>
                    </div>
                    <input type="text" class="form-control" id="Phone" placeholder="886912345678" required>
                    <div class="invalid-feedback" style="width: 100%;">
                        連絡電話為必填
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="Email">Email (電子信箱) <span class="text-muted">(必填)</span></label>
                <input type="Email" class="form-control" id="Email" placeholder="myemail@example.com" required>
                <div class="invalid-feedback">
                    請輸入正確的Email地址
                </div>
            </div>

            <div class="mb-3">
                <label for="PersonalId">身分證字號(保險用) <span class="text-muted">(必填)</span></label>
                <input type="PersonalId" class="form-control" id="PersonalId" placeholder="A000000000" required>
                <div class="invalid-feedback">
                    請輸入正確的身分證字號 用於保險用途
                </div>
            </div>

            <div class="mb-3">
                <label for="DOB">生日 <span class="text-muted">(必填)</span></label>
                <input type="DOB" class="form-control" id="DOB" name="DOB" placeholder="YYYY/MM/DD" required>
                <div class="invalid-feedback">
                    請輸入正確的出生年月日 用於保險用途
                </div>
            </div>

            <div class="mb-3">
                <label for="EmerContactName">緊急聯絡人姓名 <span class="text-muted">(必填)</span></label>
                <input type="EmerContactName" class="form-control" id="EmerContactName" placeholder="" required>
                <div class="invalid-feedback">
                    緊急聯絡人姓名為必填
                </div>
            </div>

            <div class="mb-3">
                <label for="EmerContactNum">緊急聯絡人電話(含國碼) <span class="text-muted">(必填)</span></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                    <span class="input-group-text">+</span>
                    </div>
                    <input type="text" class="form-control" id="EmerContactNum" placeholder="886912345678" required>
                    <div class="invalid-feedback" style="width: 100%;">
                        緊急聯絡人電話為必填
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="School">就讀學校 <span class="text-muted">(必填)</span></label>
                <input type="text" class="form-control" id="School" placeholder="" required>
                <div class="invalid-feedback">
                    就讀學校為必填
                </div>
            </div>

            <div class="mb-3">
                <label for="TSAOfficerRole">台灣學生會幹部職稱 <span class="text-muted">(非幹部留空即可)</span></label>
                <input type="text" class="form-control" id="TSAOfficerRole" placeholder="">
            </div>

            <div class="d-block my-3">
            <label>是否代表貴學生會參與此次晚會? <span class="text-muted">(必填)</span></label>
              <div class="custom-control custom-radio">
                <input id="RepresentTSA-NO" name="RepresentTSA" type="radio" value="No" class="custom-control-input" checked required>
                <label class="custom-control-label" for="RepresentTSA-NO">否</label>
              </div>
              <div class="custom-control custom-radio">
                <input id="RepresentTSA-YES" name="RepresentTSA" type="radio" value="Yes" class="custom-control-input" required>
                <label class="custom-control-label" for="RepresentTSA-YES">是</label>
              </div>
              <input id="RepresentTSA" type="hidden" name="RepresentTSA">
            </div>
            <hr class="mb-4">

            <h4 class="mb-3">付款方式</h4>

            <div class="d-block my-3">
                <select class="custom-select d-block w-100" id="PaymentMethod" name="paymentMethod" required>
                  <option value="Credit">信用卡</option>
                  <option value="BARCODE">超商條碼</option>
                  <option value="CVS">超商代碼</option>
                </select>
                <div class="invalid-feedback">
                  請選擇一個有效的付款方式
                </div>
                <small class="text-muted">盜刷一定有風險，犯罪投資有賺有賠，盜刷前應詳閱六法全書。</small>
            </div>
            <div class="custom-control custom-checkbox">
              <input type="checkbox" class="custom-control-input" id="agreement" required>
              <label class="custom-control-label" for="agreement">本人已詳讀<a href="<?=$Event_Agreement_URL?>">活動辦法</a>與確認上述資料填寫無誤，且同意提供個人資料予主辦單位使用，同時主辦單位將尊重個人資料機密予以嚴格保密。</label>
            </div>
            <hr class="mb-4">
            <input id="promoCodeData" type="hidden" name="promoCode">
            <button class="btn btn-primary btn-lg btn-block" type="submit">確認訂購</button>
        </form>
    </div>
</div>

  <footer class="my-5 pt-5 text-muted text-center text-small">
    <p class="mb-1">&copy; Copyright 2019 <a href="https://host.clark-chen.com/">Clark's 虛擬主機服務</a>｜設計：<a href="https://www.facebook.com/sak7025/">Yuhao Liu</a>｜All Rights Reserved</p>
    <ul class="list-inline">
      <li class="list-inline-item"><a href="<?=$Event_Website_URL?>">官方網站</a></li>
      <li class="list-inline-item"><a href="<?=$Event_FB_URL?>">粉絲專頁</a></li>
      <li class="list-inline-item"><a href="mailto:<?=$Event_Email?>">聯繫客服</a></li>
    </ul>
  </footer>
</div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js" integrity="sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js" integrity="sha384-B0UglyR+jN6CkvvICOB2joaf5I4l3gm9GU6Hc1og6Ls7i6U/mkkaduKaBhlAXv9k" crossorigin="anonymous"></script>
  </body>
</html>
