<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------
require_once("../../header.inc.php");
require_once inc_dataGrid;
require_once 'persons.js.php';

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg",$js_prefix_address . "persons.data.php?task=selectPersons&full=true", "div_grid_persons");

$dg->addColumn("PID","PersonID","string");
$dg->addColumn("","IsReal","string", true);
$dg->addColumn("","fname","string", true);
$dg->addColumn("","lname","string", true);
$dg->addColumn("","sex","string", true);
$dg->addColumn("","NationalID","string", true);
$dg->addColumn("","CompanyName","string", true);
$dg->addColumn("","EconomicID","string", true);
$dg->addColumn("","PhoneNo","string", true);
$dg->addColumn("","mobile","string", true);
$dg->addColumn("","email","string", true);
$dg->addColumn("","address","string", true);
$dg->addColumn("","IsActive","string", true);
$dg->addColumn("","ShareNo","string", true);
$dg->addColumn("","IsConfirm","string", true);
$dg->addColumn("","AttCode","string", true);
$dg->addColumn("","IsSigner","string", true);


$col = $dg->addColumn("نام و نام خانوادگی","fullname","string");

$col = $dg->addColumn("نام كاربري","UserName","string");
$col->width = 120;
$col = $dg->addColumn("تلفن ثابت","PhoneNo","string");
$col->width = 100;
$col = $dg->addColumn("تلفن همراه","mobile","string");
$col->width = 100;

$col = $dg->addColumn("<font style=font-size:10px>کاربر</font>","IsStaff","string");
$col->renderer = "function(v){return (v=='YES') ? '٭' : '';}";
$col->align = "center";
$col->width = 35;

$col = $dg->addColumn("<font style=font-size:10px>مشتری</font>","IsCustomer","string");
$col->renderer = "function(v){return (v=='YES') ? '٭' : '';}";
$col->align = "center";
$col->width = 35;

$col = $dg->addColumn("<font style=font-size:10px>سهامدار</font>","IsShareholder","string");
$col->renderer = "function(v){return (v=='YES') ? '٭' : '';}";
$col->align = "center";
$col->width = 35;

$col = $dg->addColumn("<font style=font-size:10px>سرمایه گذار</font>","IsAgent","string");
$col->renderer = "function(v){return (v=='YES') ? '٭' : '';}";
$col->align = "center";
$col->width = 35;

$col = $dg->addColumn("<font style=font-size:10px>حامی</font>","IsSupporter","string");
$col->renderer = "function(v){return (v=='YES') ? '٭' : '';}";
$col->align = "center";
$col->width = 35;

$col = $dg->addColumn("<font style=font-size:10px>کارشناس</font>","IsExpert","string");
$col->renderer = "function(v){return (v=='YES') ? '٭' : '';}";
$col->align = "center";
$col->width = 35;

$dg->addObject("PersonObject.FilterObj");

if($accessObj->AddFlag)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){PersonObject.Adding();}";
}

if($accessObj->EditFlag)
{
	$dg->addButton("", "تایید", "tick", "function(){PersonObject.Confirm();}");
	
	$col = $dg->addColumn("","","");
	$col->renderer = "Person.editRender";
	$col->sortable = false;
	$col->width = 40;
}
if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("حذف","personID","");
	$col->renderer = "Person.deleteRender";
	$col->sortable = false;
	$col->width = 40;
}

$dg->height = 500;
$dg->pageSize = 15;
$dg->width = 950;
$dg->DefaultSortField = "fullname";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "fullname";
$dg->emptyTextOfHiddenColumns = true;
$grid = $dg->makeGrid_returnObjects();

?>
<style type="text/css">
.pinkRow, .pinkRow td,.pinkRow div{ background-color:#FFB8C9 !important;}
</style>
<script>

var PersonObject = new Person();

PersonObject.grid = <?= $grid?>;
PersonObject.grid.getView().getRowClass = function(record)
{
	if(record.data.IsActive == "NO")
		return "pinkRow";
	if(record.data.IsActive == "PENDING")
		return "yellowRow";
	if(record.data.IsConfirm == "YES")
		return "greenRow";
	return "";
}
PersonObject.grid.on("itemdblclick", function(view, record){
			
		if(record.data.IsActive == "NO")
			return;
		framework.OpenPage("/framework/person/PersonInfo.php", "اطلاعات ذینفع", 
		{
			PersonID : record.data.PersonID
		});

	});
PersonObject.grid.render(PersonObject.get("div_grid_user"));

</script>
<center>
	<div id="div_info"></div>
	<br>
	<div id="div_grid_user"></div>
	<br>
	<div id="info"></div>
</center>
