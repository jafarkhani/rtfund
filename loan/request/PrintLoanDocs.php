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
$ReqObj = new LON_requests($RequestID);
$type = $_REQUEST["type"];

if($type == "tazmin")
{
	$temp = DMS_documents::SelectAll("ObjectType='loan' AND ObjectID=? AND b1.param1=1", array($RequestID));
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
					<td width=45%>کد وام : <b>" .$ReqObj->RequestID . "</b>
						<br>نوع وام : <b>" . $ReqObj->_LoanDesc . "</b>
						<br>وام گیرنده : <b>" . $ReqObj->_LoanPersonFullname . "</b>
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
}
//..............................................................................
if($type == "checks")
{
	$dt = PdoDataAccess::runquery("
		select p.*,i.*,
				b.BankDesc, 
				bi.InfoDesc PayTypeDesc, 
				t.InfoDesc ChequeStatusDesc

			from LON_BackPays p
			left join BaseInfo bi on(bi.TypeID=6 AND bi.InfoID=p.PayType)
			left join ACC_IncomeCheques i using(IncomeChequeID)
			left join ACC_banks b on(ChequeBank=BankID)
			left join BaseInfo t on(t.TypeID=4 AND t.InfoID=i.ChequeStatus)
		where p.RequestID=? AND PayType=9",	array($ReqObj->RequestID));

	//print_r(ExceptionHandler::PopAllExceptions());
	$SumCheques = 0;
	foreach($dt as $row)
		$SumCheques += $row["PayAmount"]*1;

	if(count($dt) > 0)
	{
		$rpt2 = new ReportGenerator();
		$rpt2->mysql_resource = $dt;
		$rpt2->headerContent = "
			<table id=header width=100%>
				<tr>
					<td width=20% style='font-family:titr'>چک های اقساط</td>
					<td width=45% align=left>تاریخ صدور قبض : <b>" . DateModules::shNow() . "</b></td>
				</tr>
			</table>
		";

		function dateRender($row, $value){ return DateModules::miladi_to_shamsi($value);}
		function amountRender($row, $value){ return number_format($value);}

		$rpt2->addColumn("تاریخ چک", "PayDate", "dateRender");
		$rpt2->addColumn("نام بانک", "BankDesc");
		$rpt2->addColumn("شعبه", "ChequeBranch");
		$rpt2->addColumn("شماره چک", "ChequeNo");
		$col = $rpt2->addColumn("مبلغ چک", "PayAmount", "amountRender");
		$col->EnableSummary();
	}
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
	<table style="border : 1px solid black; width:100%; padding:4px">
		<tr>
			<td>شماره وام : <b><?= $ReqObj->RequestID ?></b></td>
			<td>نوع وام : <b><?= $ReqObj->_LoanDesc ?></b></td>
		</tr>
		<tr>
			<td>وام گیرنده : <b><?= $ReqObj->_LoanPersonFullname ?></b></td>
			<td>معرف : <b><?= $ReqObj->_ReqPersonFullname ?></b></td>
		</tr>
		<tr>
			<td>شعبه اخذ وام : <b><?= $ReqObj->_BranchName ?></b></td>
			<td></td>
		</tr>
	</table>
	
	<? if($type == "tazmin"){ 
		if(count($temp) >0) {
			$rpt->generateReport(); ?>
			<div style="width: 100%; padding-top: 20px; padding-bottom: 20px; height: 120px;">
				<div class="footer"> <br>جمع کل : <?= number_format($SumAmount) ?> ریال 
					<div style="float:left;width: 250px">نام و نام خانوادگی :<br>امضاء</div><br><br><br>	
				</div>
			</div>
	<?}
	else
		echo "<center>فاقد مدارک</center>";
	}
	
	if($type == "checks"){
		if(count($dt) > 0){
			$rpt2->generateReport(); ?>
			<div style="width: 100%; padding-top: 20px; padding-bottom: 20px; height: 120px;">
				<div class="footer">
					<br>خواهشمند است وجه چک های فوق را پس از وصول به حساب <?=SoftwareName ?> منظور دارید.
					<br><div style="float:left;width: 250px">نام و نام خانوادگی :<br>امضاء</div><br><br><br>	
				</div>
			</div>
	<?}
	else
		echo "<center>فاقد مدارک</center>";
	}?>
</body>