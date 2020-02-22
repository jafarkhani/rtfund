<?php

//-----------------------------
//	Programmer	: A.Bozorgnia
//	Date		: 92.04
//-----------------------------

require_once '../../header.inc.php';
require_once '../DocOperations/class/Checks.class.php';
require_once '../baseInfo/class/signs.class.php';
require_once 'CurrencyModules.class.php';
if (!empty($_GET['DocID'])) {
	$DocID = $_GET['DocID'];
	if (!IS_COMMITMENT)
		$docItem = Checks::SelectDocItems(" di.DocID=? and checkno>0", array($DocID));
	else
		$docItem = Checks::COMSelectDocItems(" di.DocID=? and checkno>0", array($DocID));
	$res = PdoDataAccess::runquery("select * from DocItems di 
		inner join DocItems di1 on (di.sourceitemid=di1.itemid)
		inner join GeneralDocHeaders gdh on (gdh.docid=di1.docid)
		where di.docid=?", array($DocID));

	if (count($res) != 0)
		$GeneralID = $res[0]['GeneralID'];
	if ($_SESSION['ACCUSER']['UnitID'] == 505 && $_GET['IsPrinted'] == 'NO' && !empty($GeneralID)) {
		require_once '../import/purchase/purchase.data.php';
		PurchaseChangeStatus($GeneralID, 430, 'صدور چک ');
	}
	$no = $docItem->rowCount();
	$record = $docItem->fetchAll();
	$signs = signs::GetSignsNames(" SignType='CHECK' AND UnitID=? AND PeriodID=?", array($_SESSION['ACCUSER']['UnitID'], $_SESSION["ACCUSER"]["PeriodID"]));
	for ($item = 0; $item < $no; $item++) {
		$checkID = $record[$item]["ChequeID"];
		$checkNo = $record[$item]["CheckNo"];
		$LocalNo = $record[$item]["LocalNo"];
		$CheckID = $record[$item]["CheckID"];
		if ($_SESSION['ACCUSER']['UnitID'] == 505) {
			require_once '../import/purchase/purchase.data.php';
			AddItemStatus($record[$item]["DocID"], 211);
		}
		$date = DateModules::miladi_to_shamsi($record[$item]["CheckDate"]);
		$amount = $record[$item]["amount"];
		$desc = $record[$item]['CheckDesc'];
		$filename = "/mystorage/accountancy/output/" . $checkID . ".html";   

		if (!file_exists($filename)) {
			echo "<br><br><center><h2>" . "چاپ چک طراحی نشده است" . "</h2></center>";
			die();
		}

		$content = file_get_contents($filename, "r");
		$content = str_replace('<body dir="rtl">', "", $content);
		$content = str_replace("</body>", "", $content);
		$content = str_replace("absolute;top:0;left:0;", "relative", $content);
		$content = "<div>" . $content;
		$content = stripcslashes($content);

		$content = str_replace("@", "<center>", $content);
		$content = str_replace("@", "</center>", $content);
		$content = '<style>@media screen  {  div#fb_div_0 {background: url(backgrounds/' . $checkID . '.jpg) no-repeat no-repeat top right;}  }</style>' . $content;
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
		for ($i = 0; $i < 3; $i++) {
			if ($index > 109) {
				$content = str_replace("#key" . ($index++) . "#", $signs[$i]["FullName"], $content);
				$content = str_replace("#key" . ($index++) . "#", $signs[$i]["post"], $content);
			} else {
				$content = str_replace("#key" . ($index++) . "#", $signs[$i]["FullName"], $content);
				$content = str_replace("#key" . ($index++) . "#", $signs[$i]["post"], $content);
			}
		}
		if (count($signs) == 4) {
			$content = str_replace("#key118#", $signs[3]["FullName"], $content);
			$content = str_replace("#key119#", $signs[3]["post"], $content);
		}

		$content = str_replace("#key114#", $LocalNo, $content);
		$content = str_replace("#key115#", "#" . number_format($amount) . "Rls", $content);
		$content = str_replace("#key116#", $date, $content);
		$content = str_replace("#key117#", $checkNo, $content);
		$content = $content . "</div>";
		echo $content;
		echo "<p style='page-break-before: always'>";
	}
	echo
	"<center>
                <div class='noPrint' style='position: absolute;top:400px;left: 50%;'>
		<input type='checkbox' onchange='setSign(this,2,\"" . $no . "\");' checked>امضاء دوم		
                <input type='checkbox' onchange='setSign(this,3,\"" . $no . "\");' checked>امضاء سوم
	</div>
        </center>";
}
?>
<style media="print">
    .noPrint {display:none;}
</style>
<script>
	function setSign(elem, sign, item) {

		var className = elem.checked ? "" : "noPrint";
		var display = elem.checked ? "" : "none";
		for (i = 0; i < item; i++)
		{
			if (sign == 2)
			{
				document.getElementById("formItem_110").className = className;
				document.getElementById("formItem_111").className = className;

				document.getElementById("formItem_110").style.display = display;
				document.getElementById("formItem_111").style.display = display;

				document.getElementById("formItem_110").id = '';
				document.getElementById("formItem_111").id = '';
			}
			else
			{
				document.getElementById("formItem_112").className = className;
				document.getElementById("formItem_113").className = className;

				document.getElementById("formItem_112").style.display = display;
				document.getElementById("formItem_113").style.display = display;

				document.getElementById("formItem_112").id = '';
				document.getElementById("formItem_113").id = '';
			}
		}
		return true;
	}
</script>
<?

die();
?>
