<?php
//-----------------------------
//	Programmer	: 
//	Date		: 1395.08
//-----------------------------

require_once '../../header.inc.php';
require_once '../../office/dms/dms.class.php';
require_once 'request.class.php';
require_once inc_reportGenerator;

$RequestID = $_REQUEST["RequestID"];
$ReqObj = new WAR_requests($RequestID);

$temp = DMS_documents::SelectAll("ObjectType='warrenty' AND ObjectID=? AND b1.param1=1", array($RequestID));
$SumAmount = 0;
for($i=0; $i<count($temp); $i++)
{
	$temp[$i]["NO"] = "";
	$temp[$i]["AMOUNT"] = "";
	$temp[$i]["paramValues"] = "";

	$dt = PdoDataAccess::runquery("select * from DMS_DocParamValues join DMS_DocParams using(ParamID)
		where DocumentID=?", array($temp[$i]["DocumentID"]));
	foreach($dt as $row)
	{
		$value = $row["ParamValue"];
		if($row["KeyTitle"] == "no")
		{
			$temp[$i]["NO"] = $value;
			continue;
		}
		if($row["KeyTitle"] == "amount")
		{
			$temp[$i]["AMOUNT"] = $value;
			continue;
		}		
		
 		if($row["ParamType"] == "currencyfield")
			$value = number_format($value*1);
		$temp[$i]["paramValues"] .= "<div style='float:right;padding-left:10px' >" .
				$row["ParamDesc"] . " : " . $value . "</div>";

		if($row["KeyTitle"] == "amount")
			$SumAmount += $row["ParamValue"]*1;
	}
	if($temp[$i]["paramValues"] != "")
		$temp[$i]["paramValues"] = substr($temp[$i]["paramValues"], 0 , strlen($temp[$i]["paramValues"])-4);
}
if(count($temp) > 0)
{
	$rpt = new ReportGenerator();
	$rpt->mysql_resource = $temp;
	$rpt->headerContent = "
		<table id=header width=100%>
			<tr>
				<td width=45%>شماره ضمانت نامه : <b>" .$ReqObj->RequestID . "</b>
					<br>نوع درخواست : <b>" . $ReqObj->_TypeDesc . "</b>
					<br>ضمانت خواه : <b>" . $ReqObj->_fullname . "</b>
				</td>
				<td width=20% style='font-family:titr'>اسناد ضمانتی</td>
				<td width=45% align=left>تاریخ صدور قبض : <b>" . DateModules::shNow() . "</b></td>
			</tr>
		</table>
	";

	$rpt->addColumn("سند", "DocTypeDesc");
	$rpt->addColumn("سریال", "NO");
	$rpt->addColumn("مبلغ", "AMOUNT", "ReportMoneyRender");
	$rpt->addColumn("سایر اطلاعات", "paramValues");
}

?>
<meta content='text/html; charset=utf-8' http-equiv='Content-Type'/>
<link rel="stylesheet" type="text/css" href="/generalUI/fonts/fonts.css" />
<style>
	#header {border : 1px solid black;background-color: #ececec;}
	 td { font-family: nazanin; font-size: 16px !important}
	 font { font-family: nazanin; font-size: 16px !important}
	.footer {
		font-family: nazanin;
		font-weight: bold;
		font-size: 16px;
		border : 1px solid black;
		padding-right : 20px;
		float:left;
		width : 500px;
	}
</style>
<body dir="rtl">
	<?if(count($temp) >0) {
			$rpt->generateReport(); ?>
			<div style="width: 100%; padding-top: 20px; padding-bottom: 20px; height: 120px;">
				<div class="footer"> <br>جمع کل : <?= number_format($SumAmount) ?> ریال 
					<div style="float:left;width: 250px">نام و نام خانوادگی :<br>امضاء</div><br><br><br>	
				</div>
			</div>
	<?}
	else
		echo "<center>فاقد مدارک</center>";
	?>
</body>