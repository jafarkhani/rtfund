<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	97.07
//-------------------------
//ini_set("display_errors", "On");

require_once '../header.inc.php';
require_once inc_reportGenerator;
require_once '../loan/request/request.class.php';

if(isset($_REQUEST["v"]))
{
	switch($_REQUEST["v"])
	{
		case "admin":
			sys_config::$db_server['database'] = "sajakrrt_oldcomputes";
			PdoDataAccess::$DB = null;
			break;
		
		case "oldsaja":
			sys_config::$db_server['database'] = "sajakrrt_rtfund3"; 
			PdoDataAccess::$DB = null;
			break;
	}
}

showReport();

function showReport(){
	
	$dt = PdoDataAccess::runquery("select c1 RequestID from sajakrrt_rtfund.aaa order by c1");
	
	$returnArr = array();
	foreach($dt as $row)
	{
		$RequestID = $row["RequestID"];
		
		sys_config::$db_server['database'] = "sajakrrt_rtfund";
		PdoDataAccess::$DB = null;
		$ComputeArr = LON_Computes::ComputePayments($RequestID);
		$TotalRemain_rtfund = LON_Computes::GetTotalRemainAmount($RequestID, $ComputeArr);
		//-------------------------------------------------
		sys_config::$db_server['database'] = "sajakrrt_oldcomputes";
		PdoDataAccess::$DB = null;
		$ComputeArr = LON_Computes::ComputePayments($RequestID);
		$TotalRemain_admin = LON_Computes::GetTotalRemainAmount($RequestID, $ComputeArr);
		//-------------------------------------------------
		sys_config::$db_server['database'] = "sajakrrt_rtfund2";
		PdoDataAccess::$DB = null;
		$ComputeArr = LON_Computes::ComputePayments($RequestID);
		$TotalRemain_rtfund2= LON_Computes::GetTotalRemainAmount($RequestID, $ComputeArr);
		//-------------------------------------------------
		$returnArr[] = array(
			"RequestID" => $RequestID,
			"TotalRemain_admin" => $TotalRemain_admin,
			"TotalRemain_rtfund" => $TotalRemain_rtfund,
			"TotalRemain_rtfund2" => $TotalRemain_rtfund2
		);
	}

	$rpg = new ReportGenerator();
	$rpg->mysql_resource = $returnArr;

	function adminRender($row, $value){
		return "<a href=../loan/report/LoanPayment.php?show=tru&v=admin&RequestID=" . $row["RequestID"] .
			" target=blank >" . $value . "</a>";
	}
	function sajaRender($row, $value){
		return "<a href=../loan/report/LoanPayment.php?show=tru&v=saja&RequestID=" . $row["RequestID"] .
			" target=blank >" . $value . "</a>";
	}
	function oldsajaRender($row, $value){
		return "<a href=../loan/report/LoanPayment.php?show=tru&v=oldsaja&RequestID=" . $row["RequestID"] .
			" target=blank >" . $value . "</a>";
	}

	$col = $rpg->addColumn("شماره وام", "RequestID");
	$col = $rpg->addColumn("مانده ادمین", "TotalRemain_admin", "adminRender");
	$col->EnableSummary();
	$col = $rpg->addColumn("مانده سجا", "TotalRemain_rtfund", "sajaRender");
	$col->EnableSummary();
	$col = $rpg->addColumn("مانده سجا قدیم", "TotalRemain_rtfund2", "oldsajaRender");
	$col->EnableSummary();
	
	function Diff1Render($row)
	{
		$v = $row["TotalRemain_admin"]*1 - $row["TotalRemain_rtfund"]*1;
		return "<font " . ($v<0 ? "color:red" : "") . ">" . number_format($v) . "</font>";
	}
	function Diff2Render($row)
	{
		$v = $row["TotalRemain_rtfund"]*1 - $row["TotalRemain_rtfund2"]*1;
		return "<font " . ($v<0 ? "color:red" : "") . ">" . number_format($v) . "</font>";
	}
	$col = $rpg->addColumn("اختلاف ادمین و سجا", "", "Diff1Render");
	$col->EnableSummary();
	$col = $rpg->addColumn("اختلاف سجا جدید و قدیم", "", "Diff2Render");
	$col->EnableSummary();
	
	if(!$rpg->excel)
	{
		BeginReport();
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
					گزارش مانده وام ها
				</td>
				<td width='200px' align='center' style='font-family:tahoma;font-size:11px'>تاریخ تهیه گزارش : " 
			. DateModules::shNow() . "<br>";
		echo "</td></tr></table>";
		
	}
	$rpg->generateReport();
	die();
}
?>
