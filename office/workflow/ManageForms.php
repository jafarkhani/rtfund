<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.07
//-----------------------------

require_once '../header.inc.php';
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "wfm.data.php?task=SelectAllForms", "grid_div");

$dg->addColumn("", "FlowID", "", true);
$dg->addColumn("", "RowID", "", true);
$dg->addColumn("", "StepID", "", true);
$dg->addColumn("", "ObjectID", "", true);
$dg->addColumn("", "ObjectID2", "", true);
$dg->addColumn("", "PersonID", "", true);
$dg->addColumn("", "ActionType", "", true);
$dg->addColumn("", "ActionComment", "", true);
$dg->addColumn("", "url", "", true);
$dg->addColumn("", "parameter", "", true);
$dg->addColumn("", "target", "", true);
$dg->addColumn("", "param4", "", true);


$col = $dg->addColumn("نوع فرم", "ObjectTypeDesc", "");
$col->width = 130;

$col = $dg->addColumn("اطلاعات فرم", "ObjectDesc", "");

$col = $dg->addColumn("وضعیت", "StepDesc", "");
$col->width = 130;

$col = $dg->addColumn('عملیات', '', 'string');
$col->renderer = "ManageForm.OperationRender";
$col->width = 50;
$col->align = "center";

$dg->emptyTextOfHiddenColumns = true;
$dg->height = 500;
//$dg->width = 770;
$dg->title = "درخواست های وام";
$dg->DefaultSortField = "ActionDate";
$dg->autoExpandColumn = "ObjectDesc";
$grid = $dg->makeGrid_returnObjects();
?>
<script>
 
ManageForm.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function ManageForm(){
	
	this.grid = <?= $grid ?>;
	this.grid.getView().getRowClass = function(record, index)
	{
		if(record.data.ActionType == "REJECT")
			return "pinkRow";
		return "";
	}	

	this.grid.render(this.get("DivGrid"));
}

ManageForm.OperationRender = function(value, p, record){
	
	return "<div  title='عملیات' class='setting' onclick='ManageFormObject.OperationMenu(event);' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

ManageForm.prototype.OperationMenu = function(e){

	record = this.grid.getSelectionModel().getLastSelected();
	var op_menu = new Ext.menu.Menu();
	
	if(this.EditAccess)
	{
		op_menu.add({text: 'تغییر وضعیت',iconCls: 'refresh', 
		handler : function(){ return ManageFormObject.SetStatus(record); }});
	
		op_menu.add({text: 'برگشت شروع فرم',iconCls: 'undo', 
		handler : function(){ return ManageFormObject.ReturnStartFlow(record); }});
	
		op_menu.add({text: 'ابطال گردش فرم',iconCls: 'undo', 
		handler : function(){ return ManageFormObject.DeleteFlow(record); }});
	
		op_menu.add({text: 'اطلاعات آیتم',iconCls: 'info2', 
			handler : function(){ return ManageFormObject.FormInfo(); }});
	}
	op_menu.add({text: 'پیوستها',iconCls: 'attach', 
		handler : function(){ return ManageFormObject.ShowAttaches(); }});
		
	op_menu.add({text: 'سابقه درخواست',iconCls: 'history', 
		handler : function(){ return ManageFormObject.ShowHistory(); }});
	
	op_menu.showAt(e.pageX-120, e.pageY);
}

ManageForm.prototype.FormInfo = function(){
	
	if(!this.FormInfoWindow)
	{
		this.FormInfoWindow = new Ext.window.Window({
			width : 1100,
			height : 660,
			autoScroll : true,
			modal : true,
			title : "اطلاعات فرم مربوطه",
			bodyStyle : "background-color:white",
			loader : {
				scripts : true
			},
			closeAction : "hide",
			buttons : [{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
	}
	var record = this.grid.getSelectionModel().getLastSelected();
	if(record.data.target == "1")
	{
		arr = record.data.parameter.split(",");
		var params = arr[0] + "=" + record.data.ObjectID;
		params += arr.length>1 ? "&" + arr[1] + "=" + record.data.ObjectID2 : "";
		
		window.open(record.data.url + "?" + params);
		return;
	}
	this.FormInfoWindow.show();	
	
	params = {
		ExtTabID : this.FormInfoWindow.getEl().id ,
		ReadOnly : true
	};
	
	arr = record.data.parameter.split(",");
	params[ arr[0] ] = record.data.ObjectID;
	if(arr.length >1)
		params[ arr[1] ]= record.data.ObjectID2;
	
	this.FormInfoWindow.loader.load({
		url : record.data.url,
		params : params
	}); 
}

ManageForm.prototype.ShowHistory = function(){

	if(!this.HistoryWin)
	{
		this.HistoryWin = new Ext.window.Window({
			title: 'سابقه گردش',
			modal : true,
			autoScroll : true,
			width: 700,
			height : 500,
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "history.php",
				scripts : true
			},
			buttons : [{
					text : "بازگشت",
					iconCls : "undo",
					handler : function(){
						this.up('window').hide();
					}
				}]
		});
		Ext.getCmp(this.TabID).add(this.HistoryWin);
	}
	this.HistoryWin.show();
	this.HistoryWin.center();
	this.HistoryWin.loader.load({
		params : {
			RowID : this.grid.getSelectionModel().getLastSelected().data.RowID
		}
	});
}

ManageForm.prototype.ShowAttaches = function(){

	if(!this.documentWin)
	{
		this.documentWin = new Ext.window.Window({
			width : 720,
			height : 440,
			modal : true,
			bodyStyle : "background-color:white;padding: 0 10px 0 10px",
			closeAction : "hide",
			loader : {
				url : "../../office/dms/documents.php",
				scripts : true
			},
			buttons :[{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		Ext.getCmp(this.TabID).add(this.documentWin);
	}

	this.documentWin.show();
	this.documentWin.center();
	
	var record = this.grid.getSelectionModel().getLastSelected();
	this.documentWin.loader.load({
		scripts : true,
		params : {
			ExtTabID : this.documentWin.getEl().id,
			ObjectType : record.data.param4,
			ObjectID : record.data.ObjectID
		}
	});
}

ManageForm.prototype.SetStatus = function(record){
	
	this.setStatusWin = new Ext.window.Window({
		width : 412,
		height : 198,
		modal : true,
		title : "تغییر وضعیت",
		defaults : {width : 380},
		bodyStyle : "background-color:white",
		items : [{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + "wfm.data.php?task=selectFlowSteps&FlowID=" + record.data.FlowID,
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['StepID','StepDesc'],
				autoLoad : true					
			}),
			fieldLabel : "مرحله جدید",
			queryMode : 'local',
			allowBlank : false,
			displayField : "StepDesc",
			valueField : "StepID",
			itemId : "StepID"
		},{
			xtype : "textarea",
			itemId : "comment",
			fieldLabel : "توضیحات"
		}],
		closeAction : "destroy",
		buttons : [{
			text : "تغییر وضعیت",				
			iconCls : "save",
			itemId : "btn_save",
			handler : function(){
				StepID = this.up('window').getComponent("StepID").getValue();
				comment = this.up('window').getComponent("comment").getValue();
				
				mask = new Ext.LoadMask(ManageFormObject.grid, {msg:'در حال ذخيره سازي...'});
				mask.show();  

				Ext.Ajax.request({
					methos : "post",
					url : ManageFormObject.address_prefix + "wfm.data.php",
					params : {
						task : "ChangeStatus",
						mode : "CONFIRM",
						StepID : StepID,
						RowID : record.data.RowID,
						ActionComment : "[تغییر وضعیت]" + comment
					},
					success : function(){
						mask.hide();
						ManageFormObject.grid.getStore().load();
					}
				});
				this.up('window').hide();
			}
		},{
			text : "بازگشت",
			iconCls : "undo",
			handler : function(){this.up('window').close();}
		}]
	});

	Ext.getCmp(this.TabID).add(this.setStatusWin);
	this.setStatusWin.show();
	this.setStatusWin.center();
}

ManageForm.prototype.ReturnStartFlow = function(record){
	
	Ext.MessageBox.confirm("","آیا مایل به برگشت ارسال اولیه می باشید؟", function(btn){
		
		if(btn == "no")
			return;
		
		mask = new Ext.LoadMask(ManageFormObject.grid, {msg:'در حال ذخيره سازي...'});
		mask.show();  

		Ext.Ajax.request({
			methos : "post",
			url : ManageFormObject.address_prefix + "wfm.data.php",
			params : {
				task : "ReturnStartFlow",
				ObjectID : record.data.ObjectID,
				FlowID : record.data.FlowID
			},
			success : function(){
				mask.hide();
				ManageFormObject.grid.getStore().load();
			}
		});
	});
				
}

ManageForm.prototype.DeleteFlow = function(record){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف گردش فرم و خام شدن فرم می باشید؟", function(btn){
		
		if(btn == "no")
			return;
		
		mask = new Ext.LoadMask(ManageFormObject.grid, {msg:'در حال ذخيره سازي...'});
		mask.show();  

		Ext.Ajax.request({
			methos : "post",
			url : ManageFormObject.address_prefix + "wfm.data.php",
			params : {
				task : "DeleteAllFlow",
				ObjectID : record.data.ObjectID,
				FlowID : record.data.FlowID
			},
			success : function(response){
				result = Ext.decode(response.responseText);
				mask.hide();
				if(result.success)
					ManageFormObject.grid.getStore().load();
				else
					Ext.MessageBox.alert("Error", result.data == "" ? "عملیات مورد نظر با شکست مواجه شد" : result.data)
			}
		});
	});
				
}
ManageFormObject = new ManageForm();
</script>
<center><br>
	<div style="width: 95%" id="DivGrid"></div>
</center>