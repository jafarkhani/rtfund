<?php
//---------------------------
// programmer:	Jafarkhani
// create Date: 94.06
//-----------------------
require_once '../../header.inc.php';
require_once inc_dataGrid;

require_once 'BranchAccess.js.php';

$dgh = new sadaf_datagrid("dgh1",$js_prefix_address."baseInfo.data.php?task=SelectUserBranches","div_dgu");

$dgh->addColumn("","BranchName",'string',true);
$dgh->addColumn("","fullname",'string',true);

$col = $dgh->addColumn("نام کاربر ", "PersonID");
$col->renderer="function(v,p,r){return r.data.fullname;}";
$col->editor = "BranchAccessObject.PersonCombo";;

$col=$dgh->addColumn("شعبه", "BranchID");
$col->renderer="function(v,p,r){return r.data.BranchName;}";
$col->editor = "BranchAccessObject.BranchCombo";;
$col->width = 200;

$col = $dgh->addColumn("حذف", "", "string");
$col->renderer = "BranchAccess.deleteRender";
$col->width = 10;

$dgh->addButton = true;
$dgh->addHandler = "function(v,p,r){ return BranchAccessObject.Add(v,p,r);}";
$dgh->title =" کاربران سیستم - شعب منتسب";

$dgh->enableRowEdit = true ;
$dgh->rowEditOkHandler = "function(v,p,r){ return BranchAccess.Save(v,p,r);}";

$dgh->emptyTextOfHiddenColumns=true;
$dgh->EnableRowNumber = true;
$dgh->width = 600;
$dgh->DefaultSortField = "UserName";
$dgh->DefaultSortDir = "ASC";
$dgh->height = 400;
$dgh->EnableSearch = true;
$dgh->EnableRowNumber = true;
$dgh->pageSize=12;
$gridUsers = $dgh->makeGrid_returnObjects();
?>
<script>
	BranchAccessObject.grid = <?= $gridUsers?>;                
	BranchAccessObject.grid.render(BranchAccessObject.get("div_dgu"));
	
	BranchAccessObject.grid.plugins[0].on("beforeedit", function(editor,e){

		if(e.record.data.PersonID > 0)
			return false;
	});

</script>
<center>
<br>
<div id="form_Users">
	<div id="div_dgu"></div>
	<br><br>
	<div align="center" id="InfoPNL" ></div>
</div>
</center>


