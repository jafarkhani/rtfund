<?php

//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 92.02
//-----------------------------

require_once '../../../header.inc.php';
require_once 'CurrencyModules.class.php';

BeginReport();

if (!empty($_GET['DocChequeID'])) {
	
	$DocChequeID = $_GET['DocChequeID'];
	$res = PdoDataAccess::runquery("
		SELECT c.*,LocalNo,ChequeBookID,TafsiliDesc 
		FROM ACC_DocCheques c 
			join ACC_docs using(DocID)
			left join ACC_tafsilis using(TafsiliID)
			left join ACC_accounts a using(AccountID)
			left join ACC_ChequeBooks b on(a.AccountID=b.AccountID and c.CheckNo between MinNo and MaxNo)
		where DocChequeID=?
		group by DocChequeID", array($DocChequeID));
	if (count($res) == 0)
		die();
	$record = $res[0];
	
	$checkID = $record["ChequeBookID"];
	$checkNo = $record["CheckNo"];
	$LocalNo = $record["LocalNo"];
	$date = DateModules::miladi_to_shamsi($record["CheckDate"]);
	$amount = $record["amount"];
	$desc = $record["TafsiliDesc"] . "  " . $record['description'];
} else {
	$checkID = $_REQUEST["ChequeBookID"];
	$checkNo = "121212";
	$LocalNo = "12121212";
	$date = '1392/03/25';
	$amount = 12583000;
	$desc = 'در وجه تست چاپ چک';
	$signs = array(
		array("FullName" => "امضای اول", "post" => "سمت اول"),
		array("FullName" => "امضای دوم", "post" => "سمت دوم"),
		array("FullName" => "امضای سوم", "post" => "سمت سوم"),
		array("FullName" => "امضای چهارم", "post" => "سمت چهارم")
	);
}

$filename = "output/" . $checkID . ".html";
if (!file_exists($filename)) {
	echo "<br><br><center><h2>" . "چاپ چک طراحی نشده است" . "</h2></center>";
	die();
}

$content = file_get_contents($filename, "r");

$content = stripcslashes($content);

$content = str_replace("@", "<center>", $content);
$content = str_replace("@", "</center>", $content);
$content = '<style>@media screen  {  div#fb_div_0 {background: url(backgrounds/' . $checkID . '.jpg?v=' . rand(1, 1000) . ') no-repeat no-repeat top right;}  }</style>' . $content;
$content = '<style>@media print  {  div#fb_div_0 {transform:rotate(270deg) translate(-25%);} 
		span#formItem_117 {display:none;} }</style>' . $content;


$content = str_replace("#key101#", substr($date, 2, 2), $content);
$content = str_replace("#key102#", substr($date, 5, 2), $content);
$content = str_replace("#key103#", substr($date, 8, 2), $content);
$content = str_replace("#key104#", DateModules::DateToString($date), $content);
$content = str_replace("#key105#", number_format($amount) . "/--", $content);
$content = str_replace("#key106#", CurrencyModulesclass::CurrencyToString($amount) . "&nbsp;ریال", $content);
$content = str_replace("#key107#", $desc, $content);

$index = 108;
$signs = array();
if (count($signs) > 0) {
	for ($i = 0; $i < 3; $i++) {
		$content = str_replace("#key" . ($index++) . "#", $signs[$i]["FullName"], $content);
		$content = str_replace("#key" . ($index++) . "#", $signs[$i]["post"], $content);
	}
	if (count($signs) == 4) {
		$content = str_replace("#key118#", $signs[3]["FullName"], $content);
		$content = str_replace("#key119#", $signs[3]["post"], $content);
	}
}
$content = str_replace("#key114#", $LocalNo, $content);
$content = str_replace("#key115#", "#" . number_format($amount) . "Rls", $content);
$content = str_replace("#key116#", $date, $content);
$content = str_replace("#key117#", $checkNo, $content);
echo $content;
?>
<center>
<!--	<div class="noPrint" style="position: absolute;top:400px;left: 50%;">
		<input type="checkbox" onchange="setSign(this, 2)" checked>امضاء دوم
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="checkbox" onchange="setSign(this, 3)" checked>امضاء سوم
	</div>
-->
</center>

<style media="print">
	.noPrint {display:none;}
</style>
<script>
	function setSign(elem, sign, item) {

		var className = elem.checked ? "" : "noPrint";
		var display = elem.checked ? "" : "none";
		if (sign == 2)
		{
			document.getElementById("formItem_110").className = className;
			document.getElementById("formItem_111").className = className;

			document.getElementById("formItem_110").style.display = display;
			document.getElementById("formItem_111").style.display = display;
		}
		else
		{
			document.getElementById("formItem_112").className = className;
			document.getElementById("formItem_113").className = className;

			document.getElementById("formItem_112").style.display = display;
			document.getElementById("formItem_113").style.display = display;
		}

		return true;
	}
</script>
<?

die();
?>
