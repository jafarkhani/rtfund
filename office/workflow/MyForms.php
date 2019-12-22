<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.07
//-----------------------------

require_once '../header.inc.php';
require_once inc_dataGrid;

$IsSend = !empty($_REQUEST["SendForms"]) ? true : false;

$dg = new sadaf_datagrid("dg", $js_prefix_address . 
		(!$IsSend ? "wfm.data.php?task=SelectAllForms&MyForms=true" : 
					  "wfm.data.php?task=SelectAllForms&SendForms=true"), "grid_div");

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
$dg->addColumn("", "LoanRequestID", "", true);
$dg->addColumn("", "IsEnded", "", true);

if(!$IsSend)
{
	$col = $dg->addColumn('<input type=checkbox onclick=MyForm.CheckAll(this)>', "", "");
	$col->renderer = "function(v,p,r){return MyForm.ChooseRender(v,p,r);}";
	$col->width = 30;
	$col->sortable = false;
}
$col = $dg->addColumn("نوع فرم", "ObjectTypeDesc", "");
$col->width = 120; 

$col = $dg->addColumn("اطلاعات فرم", "ObjectDesc", "");
$col->renderer = "MyForm.ObjectDescRender";

if(!$IsSend)
{
	$col = $dg->addColumn("تاریخ دریافت", "ActionDate", GridColumn::ColumnType_date);
	$col->width = 90;
	
	$col = $dg->addColumn("ارسال کننده", "fullname");
	$col->width = 200;
}
else
{
	$col = $dg->addColumn("تاریخ عملیات", "SendActionDate", GridColumn::ColumnType_date);
	$col->width = 90;
}

$col = $dg->addColumn("وضعیت جاری", "StepDesc", "");
$col->width = 100;

$col = $dg->addColumn('عملیات', '', 'string');
$col->renderer = "MyForm.OperationRender";
$col->width = 50;
$col->align = "center";

if(!$IsSend)
	$dg->addButton("", "تایید گروهی", "tick2", "function(){return MyFormObject.beforeChangeStatus('CONFIRM');}");

$dg->emptyTextOfHiddenColumns = true;
$dg->height = 480;
$dg->HeaderMenu = false;
$dg->title = $IsSend ? "فرم های ارسالی" : "فرم های رسیده";
$dg->DefaultSortField = "ActionDate";
$dg->autoExpandColumn = "ObjectDesc";
$grid = $dg->makeGrid_returnObjects();
?>
<script>

MyForm.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	IsSend : <?= $IsSend ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function MyForm(){
	this.grid = <?= $grid ?>;
	this.grid.getView().getRowClass = function(record, index)
		{
			if(record.data.IsEnded == "YES")
				return "greenRow";
			return "";
		}	
	this.grid.render(this.get("DivGrid"));
}

MyForm.ObjectDescRender = function(value, p, record){
	
	if(record.data.LoanRequestID == "0" || record.data.LoanRequestID == null)
		return value;
	
	return value + "<a href=javascript:void(0) onclick=MyForm.OpenLoan("+record.data.LoanRequestID+") >"  + 
			"[ وام شماره " + record.data.LoanRequestID + " ] </a>";
}

MyForm.OpenLoan = function(LoanRequestID)
{
	framework.OpenPage('/loan/request/RequestInfo.php','اطلاعات درخواست',{
		RequestID :	LoanRequestID,
		ExtTabID : MyFormObject.TabID
	});
}

MyForm.OperationRender = function(value, p, record){
	
	return "<div  title='عملیات' class='setting' onclick='MyFormObject.OperationMenu(event);' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

MyForm.ChooseRender = function(v,p,r){
		
	return "<input type=checkbox name='chk_RowID_" + r.data.RowID + "' id='chk_RowID_" + r.data.RowID + "'>";
}

MyForm.CheckAll = function(checkAllElem){
	
	elems = MyFormObject.get("mainForm").getElementsByTagName("input");
	for(i=0; i<elems.length; i++)
		if(elems[i].id.indexOf("chk_RowID_") != -1)
			elems[i].checked = checkAllElem.checked;
}

MyForm.prototype.OperationMenu = function(e){

	record = this.grid.getSelectionModel().getLastSelected();
	var op_menu = new Ext.menu.Menu();
	
	op_menu.add({text: 'اطلاعات آیتم',iconCls: 'info2', 
		handler : function(){ return MyFormObject.FormInfo(); }});
	if(!this.IsSend)
	{
		op_menu.add({text: 'تایید درخواست',iconCls: 'tick', 
		handler : function(){ return MyFormObject.beforeChangeStatus('CONFIRM'); }});

		op_menu.add({text: 'رد درخواست',iconCls: 'cross',
		handler : function(){ return MyFormObject.beforeChangeStatus('REJECT'); }});
	}
	op_menu.add({text: 'پیوستها',iconCls: 'attach', 
		handler : function(){ return MyFormObject.ShowAttaches(); }});
	
	op_menu.add({text: 'سابقه درخواست',iconCls: 'history', 
		handler : function(){ return MyFormObject.ShowHistory(); }});
	
	op_menu.showAt(e.pageX-120, e.pageY);
}

MyForm.prototype.beforeChangeStatus = function(mode){
	
	if(mode == "CONFIRM")
	{
		Ext.MessageBox.confirm("","آیا مایل به تایید می باشید؟", function(btn){
			if(btn == "no")
				return;
			
			MyFormObject.ChangeStatus(mode, "");
		});
		return;
	}
	if(!this.commentWin)
	{
		this.commentWin = new Ext.window.Window({
			width : 412,
			height : 320,
			modal : true,
			title : "دلیل عدم تایید",
			bodyStyle : "background-color:white",
			items : [{
				xtype : "textarea",
				width : 400,
				rows : 8,
				name : "ActionComment"
			}],
			closeAction : "hide",
			buttons : [{
				text : "اعمال",				
				iconCls : "save",
				itemId : "btn_save"
			},{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		
		Ext.getCmp(this.TabID).add(this.commentWin);
	}
	this.commentWin.down("[itemId=btn_save]").setHandler(function(){
		MyFormObject.ChangeStatus(mode, this.up('window').down("[name=ActionComment]").getValue());});
	this.commentWin.show();
	this.commentWin.center();
}

MyForm.prototype.ChangeStatus = function(mode, ActionComment){
	
	record = this.grid.getSelectionModel().getLastSelected();
	
	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخيره سازي...'});
	mask.show();  
	
	Ext.Ajax.request({
		methos : "post",
		url : this.address_prefix + "wfm.data.php",
		form : this.get("mainForm"),
		params : {
			task : "ChangeStatus",
			RowID : record ? record.data.RowID : "",
			mode : mode,
			ActionComment : ActionComment
		},
		
		success : function(response){
			mask.hide();
			
			result = Ext.decode(response.responseText);
			if(!result.success)
			{
				if(result.data == "")
					Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
				else
					Ext.MessageBox.alert("",result.data);
			}
			
			MyFormObject.grid.getStore().load();
			if(MyFormObject.commentWin)
				MyFormObject.commentWin.hide();
		}
	});
}

MyForm.prototype.FormInfo = function(){
	
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
		ReadOnly : true,
		parentHandler : function(){
			MyFormObject.FormInfoWindow.hide();
			MyFormObject.grid.getStore().load();
		}
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

MyForm.prototype.ShowHistory = function(){

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

MyForm.prototype.ShowAttaches = function(){

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

var MyFormObject = new MyForm();

</script>
<center>
	<form id="mainForm">
		<br>
		<div style="width: 95%" id="DivGrid"></div>
	</form>
</center>