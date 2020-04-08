<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.10
//-----------------------------
require_once '../../header.inc.php';
require_once 'letter.class.php';

$LetterID = !empty($_REQUEST["LetterID"]) ? $_REQUEST["LetterID"] : "";
if(empty($LetterID))
	die();

$LetterObj = new OFC_letters($LetterID);

$letterYear = substr(DateModules::miladi_to_shamsi($LetterObj->LetterDate),0,4);
//..............................................................................
$content = "<b><span style=font-size:11pt;font-family:Titr>";
$dt = PdoDataAccess::runquery("
	select  p2.sex,FromPersonID,p3.PersonSign signer,p1.PersonSign regSign,
		if(p1.IsReal='YES',concat(p1.fname, ' ', p1.lname),p1.CompanyName) RegPersonName ,
		if(p2.IsReal='YES',concat(p2.fname, ' ', p2.lname),p2.CompanyName) ToPersonName ,
		concat(p3.fname, ' ', p3.lname) SignPersonName ,
		po.PostName,
		s.IsCopy
	from OFC_send s
		join OFC_letters l using(LetterID)
		join BSC_persons p1 on(l.PersonID=p1.PersonID)
		join BSC_persons p2 on(s.ToPersonID=p2.PersonID)
		left join BSC_persons p3 on(l.SignerPersonID=p3.PersonID)
		left join BSC_posts po on(l.SignPostID=po.PostID)
	where LetterID=? 
	order by SendID
	", array($LetterID));
if($LetterObj->LetterType == "INNER")
{
	foreach($dt as $row)
	{
		if($row["FromPersonID"] != $LetterObj->PersonID || $row["IsCopy"] == "YES")
			continue;	
		$content .= $row["sex"] == "MALE" ? "جناب آقای " : "سرکار خانم ";
		$content .= $row['ToPersonName'] . "<br>";
	}

	$content .= " موضوع : " . $LetterObj->LetterTitle . "<br><br></span></b>";
	$content .= "<div style=text-align:justify;text-justify:inter-word;>" . str_replace("\r\n", "", $LetterObj->context) . "</div>";
	
	if(isset($_POST["sign"]))
		$sign = $dt[0]["regSign"] != "" ? "background-image:url('" .
			data_uri($dt[0]["regSign"],'image/jpeg') . "')" : "";
	else
		$sign = "";
	
	$content .= "<table width=100%><tr><td><div class=signDiv style=\"" . $sign . "\"><b>" . 
			$dt[0]["RegPersonName"] . "</b><br>" . $dt[0]["PostName"] . "</div></td></tr></table>";
}
if($LetterObj->LetterType == "OUTCOME")
{
	$content .= $LetterObj->organization  . "<br>" . $LetterObj->OrgPost . "<br>" ;
	$content .= "موضوع: " . $LetterObj->LetterTitle . "<br><br></b>";
	$content .= str_replace("\r\n", "", $LetterObj->context);
	
	if(isset($_POST["sign"]))
		$sign = $LetterObj->IsSigned == "YES" ? "background-image:url('" .
			data_uri($dt[0]["signer"],'image/jpeg') . "')" : "";
	else
		$sign = "";
	$content .= "<table width=100%><tr><td><div class=signDiv style=\"" . $sign . "\"><b>" . 
			$dt[0]["SignPersonName"] . "</b><br>" . $dt[0]["PostName"] . "</div></td></tr></table>";
}
foreach($dt as $row)
{
	if($row["FromPersonID"] != $LetterObj->PersonID || $row["IsCopy"] == "NO")
		continue;	
	$content .= "<b>" . "رونوشت: " . ($row["sex"] == "MALE" ? "جناب آقای " : "سرکار خانم ") . 
			$row['ToPersonName'] . "<br></b>";
}

if($LetterObj->OuterCopies != "")
{
	$LetterObj->OuterCopies = str_replace("\r\n", " , ", $LetterObj->OuterCopies);
	$content .= "<br><b>رونوشت: " . $LetterObj->OuterCopies . "</b><br>";
}
?>
<html>
	<meta content='text/html; charset=utf-8' http-equiv='Content-Type'/>
	<link rel="stylesheet" type="text/css" href="/generalUI/fonts/fonts.css" />
	<style media="print">
		.noPrint {display:none;}
	</style>
	<style>
		.div-sarbarg { background-image: url('../bg.jpg');background-size: 800px 1100px;}
		@media print {
			.div-sarbarg{
				background-image: none;
			}
		}
		
		.div-sarbargA5 { background-image: url('../bgA5.jpg');}
		@media print {
			.div-sarbargA5{
				background-image: none;
			}
		}
		
		.header td{background-color: #cccccc; font-weight: bold;size: 12px;}
		td { font-family: Nazanin; font-size: 12pt; line-height: 25px; padding: 3px;}
		.signDiv {
			height: 140px;
			float : left;
			font-size:11pt;
			font-family:Titr;
			background-repeat: no-repeat; 
			width: 200px; 			
			text-align: center; 
			padding-top: 60px;
		}
		table { page-break-inside:auto }
		tr    { /*page-break-inside:avoid;*/ page-break-after:auto }
		thead { display:table-header-group }
		tfoot { display:table-footer-group }
		
		.tripleFooter {
			height: 150px;
			width:46%;
			border : 1px solid black;
			border-radius: 12px;
			padding : 10px;
			
		}
	</style>	
	<body dir="rtl">
		<center>
			<div class="noPrint" style="width:800px;font-family: Nazanin; font-size: 12pt;">
				<form method="post" id="mainForm">
					<fieldset>
						<legend>تنظیمات چاپ نامه</legend>
						<input onchange="document.forms.mainForm.submit()" name="sign" 
							<?= isset($_POST["sign"]) ? "checked" : "" ?>
							type="checkbox" > چاپ امضاء
						&nbsp;&nbsp;&nbsp;
						<input onchange="document.forms.mainForm.submit()" name="sarbarg" 
								<?= isset($_POST["sarbarg"]) ? "checked" : "" ?>
							type="checkbox"> چاپ روی برگه سربرگ دار			 A4
						&nbsp;&nbsp;&nbsp;
						<input onchange="document.forms.mainForm.submit()" name="sarbargA5" 
								<?= isset($_POST["sarbargA5"]) ? "checked" : "" ?>
							type="checkbox"> چاپ روی برگه سربرگ دار			 A5
						&nbsp;&nbsp;&nbsp;
						<input onchange="document.forms.mainForm.submit()" name="triplePart" 
								<?= isset($_POST["triplePart"]) ? "checked" : "" ?>
							type="checkbox"> چاپ سه قسمتی
					</fieldset>
				</form>
			</div>
			<!----------------------------------------------------------------->
			<? if(isset($_POST["sarbarg"])){ ?>
				<div class='div-sarbarg' style="width:800px;height:1100px;">
				<table style="/*width:19cm;*/height:100%">
					<thead>
					<tr style="height:150px">
						<td align="center">
						</td>
						<td align="center">
						</td>
						<td style="width:100px;line-height: 32px; vertical-align:top; padding-top:0px">
						<!--<td style="width:80px;line-height: 31px; vertical-align:top; padding-top:32px">-->
							<br><b><?= DateModules::miladi_to_shamsi($LetterObj->LetterDate) ?><br>
							 <span dir=ltr><?= $letterYear . "-" . $LetterObj->LetterID ?></span>
							 <br><?= OFC_letters::HasAttach($LetterObj->LetterID) ? "دارد" : "ندارد" ?></b>
						</td>
					</tr>
					</thead>
					<tr>
						<td colspan="3" style="padding-right:50px;padding-left: 50px;vertical-align: top;
							text-align: justify;text-justify: inter-word;">
							<br><br>
							<?= $content ?>
							<br>
						</td>
					</tr>
				</table>
			</div>
			<!----------------------------------------------------------------->
			<?}else if(isset($_POST["sarbargA5"])){ ?>
				<div class='div-sarbargA5' style="width:140mm;height:190mm;">
				<table style="width:140mm;height:100%">
					<thead>
					<tr style="height:150px">
						<td align="center">
						</td>
						<td align="center">
						</td>
						<td style="width: 85px;line-height: 21px;vertical-align: top;padding-top: 28px;">
							<b><?= DateModules::miladi_to_shamsi($LetterObj->LetterDate) ?><br>
							 <span dir=ltr><?= $letterYear . "-" . $LetterObj->LetterID ?></span>
							 <br><?= OFC_letters::HasAttach($LetterObj->LetterID) ? "دارد" : "ندارد" ?></b>
						</td>
					</tr> 
					</thead>
					<tr>
						<td colspan="3" style="padding-right:15px;padding-left: 15px;vertical-align: top;
							text-align: justify;text-justify: inter-word;">
							<?= $content ?>
							<br>
						</td>
					</tr>
				</table>
			</div>
			<!----------------------------------------------------------------->
			<?}else if(isset($_POST["triplePart"])){?>
			<div style="width:20cm;height:1000px;" align="center">
				<table style="width:100%;height:100%">
					<thead>
					<tr style="height:150px">
						<td align="center" style="width:200px;">
							<img  src="/framework/icons/logo.jpg" style="width:120px">
						</td>
						<td align="center" style="font-family: titr;font-size: 14px;">
							<b>به نام خداوند جان و خرد</b>
						</td>
						<td style="width:200px;line-height: 25px;">
						شماره نامه :<b><?= "<span dir=ltr>" . $letterYear . "-" . $LetterObj->LetterID."</span>" ?></b>
						<br>تاریخ نامه: <b><?= DateModules::miladi_to_shamsi($LetterObj->LetterDate) ?></b>
						<?if($LetterObj->LetterType == "INCOME"){?> 
						<br>شماره نامه وارده: <b><?= $LetterObj->InnerLetterNo ?></b>
						<br>تاریخ نامه وارده: <b><?= DateModules::miladi_to_shamsi($LetterObj->InnerLetterDate)?></b>
						<?}?>
						<?if($LetterObj->RefLetterID != ""){
							$refObj = new OFC_letters($LetterObj->RefLetterID);
							$RefletterYear = substr(DateModules::miladi_to_shamsi($refObj->LetterDate),0,4);
							echo "<br>عطف به نامه: <b><span dir=ltr>" . 
								$RefletterYear . "-" . $LetterObj->RefLetterID. "</span></b>";
						}
						?>
						
						</td>
					</tr>
					</thead>
					<tr>
						<td colspan="3" style="padding-right:50px;padding-left: 50px;vertical-align: top;
							text-align: justify;text-justify: inter-word;">
							<?= $content ?>
							<br>
						</td>
					</tr>
					<tfoot>
					<tr style="height:400px;vertical-align: top">
						<td colspan="3" style="padding-right:30px;padding-left: 30px;">
							<br><hr><br><br>
							<div style="width:100%">
								<div style="float:right;" class="tripleFooter">
									<div style="float:right;width:20%;font-family: titr">گیرنده: </div>
									<div style="float:left;width:80%;">
										<?= hebrevc($LetterObj->PostalAddress) ?></div>
								</div>
								<div style="float:left;" class="tripleFooter">
									<div style="float:right;width:18%;font-family: titr">فرستنده: </div>
									<div style="float:left;width:82%;font-size: 11pt !important;">
										<?= SoftwareName ?><br>
										<?= OWNER_ADDRESS ?><br>
									</div>
								</div>
							</div>
							<br><br><br><br><br><br><br><br><br><br><hr>
						</td>
					</tr>
					</tfoot>
				</table>
			</div>
			<!----------------------------------------------------------------->
			<?}else{?>
				<div>
				<table style="width:19cm;height:100%">
					<thead>
					<tr style="height:150px">
						<td align="center" style="width:200px;">
							<img  src="/framework/icons/logo.jpg" style="width:120px">
						</td>
						<td align="center" style="font-family: titr;font-size: 14px;">
							<b>به نام خداوند جان و خرد</b>
						</td>
						<td style="width:200px;line-height: 25px;">
						شماره نامه:<b> <?= "<span dir=ltr>" . $letterYear . "-" . $LetterObj->LetterID."</span>" ?></b>
						<br>تاریخ نامه: <b><?= DateModules::miladi_to_shamsi($LetterObj->LetterDate) ?></b>
						<?if($LetterObj->LetterType == "INCOME"){?> 
						<br>شماره نامه وارده: <b><?= $LetterObj->InnerLetterNo ?></b>
						<br>تاریخ نامه وارده: <b><?= DateModules::miladi_to_shamsi($LetterObj->InnerLetterDate)?></b>
						<?}?>
						<?if($LetterObj->RefLetterID != ""){
							$refObj = new OFC_letters($LetterObj->RefLetterID);
							$RefletterYear = substr(DateModules::miladi_to_shamsi($refObj->LetterDate),0,4);
							echo "<br>عطف به نامه: <b><span dir=ltr>" . 
								$RefletterYear . "-" . $LetterObj->RefLetterID. "</span></b>";
						}
						?>
						
						</td>
					</tr>
					</thead>
					<tr>
						<td colspan="3" style="padding-right:50px;padding-left: 50px;vertical-align: top;
							text-align: justify;text-justify: inter-word;">
							<br><br>
							<?= $content ?>
							<br>
						</td>
					</tr>
					<tfoot>
					<tr style="height:150px;">
						<td colspan="3" style="padding-right:30px;padding-left: 30px;">
							<hr>
							<b>نشانی:</b><br>
							<b>شعبه پارک علم و فناوری خراسان: </b> مشهد، کیلومتر 12 جاده قوچان، روبروی شیر پگاه
							<b>تلفن: 5003441 - فکس: 5003409 </b>
							<br>
							<b>شعبه دانشگاه فردوسی مشهد: </b>پردیس، درب غربی (ورودی شهید باهنر) 
							<b>تلفن: 38837392 - فکس: 38837392</b>
							<br>سایت: <b>www.krrtf.ir</b>
							<br>ایمیل: <b>krfn.ir@gmail.com</b>
						</td>
					</tr>
					</tfoot>
				</table>
			</div>
			<?}?>
			
		</center>
	</body>
</html>