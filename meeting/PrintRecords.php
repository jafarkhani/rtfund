<?php
//-----------------------------
//	Date		: 97.11
//-----------------------------
require_once '../header.inc.php';
require_once './meeting.class.php';
require_once '../framework/baseInfo/baseInfo.class.php';
require_once inc_reportGenerator;

$MeetingID = !empty($_REQUEST["MeetingID"]) ? (int)$_REQUEST["MeetingID"] : "";
if(empty($MeetingID))
	die();

$MeetingObj = new MTG_meetings($MeetingID);

//..........................................

$rpt = new ReportGenerator();
$rpt->mysql_resource = MTG_MeetingRecords::Get("and MeetingID=?", array($MeetingID));
$rpt->header_color = "white";
$rpt->header_alignment = "center";
$rpt->rowNumber = false;

function titleRender($row,$value){
	return "<span dir=ltr>" . $value . "<br>" . hebrevc($row["details"]) . "</div>";
}
$col = $rpt->addColumn("خلاصه مصوبات", "subject");
$col->renderFunction = "titleRender";

$col = $rpt->addColumn("مسئول پیگیری یا اجرا", "fullname");
$col->align = "center";
$col = $rpt->addColumn("مهلت انجام", "FollowUpDate", "ReportDateRender");
$col->align = "center";

function emptyRender($row, $value){
	return "";
}
$col = $rpt->addColumn("شرح مستندات", "subject", "emptyRender");
$col->align = "center";

//..........................................

$presents = MTG_MeetingPersons::Get(" AND MeetingID=? AND IsPresent='YES'", array($MeetingID));
$presents = $presents->fetchAll();
$absents = MTG_MeetingPersons::Get(" AND MeetingID=? AND IsPresent='NO'", array($MeetingID));
$absents = $absents->fetchAll();
?>
<html>
	<meta content='text/html; charset=utf-8' http-equiv='Content-Type'/>
	<link rel="stylesheet" type="text/css" href="/generalUI/fonts/fonts.css" />
	<style>
		@media print {
			#spacer {height: 130px;} /* height of footer + a little extra */
			#footer {
			  position: fixed;
			  bottom: 0;
			  width:20cm;
			}
		  }
		.meetingInfo{width:20cm;height : 100%;border-collapse: collapse}
		td { font-family: Nazanin; font-size: 12pt; line-height: 25px; }
		#footer {width:20cm;}
		#footer td{ border: 1px solid black; text-align: center}
		#presents td{text-align: right; border-color: white;}
	</style>	
	<body dir="rtl">
		<center>
			<table class="meetingInfo" cellpadding="0" cellspacing="0">
				<thead>
				<tr style="height:70px;border: 1px solid black">
					<td align="right" style="width:60px;">
						<img  src="/framework/icons/logo.jpg" style="width:60px">
					</td>
					<td align="center" style="font-family: titr;font-size: 14px;">
						<b>به نام خداوند جان و خرد</b>
						<br>
						صورتجلسه <?= $MeetingObj->_MeetingTypeDesc ?>
						<br>
						<?= SoftwareName ?>
					</td>
					<td style="width:60px;line-height: 25px;"></td>
				</tr>
				</thead>
				<!------------------------------------------------------>
				<tr style="height:65px;border: 1px solid black">
					<td colspan="3">
						<table width="100%">
							<tr>
								<td>شماره جلسه:<b> <?= $MeetingObj->MeetingNo ?></b></td>
								<td>تاریخ جلسه: <b><?= DateModules::miladi_to_shamsi($MeetingObj->MeetingDate) ?></b></td>
								<td>ساعت شروع: <b><?= substr($MeetingObj->StartTime,0,5) ?></b></td>
								<td>ساعت پایان: <b><?= substr($MeetingObj->EndTime,0,5) ?></b></td>
							</tr>
							<tr style="height:30px;">
								<td colspan="4">مکان برگزاری: <b><?= $MeetingObj->place ?></b></td>
							</tr>	
						</table>
					</td>
				</tr>
				<!------------------------------------------------------>			
				<tr>
					<td id="content" colspan="3" style="vertical-align: top;">
						<?= $rpt->generateReport() ?>
					</td>
				</tr>
				<!------------------------------------------------------>
				<tfoot>
					<tr>
						<td id="spacer" colspan="3"></td>
					  </tr>
				</tfoot>
			</table>
			<div id="footer">
				<table width="100%" style="border-collapse: collapse">
					<tr style="height: 30px; background-color: #eee">
						<td colspan="2">نام و نام خانوادگی و امضا حاضرین</td>
						<td>غایبین</td>
					</tr>
					<tr style="height: 100px">
						<td style="width:100px;background-color: #eee">اعضای جلسه</td>
						<td>
							<table id="presents" width="100%">
							<?
							for($i=0; $i<count($presents); $i++)
							{
								if($i % 2 == 0)
									echo "<tr>";
								echo "<td>" . $presents[$i]["fullname"] . "</td>";
								if($i % 2 != 0)
									echo "</tr>";
							}
							?>
							</table>
						</td>
						<td rowspan="3">
							<?
							foreach($absents as $row)
								echo $row["fullname"] . "<br>";
							?>
						</td>
					</tr>
					<tr>
						<td style="background-color: #eee">مدیر عامل</td>
						<td style="text-align: right;">
							<?
								$personObj = BSC_jobs::GetModirAmelPerson();
								echo $personObj->_fullname;
							?>
						</td>
					</tr>
					<tr>
						<td style="background-color: #eee">دبیر جلسه</td>
						<td style="text-align: right;"><?= $MeetingObj->_secretaryName ?></td>
					</tr>
				</table>
			</div>
		</center>
	</body>
</html>