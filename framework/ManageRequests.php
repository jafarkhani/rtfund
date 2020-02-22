<?php
//---------------------------
// programmer:	Sh.Jafarkhani
// create Date:	95.01
//---------------------------

require_once('../header.inc.php');

require_once inc_dataGrid;
require_once inc_dataReader;
require_once inc_response;

//------------------------- DATA BLOCK ----------------------------
$task = isset($_POST ["task"]) ? $_POST ["task"] : (isset($_GET ["task"]) ? $_GET ["task"] : "");

switch ($task) {

	case "SelectAll":
		SelectAll();
		
	case "SelectAllReceive":
		SelectAllReceive();
		
	case "selectProjects":
		selectProjects();
		
	case "saveTask":
		saveTask();
		
	case "removeTask":
		removeTask();
}

function SelectAll(){
	
	$where = "1=1";
	if(!empty($_REQUEST["TaskStatus"]))
		$where .= " AND TaskStatus in(" . ($_REQUEST["TaskStatus"] == "RAW" ? "'RAW'" : "'DONE','RESPONSE'") .")";
	
	$res = PdoDataAccess::runquery_fetchMode("
		select	t.* , 
				SysName,
				concat_ws(' ',fname,lname,CompanyName) RegPersonName
				
		from FRW_tasks t
		join BSC_persons on(RegPersonID = PersonID)
		join FRW_systems using(SystemID)
		
		where $where order by FIELD(TaskStatus,'RAW') desc,ifnull(DoneDate,CreateDate) desc");	
	$cnt = $res->rowCount();
	$res = PdoDataAccess::fetchAll($res, $_GET["start"], $_GET["limit"]);
	echo dataReader::getJsonData($res, $cnt, $_GET["callback"]);
	die();
}

function saveTask(){
	
	$obj = new FRW_tasks();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	if(isset($_POST["DoneDesc"]))
		$obj->DoneDate = PDONOW;
	
	if($obj->TaskID != "")
		$result = $obj->EditTask();
	else
	{
		$obj->RegPersonID = $_SESSION["USER"]["PersonID"];
		$obj->CreateDate = PDONOW;
		$result = $obj->AddTask();
	}
	
	Response::createObjectiveResponse($result, "");
	die();
}

function removeTask(){
	
	$result = FRW_tasks::DeleteTask($_POST["TaskID"]);
	Response::createObjectiveResponse($result, "");
	die();
}
//-----------------------------------------------------------------

$dgh = new sadaf_datagrid("dg",$js_prefix_address . "ManageRequests.php?task=SelectAll","div_dg");

$dgh->addColumn("", "TaskID","",true);
$dgh->addColumn("", "details","",true);
$dgh->addColumn("", "DoneDesc","",true);
$dgh->addColumn("", "DoneDate","",true);
$dgh->addColumn("", "SystemID","",true);

$col = $dgh->addColumn("سیستم", "SysName");
$col->width = 140;

$col = $dgh->addColumn("ایجاد کننده", "RegPersonName");
$col->width = 110;

$col = $dgh->addColumn("عنوان", "title");

$col = $dgh->addColumn("زمان ایجاد", "CreateDate", GridColumn::ColumnType_datetime);
$col->width = 120;

$col = $dgh->addColumn("وضعیت", "TaskStatus");
$col->renderer = "function(v,p,r){ return TaskRequestObj.StatusRender(v,p,r);}";
$col->width = 90;

$col = $dgh->addColumn("زمان اقدام", "DoneDate", GridColumn::ColumnType_datetime);
$col->width = 120;

$col = $dgh->addColumn("عملیات", "");
$col->renderer = "function(v,p,r){ return TaskRequestObj.OperationRender(v,p,r);}";
$col->width = 60;

$dgh->addObject('this.FilterObj');
$dgh->addPlugin("this.RequestDetails");

$dgh->addButton("", "ایجاد درخواست جدید", "add", "function(){TaskRequestObj.AddTask();}");

if($_SESSION["USER"]["UserName"] == "admin")
{
	$col = $dgh->addColumn("", "");
	$col->renderer = "function(v,p,r){ return TaskRequestObj.ActionRender(v,p,r);}";
	$col->width = 30;
	$dgh->addButton("", "اقدام کار", "send", "function(){TaskRequestObj.ActionTask();}");
}

$dgh->title = "لیست درخواستهای ارسالی برای پشتیبان سیستم";
$dgh->width = 850;
$dgh->DefaultSortField = "CreateDate";
$dgh->autoExpandColumn = "title";
$dgh->DefaultSortDir = "DESC";
$dgh->height = 450;
$dgh->emptyTextOfHiddenColumns = true;
$dgh->EnableSearch = false;
$dgh->pageSize = 15;
$grid = $dgh->makeGrid_returnObjects();

?>
<style type="text/css">
.pinkRow, .pinkRow td,.pinkRow div{ background-color:#FFB8C9 !important;}
.greenRow,.greenRow td,.greenRow div{ background-color:#D0F7E2 !important;}
</style>
<script>
TaskRequest.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function TaskRequest()
{
	this.RequestDetails = {
		ptype: 'rowexpander',
		rowBodyTpl : [
			'<hr>','توضیحات: {details}<br>',
			'پاسخ پشتیبان: {[values.DoneDesc == null ? "" : values.DoneDesc]}'
		]
	};
	
	this.FilterObj = Ext.button.Button({
		text: 'فیلتر لیست',
		iconCls: 'list',
		menu: {
			xtype: 'menu',
			plain: true,
			showSeparator : true,
			items: [{
				text: "کلیه درخواست ها",
				checked: true,
				group: 'filter',
				handler : function(){
					me = TaskRequestObj;
					me.grid.getStore().proxy.extraParams["TaskStatus"] = "";
					me.grid.getStore().load();
				}
			},{
				text: "درخواست های خام",
				group: 'filter',
				checked: true,
				handler : function(){
					me = TaskRequestObj;
					me.grid.getStore().proxy.extraParams["TaskStatus"] = "RAW";
					me.grid.getStore().load();
				}
			},{
				text: "درخواست های انجام شده",
				group: 'filter',
				checked: true,
				handler : function(){
					me = TaskRequestObj;
					me.grid.getStore().proxy.extraParams["TaskStatus"] = "NotRaw";
					me.grid.getStore().load();
				}
			}]
		}
	});
	this.grid = <?=$grid?>;
	this.grid.getView().getRowClass = function(record)
	{
		if(record.data.TaskStatus == "RESPONSE" || record.data.TaskStatus == "DONE")
			return "greenRow";
		return "";
	}
	this.grid.render(this.get("div_dg"));
	
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		hidden : true,
		bodyStyle : "text-align:right;padding:5px",
		title : "ایجاد کار جدید",
		defaults : {
			labelWidth :100
		},
		width : 500,
		items :[{
			xtype : "combo",
			store : new Ext.data.Store({
				fields: ['SystemID','SysName'],
				proxy : {
					type : 'jsonp',
					url : this.address_prefix + "management/framework.data.php?task=selectSystems",
					reader : {
						root: 'rows',
						totalProperty: 'totalCount'
					}
				},
				autoLoad : true,
				listeners : {
					load : function(){
						if(this.getCount() > 0)
							TaskRequestObj.formPanel.down("[name=SystemID]").select(this.getAt(0));
					}
				}
			}),
			queryMode : 'local',
			fieldLabel : "سیستم",
			displayField : "SysName",
			valueField : "SystemID",
			anchor : "100%",
			allowBlank : false,
			name : "SystemID"
					
		},{
			xtype : "textfield",
			name : "title",
			anchor : "100%",	
			allowBlank : false,
			fieldLabel : "عنوان"			
		},{
			xtype : "textarea",
			name : "details",
			anchor : "100%",		
			fieldLabel : "شرح"			
		},{
			xtype : "hidden",
			name : "TaskID"
		}],
		buttons : [{
			text : "ذخیره",
			iconCls : "save",
			handler : function()
			{
				var projectEl = this.up('form').down("[name=SystemID]");
				if(projectEl.getValue() == null)
				{
					alert("انتخاب سیستم الزامی است");
					return;
				}
				mask = new Ext.LoadMask(Ext.getCmp(TaskRequestObj.TabID), {msg:'در حال حذف...'});
				mask.show();
				this.up('form').getForm().submit({
					clientValidation: true,
					url: TaskRequestObj.address_prefix + 'ManageRequests.php?task=saveTask',
					method : "POST",
					success : function(form,action){
						TaskRequestObj.grid.getStore().load();
						TaskRequestObj.formPanel.getForm().reset();
						mask.hide();
					},
					failure : function(form,action)
					{
						mask.hide();
					}
				});
			}
		},{
			text : "انصراف",
			iconCls : "undo",
			handler : function(){
				TaskRequestObj.formPanel.hide();
			}
		}]
	});
}

TaskRequestObj = new TaskRequest();

TaskRequest.prototype.AddTask = function(){

	this.formPanel.getForm().reset();
	this.formPanel.show();
}

TaskRequest.prototype.EditTask = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	this.formPanel.show();
	this.formPanel.loadRecord(record);
}

TaskRequest.prototype.Remove = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return; 
		
		me = TaskRequestObj;
		var record = me.grid.getSelectionModel().getLastSelected();
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'ManageRequests.php?task=removeTask',
			params:{
				TaskID: record.data.TaskID
			},
			method: 'POST',

			success: function(response){
				mask.hide();
				TaskRequestObj.grid.getStore().load();
			},
			failure: function(){}
		});
	});
	
}

TaskRequest.prototype.DescRender = function(v, p, record){

	var desc = record.data.details;
	desc = desc.replace(/\n/g, "<br>");
	var DoneDesc = record.data.DoneDesc == null ? "" : record.data.DoneDesc; 
	DoneDesc = DoneDesc.replace(/\n/g, "<br>");
	
	desc = "<b>شرح : </b>" + (desc == "" ? "---" : desc);	

	if(DoneDesc != "" && DoneDesc != null)
		desc += "<hr><b>توضیح پشتیبان : </b>" + DoneDesc;

	p.tdAttr = 'data-qtip="' + desc + '"';
	return v;
}

TaskRequest.prototype.StatusRender = function(v, p, record){

	switch(v)
	{
		case "RAW": return "خام";
		case "DONE": return "اقدام شده";
		case "RESPONSE": return "پاسخ داده شده";
	}
}

TaskRequest.prototype.OperationRender = function(v,p,r){

	if(r.data.TaskStatus == "RAW")
		return "<div style='background-repeat:no-repeat;background-position:center;cursor:pointer;height:16;width:20px;float:left' "+
			" onclick=TaskRequestObj.Remove() class=remove></div>" + 
			"<div style='background-repeat:no-repeat;background-position:center;cursor:pointer;height:16;width:20px;float:left' "+
			" onclick=TaskRequestObj.EditTask() class=edit></div>";
		
	return "";
}

TaskRequest.prototype.ActionRender = function(v,p,r){

	if(r.data.TaskStatus == "RAW")
		return "<div style='background-repeat:no-repeat;background-position:center;cursor:pointer;height:16;width:20px;float:left' "+
			" onclick=TaskRequestObj.ActionTask() class=send></div>";
}

TaskRequest.prototype.ActionTask = function(){
	
	if(!this.commentWin)
	{
		this.commentWin = new Ext.window.Window({
			width : 412, 
			height : 300,
			modal : true,
			title : "اقدام کار",
			bodyStyle : "background-color:white",
			items : [{
				xtype : "combo",
				name : "TaskStatus",
				store: new Ext.data.SimpleStore({
					fields : ['id','title'],
					data : [ 
						['DONE', 'اقدام شده'],
						['RESPONSE', 'پاسخ داده شده']
					]
				}),  
				displayField: 'title',
				valueField: 'id'
			},{
				xtype : "textarea",
				width : 400,
				rows : 8,
				name : "DoneDesc"
			}],
			closeAction : "hide",
			buttons : [{
				text : "ذخیره",				
				iconCls : "save",
				itemId : "btn_save",
				handler : function(){
					me = TaskRequestObj;
					var record = me.grid.getSelectionModel().getLastSelected();
					mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف...'});
					mask.show();

					Ext.Ajax.request({
						url: me.address_prefix + 'ManageRequests.php?task=saveTask',
						params:{
							TaskID: record.data.TaskID,
							TaskStatus : me.commentWin.down("[name=TaskStatus]").getValue(),
							DoneDesc : me.commentWin.down("[name=DoneDesc]").getValue()
						},
						method: 'POST',

						success: function(response){
							mask.hide();
							TaskRequestObj.grid.getStore().load();
							TaskRequestObj.commentWin.hide();
						},
						failure: function(){}
					});
				}
			},{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		
		Ext.getCmp(this.TabID).add(this.commentWin);
	}
	this.commentWin.show();
	this.commentWin.center();	
}


</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
		<br>
		<div id="div_dg"></div>
		<br>
		<div id="div_dg2"></div>
	</center>
</form>
