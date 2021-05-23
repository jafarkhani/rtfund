<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.07
//-----------------------------

require_once '../../header.inc.php';
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

require_once 'ManageRequests.js.php';

$dg = new sadaf_datagrid("dg", $js_prefix_address . "request.data.php?task=SelectAllWarrentyRequests", "grid_div");

$dg->addColumn("", "StatusID", "", true);
$dg->addColumn("", "PersonID", "", true);
$dg->addColumn("", "TypeID", "", true);
$dg->addColumn("", "IsEnded", "", true);
$dg->addColumn("", "LetterNo", "", true);
$dg->addColumn("", "LetterDate", "", true);
$dg->addColumn("", "IsBlock", "", true);
$dg->addColumn("", "BranchID", "", true);
$dg->addColumn("", "BranchName", "", true);
$dg->addColumn("شماره ضمانت نامه", "RefRequestID", "", true);
$dg->addColumn("", "RequestID", "", true);
$dg->addColumn("", "wage", "", true);
$dg->addColumn("", "CancelDate", "", true);
$dg->addColumn("", "RegisterAmount", "", true);
$dg->addColumn("نام شرکت", "fullname", "", true);
$dg->addColumn("", "IsCurrent", "", true);
$dg->addColumn("", "SavePercent", "", true);
$dg->addColumn("", "SubjectDesc", "", true);
$dg->addColumn("", "SubjectNO", "", true);
$dg->addColumn("", "SendEnable", "", true);
$dg->addColumn("", "ActionType", "", true);
$dg->addColumn("", "SepasCode", "", true);
$dg->addColumn("", "orgNationalID", "", true);

$dg->addColumn("", "RelatedOrg", "", true); 
$dg->addColumn("", "amount", "", true);
$dg->addColumn("", "duration", "", true);
$dg->addColumn("", "SugBail", "", true);
$dg->addColumn("", "comments", "", true);
$dg->addColumn("", "ExtraComments", "", true);
$dg->addColumn("", "KnowledgeBase", "", true);
$dg->addColumn("", "EmpType", "", true);


$col = $dg->addColumn("شعبه", "BranchName");
$col->width = 120;

$col = $dg->addColumn("نوع", "TypeDesc");
$col->renderer = "function(v,p,r){p.tdAttr = \"data-qtip='\"+r.data.BranchName+\"'\"; return v;}";
$col->width = 80;

$col = $dg->addColumn("مبلغ درخواست", "amount", GridColumn::ColumnType_money);
$col->width = 100;

$col = $dg->addColumn("سازمان مربوطه", "organization");
$col->renderer = "WarrentyRequest.OrgRender";

$col = $dg->addColumn("تاریخ شروع", "StartDate", GridColumn::ColumnType_date);
$col->width = 70;

$col = $dg->addColumn("تاریخ پایان", "EndDate", GridColumn::ColumnType_date);
$col->width = 70;

$col = $dg->addColumn("بلوکه", "IsBlock");
$col->renderer = "function(v){return (v == 'YES') ? '√' : '';}";
$col->width = 40;
$col->align = "center";

$col = $dg->addColumn("وضعیت", "StepDesc", "");
$col->renderer = "function(v,p,r){ if(r.data.StatusID == ".WAR_STEPID_CANCEL.") return v + '<br>تاریخ ابطال :' + "
		. "MiladiToShamsi(r.data.CancelDate); return v; }";
$col->width = 80;

$col = $dg->addColumn("اسناد", "docs", "");
$col->width = 200;

$col = $dg->addColumn('عملیات', '', 'string');
$col->renderer = "WarrentyRequest.OperationRender";
$col->width = 50;
$col->align = "center";

$dg->addObject("WarrentyRequestObject.FilterObj");

if($accessObj->AddFlag)
	$dg->addButton("", "ایجاد ضمانتنامه جدید", "add", "function(){WarrentyRequestObject.AddNew();}");

$dg->EnableGrouping = true;
$dg->DefaultGroupField = "RefRequestID";
$dg->groupHeaderTpl = "ضمانتنامه شماره [ {[values.rows[0].data.RefRequestID]} ] به نام ".
		"[ {[values.rows[0].data.fullname]} ]";

$dg->emptyTextOfHiddenColumns = true;
$dg->height = 500;
$dg->pageSize = 15;
$dg->title = "ضمانت نامه ها";
$dg->DefaultSortField = "RefRequestID";
$dg->autoExpandColumn = "organization";
$grid = $dg->makeGrid_returnObjects();
?>
<script>
WarrentyRequestObject.grid = <?= $grid ?>;
WarrentyRequestObject.grid.getView().getRowClass = function(record, index)
	{
		if(record.data.IsEnded == "YES")
			return "greenRow";
		if(record.data.ActionType == "REJECT")
			return "pinkRow";
		return "";
	}	
	
WarrentyRequestObject.grid.render(WarrentyRequestObject.get("DivGrid"));
</script>
<center><br>
	<div><div id="RequestInfo"></div></div>
	<br>
	<div style="width:98%" id="DivGrid"></div>	
</center>