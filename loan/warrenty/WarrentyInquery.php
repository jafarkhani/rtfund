<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 96.11
//-----------------------------

require_once getenv("DOCUMENT_ROOT") . '/framework/configurations.inc.php';
require_once getenv("DOCUMENT_ROOT") . '/generalClasses/PDODataAccess.class.php';
require_once getenv("DOCUMENT_ROOT") . '/generalClasses/ReportGenerator.class.php';
require_once getenv("DOCUMENT_ROOT") . '/definitions.inc.php';

ini_set("display_errors", "On");

function ShowInfo(&$errorMsg){
	
	//-------------------- captcha check ----------------
	
	require_once getenv("DOCUMENT_ROOT") . '/generalClasses/recaptchalib.php';
	// your secret key
	$secret = "6LcDx0UUAAAAANzj3XJRTQVt0Y2jioK9X80fiV3J";
	// empty response
	$response = null;
	// check secret key
	$reCaptcha = new ReCaptcha($secret);
	// if submitted check response
	if ($_POST["g-recaptcha-response"]) {
		$response = $reCaptcha->verifyResponse(
			$_SERVER["REMOTE_ADDR"],
			$_POST["g-recaptcha-response"]
		);
	}
	if ($response == null || !$response->success)
	{
		$errorMsg = "اهراز هویت شما تایید نگردید";
		return false;
	}
	//------------------------------------------------------
	
	$dt = PdoDataAccess::runquery("select w.*,NationalID from WAR_requests w join BSC_persons using(PersonID) where RefRequestID=?",
			array((int)$_POST["RequestID"]));
	if(count($dt) == 0)
	{
		$errorMsg =  "ضمانت نامه ایی با این شماره در سیستم صندوق ثبت نشده است";
		return false;
	}
	
	if($dt[0]["NationalID"] != $_POST["NationalID"] AND $dt[0]["orgNationalID"] != $_POST["NationalID"])
	{
		$errorMsg = "کدملی / شناسه ملی وارد شده مطابق با ضمانت نامه موجود نمی باشد";
		return false;
	}
	
	//---------------------------------------------------------
	ReportGenerator::BeginReport();
	$rpt = new ReportGenerator();
	$rpt->mysql_resource = $dt;
	
	$rpt->header = "سابقه تمدید ضمانت نامه";
	$rpt->width = 800;
	
	$rpt->addColumn("شماره ضمانت نامه", "RefRequestID");
	$rpt->addColumn("مبلغ ضمانت نامه", "amount", "ReportMoneyRender");
	$rpt->addColumn("تاریخ شروع", "StartDate", "ReportDateRender");
	$rpt->addColumn("تاریخ پایان", "EndDate", "ReportDateRender");
	
	echo "<center>";
	echo $rpt->generateReport();
	echo "<a href='WarrentyInquery.php?task=DownloadFile&ObjectID=".$_POST["RequestID"]."&NationalID=".$_POST["NationalID"]."'>دانلود تصویر ضمانتنامه </a>";
	echo "</center>";
	
	//require_once 'PrintWarrenty.php';
	
	
	return true;
}

function DownloadFile(){
	
	$RequestID = (int)$_REQUEST["ObjectID"];
	$NationalID = $_REQUEST["NationalID"];
	
	$dt = PdoDataAccess::runquery("select w.*,NationalID from WAR_requests w join BSC_persons using(PersonID) where RefRequestID=?",
			array($RequestID));
	if(count($dt) == 0)
	{
		$errorMsg =  "ضمانت نامه ایی با این شماره در سیستم صندوق ثبت نشده است";
		return false;
	}
	
	if($dt[0]["NationalID"] != $NationalID AND $dt[0]["orgNationalID"] != $NationalID)
	{
		$errorMsg = "کدملی / شناسه ملی وارد شده مطابق با ضمانت نامه موجود نمی باشد";
		return false;
	}
	$dt = PdoDataAccess::runquery("select RowID,PageNo,FileType,FileContent,DocumentID,ObjectID,DocDesc
	from DMS_DocFiles df join DMS_documents d using(DocumentID)
	where ObjectID=? AND d.DocType in(69,70) order by PageNo", array($RequestID));
	
	$FileContent = $dt[0]["FileContent"] . file_get_contents(getenv("DOCUMENT_ROOT") . "/storage/documents/" . 
	$dt[0]["RowID"] . "." . $dt[0]["FileType"]);

	$file = "file"; 
	if(array_search ($dt[0]["FileType"], array("jpg","jpeg","png","gif")) !== false)
		$file = "image/" . $dt[0]["FileType"];
	if($dt[0]["FileType"] == "pdf" )
		$file = "application/" . $dt[0]["FileType"];

	header('Content-disposition:inline; filename=file.' . $dt[0]["FileType"]);
	header('Content-type: '. $file);
	header('Pragma: no-cache');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header("Content-Transfer-Encoding: binary");

	echo $FileContent;
	die();	
}

$errorMsg = "";
if(!empty($_REQUEST["task"]) && $_REQUEST["task"] == "DownloadFile")
{
	DownloadFile();
	die();
}

if(!empty($_POST["RequestID"]))
{
	$result = ShowInfo($errorMsg);
	if($result)
		die();
}
?>
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" type="text/css" href="/generalUI/fonts/fonts.css" />
    <title></title>
    <style>
      
		.btn { display: inline-block; *display: inline; *zoom: 1; padding: 4px 10px 4px; margin-bottom: 0; font-size: 13px; line-height: 18px; color: #333333; text-align: center;text-shadow: 0 1px 1px rgba(255, 255, 255, 0.75); vertical-align: middle; background-color: #f5f5f5; background-image: -moz-linear-gradient(top, #ffffff, #e6e6e6); background-image: -ms-linear-gradient(top, #ffffff, #e6e6e6); background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#ffffff), to(#e6e6e6)); background-image: -webkit-linear-gradient(top, #ffffff, #e6e6e6); background-image: -o-linear-gradient(top, #ffffff, #e6e6e6); background-image: linear-gradient(top, #ffffff, #e6e6e6); background-repeat: repeat-x; filter: progid:dximagetransform.microsoft.gradient(startColorstr=#ffffff, endColorstr=#e6e6e6, GradientType=0); border-color: #e6e6e6 #e6e6e6 #e6e6e6; border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25); border: 1px solid #e6e6e6; -webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; -webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05); -moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05); cursor: pointer; *margin-left: .3em; }
		.btn:hover, .btn:active, .btn.active, .btn.disabled, .btn[disabled] { background-color: #e6e6e6; }
		.btn-large { padding: 9px 14px; font-size: 15px; line-height: normal; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; }
		.btn:hover { color: #333333; text-decoration: none; background-color: #e6e6e6; background-position: 0 -15px; -webkit-transition: background-position 0.1s linear; -moz-transition: background-position 0.1s linear; -ms-transition: background-position 0.1s linear; -o-transition: background-position 0.1s linear; transition: background-position 0.1s linear; }
		.btn-primary, .btn-primary:hover { text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25); color: #ffffff; }
		.btn-primary.active { color: rgba(255, 255, 255, 0.75); }
		.btn-primary { background-color: #4a77d4; background-image: -moz-linear-gradient(top, #6eb6de, #4a77d4); background-image: -ms-linear-gradient(top, #6eb6de, #4a77d4); background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#6eb6de), to(#4a77d4)); background-image: -webkit-linear-gradient(top, #6eb6de, #4a77d4); background-image: -o-linear-gradient(top, #6eb6de, #4a77d4); background-image: linear-gradient(top, #6eb6de, #4a77d4); background-repeat: repeat-x; filter: progid:dximagetransform.microsoft.gradient(startColorstr=#6eb6de, endColorstr=#4a77d4, GradientType=0);  border: 1px solid #3762bc; text-shadow: 1px 1px 1px rgba(0,0,0,0.4); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.5); }
		.btn-primary:hover, .btn-primary:active, .btn-primary.active, .btn-primary.disabled, .btn-primary[disabled] { filter: none; background-color: #4a77d4; }
		.btn-block { width: 100%; display:block; }

		* { -webkit-box-sizing:border-box; -moz-box-sizing:border-box; -ms-box-sizing:border-box; -o-box-sizing:border-box; box-sizing:border-box; }

		html { width: 100%; height:100%; overflow:hidden; }

		body { 
			width: 100%;
			height:100%;
			font-family: nazanin, sans-serif;
			font-size: 14px;
			font-weight: bold;
			/*background: #092756;
			background: -moz-radial-gradient(0% 100%, ellipse cover, rgba(104,128,138,.4) 10%,rgba(138,114,76,0) 40%),-moz-linear-gradient(top,  rgba(57,173,219,.25) 0%, rgba(42,60,87,.4) 100%), -moz-linear-gradient(-45deg,  #670d10 0%, #092756 100%);
			background: -webkit-radial-gradient(0% 100%, ellipse cover, rgba(104,128,138,.4) 10%,rgba(138,114,76,0) 40%), -webkit-linear-gradient(top,  rgba(57,173,219,.25) 0%,rgba(42,60,87,.4) 100%), -webkit-linear-gradient(-45deg,  #670d10 0%,#092756 100%);
			background: -o-radial-gradient(0% 100%, ellipse cover, rgba(104,128,138,.4) 10%,rgba(138,114,76,0) 40%), -o-linear-gradient(top,  rgba(57,173,219,.25) 0%,rgba(42,60,87,.4) 100%), -o-linear-gradient(-45deg,  #670d10 0%,#092756 100%);
			background: -ms-radial-gradient(0% 100%, ellipse cover, rgba(104,128,138,.4) 10%,rgba(138,114,76,0) 40%), -ms-linear-gradient(top,  rgba(57,173,219,.25) 0%,rgba(42,60,87,.4) 100%), -ms-linear-gradient(-45deg,  #670d10 0%,#092756 100%);
			background: -webkit-radial-gradient(0% 100%, ellipse cover, rgba(104,128,138,.4) 10%,rgba(138,114,76,0) 40%), linear-gradient(to bottom,  rgba(57,173,219,.25) 0%,rgba(42,60,87,.4) 100%), linear-gradient(135deg,  #670d10 0%,#092756 100%);
			filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#3E1D6D', endColorstr='#092756',GradientType=1 );*/
			background-image : url('icons/bg.jpg');
		}
		.mainPanel { 
			background-color: #EBFDFF;
			border-radius: 20px;
			height: 400px;
			left: 50%;
			margin: -230px 0 0 -230px;
			padding: 20px;
			position: absolute;
			top: 50%;
			width: 460px;
			box-shadow: 10px 10px 5px #aaaaaa;
		}
		.title {font-family: titr; font-size: 18px; color: #0F5163; font-weight: normal;
			   text-align: center; line-height: 30px; padding-bottom: 20px;}
		input { 
			width: 100%; 
			margin-bottom: 10px; 
			background: rgba(0,0,0,0.3);
			border: none;
			outline: none;
			padding: 10px;
			font-size: 13px;
			color: #fff;
			text-shadow: 1px 1px 1px rgba(0,0,0,0.3);
			border: 1px solid rgba(0,0,0,0.3);
			border-radius: 4px;
			box-shadow: inset 0 -5px 45px rgba(100,100,100,0.2), 0 1px 1px rgba(255,255,255,0.2);
			-webkit-transition: box-shadow .5s ease;
			-moz-transition: box-shadow .5s ease;
			-o-transition: box-shadow .5s ease;
			-ms-transition: box-shadow .5s ease;
			transition: box-shadow .5s ease;
			vertical-align: middle;
		}
		
		input:focus { box-shadow: inset 0 -5px 45px rgba(100,100,100,0.4), 0 1px 1px rgba(255,255,255,0.2); }
    </style>
	
	<script src='https://www.google.com/recaptcha/api.js'></script>
	
  </head>

  <body dir="rtl">
    <div class="mainPanel">
		<div class="title" >	استعلام ضمانت نامه <br><?= SoftwareName ?>
		<br></div>
		<center><font style="color:red; font-weight: bold"><?= $errorMsg ?></font></center>
		<form method="post" id="MainForm">
			شماره ضمانت نامه :
			<input type="text" name="RequestID" id="RequestID" value="<?= isset($_POST["RequestID"]) ? $_POST["RequestID"] : "" ?>" required="required"  />
			شناسه ملی / کد ملی ذینفع :
			<input type="text" name="NationalID" id="NationalID" value="<?= isset($_POST["NationalID"]) ? $_POST["NationalID"] : "" ?>" required="required" />
			
			<center><div class="g-recaptcha" data-sitekey="6LcDx0UUAAAAAGUHlh3jS9xpp1eHhV7G3t6nCxBV" style="margin-bottom: 10px"></div></center>
			
			<button type="submit" class="btn btn-primary btn-block btn-large ">استعلام</button>
		</form>
	</div>
  </body>
</html>