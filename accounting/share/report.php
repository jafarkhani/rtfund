<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 91.02
//-----------------------------

require_once '../../header.inc.php';
require_once "ReportGenerator.class.php";

if(session::IsPortal())
{
	$_SESSION["accounting"]["CycleID"] = substr(DateModules::shNow(),0,4);
} 

$query = "select sum(CreditorAmount-DebtorAmount) amount, 
				ShareNo,
				TafsiliDesc,
				IsGovermental,
				round(sum(CreditorAmount-DebtorAmount) /" . ShareBaseAmount . ") shareCount
				
	from ACC_DocItems 
		join ACC_docs using(DocID)
		join ACC_tafsilis using(TafsiliID)
		join BSC_persons on(ObjectID=PersonID)
	where CostID=" . COSTID_share . " AND CycleID=" . $_SESSION["accounting"]["CycleID"] . 
	" group by TafsiliID having shareCount > 0";

$query .= " order by amount desc";
$dataTable = PdoDataAccess::runquery($query);
//print_r(ExceptionHandler::PopAllExceptions());

global $TotalShare;
$TotalShare = 0;
foreach($dataTable as $row)
	$TotalShare += $row["shareCount"]*1;

function moneyRender($row,$val)
{
	return number_format($val, 0, '.', ',');
}

function IsGovermentalRender($row,$val){
	return $val == "YES" ? "دولتی" : "خصوصی";		
}

function PercentRender($row, $val){
	global $TotalShare;
	return round($val*100/$TotalShare, 2) . "%";
}

$rpg = new ReportGenerator();
$rpg->excel = !empty($_POST["excel"]);

$rpg->addColumn("سهامدار", "TafsiliDesc");
$rpg->addColumn("شماره دفتر", "ShareNo");

$rpg->addColumn("بخش دولتی/خصوصی", "IsGovermental", "IsGovermentalRender");

$col = $rpg->addColumn("تعداد سهام", "shareCount");
$col->align = "center";
$col->EnableSummary();

function SumRender($sum){
	return round($sum, 0)*1 . "%";
}
$col = $rpg->addColumn("درصد سهم", "shareCount", "PercentRender");
$col->align = "center";
$col->SumRener = "SumRender";
$col->EnableSummary(true);

$col = $rpg->addColumn("ارزش سهام", "amount","moneyRender");
$col->align = "center";
$col->EnableSummary();

$rpg->mysql_resource = $dataTable;

$percentGovermental = array(0,0);
foreach($dataTable as $row)
	if($row["IsGovermental"] == "YES")
		$percentGovermental[0] += round($row["shareCount"]*100/$TotalShare,2);
	else
		$percentGovermental[1] += round($row["shareCount"]*100/$TotalShare,2);
$percentGovermental = "<table width=100% style=font-weight:bold;font-family:tahoma;font-size:13px><tr>
	<td align=center>جمع درصد سهم بخش دولتی : " . round($percentGovermental[0]) . " %</td>
	<td align=center>جمع درصد سهم بخش خصوصی : " . round($percentGovermental[1]) . " %</td></tr></table>";

if(isset($_REQUEST["print"]))
{
	BeginReport();
	$rpg->generateReport();
	echo "<br>" . $percentGovermental . "<br>";
	die(); 
}

?>
<script>
ShareReport.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function ShareReport()
{
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		width : 750,
		height : 500,
		autoScroll : true,
		frame : true,
		contentEl : this.get("subDiv"),		
		buttons : [{
			text : "چاپ",
			handler : function(){
				window.open(ShareReportObj.address_prefix + "report.php?print=true");
			},
			iconCls : "print"
		}]
	});
}

ShareReportObj = new ShareReport();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" >
			<div id="subDiv">
				<?= $rpg->generateReport(); ?>
				<br>
				<?= $percentGovermental ?>
				<br>
			</div>
		</div>
	</center>
</form>