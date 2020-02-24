<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	95.07
//-------------------------
require_once('../../header.inc.php');
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "doc.data.php?task=GetAllCostBlocks", "grid_div");

$dg->addColumn("", "CostBlockID", "", true);
$dg->addColumn("", "CostBlockType", "", true);
$dg->addColumn("", "title", "", true);
$dg->addColumn("", "ObjectID", "", true);

$col = $dg->addColumn("کد حساب", "CostCode");
$col->width = 80;

$col = $dg->addColumn("عنوان حساب", "CostDesc", "");

$col = $dg->addColumn("گروه تفصیلی", "TafsiliTypeDesc", "");
$col->width = 120;
$col = $dg->addColumn("تفصیلی", "TafsiliDesc", "");
$col->width = 120;

$col = $dg->addColumn("مبلغ بلوکه", "BlockAmount", GridColumn::ColumnType_money);
$col->width = 100;

$col = $dg->addColumn("تاریخ پایان", "EndDate", GridColumn::ColumnType_date);
$col->width = 100;

$col = $dg->addColumn("جزئیات", "details", "");
$col->width = 120;
$col->ellipsis = 20;

if($accessObj->AddFlag)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){CostBlockObject.AddCostBlock();}";
}
if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("عملیات", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return CostBlock.OperationRender(v,p,r);}";
	$col->width = 50;
}

$dg->title = "مبالغ بلوکه حساب ها";
$dg->height = 500;
$dg->width = 750;
$dg->DefaultSortField = "BlockID";
$dg->autoExpandColumn = "CostDesc";
$dg->emptyTextOfHiddenColumns = true;
$grid = $dg->makeGrid_returnObjects();

?>
<center>
    <form id="mainForm">
		<div id="newDiv"></div>
        <div id="grid_div"></div>
    </form>
</center>
<script>

CostBlock.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : '<?= $js_prefix_address ?>',

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function CostBlock(){

	this.grid = <?= $grid ?>;
	this.grid.plugins[0].on("beforeedit", function(editor,e){
		if(e.record.data.ObjectID*1 > 0)
			return false;
		if(!e.record.data.CostBlockID)
			return CostBlockObject.AddAccess;
		return CostBlockObject.EditAccess;
	});
	this.grid.render(this.get("grid_div"));
	
	this.formPanel = new Ext.form.Panel({
		frame : true,
		hidden : true,
		style : "margin:10px 0 10px",
		renderTo : this.get("newDiv"),
		width : 700,
		layout : {
			type : "table",
			columns : 2
		},
		defaults : {width : 300},
		title : "اطلاعات بلوکه حساب",
		items : [{
			xtype : "combo",
			fieldLabel : "کد حساب",
			store: new Ext.data.Store({
				fields:["CostID","CostCode","CostDesc", "TafsiliType1","TafsiliType2",{
					name : "fullDesc",
					convert : function(value,record){
						return "[ " + record.data.CostCode + " ] " + record.data.CostDesc
					}				
				}],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=SelectCostCode',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				}
			}),
			typeAhead: false,
			name : "CostID",
			colspan : 2,
			valueField : "CostID",
			displayField : "fullDesc",
			listConfig: {
				loadingText: 'در حال جستجو...',
				emptyText: 'فاقد اطلاعات'
			},
			listeners :{
				select : function(combo,records){
					if(records[0].data.TafsiliType1 != null)
					{
						CostBlockObject.formPanel.down("[name=TafsiliType]").setValue(records[0].data.TafsiliType1);
						combo = CostBlockObject.formPanel.down("[name=TafsiliID]");
						combo.setValue();
						combo.getStore().proxy.extraParams["TafsiliType"] = records[0].data.TafsiliType1;
						combo.getStore().load();
					}
				}
			}
		},{
			xtype : "combo",
			fieldLabel : "گروه تفصیلی",
			store: new Ext.data.Store({
				fields:["InfoID","InfoDesc"],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=SelectTafsiliGroups',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				autoLoad : true
			}),
			typeAhead: false,
			queryMode : "local",
			name : "TafsiliType",
			valueField : "InfoID",
			displayField : "InfoDesc",
			listeners : {
				select : function(combo,records){
					combo = CostBlockObject.formPanel.down("[name=TafsiliID]");
					combo.setValue();
					combo.getStore().proxy.extraParams["TafsiliType"] = this.getValue();
					combo.getStore().load();
				}
			}
		},
		{
			xtype : "combo",
			fieldLabel : "تفصیلی",
			store: new Ext.data.Store({
				fields:["TafsiliID","TafsiliCode","TafsiliDesc",{
					name : "fullDesc",
					convert : function(v,r){
						return "[" + r.data.TafsiliCode + "]" + r.data.TafsiliDesc;
					}
				}],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=GetAllTafsilis',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				listeners : {
					beforeload : function(store){
						if(!store.proxy.extraParams.TafsiliType)
						{
							group = CostBlockObject.formPanel.down("[name=TafsiliType]").getValue();
							if(group == "")
								return false;
							this.proxy.extraParams["TafsiliType"] = group;
						}
					}							
				}
			}),
			typeAhead: false,
			pageSize : 10,
			name : "TafsiliID",
			valueField : "TafsiliID",
			displayField : "fullDesc"
		},{
			xtype : "currencyfield",
			fieldLabel : "مبلغ",
			name : "BlockAmount",
			hideTrigger : true
		},{
			xtype : "shdatefield",
			fieldLabel : "تاریخ پایان",
			name : "EndDate"
		},{
			xtype : "textfield",
			fieldLabel : "شرح",
			name : "details",
			colspan : 2,
			width : 630
		},{
			xtype : "hidden",
			name : "CostBlockID"
		}],
		buttons : [{
			text : "ذخیره",
			iconCls : "save",
			handler : function(){CostBlockObject.SaveCostBlock();}
		},{
			text : "انصراف",
			iconCls : "undo",
			handler : function(){this.up('panel').hide();}
		}]
	});
}

CostBlock.OperationRender = function(v,p,r){
	
	if(r.data.ObjectID*1 > 0)
		return "";
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='CostBlockObject.DeleteCostBlock();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

CostBlock.prototype.AddCostBlock = function(){

	this.formPanel.show();
}

CostBlock.prototype.SaveCostBlock = function(){

	mask = new Ext.LoadMask(this.formPanel,{msg:'در حال ذخیره سازی ...'});
	mask.show();

	this.formPanel.getForm().submit({
		url: this.address_prefix +'doc.data.php',
		method: "POST",
		params: {
			task: "SaveCostBlock"
		},
		success: function(form,result){
			mask.hide();
			CostBlockObject.grid.getStore().load();
			CostBlockObject.formPanel.hide();
		},
		failure: function(form,action){
			mask.hide();
			Ext.MessageBox.alert("ERROR", action.result.data == "" ? "عملیات مورد نظر با شکست مواجه گردید" : action.result.data);
		}
	});
}

CostBlock.prototype.DeleteGroup = function(CostBlockType){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = CostBlockObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'doc.data.php',
			params:{
				task: "DeleteCostBlock",
				CostBlockID : record.data.CostBlockID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				sd = Ext.decode(response.responseText);
				if(sd.success)
				{
					CostBlockObject.grid.getStore().load();
				}	
				else
				{
					Ext.MessageBox.alert("ERROR", sd.data == "" ? "عملیات مورد نظر با شکست مواجه گردید" : sd.data);
				}
			},
			failure: function(){}
		});
	});
}

CostBlock.prototype.DeleteCostBlock = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = CostBlockObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'doc.data.php',
			params:{
				task: "DeleteCostBlock",
				CostBlockID : record.data.CostBlockID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				sd = Ext.decode(response.responseText);
				if(sd.success)
				{
					CostBlockObject.grid.getStore().load();
				}	
				else
				{
					Ext.MessageBox.alert("ERROR", sd.data == "" ? "عملیات مورد نظر با شکست مواجه گردید" : sd.data);
				}
			},
			failure: function(){}
		});
	});
}

var CostBlockObject = new CostBlock();	

</script>