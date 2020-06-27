<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 97.10
//-----------------------------
require_once "../header.inc.php";
require_once inc_dataGrid;
require_once '../loan/request/request.class.php';
require_once './ExecuteEvent.class.php';
require_once './baseinfo/baseinfo.class.php';

$EventID = $_POST["EventID"]*1;
$Eobj = new COM_events($EventID);
$CallbackFn = $_POST["CallbackFn"] == "" ? "function(){}" : $_POST["CallbackFn"];

$SourcesArr = array();
$SourceIDs = "";

for($i=1; $i<=5; $i++)
{
	if(!empty($_POST["SourceID" . $i]))
	{
		$SourcesArr[] = $_POST["SourceID" . $i];
		$SourceIDs .= "&SourceID".$i."=" . $_POST["SourceID" . $i];
	}	
}


if(count($SourcesArr)>0)
{
	$LocalNo = ExecuteEvent::GetRegisteredDoc($EventID, $SourcesArr);
	if($LocalNo !== false)
	{
		echo "<center><br><h1>" . 
				"رویداد مربوط به شرایط انتخابی شما قبلا در سند شماره " . $LocalNo . 
				" صادر شده و قادر به صدور مجدد سند نمی باشید" . 
				"</h1></center>";
		die();
	}
}

$dg = new sadaf_datagrid("dg", $js_prefix_address . "ExecuteEvent.data.php?task=selectEventRows&EventID=" . 
		$EventID . $SourceIDs, "div_detail_dg");

$dg->addColumn("", "RowID", "", true);
$dg->addColumn("", "EventID", "", true);
$dg->addColumn("", "CostID", "", true);
$dg->addColumn("", "CostType", "", true);

$dg->addColumn("", "TafsiliType1", "", true);
$dg->addColumn("", "TafsiliType2", "", true);
$dg->addColumn("", "TafsiliType3", "", true);
$dg->addColumn("", "TafsiliTypeDesc1", "", true);
$dg->addColumn("", "TafsiliTypeDesc2", "", true);
$dg->addColumn("", "TafsiliTypeDesc3", "", true);

$dg->addColumn("", "TafsiliDesc1", "", true);
$dg->addColumn("", "TafsiliDesc2", "", true);
$dg->addColumn("", "TafsiliDesc3", "", true);

$dg->addColumn("", "CostDesc", "", true);
$dg->addColumn("", "IsActive", "", true);
$dg->addColumn("", "ChangeDate", "", true);
$dg->addColumn("", "changePersonName", "", true);
$dg->addColumn("", "PriceDesc", "", true);
$dg->addColumn("", "DocDesc", "", true);
$dg->addColumn("", "ComputeItemID", "", true);
$dg->addColumn("", "ComputeItemDesc", "", true);

$dg->addColumn("", "paramDesc1", "", true);
$dg->addColumn("", "paramDesc2", "", true);
$dg->addColumn("", "paramDesc3", "", true);

$dg->addColumn("", "ParamValue1", "", true);
$dg->addColumn("", "ParamValue2", "", true);
$dg->addColumn("", "ParamValue3", "", true);

$dg->addColumn("", "paramType1", "", true);
$dg->addColumn("", "paramType2", "", true);
$dg->addColumn("", "paramType3", "", true);

$dg->addColumn("", "ParamID1", "", true);
$dg->addColumn("", "ParamID2", "", true);
$dg->addColumn("", "ParamID3", "", true);

$col = $dg->addColumn(" کد حساب ", "CostCode");
$col->width = 80; 

$col = $dg->addColumn("عنوان حساب", "CostDesc");

$col = $dg->addColumn("تفصیلی1", "TafsiliID1");
$col->renderer = "ExecuteEvent.TafsiliRenderer1";
$col->width = 150; 

$col = $dg->addColumn("تفصیلی2", "TafsiliID2");
$col->renderer = "ExecuteEvent.TafsiliRenderer2";
$col->width = 150; 

$col = $dg->addColumn("تفصیلی3", "TafsiliID3");
$col->renderer = "ExecuteEvent.TafsiliRenderer3";
$col->width = 150; 

$col = $dg->addColumn("بدهکار", "DebtorAmount", "int");
$col->renderer = "ExecuteEvent.DebtorAmountRenderer";
$col->width = 100; 
$col->summaryType = GridColumn::SummeryType_sum;
$col->summaryRenderer = "function(value){return Ext.util.Format.Money(value);}";

$col = $dg->addColumn("بستانکار", "CreditorAmount", "int");
$col->renderer = "ExecuteEvent.CreditorAmountRenderer";
$col->width = 100; 
$col->summaryType = GridColumn::SummeryType_sum;
$col->summaryRenderer = "function(value){return Ext.util.Format.Money(value);}";

$col = $dg->addColumn("آیتم1", "param1", "int");
$col->renderer = "ExecuteEvent.Param1Renderer";
$col->width = 90; 

$col = $dg->addColumn("آیتم2", "param2", "int");
$col->renderer = "ExecuteEvent.Param2Renderer";
$col->width = 90; 

$col = $dg->addColumn("آیتم3", "param3", "int");
$col->renderer = "ExecuteEvent.Param3Renderer";
$col->width = 90; 

//$dg->addPlugin("this.RowDetails");
$dg->addButton("", "صدور سند رویداد", "send", "function(){ExecuteEventObj.RegisterEventDoc()}");

$dg->EnableSummaryRow = true;
$dg->DefaultSortField = "RowID";
$dg->autoExpandColumn = "CostDesc";
$dg->DefaultSortDir = "DESC";
$dg->EnableRowNumber = false;
$dg->EnableSearch = false;
$dg->height = 460;
$dg->title = $Eobj->_ParentEventTitle . " - " . "رویداد " . $Eobj->EventTitle . " [ " . $Eobj->EventID . " ]";
$dg->emptyTextOfHiddenColumns = true;
$dg->EnablePaging = false;
$grid = $dg->makeGrid_returnObjects();

require_once './ExecuteEvent.js.php';

?>
<center>
	<form id="MainForm">
		<div id="div_grid"></div>
	</form>
</center>