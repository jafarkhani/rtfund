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
$error = "";

if (!empty($_POST["UserName"])) {
	login($error);
}

function login(&$error) {

	$user = $_POST["UserName"];
	$pass = $_POST["md5Pass"];

	$result = session::login($user, $pass);
	if ($result !== true) {
		switch($result)
		{
			case "WrongUserName": $error = "کلمه کاربری وارد شده وجود ندارد";break;
			case "TooMuchAttempt": "شناسه شما برای 10 دقیقه مسدود می باشد";break;
			case "WrongPassword": $error = "رمز عبور وارد شده صحیح نمی باشد";break;
			case "InActiveUser": $error = "کلمه کاربری شما هنوز در صندوق فعال نشده است";break;
		}
	}
	else
	{
		header("location: /messenger/MyMessenger.php");
		die();
	}
}
?> 
<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Bootstrap Example</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
		<style>
			input[type=button], input[type=submit], input[type=reset]  {
				background-color: #56baed;
				font-family: tahoma;
				border: none;
				color: white;
				padding: 15px 80px;
				text-align: center;
				text-decoration: none;
				display: inline-block;
				text-transform: uppercase;
				font-size: 13px;
				-webkit-box-shadow: 0 10px 30px 0 rgba(95,186,233,0.4);
				box-shadow: 0 10px 30px 0 rgba(95,186,233,0.4);
				-webkit-border-radius: 5px 5px 5px 5px;
				border-radius: 5px 5px 5px 5px;
				margin: 5px 20px 40px 20px;
				-webkit-transition: all 0.3s ease-in-out;
				-moz-transition: all 0.3s ease-in-out;
				-ms-transition: all 0.3s ease-in-out;
				-o-transition: all 0.3s ease-in-out;
				transition: all 0.3s ease-in-out;
			}
			input[type=button]:hover, input[type=submit]:hover, input[type=reset]:hover  {
				background-color: #39ace7;
			}

			input[type=button]:active, input[type=submit]:active, input[type=reset]:active  {
				-moz-transform: scale(0.95);
				-webkit-transform: scale(0.95);
				-o-transform: scale(0.95);
				-ms-transform: scale(0.95);
				transform: scale(0.95);
			}

			.formField {
				background-color: #f6f6f6;
				font-family: tahoma;
				border: none;
				color: #0d0d0d;
				padding: 15px 32px;
				text-align: center;
				text-decoration: none;
				display: inline-block;
				font-size: 16px;
				margin: 5px;
				width: 85%;
				border: 2px solid #f6f6f6;
				-webkit-transition: all 0.5s ease-in-out;
				-moz-transition: all 0.5s ease-in-out;
				-ms-transition: all 0.5s ease-in-out;
				-o-transition: all 0.5s ease-in-out;
				transition: all 0.5s ease-in-out;
				-webkit-border-radius: 5px 5px 5px 5px;
				border-radius: 5px 5px 5px 5px;
			}

			.formField:focus {
				background-color: #fff;
				border-bottom: 2px solid #5fbae9;
			}

			.formField:placeholder {
				color: #cccccc;
				font-family: tahoma;
			}
			.formContent {
				-webkit-border-radius: 10px 10px 10px 10px;
				border-radius: 10px 10px 10px 10px;
				background: #fff;
				padding: 30px;
				width: 90%;
				max-width: 450px;
				position: relative;
				padding: 0px;
				-webkit-box-shadow: 0 30px 60px 0 rgba(0,0,0,0.3);
				box-shadow: 0 10px 20px 0 rgba(0,0,0,0.3);
				text-align: center;
			}
			.error{
				color: red !important;
				font-weight: bold;
				font-family: tahoma;
			}
		</style>
		<?php require_once 'md5.php'; ?>
		<script>
			function BeforeSubmit(){
				document.getElementById("md5Pass").value = MD5(document.getElementById("password").value);
				return true;
			}
		</script>
	</head>
	<body>
	<br><center><font style="font-family: tahoma;font-weight: bold"><?= SoftwareName ?></font></center>
		<br>
		<div class="container formContent" align="center">
			<br><div class="error"><?= $error ?></div>
			<form  onsubmit="return BeforeSubmit()" method="post">
				<br>
				<div class="form-group">
					<input type="text" class="formField" id="UserName" placeholder="کلمه کاربری ..." name="UserName">
				</div>
				<div class="form-group">
					<input type="password" class="formField" id="password" placeholder="رمز عبور ..." name="password">
				</div>
				<input type="hidden" name="md5Pass" id="md5Pass">
				<input  type="submit" class="btn btn-default" value="ورود به سیستم"/>
			</form>
		</div>

	</body>
</html>
