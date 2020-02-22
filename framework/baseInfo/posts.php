<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------
require_once("../../header.inc.php");
require_once inc_dataGrid;
 
//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg",$js_prefix_address . "baseInfo.data.php?task=SelectPosts","div_grid_user");

$col = $dg->addColumn("عنوان پست","PostName","string");
$col->editor = ColumnEditor::TextField();
$col->sortable = false;

$col = $dg->addColumn("امضا کننده حکم ماموریت", "MissionSigner", "string");
$col->editor = ColumnEditor::CheckField("", "YES");
$col->renderer = "function(v){if(v == 'YES') return '*';}";
$col->sortable = false;
$col->width = 140;

if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("حذف","PostID","");
	$col->renderer = "BSC_post.deleteRender";
	$col->sortable = false;
	$col->width = 40;
}
if($accessObj->AddFlag)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){BSC_postObject.Adding();}";
}
if($accessObj->AddFlag || $accessObj->EditFlag)
{
	$dg->enableRowEdit = true;
	$dg->rowEditOkHandler = "function(v,p,r){return BSC_postObject.saveData(v,p,r);}";
}
$dg->height = 350;
$dg->width = 780;
$dg->DefaultSortField = "PostName";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "PostName";
$dg->editorGrid = true;
$dg->title = "پست های سازمانی";
$dg->EnablePaging = false;
$dg->EnableSearch = false;
$grid = $dg->makeGrid_returnObjects();
?>
<style type="text/css">
.pinkRow, .pinkRow td,.pinkRow div{ background-color:#FFB8C9 !important;}
</style>
<script>
BSC_post.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function BSC_post()
{
	this.grid = <?= $grid?>;
	this.grid.render(this.get("div_grid"));
}

BSC_post.deleteRender = function(v,p,r)
{
	return "<div align='center' title='حذف ' class='remove' onclick='BSC_postObject.Deleting();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

BSC_post.prototype.Adding = function()
{
	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		PostID : ""
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

BSC_post.prototype.saveData = function(store,record)
{
    mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		params: {
			task: 'SavePost',
			record : Ext.encode(record.data)
		},
		url: this.address_prefix +'baseInfo.data.php',
		method: 'POST',

		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(st.success)
			{
				BSC_postObject.grid.getStore().load();
			}
			else
			{
				alert(st.data);
			}
		},
		failure: function(){}
	});
}

BSC_post.prototype.Deleting = function()
{
	var record = this.grid.getSelectionModel().getLastSelected();
	Ext.MessageBox.confirm("","آيا مايل به حذف مي باشيد؟", function(btn){
	
		if(btn == "no")
			return;
		
		me = BSC_postObject;
		
		Ext.Ajax.request({
		  	url : me.address_prefix + "baseInfo.data.php",
		  	method : "POST",
		  	params : {
		  		task : "DeletePost",
		  		PostID : record.data.PostID
		  	},
		  	success : function(response,o)
		  	{
				result = Ext.decode(response.responseText);
				if(!result.success)
				{
					if(result.data == "")
						Ext.MessageBox.alert("Error","عملیات مورد نظر با شکست مواجه شد");
					else
						Ext.MessageBox.alert("Error",result.data);
				}
		  		BSC_postObject.grid.getStore().load();
		  	}
		});
	});
}

var BSC_postObject = new BSC_post();

</script>
<center>
	<br>
	<div id="div_grid"></div>
</center>