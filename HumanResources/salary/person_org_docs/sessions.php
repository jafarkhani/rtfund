<?php
//---------------------------
// programmer:	Mahdipour
// create Date:	91.06
//---------------------------
require_once '../../header.inc.php';
require_once inc_dataGrid;
require_once inc_dataReader;

require_once 'sessions.js.php';

$dg = new sadaf_datagrid("BGrid", $js_prefix_address . "sessions.data.php?task=Searchbank", "BDIV");

$dg->addColumn("کد جلسه", "SessionID","",true);

$col = $dg->addColumn("نام کامل فرد", "pfname", "string",true);
$col->renderer = "function(v){ return ' '; }" ;

$col = $dg->addColumn("نام خانوادگی", "plname", "string",true);
$col->renderer = "function(v){ return ' '; }" ;

$col = $dg->addColumn("عنوان قلم", "full_title", "string",true);
$col->renderer = "function(v){ return ' '; }" ;

$col = $dg->addColumn("نام و نام خانوادگی", "PersonID", "int");
$col->renderer = "function(v,p,r){return r.data.pfname + '  ' + r.data.plname }";
$col->editor = "bankObject.personCombo";

$col = $dg->addColumn("عنوان قلم", "salary_item_type_id", "int");
$col->renderer = "function(v,p,r){return r.data.full_title }";
$col->editor = "bankObject.SITCombo";
$col->width = 200;

$col = $dg->addColumn("ساعات/جلسه", "TotalHour", "string");
$col->editor = ColumnEditor::TextField(true);
$col->width = 80;    

$col = $dg->addColumn("تاریخ", "SessionDate", "string");
$col->renderer = "function(v){return MiladiToShamsi(v);}";
$col->editor = ColumnEditor::SHDateField();
$col->width = 90;

$col = $dg->addColumn("عملیات", "", "string");
$col->renderer = "  function(v,p,r){  return bank.opRender(v,p,r);}";
$col->width = 80; 

$dg->addButton = true;
$dg->addHandler = " function(){  bankObject.AddBnak();}";

$dg->pageSize = "15";
$dg->EnableSearch = false ;
$dg->width = 780;
$dg->height = 500;
$dg->title = " حق الجلسه";
$dg->autoExpandColumn = "PersonID";

$dg->DefaultSortField = "SessionDate";
$dg->DefaultSortDir = "DESC";

$dg->enableRowEdit = true ;
$dg->rowEditOkHandler = "function(v,p,r){ return bankObject.editBank(v,p,r);}";

$grid = $dg->makeGrid_returnObjects();

?>
<script>
    bankObject.grid = <?=$grid?>;  
    bankObject.grid.render("BDIV");    
</script>
<center>
    <div id="ErrorDiv" style="width:40%"></div>
    <div id="BDIV" style="width:100%"></div>
</center>
