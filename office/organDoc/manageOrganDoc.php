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
$orgDocType = $_REQUEST["orgDocType"];
require_once 'manageOrganDoc.js.php';
/*$dttttt = 23;*/
$dg = new sadaf_datagrid("dg", $js_prefix_address . "organDoc.data.php?task=SelectOrgDocs&orgDocType=". $orgDocType ." ", "div_dg");
if ($orgDocType == 142){
    $dg->addColumn("", "orgDocID", "", true);
    $dg->addColumn("", "orgDocType", "", true);
    $col = $dg->addColumn("&#1593;&#1576;&#1575;&#1585;&#1578; &#1601;&#1575;&#1585;&#1587;&#1740;", "field1");
    $col->align = "center";
    $col->width = 100;
    $col = $dg->addColumn("&#1593;&#1576;&#1575;&#1585;&#1578; &#1575;&#1606;&#1711;&#1604;&#1740;&#1587;&#1740;", "field2");
    $col->align = "center";
    $col->width = 100;
    $col = $dg->addColumn("&#1588;&#1585;&#1581;", "field3");
    $col->align = "center";
    $col->width = 250;
    $col = $dg->addColumn("&#1606;&#1608;&#1593; &#1605;&#1575;&#1582;&#1584;", "field4");
    $col->renderer = "function(v){return v == 200 ? '&#1587;&#1575;&#1740;&#1585;' : '&#1583;&#1575;&#1582;&#1604;&#1740;';}";
    $col->align = "center";
    $col->width = 100;
    $col = $dg->addColumn("&#1593;&#1606;&#1608;&#1575;&#1606; &#1605;&#1575;&#1582;&#1584;", "field5");
    $col->align = "center";
    $col->width = 100;
    $col = $dg->addColumn('عملیات', '', 'string');
    $col->renderer = "ManageOrgDocObj.OperationRender";
    $col->width = 50;
    $col->align = "center";
}else{
    $dg->addColumn("", "orgDocID", "", true);
    $dg->addColumn("", "orgDocType", "", true);
    $dg->addColumn("", "endDate", "", true);
    $dg->addColumn("", "PersonID2", "", true);

    $col = $dg->addColumn("عنوان سند سازمانی", "title");
    $col->align = "center";
    $col->width = 350;

    /*$dg->addColumn("", "RegPersonID", "", true);*/

    $col = $dg->addColumn("تاریخ سند سازمانی", "date", GridColumn::ColumnType_date);
    $col->width = 200;


    $col = $dg->addColumn('عملیات', '', 'string');
    $col->renderer = "ManageOrgDocObj.OperationRender";
    $col->width = 50;
    $col->align = "center";


    /*if($accessObj->AddFlag)
        $dg->addButton("", "ایجاد قرارداد", "add", "function(){ManageContractsObj.AddContract();}");*/
}

$dg->title = "لیست قراردادها";
$dg->DefaultSortField = "orgDocID";
$dg->DefaultSortDir = "asc";
$dg->autoExpandColumn = "PersonFullname";
$dg->width = 640;
$dg->height = 500;
$dg->pageSize = 10;
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
