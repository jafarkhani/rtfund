<?php
//---------------------------
// programmer:	Jafarkhani
// create Date:	94.06
//---------------------------

require_once '../../header.inc.php';
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

require_once 'blocks.js.php';

$Essence = array(array('val' => 'DEBTOR', 'name' => 'بدهکار'),
    array('val' => 'CREDITOR', 'name' => 'بستانکار'),
    array('val' => 'NONE', 'name' => 'هیچکدام'));

$temp = array(
	array("LevelID" => 0, "LevelTitle" => "گروه حساب", "HasEssence" => false, "HasGroup" => false),
	array("LevelID" => 1, "LevelTitle" => "حساب کل", "HasEssence" => true, "HasGroup" => true),
	array("LevelID" => 2, "LevelTitle" => "حساب معین1", "HasEssence" => false, "HasGroup" => false),
	array("LevelID" => 3, "LevelTitle" => "حساب معین2", "HasEssence" => false, "HasGroup" => false),
	array("LevelID" => 4, "LevelTitle" => "حساب معین3", "HasEssence" => false, "HasGroup" => false)
);
for ($i = 0; $i < count($temp); $i++) {
    $levelID = $temp[$i]['LevelID'];
    $levelTitle = $temp[$i]['LevelTitle'];

    $BlockGrid = new sadaf_datagrid('BlockGrid', $js_prefix_address . 'baseinfo.data.php?' .
		'task=SelectBlocks&level=' . $levelID, 'BlockGrid_Box');

    $BlockGrid->addColumn('', "BlockID", "", true);
    $BlockGrid->addColumn('', "LevelID", "", true);
    $BlockGrid->addColumn('', "IsActive", "", true);
	$BlockGrid->addColumn('', "IsNew", "", true);
	$BlockGrid->addColumn('', "MainCostCode", "", true);

    $col = $BlockGrid->addColumn('کد', "BlockCode");
    $col->width = 80;
    $col->editor = ColumnEditor::TextField();

    $col = $BlockGrid->addColumn('شرح', "BlockDesc");
    $col->editor = ColumnEditor::TextField();
	
	if ($temp[$i]["HasGroup"]) {
        $col = $BlockGrid->addColumn('گروه حساب', "GroupID");
        $col->width = 150;
		if($accessObj->AddFlag || $accessObj->EditFlag)
		{
			$groups = PdoDataAccess::runquery ("select * from ACC_blocks where levelID=0");
			$col->editor = ColumnEditor::ComboBox($groups, 'BlockID', 'BlockDesc');
		}
			
    }
	
	if ($temp[$i]["HasEssence"]) {
        $col = $BlockGrid->addColumn('ماهیت', "essence");
        $col->width = 100;
		if($accessObj->AddFlag || $accessObj->EditFlag)
			$col->editor = ColumnEditor::ComboBox($Essence, 'val', 'name');
		
		$col = $BlockGrid->addColumn('کد حساب انتقالی', "MainCostID");
		$col->renderer = "function(v,p,r){return r.data.MainCostCode}";
        $col->width = 200;
		if($accessObj->AddFlag || $accessObj->EditFlag)
			$col->editor = "BlockObj.accountCombo";
    }

	if($accessObj->AddFlag)
	{
		$BlockGrid->addButton = true;
		$BlockGrid->addHandler = "function(){return BlockObj.NewRowBlock(" . $i . ");}";
	}
	$BlockGrid->enableRowEdit = true;
	$BlockGrid->rowEditOkHandler = "function(){return BlockObj.SaveBlock(" . $i . ");}";
	
	if($accessObj->RemoveFlag)
	{
		$col = $BlockGrid->addColumn('حذف', '', 'string');
		$col->renderer = "function(v,p,r){return BlockObj.RemoveBlock(" . $i . ",r);}";
		$col->width = 50;
	}
    $BlockGrid->width = 700;
    $BlockGrid->title = $levelTitle;
    $BlockGrid->autoExpandColumn = "BlockDesc";
    $BlockGrid->pageSize = 15;
    $BlockGrid->height = 500;
    $BlockGrid->PrintButton=true;
    $GBlock = $BlockGrid->makeGrid_returnObjects();
    ?>
    <script type="text/javascript">

        BlockObj.levelID<?= $i ?> = <?= $levelID ?>;
        BlockObj.grid<?= $i ?> = <?= $GBlock ?>;
		BlockObj.grid<?= $i ?>.plugins[0].on("beforeedit", function(editor,e){
			if(!e.record.data.BlockID)
				return BlockObj.AddAccess;
			return BlockObj.EditAccess;
		});
		BlockObj.grid<?= $i ?>.getView().getRowClass = function(record, index)
		{
			if(record.data.IsNew == "YES")
				return "yellowRow";
		}	
		
		
		BlockObj.mainTab.add({
			title:"<?= $levelTitle ?>",
			style:'padding:5px',
			width: 780,
			items: [BlockObj.grid<?= $i ?>]
		});

    </script>
<? } ?>
<center>
    <br>
    <div id="mainTab">
	</div>
</center>