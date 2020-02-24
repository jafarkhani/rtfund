<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 95.06
//-----------------------------

require_once '../../header.inc.php';
require_once 'request.class.php';
require_once inc_CurrencyModule;

$ReqObj = new WAR_requests($_REQUEST["RequestID"]);
if($ReqObj->RequestID == "")
	die();

if($ReqObj->StatusID != WAR_STEPID_CONFIRM && empty($_POST["ReadOnly"]))
{
	/*echo '<META http-equiv=Content-Type content="text/html; charset=UTF-8" >';
	echo "<br><br><h2><center> فقط زمانی قادر به چاپ ضمانتنامه می باشید که تایید نهایی شده باشد </center></h2>";
	die();*/
}

$dt = WAR_requests::SelectAll("r.RequestID=?" , array($ReqObj->RequestID));
if($_SESSION["USER"]["UserName"] == "admin")
{
	//echo PdoDataAccess::GetLatestQueryString();
	//print_r(ExceptionHandler::PopAllExceptions());
}
$record = $dt->fetch();
$record["LetterDate"] = DateModules::miladi_to_shamsi($record["LetterDate"]);
$record["StartDate"] = DateModules::miladi_to_shamsi($record["StartDate"]);
$record["EndDate"] = DateModules::miladi_to_shamsi($record["EndDate"]);
$record["amount_char"] = CurrencyModulesclass::CurrencyToString($record["amount"]);
$record["amount"] = number_format($record["amount"]);
$record["duration_month"] = DateModules::GetDiffInMonth($record["StartDate"], $record["EndDate"]);
$record["EndDate_char"] = DateModules::DateToString(DateModules::miladi_to_shamsi($record["EndDate"]));

$content = file_get_contents("prints/" . $ReqObj->TypeID . ".html");
$contentArr = explode("#", $content);
$content = "";
for ($i = 0; $i < count($contentArr); $i++) {
	if ($i % 2 == 0) 
	{
		$content .= $contentArr[$i];
		continue;
	}
		
	$content .= $record[ $contentArr[$i] ];
}

?>
<html>
	<head>
		<META http-equiv=Content-Type content="text/html; charset=UTF-8" >
		<link rel="stylesheet" type="text/css" href="/generalUI/fonts/fonts.css" /></head>
		<style>
		@media print {
			.pageBreak {page-break-before:always;height:1px;}
		}
		.page {
			width:800px;
			height:1100px;
			background-image: url('prints/bg.jpg');
			background-size: 800px 1100px;
		}
		@media print {
			.page{
				background-image: none;
			}
		} 
			
		.title {
			font-family: titr; font-size: 18px;
			margin-right: 80px;
			line-height: 55px;
			text-align: right;
			padding-right: 300px;
		}
		
		.context {
			text-align: justify;
			padding-left: 60px;
			padding-right: 60px;
			font-family: nazanin; 
			font-size: 18px;
		}
		.sign {
			width: 80%; 
			font-family: titr;
			margin-left: 20px;
		}
		</style>
	</head>
	<body dir="rtl">
	<center> 
		<div class=page>
			<div class=title style="height: 220px">
				<br>
				<?= $ReqObj->_TypeDesc ?>
				<br><?= DateModules::miladi_to_shamsi($ReqObj->StartDate) ?>
				<div style="line-height: 15px;padding-right:20px"><?= $ReqObj->RefRequestID ?></div>				
			</div>
			<div style="font-family: titr;font-size: 15px;text-align: left;margin-left: 50px;">کد سپاص : 
				<?= $ReqObj->SepasCode ?>
			</div>
			<div class='context' style="height: 600px">
				<?= $content ?>
				<center><div class='sign' align="center"><br><br>
					صندوق پژوهش و فناوری خراسان رضوی<br><br>
					<div style="float: right" >مدیر عامل  </div>
					<div style="float: left" >عضو هیات مدیره  </div>
				</div></center>
			</div>
			
		</div>
	</center>
	</body>
</html>