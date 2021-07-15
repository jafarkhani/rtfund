<?php
//---------------------------
// programmer:	Mahdipour
// create Date:	400.02
//---------------------------
ini_set("display_errors", "on");
require_once '../../header.inc.php';
require_once inc_dataGrid;
require_once inc_dataReader;

require_once 'ManageWarrentyReq.js.php';

$dg = new sadaf_datagrid("WGrid", $js_prefix_address . "ManageWarrentyReq.data.php?task=searchWR", "WDIV");

$dg->addColumn("کد پرسنلی", "PersonID", "int",true);
$dg->addColumn("شماره نامه ", "LetterNo", "int",true); 

$col = $dg->addColumn("کد ضمانت  نامه", "BID", "int");
$col->width = 100;

$col = $dg->addColumn("نام شخص حقیقی/حقوقی ", "fullname", "");
$col->width = 150;

$col = $dg->addColumn("نوع ضمانت نامه ", "BailTypeTitle","string");
$col->width = 130;

$col = $dg->addColumn("موضوع قرارداد", "subject","string");

$col = $dg->addColumn("وضعیت فرم", "status","string");
$col->width = 100;

$col = $dg->addColumn("وضعیت ضمانت نامه", "ProcessStatus","string");
$col->width = 110;

$col = $dg->addColumn("عملیات", "", "string");
//$col->renderer = "function(v,p,r){return WelfareCenters.opRender(v,p,r);}";
$col->renderer = "WelfareCenters.OperationRender";
$col->width = 60;

$dg->addColumn("کد نوع ضمانت نامه ", "BailType", "int",true); 
$dg->addColumn(" پارامتر1", "param1", "int",true); 
$dg->addColumn(" پارامتر2", "param2", "int",true); 
$dg->addColumn(" پارامتر3", "param3", "int",true); 
$dg->addColumn(" پارامتر4", "param4", "int",true); 
$dg->addColumn(" پارامتر5", "param5", "int",true); 
$dg->addColumn(" ", "IID", "int",true); 

$dg->addButton = true;
$dg->addHandler = "function(){WelfareCentersObject.AddWFC();}";

$dg->pageSize = "15";
$dg->emptyTextOfHiddenColumns = true;
$dg->width = 860;
$dg->height = 520;
$dg->title = "فهرست ضمانت نامه های درخواستی";
$dg->autoExpandColumn = "subject";
$dg->DefaultSortField = "BID"; 
$dg->DefaultSortDir = "DESC";

$grid = $dg->makeGrid_returnObjects();

?>
<style>.suite {background-image:url('/fumservices/HTL/img/suite.png') !important;}</style>

<script>
    WelfareCentersObject.grid = <?=$grid?>;  
    WelfareCentersObject.grid.render("WDIV");
    
</script>
<center>
    <div id="ErrorDiv" style="width:40%"></div><br>
    <form id="mainForm"> <div id="mainpanel"></div> </form>    <br>
    <div id="WDIV" style="width:100%"></div>
</center>
