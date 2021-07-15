<?php
//-----------------------------
//	Programmer	: Fatemipour
//	Date		: 94.10
//-----------------------------
require_once '../header.inc.php';
require_once inc_dataGrid;
/*echo 'That isssss';*/
//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................
require_once 'manageOrganDocOld.js.php';
/*$dttttt = 23;*/
$dg = new sadaf_datagrid("dg", $js_prefix_address . "organDoc.data.php?task=SelectOrgDocs", "div_dg");

$dg->addColumn("", "orgDocID", "", true);
$dg->addColumn("", "orgDocType", "", true);
$dg->addColumn("", "endDate", "", true);
$dg->addColumn("", "PersonID2", "", true);

$col = $dg->addColumn("عنوان سند سازمانی", "title");
$col->align = "center";
$col->width = 450;

/*$dg->addColumn("", "RegPersonID", "", true);*/

$col = $dg->addColumn("تاریخ سند سازمانی", "date", GridColumn::ColumnType_date);
$col->width = 200;


$col = $dg->addColumn('عملیات', '', 'string');
$col->renderer = "ManageOrgDocObj.OperationRender";
$col->width = 50;
$col->align = "center";


/*if($accessObj->AddFlag)
	$dg->addButton("", "ایجاد قرارداد", "add", "function(){ManageContractsObj.AddContract();}");*/

$dg->title = "لیست قراردادها";
$dg->DefaultSortField = "orgDocID";
$dg->DefaultSortDir = "desc";
$dg->autoExpandColumn = "PersonFullname";
$dg->width = 740;
$dg->height = 500;
$dg->pageSize = 20;
$dg->EnableRowNumber = true;
$grid = $dg->makeGrid_returnObjects();
?>
<script>
    ManageOrgDocObj = new ManageOrgDocs();
    ManageOrgDocObj.grid = <?= $grid ?>;
    /*ManageOrgDocObj.grid.getView().getRowClass = function(record, index)
	{
		if(record.data.IsEnded == "YES")
			return "greenRow";
		
		if(record.data.ActionType == "REJECT")
			return "pinkRow";
	
		return "";
	}*/
    /*ManageContractsObj.grid.render(ManageContractsObj.get("div_dg"));*/       /*new Commented*/
	
</script>
<br>
<center>
    <form id="mainForm">
        <div id="DivPanel" style="margin:8px;width:98%"></div>  <!-- new Added -->
    </form>

</center>
