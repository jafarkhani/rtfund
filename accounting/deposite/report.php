<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------
 
require_once '../header.inc.php';
require_once '../docs/import.data.php';

$dataTable = ComputeDepositeProfit(DateModules::shamsi_to_miladi($_REQUEST["ToDate"]), array($_REQUEST["TafsiliID"]), true);
$dataTable = $dataTable[$_REQUEST["TafsiliID"]];

echo '<META http-equiv=Content-Type content="text/html; charset=UTF-8" ><body dir="rtl">';
echo '<link rel="stylesheet" type="text/css" href="/generalUI/fonts/fonts.css" />';
echo "<style>
		table { border-collapse:collapse; width:100%}
		#header {background-color : blue; color : white; font-weight:bold}
		#footer {background-color : #bbb;}
		td {font-family : nazanin; font-size:16px; padding:4px}
	</style>";
echo "<table></table>";
echo "<table border=1>
	<tr id=header>
		<td>تاریخ</td>
		<td>مبلغ گردش</td>
		<td>مانده حساب</td>
		<td>تعداد روز</td>
		<td>سود</td>
	</tr>";
$amount = 0;
$sumProfit = 0;
for($i=0; $i<count($dataTable)-1; $i++)
{
	$row = $dataTable[$i];
	$nextRow = $i+1<count($dataTable) ? $dataTable[$i+1] : null;
	
	$amount += $row["row"]["amount"]*1;
	$sumProfit += ($nextRow ? $nextRow["profit"] : 0);
	echo "<tr>
			<td>" . DateModules::miladi_to_shamsi($row["row"]["DocDate"]) . "</td>
			<td>" . number_format($row["row"]["amount"]) . "</td>
			<td>" . number_format($amount) . "</td>
			<td>" . ($nextRow ? $nextRow["days"] : 0) . "</td>
			<td>" . number_format(($nextRow ? $nextRow["profit"] : 0)) . "</td>
		</tr>";
}
echo "<tr id=footer>
		<td colspan=4>جمع</td>
		<td>" . number_format($sumProfit) . "</td>
	</tr>";
echo "</table>";

?>
