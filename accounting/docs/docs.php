<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------

require_once '../header.inc.php';
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................
require_once 'docs.js.php';

$dg = new sadaf_datagrid("dg", $js_prefix_address . "doc.data.php?task=selectDocs","div_dg");

$dg->addColumn("کد سند","DocID","",true);
$dg->addColumn("","BranchID","",true);
$dg->addColumn("","DocStatus","",true);
$dg->addColumn("شعبه سند","BranchName","",true);
$dg->addColumn("تاریخ سند","DocDate","",true);
$dg->addColumn("تاریخ ثبت","RegDate","",true);
$dg->addColumn("توضیحات","description","",true);
$dg->addColumn("کد سند","LocalNo","",true);
$dg->addColumn("نوع سند","DocType","",true);


$dg->addColumn("ثبت کننده سند","regPerson","",true);
$dg->addColumn("","SubjectDesc","",true);
$dg->addColumn("","SubjectID","",true);
$dg->addColumn("","DocTypeDesc","",true);

$dg->addColumn("", "FlowID", "", true);
$dg->addColumn("", "StatusID", "", true);
$dg->addColumn("", "StepID", "", true);
$dg->addColumn("", "ActionType", "", true);

$col = $dg->addColumn("اطلاعات سند","DocID");
$col->renderer = "AccDocs.docRender";

$dg->addButton('HeaderBtn', 'عملیات', 'list', 'function(e){ return AccDocsObject.operationhMenu(e); }');

$dg->title = "سند های حسابداری";
$dg->width = 780;
$dg->DefaultSortField = "LocalNo";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "DocID";
$dg->emptyTextOfHiddenColumns = true;
$dg->hideHeaders = true;
$dg->pageSize = 1;
$dg->disableChangePageSize = true;
$dg->PageSizeChange = false;
$dg->EnableRowNumber = false;
$dg->hideHeaders = true;
$dg->EnableSearch = false;
$dg->ExcelButton = false;
//$dg->collapsible = true;
$grid = $dg->makeGrid_returnObjects();

//--------------------------------------------
$dg = new sadaf_datagrid("dg",$js_prefix_address . "doc.data.php?task=selectDocItems","div_detail_dg");

$dg->addColumn("","DocID","",true);
$dg->addColumn("","CostID","",true);
$dg->addColumn("","CostCode","",true);
$dg->addColumn("","TafsiliType","",true);
$dg->addColumn("","TafsiliType2","",true);
$dg->addColumn("","TafsiliType3","",true);
$dg->addColumn("","TafsiliDesc","",true);
$dg->addColumn("","TafsiliGroupDesc","",true);
$dg->addColumn("","Tafsili2Desc","",true);
$dg->addColumn("","Tafsili2GroupDesc","",true);
$dg->addColumn("","Tafsili3Desc","",true);
$dg->addColumn("","Tafsili3GroupDesc","",true);
$dg->addColumn("ردیف سیستمی", "locked", "", true);
$dg->addColumn("","details","",true);

$dg->addColumn("", "paramDesc1", "", true);
$dg->addColumn("", "paramDesc2", "", true);
$dg->addColumn("", "paramDesc3", "", true);

$dg->addColumn("", "ParamValue1", "", true);
$dg->addColumn("", "ParamValue2", "", true);
$dg->addColumn("", "ParamValue3", "", true);

$col = $dg->addColumn("ردیف","ItemID","", true);

$col = $dg->addColumn("سرفصل حساب", "CostDesc");
$col->renderer = "function(v,p,r){return '[' + r.data.CostCode + '] ' + v;}";

$col = $dg->addColumn("تفصیلی", "TafsiliID");
//$col->editor = "AccDocsObject.tafsiliCombo";
$col->renderer = "function(v,p,r){p.tdAttr = \"data-qtip='\" + r.data.TafsiliGroupDesc + \"'\";".
		"return r.data.TafsiliDesc;}";
$col->width = 110;

$col = $dg->addColumn("تفصیلی2", "TafsiliID2");
//$col->editor = "AccDocsObject.tafsili2Combo";
$col->renderer = "function(v,p,r){p.tdAttr = \"data-qtip='\" + r.data.Tafsili2GroupDesc + \"'\";".
		"return r.data.Tafsili2Desc;}";
$col->width = 110;

$col = $dg->addColumn("تفصیلی3", "TafsiliID3");
//$col->editor = "AccDocsObject.tafsili2Combo";
$col->renderer = "function(v,p,r){p.tdAttr = \"data-qtip='\" + r.data.Tafsili3GroupDesc + \"'\";".
		"return r.data.Tafsili3Desc;}";
$col->width = 110;

$col = $dg->addColumn("بدهکار", "DebtorAmount", GridColumn::ColumnType_money);
$col->editor = ColumnEditor::CurrencyField(true, "cmp_DebtorAmount");
$col->width = 100;

$col = $dg->addColumn("بستانکار", "CreditorAmount", GridColumn::ColumnType_money);
$col->editor = ColumnEditor::CurrencyField(true, "cmp_CreditorAmount");
$col->width = 100;

$col = $dg->addColumn("جزئیات", "details");
//$col->editor = ColumnEditor::TextField(true, "cmp_details");
$col->width = 100;
$col->ellipsis = 50;

$col = $dg->addColumn("آیتم1", "param1");
$col->renderer = "AccDocs.Param1Render";
$col->width = 110;

$col = $dg->addColumn("آیتم2", "param2");
$col->renderer = "AccDocs.Param2Render";
$col->width = 110;

$col = $dg->addColumn("آیتم3", "param3");
$col->renderer = "AccDocs.Param3Render";
$col->width = 110;

/*$col = $dg->addColumn("کدحساب قدیم", "OldCostCode");
$col->editor = ColumnEditor::TextField(true);
$col->width = 90;*/

if($accessObj->RemoveFlag)
{
    $col = $dg->addColumn("", "", "string");
    $col->sortable = false;
    $col->renderer = "AccDocs.deleteitemRender";
    $col->width = 50;	
}

if($accessObj->AddFlag)
{
	$dg->addButton("", "ایجاد ردیف سند", "add", "function(v,p,r){ return AccDocsObject.AddItem(v,p,r);}");
}

$dg->enableRowEdit = true ;
$dg->rowEditOkHandler = "function(store,record){ return AccDocsObject.SaveItem(store,record);}";

$dg->DefaultSortField = "ItemID";
$dg->autoExpandColumn = "CostDesc";
$dg->DefaultSortDir = "ASC";
$dg->emptyTextOfHiddenColumns = true;
//$dg->height = 320;
$dg->pageSize = 15;
$itemsgrid = $dg->makeGrid_returnObjects();
//---------------------------------------------------
$dgh = new sadaf_datagrid("dg",$js_prefix_address."doc.data.php?task=selectCheques","div_dg");

$dgh->addColumn("","DocID","",true);
$dgh->addColumn("","AccountDesc","",true);
$dgh->addColumn("","TafsiliDesc","",true);
$dgh->addColumn("","StatusTitle","",true);
$dgh->addColumn("","AccountTafsiliDesc","",true);

$col = $dgh->addColumn("کد","DocChequeID","",true);
$col->width = 50;

$col = $dgh->addColumn("نوع حساب", "AccountTafsiliID");
$col->renderer = "function(v,p,r){return r.data.AccountTafsiliDesc;}";
$col->editor = "AccDocsObject.accountTafsiliCombo";
$col->width = 80;

$col = $dgh->addColumn("حساب", "AccountID");
$col->renderer = "function(v,p,r){return r.data.AccountDesc;}";
$col->editor = "AccDocsObject.accountCombo";
$col->width = 150;

$col = $dgh->addColumn("شماره چک", "CheckNo");
$col->editor = ColumnEditor::TextField(true, "cmp_CheckNo");
$col->width = 100;

$col = $dgh->addColumn("تاریخ چک", "CheckDate", GridColumn::ColumnType_date);
$col->editor = ColumnEditor::SHDateField();
$col->width = 90;

$col = $dgh->addColumn("مبلغ", "amount", GridColumn::ColumnType_money);
$col->editor = ColumnEditor::CurrencyField();
$col->width = 200;

$col = $dgh->addColumn("در وجه", "TafsiliID");
$col->renderer = "function(v,p,r){return r.data.TafsiliDesc;}";
$col->editor = "AccDocsObject.checkTafsiliCombo";

$col = $dgh->addColumn("بابت", "description");
$col->editor = ColumnEditor::TextField(true);

$col = $dgh->addColumn("وضعیت", "CheckStatus");
$col->editor = "AccDocsObject.ChequeStatusCombo";
$col->renderer = "function(v,p,r){return r.data.StatusTitle}";
$col->width = 80;

if($accessObj->RemoveFlag)
{
	$col = $dgh->addColumn("حذف", "", "string");
	$col->renderer = "AccDocsObject.check_deleteRender";
	$col->width = 50;
	$col->align = "center";
}
if($accessObj->AddFlag)
{
	$dgh->addButton = true;
	$dgh->addHandler = "function(v,p,r){ return AccDocsObject.check_Add(v,p,r);}";
}

$dgh->enableRowEdit = true ;
$dgh->rowEditOkHandler = "function(v,p,r){ return AccDocsObject.check_Save(v,p,r);}";

$dgh->addButton("", "چاپ چک", "print", "function(){ return AccDocsObject.printCheck();}");

$dgh->addColumn("", "CheckStatus","",true);
$dgh->addColumn("", "PrintPage1","",true);
$dgh->addColumn("", "PrintPage2","",true);

$dgh->DefaultSortField = "DocChequeID";
$dgh->autoExpandColumn = "description";
$dgh->emptyTextOfHiddenColumns = true;
$dgh->DefaultSortDir = "ASC";
$dgh->height = 315;
$dgh->EnableSearch = false;
$dgh->EnablePaging = false;
$dgh->width = 1000;
$checksgrid = $dgh->makeGrid_returnObjects();

//-----------------------------------------

$whereParam = array(":cid" => $_SESSION["accounting"]["CycleID"]);
$dt = PdoDataAccess::runquery("select ifnull(count(*),0) from ACC_docs where CycleID=:cid ", $whereParam);
$docsCount = $dt[0][0];

?>
<script>
AccDocsObject.grid = <?= $grid ?>;
AccDocsObject.grid.getView().getRowClass = function(record, index)
{
	if(record.data.StepID == "1" && record.data.ActionType == "REJECT")
		return "pinkRow";
	if(record.data.StatusID == "<?= ACC_STEPID_RAW ?>")
		return "";
	if(record.data.StatusID == "<?= ACC_STEPID_CONFIRM ?>")
		return "yellowRow";
	
	return "greenRow";
}

var pagingToolbar = AccDocsObject.grid.getDockedItems('pagingtoolbar')[0];
pagingToolbar.dock = "top";
pagingToolbar.down("[itemId=inputItem]").hide();
pagingToolbar.down("[itemId=displayItem]").hide();

pagingToolbar.dock = "top";
pagingToolbar.down("[itemId=last]").setTooltip('آخرین سند');
pagingToolbar.down("[itemId=next]").setTooltip('سند بعدی');
pagingToolbar.down("[itemId=prev]").setTooltip('سند قبلی');
pagingToolbar.down("[itemId=first]").setTooltip('اولین سند');
pagingToolbar.down("[itemId=refresh]").setTooltip('بارگزاری مجدد');
pagingToolbar.afterPageText = "اسناد";

pagingToolbar.add([{
	xtype : "numberfield", 
	itemId : "Number",
	value: '',
	hideTrigger: true,
	size:5,
	fieldLabel:'شماره سند',
	labelWidth:65,
	width:120,
	listeners: {
		specialkey: function(field, e){
			// e.HOME, e.END, e.PAGE_UP, e.PAGE_DOWN,
			// e.TAB, e.ESC, arrow keys: e.LEFT, e.RIGHT, e.UP, e.DOWN
			if (e.getKey() == e.ENTER) 
				AccDocsObject.SearchDoc()
		}
	}
},{
	xtype : "button",
	iconCls : "search",
	handler : function(){
		return AccDocsObject.SearchDoc();}
}]);

AccDocsObject.grid.getStore().on("load", AccDocsObject.afterHeaderLoad);	
AccDocsObject.grid.getStore().currentPage = <?= $docsCount ?>;
AccDocsObject.grid.render(AccDocsObject.get("div_dg"));

bars = AccDocsObject.grid.getDockedItems();
bars[2].add(bars[1].items.items);
bars[1].hide();

//...................................................
AccDocsObject.itemGrid = <?= $itemsgrid ?>;
//AccDocsObject.itemGrid.plugins[0].on("beforeedit", AccDocs.beforeRowEdit);
AccDocsObject.itemGrid.getView().getRowClass = function(record, index)
{
	if(record.data.locked == "YES")
		return "violetRow";
	return "";
}

//...................................................
AccDocsObject.checkGrid = <?= $checksgrid ?>;
AccDocsObject.checkGrid.plugins[0].on("beforeedit", AccDocs.beforeCheckEdit);
AccDocsObject.checkGrid.plugins[0].on("beforeedit", function(editor,e){
	if(e.record.data.CheckStatus == '<?= INCOMECHEQUE_VOSUL ?>')
		return false;
	if(!e.record.data.DocChequeID)
		return AccDocsObject.AddAccess;
	return AccDocsObject.EditAccess;
});
//...................................................
</script>
<style type="text/css">
.check {background-image:url('/framework/icons/check.png') !important;}
.archive {background-image:url('/framework/icons/archive.png') !important;}
.docInfo td{height:20px;}
</style>
<center>
<form id="mainForm">
	<br><div id="div_dg"></div>
	<br>
	<div id="div_tab" >
		<div id="tabitem_rows">
			<div style="margin-left:10px;margin-right: 10px" id="div_detail_dg"></div>
		</div>
	</div>	
</form>
<div id="fs_summary"></div>
<div id="div_checksWin" class="x-hidden"></div>
</center>