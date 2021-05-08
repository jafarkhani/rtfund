<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------
require_once("../header.inc.php");
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg",$js_prefix_address . "wfm.data.php?task=SelectAllFlows","");

$dg->addColumn("","param4","string",true);
$dg->addColumn("","ObjectType","string",true);

$col = $dg->addColumn("عنوان گردش","FlowDesc","string");
$col->editor = ColumnEditor::TextField();

$col = $dg->addColumn("نوع فرآیند","IsTree","string");
$col->editor = ColumnEditor::ComboBox(array(
									  array("id"=>'NO',"title"=>'لیستی'),
									  array("id"=>"YES",'title'=>"درختی")), 
											"id", "title");
$col->width = 100;

$col = $dg->addColumn("آیتم مورد نظر", "ObjectDesc", "string");

$col = $dg->addColumn("مراحل","","");
$col->renderer = "WFM.StepsRender";
$col->sortable = false;
$col->width = 50;
if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("حذف","FlowID","");
	$col->renderer = "WFM.deleteRender";
	$col->sortable = false;
	$col->width = 40;
}
if($accessObj->AddFlag)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){WFMObject.Adding();}";
}
if($accessObj->EditFlag && $accessObj->AddFlag)
{
	$dg->editorGrid = true;
	$dg->enableRowEdit = true;
	$dg->rowEditOkHandler = "function(v,p,r){return WFMObject.saveData(v,p,r);}";
}
$dg->height = 350;
$dg->width = 600;
$dg->DefaultSortField = "FlowID";
$dg->autoExpandColumn = "FlowDesc";
$dg->title = "مدیریت گردش های کار";
$dg->EnablePaging = false;
$dg->EnableSearch = false;

$grid = $dg->makeGrid_returnObjects();

//------------------------------------------------------------------------------

$dg = new sadaf_datagrid("dg",$js_prefix_address . "wfm.data.php?task=SelectSteps","");

$dg->addColumn("","StepRowID","string", true);
$dg->addColumn("","FlowID","string", true);
$dg->addColumn("","fullname","string", true);

$col = $dg->addColumn("مرحله","StepID","string");
$col->width = 60;
$col = $dg->addColumn("عنوان مرحله","StepDesc","string");
$col->editor = ColumnEditor::TextField();

$col = $dg->addColumn("پست مربوطه", "PostID", "string");
$col->editor = ColumnEditor::ComboBox(
	PdoDataAccess::runquery("select * from BSC_posts where IsActive='YES'"), 
		"PostID", "PostName","","", true);
$col->width = 130;

$col = $dg->addColumn("شغل مربوطه", "JobID", "string");
$col->editor = ColumnEditor::ComboBox(
	PdoDataAccess::runquery("select JobID,concat(JobID,'-',PostName) title "
		. "from BSC_jobs join BSC_posts using(PostID)"), 
		"JobID", "title","","", true);
$col->width = 130;

$col = $dg->addColumn("شخص مربوطه", "PersonID", "string");
$col->renderer = "function(v,p,r){return r.data.fullname;}";
$col->editor = "this.PersonCombo";
$col->width = 130;

$col = $dg->addColumn("","","");
$col->renderer = "WFM.upRender";
$col->sortable = false;
$col->width = 30;

$col = $dg->addColumn("","","");
$col->renderer = "WFM.downRender";
$col->sortable = false;
$col->width = 30;

$col = $dg->addColumn("افراد","","");
$col->renderer = "WFM.PersonsRender";
$col->sortable = false;
$col->align = "center";
$col->width = 40;

if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("حذف","","");
	$col->renderer = "WFM.deleteStepRender";
	$col->sortable = false;
	$col->width = 40;
}
if($accessObj->AddFlag)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){WFMObject.AddStep();}";
}
if($accessObj->EditFlag && $accessObj->AddFlag)
{
	$dg->enableRowEdit = true;
	$dg->rowEditOkHandler = "function(v,p,r){return WFMObject.saveStep(v,p,r);}";
}
$dg->height = 400;
$dg->width = 700;
$dg->DefaultSortField = "StepID";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "StepDesc";
$dg->editorGrid = true;
$dg->EnablePaging = false;
$dg->EnableSearch = false;
$dg->emptyTextOfHiddenColumns = true;
$grid2 = $dg->makeGrid_returnObjects();

?>
<style type="text/css">
.step{background-image:url('/generalUI/ext4/resources/themes/icons/step.png') !important;}
</style>
<script type="text/javascript">

WFM.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function WFM(){
	this.PersonCombo = new Ext.form.ComboBox({
		store: new Ext.data.Store({
			proxy:{
				type: 'jsonp',
				url: this.address_prefix + '../../framework/person/persons.data.php?task=selectPersons'+
					'&UserType=IsStaff',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			},
			fields :  ['PersonID','fullname']
		}),
		displayField: 'fullname',
		valueField : "PersonID",
		allowBlank : true
	});
	
	this.grid = <?= $grid?>;
	this.grid.plugins[0].on("beforeedit", function(editor,e){
			if(e.record.data.param4 != "form")
				return false;
			return true;
		});
	this.grid.render(this.get("div_grid"));
	this.StepsGrid = <?= $grid2 ?>;
}

WFM.deleteRender = function(v,p,r){
	
	if(r.data.param4 == 'form')
		return "<div align='center' title='حذف ' class='remove' onclick='WFMObject.Deleting();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

WFM.StepsRender = function(v,p,r){
	
	return "<div align='center' title='مراحل ' class='step' onclick='WFMObject.Steps();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

WFM.prototype.Adding = function(){
	
	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		FlowID : "",
		param4 : "form",
		ObjectType : "5",
		FlowDesc : ""
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

WFM.prototype.saveData = function(store,record){
	
    mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		params: {
			task: 'SaveFlow',
			record : Ext.encode(record.data)
		},
		url: this.address_prefix +'wfm.data.php',
		method: 'POST',

		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(st.success)
			{
				WFMObject.grid.getStore().load();
			}
			else
			{
				Ext.MessageBox.alert("Error",st.data);
			}
		},
		failure: function(){}
	});
}

WFM.prototype.Deleting = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	if(record && confirm("آيا مايل به حذف مي باشيد؟"))
	{
		Ext.Ajax.request({
		  	url : this.address_prefix + "wfm.data.php",
		  	method : "POST",
		  	params : {
		  		task : "DeleteFlow",
		  		FlowID : record.data.FlowID
		  	},
		  	success : function(response,o)
		  	{
				sd = Ext.decode(response.responseText);
				if(sd.success)
					WFMObject.grid.getStore().load();
				else{
					Ext.MessageBox.alert("ERROR", sd.data != "" ? sd.data : "عملیات مورد نظر با شکست مواجه شد" )
				}
					
		  	}
		});
	}
}

WFM.prototype.Steps = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	this.StepsGrid.getStore().proxy.extraParams = {
		FlowID : record.data.FlowID
	};
	
	if(record.data.IsTree === 'YES' ) 
	{
		if (!this.ProcessBlockWin)
        {
            this.ProcessBlockWin = new Ext.window.Window({
                width: 800,
                height: 514,
                title: 'گامهای فرایند',
                modal : true,
				closeAction : "hide",
                loader: {
                    autoLoad: true,
                    scripts: true,
					url: "/office/workflow/TreeProcess.php?ParentID=" + record.data.FlowID
                    //url: '/process/baseinfo/Process/ProcessesTree.php?FromNonExtPage=0&AfterSendHandler=ProcessDuty_OnProcessSelected&ExtTabID=mainForm&AccessCheck=false'
                },
                listeners: {
                    close: function () {
                        Ext.getBody().unmask();
                    }
                }
            });
			Ext.getCmp(this.TabID).add(this.ProcessBlockWin);
        }
        this.ProcessBlockWin.show();
        Ext.getBody().mask();
		
	}
	else {
		if(!this.stepsWin )
		{
			this.stepsWin = new Ext.window.Window({
				width : 710,
				title : "مراحل گردش",
				height : 460,
				modal : true,
				closeAction : "hide",
				items : [this.StepsGrid],
				buttons :[{
					text : "بازگشت",
					iconCls : "undo",
					handler : function(){this.up('window').hide();}
				}]
			});
			Ext.getCmp(this.TabID).add(this.stepsWin);
		}
		else
			this.StepsGrid.getStore().load();
		
	this.stepsWin.show();
	this.stepsWin.center();
	
	}
	
	
}

//----------------------------------------------------------

WFM.deleteStepRender = function(v,p,r){
	
	return "<div align='center' title='حذف ' class='remove' onclick='WFMObject.DeleteStep();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

WFM.upRender = function(v,p,r){
	
	if(r.data.StepID == 1)
		return "";
	return "<div align='center' title='حذف ' class='up' onclick='WFMObject.moveStep(-1);' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

WFM.downRender = function(v,p,r){
	
	store = WFMObject.StepsGrid.getStore();
	record = store.getAt(store.getCount()-1);
	if(r.data.StepID == record.data.StepID)
		return "";
	return "<div align='center' title='حذف ' class='down' onclick='WFMObject.moveStep(1);' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

WFM.PersonsRender = function(v,p,r){
	
	return "<div align='center' title='افراد' class='list' onclick='WFMObject.ShowPersons();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:16px;height:16'></div>";
}

WFM.prototype.AddStep = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	
	var modelClass = this.StepsGrid.getStore().model;
	var record = new modelClass({
		FlowID : record.data.FlowID,
		StepID : "",
		StepDesc : ""
	});

	this.StepsGrid.plugins[0].cancelEdit();
	this.StepsGrid.getStore().insert(0, record);
	this.StepsGrid.plugins[0].startEdit(0, 0);
}

WFM.prototype.saveStep = function(store,record){
	
    mask = new Ext.LoadMask(this.stepsWin, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		params: {
			task: 'SaveStep',
			record : Ext.encode(record.data)
		},
		url: this.address_prefix +'wfm.data.php',
		method: 'POST',

		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(st.success)
			{
				WFMObject.StepsGrid.getStore().load();
			}
			else
			{
				Ext.MessageBox.alert("Error",st.data);
			}
		},
		failure: function(){}
	});
}

WFM.prototype.moveStep = function(direction){
	
	var record = this.StepsGrid.getSelectionModel().getLastSelected();
	
    mask = new Ext.LoadMask(this.stepsWin, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		params: {
			task: 'MoveStep',
			FlowID : record.data.FlowID,
			StepID : record.data.StepID,
			direction : direction
		},
		url: this.address_prefix + 'wfm.data.php',
		method: 'POST',

		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(st.success)
			{
				WFMObject.StepsGrid.getStore().load();
			}
			else
			{
				Ext.MessageBox.alert("Error",st.data);
			}
		},
		failure: function(){}
	});
}

WFM.prototype.DeleteStep = function(){
	
	var record = this.StepsGrid.getSelectionModel().getLastSelected();
	Ext.MessageBox.confirm("", "آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		me = WFMObject;
		
		Ext.Ajax.request({
		  	url : me.address_prefix + "wfm.data.php",
		  	method : "POST",
		  	params : {
		  		task : "DeleteStep",
		  		StepRowID : record.data.StepRowID
		  	},
		  	success : function(response)
		  	{
				result = Ext.decode(response.responseText);
				if(result.success)
					WFMObject.StepsGrid.getStore().load();
				else if(result.data == "FlowRowExists")
					Ext.MessageBox.alert("Error","آیتم هایی هستند که گردش آنها در این مرحله می باشید و قادر به حذف این مرحله نمی باشید");
				else
					Ext.MessageBox.alert("Error", "عملیات مورد نظر با شکست مواجه شد");
					
		  	}
		});
	});
}

WFM.prototype.ShowPersons = function(){
	
	var record = this.StepsGrid.getSelectionModel().getLastSelected();
	
	if(!this.PersonsWin)
	{
		this.PersonsWin = new Ext.window.Window({
			width : 765,
			title : "لیست کاربران جهت مشاهده فرم",
			bodyStyle : "background-color:white;text-align:-moz-center",
			height : 565,
			modal : true,
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "FlowStepPersons.php",
				scripts : true
			},
			buttons :[{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		Ext.getCmp(this.TabID).add(this.PersonsWin);
	}
	this.PersonsWin.show();
	this.PersonsWin.center();
	this.PersonsWin.loader.load({
		params : { 
			ExtTabID : this.PersonsWin.getEl().id,
			MenuID : <?= $_REQUEST["MenuID"] ?>,
			StepRowID : record.data.StepRowID}
	});
}

var WFMObject = new WFM();

</script>
<center>
	<br>
	<div id="div_grid"></div>
</center>