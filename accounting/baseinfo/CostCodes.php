<?php
//---------------------------
// developer:	Sh.Jafarkhani
// Date:		94.06
//---------------------------
require_once '../../header.inc.php';
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg_cost = new sadaf_datagrid('cost',$js_prefix_address."baseinfo.data.php?task=SelectCostCode",'divCost');

$dg_cost->addcolumn('','IsActive',"",true);
$dg_cost->addcolumn('','IsNew',"",true);
$dg_cost->addcolumn('','CostID',"",true);
$dg_cost->addcolumn('','CostCode',"",true);
$dg_cost->addcolumn('','level1',"",true);
$dg_cost->addcolumn('','level2',"",true);
$dg_cost->addcolumn('','level3',"",true);
$dg_cost->addcolumn('','TafsiliType1',"",true);
$dg_cost->addcolumn('','TafsiliType2',"",true);
$dg_cost->addcolumn('','TafsiliType3',"",true);
$dg_cost->addcolumn('','IsBlockable',"",true);
$dg_cost->addcolumn('','IsCheque',"",true);
$dg_cost->addcolumn('','param1',"",true);
$dg_cost->addcolumn('','param2',"",true);
$dg_cost->addcolumn('','param3',"",true);

$col = $dg_cost->addcolumn('کد حساب','CostCode');
$col->width = 60;

$col = $dg_cost->addcolumn('گروه حساب','LevelTitle0');

$col = $dg_cost->addcolumn("کل", "LevelTitle1");

$col = $dg_cost->addcolumn("معین1", "LevelTitle2");

$col = $dg_cost->addcolumn("معین2", "LevelTitle3");

$col = $dg_cost->addcolumn("معین3", "LevelTitle4");

$col = $dg_cost->addcolumn("تفصیلی1", "TafsiliTypeDesc1");

$col = $dg_cost->addcolumn("تفصیلی2", "TafsiliTypeDesc2");

$col = $dg_cost->addcolumn("تفصیلی3", "TafsiliTypeDesc3");

$col = $dg_cost->addcolumn('آیتم اطلاعاتی1','ParamDesc1');

$col = $dg_cost->addcolumn('آیتم اطلاعاتی2','ParamDesc2');

$col = $dg_cost->addcolumn('آیتم اطلاعاتی3','ParamDesc3');

//$col = $dg_cost->addcolumn('ObjectType1','ObjectType1');
$col = $dg_cost->addcolumn('ObjectType2','ObjectType2');
//$col = $dg_cost->addcolumn('ObjectType3','ObjectType3');


if($accessObj->EditFlag	)
{
	$col=$dg_cost->addcolumn('ویرایش','','string');
	$col->renderer = "CostCode.EditCost";
	$col->width=40;
}
if($accessObj->RemoveFlag)
{
	$col=$dg_cost->addcolumn('حذف','','string');
	$col->renderer = "CostCode.RemoveCost";
	$col->width=40;
}

if($accessObj->AddFlag)
{
	$dg_cost->addButton = true;
	$dg_cost->addHandler = 'function(){return CostCodeObj.BeforeSaveCost(false);}';
}

$dg_cost->addButton('prn__btn', 'چاپ کد حسابها', 'print', 'function(e){ return CostCodeObj.PrintCost(); }');

$dg_cost->title = 'کدینگ حساب';
$dg_cost->pageSize = 19;
$dg_cost->pageSize = 12;
//$dg_cost->height = 500;
$dg_cost->autoExpandColumn = "LevelTitle1";
$dg_cost->DefaultSortField = "CostCode";
$dg_cost->DefaultSortDir = "ASC";
$dgCost = $dg_cost->makeGrid_returnObjects();

require_once 'CostCode.js.php';

?>

<script type="text/javascript" >
	
	CostCode.prototype.afterLoad=function(){
		
		this.grid=<?= $dgCost?>;
		this.grid.getView().getRowClass = function(record, index)
		{
			if(record.data.IsNew == "YES")
				return "yellowRow";
			if(record.data.IsActive == "NO")
				return "pinkRow";
		}	

		this.grid.render(this.get("divCost"));
	}
	
	var CostCodeObj = new CostCode();
	
</script>
<center>
	<div><div id="mainform"></div>
	<br>	
	</div>
	<div style='width:98%' id="divCost"></div>
</center>
