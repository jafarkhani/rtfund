<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------

require_once getenv("DOCUMENT_ROOT") . '/framework/configurations.inc.php';
set_include_path(get_include_path() . PATH_SEPARATOR . getenv("DOCUMENT_ROOT") . "/generalClasses");
require_once 'PDODataAccess.class.php';
require_once 'DataAudit.class.php';
require_once getenv("DOCUMENT_ROOT") . '/framework/PasswordHash.php';
require_once '../definitions.inc.php';

require_once getenv("DOCUMENT_ROOT") . '/framework/session.php';
session::sec_session_start();

$task = empty($_REQUEST["task"]) ? "" : $_REQUEST["task"];
switch($task)
{
	case "login": login();die();
	case "register": register();die();
	case "forget": forget();die();
}

function login(){
	
	$user = $_POST["UserName"];
	$pass = $_POST["md5Pass"];
	
	$result = session::login($user, $pass);
	if($result !== true)
	{
		echo $result;
		die();
	}
	
	if($_SESSION['USER']["IsStaff"] == "YES")
	{
		//$_SESSION['USER']["framework"] = true;
		//unset($_SESSION['USER']["portal"]);
		echo "/framework/desktop.php";
		die();
	}
	else
	{
		//unset($_SESSION['USER']["framework"]);
		//$_SESSION['USER']["portal"] = true;
		echo "/portal/index.php";
		die();
	}
}
function register(){
	require_once getenv("DOCUMENT_ROOT") . '/framework/person/persons.class.php';
	
	$obj = new BSC_persons();
	$return = session::register($obj);
	if($return === true)
	{
		$result = session::login($obj->UserName, $_POST["md5Pass"]);
		echo "/portal/index.php";
		die();
	}
	echo $return;
	die();
}
function forget(){
	
	if(isset($_REQUEST["forgetStep1"]))
	{
		if(!empty($_POST["ForgetUserName"]))
		{
			echo session::getMobile($_POST["ForgetUserName"], true);
			die();			
		}

		echo "WrongUserName";die();
	}
	else if(isset($_REQUEST["forgetStep2"]))
	{
		$mobile = session::getMobile($_POST["ForgetUserName"]);
		if($mobile != $_POST["forgetmobile"])
		{
			echo "WrongMobile";
			die();	
		}
		session::SendNewPass($_POST["ForgetUserName"]);
		die();
	}
}
//........................................

$pics = PdoDataAccess::runquery("select PicID,FileType from FRW_pics where SourceType='login'");
$index = rand(0, count($pics)-1);
?>
<html>
  <head>
	<link rel="stylesheet" type="text/css" href="/generalUI/fonts/fonts.css" />  
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta http-equiv="expires" content="now">
	<meta http-equiv="pragma" content="no-cache">
    <title>نرم افزار <?= SoftwareName?></title>
	<?php require_once 'md5.php'; ?>
	<script>
		var xmlhttp;
		if (window.XMLHttpRequest)
		{
			// code for IE7+, Firefox, Chrome, Opera, Safari
			xmlhttp = new XMLHttpRequest();
		}
		else
		{
			// code for IE6, IE5
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
		
		function pressing(e)
		{
			var c = (e.keyCode)? e.keyCode: (e.charCode)? e.charCode: e.which;
			if(c == 13)
			{
				loginFN();
			}
		}
		
		function loginFN()
		{
			document.getElementById("md5Pass").value = MD5(document.getElementById('password').value);
			xmlhttp.onreadystatechange = function()
			{
				if (xmlhttp.readyState==4 && xmlhttp.status==200)
				{
					document.getElementById("ajax-loading").style.display = "none";
					document.getElementById("LoginErrorDiv").style.display = "block";
					switch(xmlhttp.responseText)
					{
						case "WrongUserName":
							document.getElementById("LoginErrorDiv").innerHTML = "کلمه کاربری وارد شده وجود ندارد";
							break;
						case "TooMuchAttempt":
							document.getElementById("LoginErrorDiv").innerHTML = "شناسه شما برای 10 دقیقه مسدود می باشد";
							break;
						case "WrongPassword":
							document.getElementById("LoginErrorDiv").innerHTML = "رمز عبور وارد شده صحیح نمی باشد";
							break;
						case "InActiveUser":
							document.getElementById("LoginErrorDiv").innerHTML = "کلمه کاربری شما هنوز در صندوق فعال نشده است";
							break;
						
						default :
							document.getElementById("LoginErrorDiv").style.display = "none";
							window.location = xmlhttp.responseText;
					}
				}
			}

			xmlhttp.open("POST","login.php?task=login",true);
			document.getElementById("ajax-loading").style.display = "block";
			xmlhttp.send(new FormData(document.getElementById("LoginForm")));
		}
		
		function RegisterFN()
		{
			document.getElementById("md5Pass2").value = MD5(document.getElementById('password1').value);
			if(document.getElementById("NationalID").value == "")
			{
				document.getElementById("NationalID").setCustomValidity("ورود کد ملی/شناسه ملی الزامی است");
				document.getElementById("RegisterErrorDiv").innerHTML = "تکمیل کد ملی / شناسه ملی الزامی است";
				document.getElementById("RegisterErrorDiv").style.display = "block";
				return;
			}
			xmlhttp.onreadystatechange = function()
			{
				if (xmlhttp.readyState==4 && xmlhttp.status==200)
				{
					document.getElementById("ajax-loading").style.display = "none";
					document.getElementById("RegisterErrorDiv").style.display = "block";
					switch(xmlhttp.responseText)
					{
						case "DuplicateUserName":
							document.getElementById("RegisterErrorDiv").innerHTML = "کلمه کاربری انتخابی شما قبلا استفاده شده است";
							break;
						case "DuplicateNationalID":
							document.getElementById("RegisterErrorDiv").innerHTML = "با این کدملی / شناسه ملی قبلا ثبت نام انجام شده است";
							break;
						default:
							/*document.getElementById("RegisterErrorDiv").innerHTML = 
									"ثبت نام شما با موفقیت انجام شد. نتیجه از طریق ایمیل بعد از حداکثر یک روز کاری به شما اطلاع داده خواهد شد";
							document.getElementById("RegisterErrorDiv").className = "success";*/
							document.getElementById("RegisterErrorDiv").style.display = "none";
							window.location = xmlhttp.responseText;
					}
				}
			}

			xmlhttp.open("POST","login.php?task=register",true);
			document.getElementById("ajax-loading").style.display = "block";
			xmlhttp.send(new FormData(document.getElementById("RegisterForm")));
		}
				
		function Register(registerMode){
		
			document.getElementById("loginDIV").style.display = registerMode ? "none" : "block";
			document.getElementById("registerDIV").style.display = registerMode ? "block" : "none";
		}
		
		function ChangePersonType(elem){
			document.getElementById("fname").style.display = elem.value == "YES" ? "block" : "none";
			document.getElementById("lname").style.display = elem.value == "YES" ? "block" : "none";
			document.getElementById("CompanyName").style.display = elem.value == "YES" ? "none" : "block";
			
			document.getElementById("fname").required = elem.value == "YES" ? "required" : "";
			document.getElementById("lname").required = elem.value == "YES" ? "required" : "";
			document.getElementById("CompanyName").required = elem.value == "YES" ? "" : "required";
		}
		
		function ConfirmPassword(input) {
			if (input.value != document.getElementById('password1').value) {
				input.setCustomValidity('Password Must be Matching.');
			} else {
				input.setCustomValidity('');
			}
		}
		
		function ForgetPass(step){
			
			if(step == 0)
			{
				document.getElementById("loginDIV").style.display = "block";
				document.getElementById("forgetDIV").style.display ="none";
				document.getElementById("descDIV").innerHTML = "ابتدا کلمه کاربری خود را وارد کنید" + "<br><br>";
				document.getElementById("ForgetUserName").style.display = "block";
				document.getElementById("loginBTN").style.display = "none";	
				document.getElementById("step1BTN").style.display = "block";	
				document.getElementById("ForgetUserName").value = "";
				document.getElementById("forgetmobile").value = "";
				return;
			}
			if(step == 1)
			{
				document.getElementById("loginDIV").style.display = "none";
				document.getElementById("forgetDIV").style.display ="block";
				return;
			}
			if(step == 2)
			{
				xmlhttp.onreadystatechange = function()
				{
					if (xmlhttp.readyState==4 && xmlhttp.status==200)
					{
						document.getElementById("ajax-loading").style.display = "none";
						
						if(xmlhttp.responseText == "WrongUserName")
						{
							document.getElementById("ForgetErrorDiv").style.display = "block";
							document.getElementById("ForgetErrorDiv").innerHTML = "کلمه کاربری وارد شده وجود ندارد";
							return;
						}
						if(xmlhttp.responseText == "EmptyMobile")
						{
							document.getElementById("ForgetErrorDiv").style.display = "block";
							document.getElementById("ForgetErrorDiv").innerHTML = 
								"شماره تلفن همراه شما برای ارسال رمز عبور جدید در سیستم ثبت نشده است"+
								"برای بازیابی رمز با صندوق تماس بگیرید ";
							return;
						}
						document.getElementById("ForgetErrorDiv").style.display = "none";
						document.getElementById("ForgetUserName").style.display = "none";
						document.getElementById("descDIV").innerHTML = "شماره تلفن همراه خود را که مطابق با الگوی زیر می باشد وارد کنید" +
							"<br><br><div style=direction:ltr align=left>" + xmlhttp.responseText + "</div><br><br>";
						document.getElementById("forgetmobile").style.display = "block";
						document.getElementById("step1BTN").style.display = "none";
						document.getElementById("step2BTN").style.display = "block";					
					}
				}

				xmlhttp.open("POST","login.php?task=forget&forgetStep1=true",true);
				document.getElementById("ajax-loading").style.display = "block";
				xmlhttp.send(new FormData(document.getElementById("forgetForm")));
			}
			if(step == 3)
			{				
				xmlhttp.onreadystatechange = function()
				{
					if (xmlhttp.readyState==4 && xmlhttp.status==200)
					{
						document.getElementById("ajax-loading").style.display = "none";
						if(xmlhttp.responseText == "WrongMobile")
						{						
							document.getElementById("ForgetErrorDiv").style.display = "block";
							document.getElementById("ForgetErrorDiv").innerHTML = "شماره وارد شده صحیح نمی باشد";
							return;
						}
						if(xmlhttp.responseText == "SendedMobileBefore10Min")
						{						
							document.getElementById("ForgetErrorDiv").style.display = "block";
							document.getElementById("ForgetErrorDiv").innerHTML = " برای ارسال مجدد پیامک باید حداقل 10 دقیقه منتظر بمانید";
							return;
						}
						
						if(xmlhttp.responseText == "true")
						{						
							document.getElementById("ForgetErrorDiv").style.display = "none";
							document.getElementById("descDIV").innerHTML = "رمز عبور جدید به شماره تلفن همراه شما ارسال گردید" +
							"<br><br>";
							document.getElementById("step2BTN").style.display = "none";
							document.getElementById("loginBTN").style.display = "block";	
							return;
						}
						
						document.getElementById("ForgetErrorDiv").style.display = "block";
						document.getElementById("ForgetErrorDiv").innerHTML = "ارسال پیامک با شکست مواجه گردید";
										
					}
				}

				xmlhttp.open("POST","login.php?task=forget&forgetStep2=true",true);
				document.getElementById("ajax-loading").style.display = "block";
				xmlhttp.send(new FormData(document.getElementById("forgetForm")));
			}			
		}
		
		function BackToLogin(){
			
			document.getElementById("loginDIV").style.display = "block";
			document.getElementById("forgetDIV").style.display ="none";
			document.getElementById("registerDIV").style.display ="none";
		}
		
	</script>
	 <style>
	body, td, div, button{
		font-family: irsans;
	}
	header{
		background: url("../framework/management/ShowFile.php?PicID=<?= $pics[$index]["PicID"] ?>") center center no-repeat;
		width: 100%;
		height: 95vh; 
		overflow: hidden;
		background-size: cover;
		-webkit-background-size: cover;
		-moz-background-size: cover;
		-o-background-size: cover;
		display: block;
		box-sizing: border-box;
	}
	.footerDiv{
		background-color: black;
		position: absolute;
		bottom: 0;
		height : 50px;
		width : 100%;		
	}
	.footerDiv td , a{
		padding-top : 10px;
		font-size: 13px;
		text-decoration: none;
		font-weight: bold;
		color : white;		
	}
	.loginDiv{
		width: 400px;
		position: fixed;
		top: 40%;
		left: 50%;
		margin-left: -211px;
		margin-top: -187px;
	}
	.loginDiv .title{
		color: #fff;
		background-color: rgba(0,0,0,.6);
		border-top-right-radius: 5px;
		border-top-left-radius: 5px;
		padding: 14px;
		font-size: 21px;
		text-align: center;
	}
	.loginDiv .error{
		background-color: rgba(255,0,0,.8) !important;
		height: 20px;
		color: white !important;
		font-size: 12px;
		text-align: justify;
		margin-bottom: 20px;
		background-color: #fff;
		color: #000;
		padding: 10px;
		line-height: 2em;
		-webkit-border-radius: .3em;
		-moz-border-radius: .3em;
		border-radius: .3em;
	}
	
	.loginDiv .success{
		background-color: lawngreen !important;
		height: 50px;
		color: white !important;
		font-size: 12px;
		font-weight: bold;
		text-align: justify;
		margin-bottom: 20px;
		background-color: #fff;
		color: #000;
		padding: 4px;
		line-height: 2em;
		-webkit-border-radius: .3em;
		-moz-border-radius: .3em;
		border-radius: .3em;
	}
	
	.loginDiv .body{
		background-color: rgba(0,0,0,.5);
		border: 1px solid rgba(0,0,0,.22);
		border-top-width: 1px;
		border-top-style: solid;
		border-top-color: rgba(0, 0, 0, 0.22);
		border-top: none;
		padding: 20px 35px 0;
		position: relative;
	}
	.loginDiv .footer{
		color: #fff;
		background-color: rgba(0,0,0,.6);
		border-bottom-right-radius: 5px;
		border-bottom-left-radius: 5px;
		padding: 10px;
		font-size: 11px;
		text-align: center;
		font-size: 12px;
		height: 20px;
		color: white;
		cursor: pointer;
	}
	
	.textfield {
		border: 1px solid rgba(0,0,0,.22);
		height: 55px;
		border-radius: 4px;
		font-size: 16px;
		direction: ltr;
		text-align: left;
		color: #858585;
		width: 100%;
		margin-top : 5px;
		font-weight: 400;
		padding-right: 20px;
		padding-left: 20px;
	}
	.textfield2 {
		border: 1px solid rgba(0,0,0,.22);
		height: 35px;
		border-radius: 4px;
		font-size: 16px;
		direction: ltr;
		text-align: left;
		margin-top : 5px;
		color: #858585;
		width: 100%;
		font-weight: 400;
		padding-right: 20px;
		padding-left: 20px;
	}
	
	.btn{
		height: 55px;
		display: block;
		width: 100%;
		font-size: 18px;
		margin-top: 30px;
		background-color: #009cfc;
		color: #fff;
		border-radius: 4px;
		border: 1px solid rgba(0,0,0,.1);
	}

	.btn:hover {
		background-color: #48d1fe;
	}
	.forget{
		float: left;
	}
	.register{
		float: right;
	}
	
	#ajax-loading{
		position: fixed;
		top: 0;
		left: 0;
		height:100%;
		width:100%;
		z-index: 9999999;
		background-color : #999;
		opacity: 0.7;
		filter: alpha(opacity=70); /* ie */
		-moz-opacity: 0.7; /* mozilla */    
		display : none;
	}
	#ajax-loading div{
		position: absolute;
		width: 30px;
		height: 30px;
		left: 50%;
		top: 10%;
		margin: 0 0 0 -15px;
		border: 8px solid #fff;
		border-right-color: transparent;
		border-radius: 50%;
		box-shadow: 0 0 25px 2px #eee;
		-webkit-animation: spin 1s linear infinite;
		-moz-animation: spin 1s linear infinite;
		-ms-animation: spin 1s linear infinite;
		-o-animation: spin 1s linear infinite;
		animation: spin 1s linear infinite;
	} 
	#ajax-loading span{
		position: absolute;
		left: 44%;
		top: 20%;
		font-family: tahoma;
		color: white;
		direction : rtl;
		font-weight : bold;
	}
	@-webkit-keyframes spin
	{
		from { -webkit-transform: rotate(0deg); opacity: 0.4; }
		50% { -webkit-transform: rotate(180deg); opacity: 1; }
		to { -webkit-transform: rotate(360deg); opacity: 0.4; }
	}
	@-moz-keyframes spin
	{
		from { -moz-transform: rotate(0deg); opacity: 0.4; }
		50% { -moz-transform: rotate(180deg); opacity: 1; }
		to { -moz-transform: rotate(360deg); opacity: 0.4; }
	}
	@-ms-keyframes spin
	{
		from { -ms-transform: rotate(0deg); opacity: 0.4; }
		50% { -ms-transform: rotate(180deg); opacity: 1; }
		to { -ms-transform: rotate(360deg); opacity: 0.4; }
	}
	@-o-keyframes spin
	{
		from { -o-transform: rotate(0deg); opacity: 0.4; }
		50% { -o-transform: rotate(180deg); opacity: 1; }
		to { -o-transform: rotate(360deg); opacity: 0.4; }
	}
	@keyframes spin
	{
		from { transform: rotate(0deg); opacity: 0.2; }
		50% { transform: rotate(180deg); opacity: 1; }
		to { transform: rotate(360deg); opacity: 0.2; }
	} 
  </style>
  </head> 
  <body dir="rtl" style="margin:0">
	  <div id="ajax-loading"><div><span>&nbsp;&nbsp;&nbsp;</span></div></div>
	  <header>
		  
		<div class="loginDiv" id="loginDIV">
			<div class="title" > ورود به پرتال </div>
			<div class="body">
				<div class="error" id="LoginErrorDiv" style="display:none"></div>
				<form method="post" id="LoginForm">
					<input type="text" name="UserName" class="textfield" id="UserName" 
						placeholder="کلمه کاربری ..." required="required" dir="ltr" />
					<input onkeydown="pressing(event);" type="password" class="textfield" id="password" 
						placeholder="رمز عبور ..." required="required" dir="ltr"/>
					<button type="button" onclick="loginFN()" class="btn">ورود</button>
					<input type="hidden" id="md5Pass" name="md5Pass">
				</form>
			</div>
			<div class="footer">
				<div onclick="ForgetPass(1);" class="forget">◄&nbsp;رمز عبور را فراموش کرده ام </div>
				<div onclick="Register(true)" class="register">۞  ثبت نام در سامانه</div>
			</div>
		</div>
		  
		<div class="loginDiv" id="registerDIV" style="display:none;" align="right">
			<div class="title">ثبت نام در پرتال</div>
			<div class="body">
				<div class="error" id="RegisterErrorDiv" style="display:none"></div>
				<form method="post" id="RegisterForm" onsubmit="return RegisterFN();">
					<div style="margin-bottom:10px;direction:rtl;font-size: 13px;color:white">
						<input type="radio" id="isReal" name="IsReal" value="YES" onclick="ChangePersonType(this);" checked> شخص حقیقی
						<input type="radio" id="noReal" name="IsReal" value="NO" onclick="ChangePersonType(this);" value="LEGAL"> شخص حقوقی
					</div>
					<input type="text" name="fname" class="textfield2" id="fname" placeholder="...نام" required="required" style="text-align: right;" dir="rtl"/>
					<input type="text" name="lname" class="textfield2" id="lname" placeholder="...نام خانوادگی" required="required" style="text-align: right;" dir="rtl"/>
					<input type="text" name="CompanyName" class="textfield2" id="CompanyName" style="display:none;text-align: right;" placeholder="نام شرکت ..." dir="rtl"/>
					<input type="text" name="mobile" id="mobile" class="textfield2" placeholder="تلفن همراه ..." required="required" dir="ltr"/>
					<input type="email" name="email" id="email" class="textfield2" placeholder="پست الکترونیک ..." required="required" dir="ltr"/>
					<input type="text" name="NationalID" id="NationalID" class="textfield2" placeholder="کد ملی/ شناسه ملی ..." required="required" dir="ltr"/>
					<div id="UserNameDiv2"><input type="text" id="UserName2" name="UserName" class="textfield2" placeholder="کلمه کاربری ..." required="required" dir="ltr"/></div>
					<input type="password" class="textfield2" id="password1" placeholder="رمز عبور ..." required="required" dir="ltr"/>
					<input type="password" class="textfield2" id="password2" placeholder="تکرار رمز عبور ..." required="required" 
						   dir="ltr" oninput="ConfirmPassword(this)"/><br>
					<input type="hidden" id="md5Pass2" name="md5Pass">
					<button type="button" onclick="RegisterFN()" class="btn"> ثبت نام </button>					
				</form>
			</div>
			<div class="footer"><div align="left" style="cursor: pointer;" onclick="BackToLogin()">بازگشت</div></div>
		</div>	
		  
		<div class="loginDiv" id="forgetDIV" style="display:none">
			<div class="title">بازیابی کلمه عبور</div>
			<div class="body">
				<div class="error" id="ForgetErrorDiv" style="display: none"></div>
				<form id="forgetForm">
					<div style="font-size: 12px;color:white;" id="descDIV">ابتدا کلمه کاربری خود را وارد کنید
					<br><br></div>
					<input type="text" id="ForgetUserName" name="ForgetUserName" class="textfield" 
					placeholder="کلمه کاربری ..." required="required" dir="ltr"/>
					<input type="text" id="forgetmobile" name="forgetmobile" style="display:none" class="textfield" 
							placeholder="شماره تلفن همراه ..." dir="ltr"/>
					<button type="button" onclick="ForgetPass(2)" style="" id="step1BTN" class="btn"> مرحله بعد </button>
					<button type="button" onclick="ForgetPass(3)" style="display:none" id="step2BTN" class="btn"> ارسال پیامک </button>
					<button type="button" onclick="ForgetPass(0)" style="display:none" id="loginBTN" class="btn"> ورود </button>
				</form>
			</div>
			<div class="footer"><div align="left" style="cursor: pointer;" onclick="BackToLogin()">بازگشت</div></div>
		</div>
				
		<div class="footerDiv" align="center">
			<table width="90%">
				<tr>
					<td width="33%">تلفن پشتیبانی : 05138837393</td>
					<td width="33%" align="center"><a target="blank" href="http://krrtf.ir/">سایت صندوق پژوهش و فناوری خراسان رضوی</a></td>
					<td width="33%" align="left">© کلیه حقوق این نرم افزار محفوظ می باشد</td>
				</tr>
			</table>
		</div>
	</header>
</body>
</html>