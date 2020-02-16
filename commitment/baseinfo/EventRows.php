<?php
//---------------------------
//	Programmer	: Sh.Jafarkhani
//	Date		: 97.05
//---------------------------

require_once "../header.inc.php";
require_once './baseinfo.class.php';
require_once inc_dataGrid;
$EventID = (int) $_POST["EventID"];
$EventObj = new COM_events($EventID);

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................
$random = rand();
require_once 'EventRows.js.php';
 
$dg = new sadaf_datagrid("dg2", $js_prefix_address . "baseinfo.data.php?task=selectEventRows&EventID=" . $EventID, "div_detail_dg");

$dg->addColumn(" ", "RowID", "", true);
$dg->addColumn(" ", "EventID", "", true);
$dg->addColumn(" ", "CostID", "", true);
$dg->addColumn(" ", "IsActive", "", true);
$dg->addColumn(" ", "ChangeDate", "", true);
$dg->addColumn(" ", "changePersonName", "", true);
$dg->addColumn(" ", "PriceDesc", "", true);
$dg->addColumn(" ", "ComputeGroup", "", true);
$dg->addColumn(" ", "ComputeItemID", "", true);

$col = $dg->addColumn("ماهیت", "CostType");
$col->renderer = "function(v){ return v== 'DEBTOR' ? 'بدهکار' : 'بستانکار'}";
$col->width = 60;

$col = $dg->addColumn(" کد حساب ", "CostCode");
$col->width = 65;

$col = $dg->addColumn(" عنوان حساب ", "CostDesc");

$col = $dg->addColumn("مبنای صدور سند", "DocDesc");
$col->width = 120;
$col->ellipsis = 40; 

$col = $dg->addColumn("آیتم محاسباتی", "ComputeItemDesc");
$col->width = 200;

$col = $dg->addColumn("توضیحات", "ChangeDesc");
$col->width = 150;
$col->renderer = "EventRows.ChangeRender";
if ($accessObj->AddFlag)
    $dg->addButton("", "ایجاد ردیف", "add", "function(v,p,r){ return EventRowsObj" . $random . ".AddItem(v,p,r);}");

$col = $dg->addColumn("تاریخ تغییر", "ChangeDate", GridColumn::ColumnType_date);
$col->width = 80;

$col = $dg->addColumn("", "changePersonName");
$col->width = 120;


$col = $dg->addColumn("عملیات", "PlanID");
$col->renderer = "EventRows.OperationRender";
$col->width = 60;

$dg->addObject('EventRowsObj' . $random . '.HistoryObj');

$dg->PrintButton = true;

$dg->DefaultSortField = "RowID";
$dg->autoExpandColumn = "CostDesc";
$dg->DefaultSortDir = "DESC";
$dg->EnableRowNumber = false;
$dg->EnableSearch = false;
$dg->height = 500;
$dg->emptyTextOfHiddenColumns = true;
$dg->EnablePaging = false;
$itemsgrid = $dg->makeGrid_returnObjects();
?>
<style type="text/css">
    .docInfo td{height:20px;}
    .blue{ color: #1E4685; font-weight:bold;}
</style>
<script>
    me = EventRowsObj<?= $random ?>;
    me.itemGrid = <?= $itemsgrid ?>;
    me.itemGrid.getView().getRowClass = function (record, index)
    {
        if (record.data.IsActive == "NO")
            return "pinkRow";
        if (record.data.ChangeDate != null)
            return "greenRow";
        return "";
    }
    me.itemGrid.render(me.get("div_detail_dg"));
</script>
<center>
    <br>
    <form id="mainForm">
        <div id="div_Event"></div>
        <div align="right"><div id="DIV_formPanel" align="right"></div></div>
        <br>
        <div id="div_detail_dg" style="width:97%; padding-left: 10px;padding-right: 10px"></div>
    </form>
</center>





