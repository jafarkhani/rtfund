<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------
 
require_once '../../header.inc.php';
require_once '../docs/import.data.php';

$IsFlow = false;
if(isset($_REQUEST["IsFlow"]) && $_REQUEST["IsFlow"] == "true")
{
	$ToDate = DateModules::Now();
	$IsFlow = true;
}
else
	$ToDate = DateModules::shamsi_to_miladi($_REQUEST["ToDate"]);

$CostID = $_REQUEST["CostID"];

echo '<META http-equiv=Content-Type content="text/html; charset=UTF-8" ><body dir="rtl">';
echo '<link rel="stylesheet" type="text/css" href="/generalUI/fonts/fonts.css" />';
echo "<style>
		table { border-collapse:collapse; width:100%}
		#header {background-color : blue; color : white; font-weight:bold}
		#footer {background-color : #bbb;}
		td {font-family : nazanin; font-size:16px; padding:4px}
	</style>";

$TraceArr = ComputeDepositeProfit($ToDate, 
		array(array("TafsiliID" => $_REQUEST["TafsiliID"],"CostID" => $CostID)), 
		true, $IsFlow);
$dataTable = $TraceArr;

echo "<table></table>";
echo "<table border=1>
	<tr id=header>
		<td>تاریخ</td>
		<td>شرح</td>
		<td>مبلغ گردش</td>
		<td>مانده حساب</td>
		<td>تعداد روز</td>
		<td>سقف مبلغ جهت پرداخت سود</td>
		<td>درصد پرداختی</td>
		<td>سود پرداختی</td>
		<td>درصد دریافتی</td>
		<td>سود دریافتی</td>
	</tr>";
$sumProfit = 0;
$sumReturnProfit = 0;
for($i=0; $i<count($dataTable); $i++)
{
	$row = $dataTable[$i];
	
	$sumProfit += $row["profit"]*1;
	$sumReturnProfit += $row["ReturnProfit"]*1;
	echo "<tr>
			<td>" . DateModules::miladi_to_shamsi($row["EndDate"]) . "</td>
			<td>" . $row["row"]["DocDesc"] . "</td>
			<td>" . number_format($row["row"]["amount"]) . "</td>
			<td>" . number_format($row["remainAmount"]) . "</td>
			<td>" . $row["days"] . "</td>
			<td>" . number_format($row["MaxAmount"]) . "</td>
			<td>" . $row["percent"] . "</td>				
			<td>" . number_format($row["profit"]) . "</td>
				<td>" . $row["ReturnPercent"] . "</td>
			<td>" . number_format($row["ReturnProfit"]) . "</td>
		</tr>";
}
echo "<tr id=footer>
		<td colspan=7>جمع</td>
		<td>" . number_format($sumProfit) . "</td>
		<td></td>
		<td>" . number_format($sumReturnProfit) . "</td>
	</tr>";
echo "</table>";

?>
