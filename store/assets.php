<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1398.06
//-----------------------------

require_once '../header.inc.php';
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "store.data.php?task=SelectAllAssets", "grid_div");

$dg->addColumn("", "AssetID", "", true);
$dg->addColumn("", "GoodID", "", true);
$dg->addColumn("", "BranchID", "", true);
$dg->addColumn("", "StatusID", "", true);

$col = $dg->addColumn("شماره پلاک", "LabelNo");
$col->width = 100;

$col = $dg->addColumn("کالا", "GoodName");
$col->width = 120;

$col = $dg->addColumn("مبلغ", "amount", GridColumn::ColumnType_money);
$col->width = 100;

$col = $dg->addColumn("جزئیات", "details");


$col = $dg->addColumn("دوره نت پيشگيرانه", "NetPeriod");
$col->width = 100;

$col = $dg->addColumn("نحوه نت پيشگيرانه", "NetMethod");


$col = $dg->addColumn("تاریخ خرید", "BuyDate", GridColumn::ColumnType_date);
$col->width = 100;

$col = $dg->addColumn("وضعیت", "StatusDesc", "");
$col->width = 120;

$col = $dg->addColumn('عملیات', '', 'string');
$col->renderer = "STO_Asset.OperationRender";
$col->width = 50;
$col->align = "center";

if($accessObj->AddFlag)
	$dg->addButton("", "ایجاد اموال جدید", "add", "function(){STO_AssetObject.AddNew();}");

$dg->emptyTextOfHiddenColumns = true;
$dg->height = 500;
$dg->pageSize = 15;
$dg->title = "لیست اموال";
$dg->DefaultSortField = "LabelNo";
$dg->autoExpandColumn = "details";
$grid = $dg->makeGrid_returnObjects();
		
//-------------------------------------------------------------
		
$dg = new sadaf_datagrid("dg", $js_prefix_address . "store.data.php?task=SelectAllAssetFlow");

$dg->addColumn("", "AssetID", "", true);
$dg->addColumn("", "IsLock", "", true);
$dg->addColumn("", "FlowID", "", true);
$dg->addColumn("", "StatusDesc", "", true);
$dg->addColumn("", "ReceiverFullName", "", true);

$dt = PdoDataAccess::runquery("select * from BaseInfo where TypeID=95");
$col = $dg->addColumn("وضعیت", "StatusID", "");
$col->editor = ColumnEditor::ComboBox($dt, "InfoID", "InfoDesc");
$col->renderer = "function(v,p,r){return r.data.StatusDesc;}";
$col->width = 120;

$col = $dg->addColumn("تاریخ عملیات", "ActDate", GridColumn::ColumnType_date);
$col->width = 100;

$col = $dg->addColumn("عامل", "ActFullname");
$col->width = 100;

$col = $dg->addColumn("بهره برداری", "IsUsable");
$col->editor = ColumnEditor::CheckField("", "YES");
$col->renderer = sadaf_datagrid::checkRender('YES');
$col->width = 70;
$col->align = "center";

$col = $dg->addColumn("مبلغ", "amount", GridColumn::ColumnType_money);
$col->editor = ColumnEditor::CurrencyField(true);
$col->width = 90;

$col = $dg->addColumn("مبلغ استهلاک", "DepreciationAmount", GridColumn::ColumnType_money);
$col->width = 90;
$col->summaryType = 'sum';

$col = $dg->addColumn("تحویل گیرنده", "ReceiverPersonID");
$col->renderer = "function(v,p,r){return r.data.ReceiverFullName;}";
$col->editor = "this.PersonCombo";
$col->width = 150;

$col = $dg->addColumn("توضیحات", "details");
$col->editor = ColumnEditor::TextArea(true);

if($accessObj->AddFlag)
{
	$dg->addButton("", "ایجاد گردش جدید", "add", "function(){STO_AssetObject.AddNewFlow();}");
	$dg->enableRowEdit = true;
	$dg->rowEditOkHandler = "function(v,p,r){ return STO_AssetObject.SaveFlow(v,p,r);}";
}

if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn('عملیات', '', 'string');
	$col->renderer = "STO_Asset.DeleteFlowRender";
	$col->width = 50;
	$col->align = "center";
}

$dg->EnableSummaryRow = true;

$dg->emptyTextOfHiddenColumns = true;
$dg->height = 400;
$dg->width = 900;
$dg->title = "گردش اموال";
$dg->DefaultSortField = "ActDate";
$dg->autoExpandColumn = "details";
$grid2 = $dg->makeGrid_returnObjects();
?>
<script>
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.07
//-----------------------------

STO_Asset.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function STO_Asset(){
	
	this.grid = <?= $grid ?>;
	this.grid.render(this.get("DivGrid"));
	this.grid.on("itemdblclick", function(view, record){
		
		me = STO_AssetObject;
		me.FlowGrid.getStore().proxy.extraParams.AssetID = record.data.AssetID;
		if(!me.FlowGrid.rendered)
			me.FlowGrid.render(me.get("DivGridFlow"));
		else
			me.FlowGrid.getStore().load();
	});
	
	this.PersonCombo = new Ext.form.ComboBox({
		store: new Ext.data.Store({
			proxy:{
				type: 'jsonp',
				url: '/framework/person/persons.data.php?task=selectPersons&UserType=IsStaff',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			},
			fields :  ['PersonID','fullname'],
			autoLoad : true
		}),
		displayField: 'fullname',
		valueField : "PersonID"
	});
	
	this.FlowGrid = <?= $grid2 ?>;
	this.FlowGrid.plugins[0].on("beforeedit",function(rowEditor,e){
	
		var record = STO_AssetObject.FlowGrid.getStore().getAt(e.rowIdx);
		if(record.data.IsLock == "YES")
			return false;
		return true;
	});
	this.FlowGrid.getView().getRowClass = function (record, index)
	{
		if (record.data.IsLock == "YES")
			return "violetRow";
		return "";
	}
	
	this.ParamsStore = new Ext.data.Store({
		fields:["GoodID","PropertyID","PropertyTitle","PropertyType", "PropertyValues"],
		proxy: {
			type: 'jsonp',
			url: this.address_prefix + 'store.data.php?task=SelectProperties',
			reader: {root: 'rows',totalProperty: 'totalCount'}
		},
		autoLoad : true
	});
	
	this.PropertyValuesStore = new Ext.data.Store({
		fields:["PropertyID","PropertyValue"],
		proxy: {
			type: 'jsonp',
			url: this.address_prefix + 'store.data.php?task=selectPropertyValues',
			reader: {root: 'rows',totalProperty: 'totalCount'}
		}
	});
	
	this.MainPanel = new Ext.form.Panel({
		width : 650,
		hidden : true,
		layout : {
			type : "table",
			columns : 2
		},		
		applyTo : this.get("AssetInfo"),
		defaults : {
			width : 300
		},
		frame : true,
		items : [{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../../framework/baseInfo/baseInfo.data.php?' +
						"task=SelectBranches&WarrentyAllowed=true",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['BranchID','BranchName'],
				autoLoad : true					
			}),
			fieldLabel : "شعبه",
			queryMode : 'local',
			allowBlank : false,
			displayField : "BranchName",
			valueField : "BranchID",
			name : "BranchID"
		},{
			xtype : "textfield",
			fieldLabel : "شماره پلاک",
			name : "LabelNo",
			colspan : 2
		},{
			xtype : "combo",
			displayField : "GoodName",
			valueField : "GoodID",
			name : "GoodID",
			colspan : 2,
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + 'store.data.php?task=SelectGoods',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['GoodID','GoodName'],
				autoLoad : true					
			}),
			allowBlank : false,
			fieldLabel : "کالا",
			listeners :{
				select : function(combo,records){
					STO_AssetObject.LoadProperties(records[0].data.GoodID);
				}
			}
		},{
			xtype : "currencyfield",
			hideTrigger : true,
			fieldLabel : "مبلغ خرید",
			name : "amount"			
		},{
			xtype : "shdatefield",
			fieldLabel : "تاریخ خرید",
			name : "BuyDate"
		},{
			xtype : "fieldset",
			colspan : 2,
			width : 600,
			title : "اطلاعات خاص کالای انتخابی",
			itemId : "ParamsFS",
			layout : "column",
			columns : 2
		},{
            xtype : "textfield",
            fieldLabel : "دوره نت پيشگيرانه",
            name : "NetPeriod"
        },{
            xtype : "textfield",
            name : "NetMethod",
            fieldLabel : "نحوه نت پيشگيرانه"
        },{
			xtype : "textarea",
			name : "details",
			fieldLabel : "جزئیات",
			colspan : 2,
			width : 600
		},{
			xtype : "hidden",
			name : "AssetID"
		}],
		buttons :[{
			text : "ذخیره",
			iconCls : "save",
			itemId : "btn_save",
			handler : function(){ STO_AssetObject.SaveAsset(); }
		},{
			text : "انصراف",
			iconCls : "undo",
			handler : function(){ this.up('panel').hide(); }
		}]
	});
}

STO_Asset.OperationRender = function(value, p, record){
	
	return "<div  title='عملیات' class='setting' onclick='STO_AssetObject.OperationMenu(event);' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

STO_Asset.prototype.OperationMenu = function(e){

	record = this.grid.getSelectionModel().getLastSelected();
	var op_menu = new Ext.menu.Menu();
	
	if(record.data.StatusID == "<?= STO_STEPID_RAW ?>" )
	{
		if(this.EditAccess)
		{
			op_menu.add({text: 'ویرایش اموال',iconCls: 'edit', 
			handler : function(){ return STO_AssetObject.editAsset(); }});
		}
		if(this.RemoveAccess)
			op_menu.add({text: 'حذف اموال',iconCls: 'remove', 
			handler : function(){ return STO_AssetObject.deleteAsset(); }});
	}
	else
	{
		op_menu.add({text: 'اطلاعات اموال',iconCls: 'info', 
			handler : function(){ return STO_AssetObject.InfoAsset(); }});
	}

    //new added
    op_menu.add({text: 'رويدادها',iconCls: 'task',
        handler : function(){ return STO_AssetObject.ShowEvents(); }});
    op_menu.add({text: 'برنامه نت',iconCls: 'process',
        handler : function(){ return STO_AssetObject.ShowNets(); }});
    //end new added

	if(this.EditAccess && record.data.StatusID == "<?= WAR_STEPID_CONFIRM ?>")
	{
		
	}
	if(this.EditAccess && record.data.StatusID == "<?= WAR_STEPID_CANCEL ?>" && record.data.IsCurrent == "YES")
	{
	}
	
	op_menu.add({text: 'مدارک اموال',iconCls: 'attach', 
		handler : function(){ return STO_AssetObject.Documents('asset'); }});

	op_menu.add({text: 'سابقه اموال',iconCls: 'history', 
		handler : function(){ return STO_AssetObject.ShowHistory(); }});
		
	op_menu.showAt(e.pageX-120, e.pageY);
}

STO_Asset.prototype.LoadProperties = function(GoodID){

	ParamsFS = this.MainPanel.getComponent("ParamsFS");
	ParamsFS.removeAll();

	this.ParamsStore.each(function(record){
		if(GoodID == record.raw.GoodID)
		{
			if(record.data.PropertyType == "combo")
			{
				arr = record.data.ComboValues.split("#");
				data = [];
				for(j=0;j<arr.length;j++)
					if(arr[j] != "")
						data.push([ arr[j] ]);

				ParamsFS.add({
					store : new Ext.data.SimpleStore({
						fields : ['value'],
						data : data
					}),
					xtype: "combo",
					valueField : "value",
					displayField : "value",
					itemId: 'Property_' + record.data.PropertyID,
					name: 'Property_' + record.data.PropertyID,
					fieldLabel : record.data.PropertyTitle
				});
			}								
			else
				ParamsFS.add({
					xtype : record.data.PropertyType,
					name : "Property_" + record.data.PropertyID,
					fieldLabel : record.data.PropertyTitle,
					hideTrigger : (record.data.PropertyType == "numberfield" || 
						record.data.PropertyType == "currencyfield" ? true : false)
				});
		}
	});

	//------------- fill params -------------------
	AssetID = this.MainPanel.down("[name=AssetID]").getValue();
	if(AssetID > 0)
	{
		pmask = new Ext.LoadMask(this.MainPanel, {msg:'در حال بارگذاری ...'});
		pmask.show();
		
		this.PropertyValuesStore.load({
			params : {
				AssetID : AssetID
			},
			callback : function(){
				store = STO_AssetObject.PropertyValuesStore;
				store.each(function(record){
					STO_AssetObject.MainPanel.down("[name=Property_" + 
						record.data.PropertyID + "]").setValue(record.data.PropertyValue);
				});
				
				pmask.hide();
			}
		});
	}					
}

STO_Asset.prototype.AddNew = function(){
	
	this.MainPanel.show();
	this.MainPanel.setReadOnly(false);
	
	this.MainPanel.getForm().reset();
}

STO_Asset.prototype.editAsset = function(){
	
	this.MainPanel.down("[itemId=btn_save]").show();
	this.MainPanel.setReadOnly(false);
	record = this.grid.getSelectionModel().getLastSelected();
	this.MainPanel.show();
	
	mask = new Ext.LoadMask(this.MainPanel, {msg:'در حال بارگذاری ...'});
	mask.show();
	
	this.MainPanel.loadRecord(record);
	
	this.MainPanel.down("[name=BuyDate]").setValue(MiladiToShamsi(record.data.BuyDate) );
	this.MainPanel.down("[name=GoodID]").getStore().load({
		params : {
			GoodID : record.data.GoodID
		},
		callback : function(){
			if(this.getCount() > 0)
			{
				STO_AssetObject.MainPanel.down("[name=GoodID]").setValue(this.getAt(0).data.GoodID);
				STO_AssetObject.LoadProperties(this.getAt(0).data.GoodID);
			}
			mask.hide();
		}
	});
}

STO_Asset.prototype.InfoAsset = function(){
	
	record = this.grid.getSelectionModel().getLastSelected();
	this.MainPanel.show();
	mask = new Ext.LoadMask(this.MainPanel, {msg:'در حال ذخیره سازی ...'});
	mask.show();
	
	this.MainPanel.loadRecord(record);
	
	this.MainPanel.down("[name=BuyDate]").setValue(MiladiToShamsi(record.data.BuyDate) );
	this.MainPanel.down("[name=GoodID]").getStore().load({
		params : {
			GoodID : record.data.GoodID
		},
		callback : function(){
			mask.hide();	
			if(this.getCount() > 0)
				STO_AssetObject.MainPanel.down("[name=GoodID]").setValue(this.getAt(0).data.GoodID);
		}
	});
	
	this.MainPanel.down("[itemId=btn_save]").hide();
	this.MainPanel.setReadOnly(true);
}

STO_Asset.prototype.SaveAsset = function(){
	
	if(!this.MainPanel.getForm().isValid())
		return;
	
	mask = new Ext.LoadMask(this.MainPanel, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	this.MainPanel.getForm().submit({
		clientValidation: true,
		url: this.address_prefix +'store.data.php',
		method: "POST",
		params: {
			task: "SaveAsset"
		},
		success: function(form,action){
			mask.hide();
			STO_AssetObject.grid.getStore().load();
			STO_AssetObject.MainPanel.hide();
		},
		failure: function(){
			mask.hide();
		}
	});
}

//new added
STO_Asset.prototype.ShowEvents = function(){

    if(!this.EventsWin)
    {
        this.EventsWin = new Ext.window.Window({
            title: 'رویدادهای مرتبط با اموال',
            modal : true,
            autoScroll : true,
            width: 1000,
            height : 400,
            bodyStyle : "background-color:white",
            closeAction : "hide",
            loader : {
                url : this.address_prefix + "events.php",
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
        Ext.getCmp(this.TabID).add(this.EventsWin);
    }
    this.EventsWin.show();
    this.EventsWin.center();
    record = this.grid.getSelectionModel().getLastSelected();
    this.EventsWin.loader.load({
        params : {
            ExtTabID : this.EventsWin.getEl().id,
            AssetID : record.data.AssetID,
            MenuID : this.MenuID
        }
    });
    this.get("excel").value = "";
}

STO_Asset.prototype.ShowNets = function(){

    if(!this.NetsWin)
    {
        this.NetsWin = new Ext.window.Window({
            title: 'برنامه نگهداری و تعمیرات',
            modal : true,
            autoScroll : true,
            width: 600,
            height : 400,
            bodyStyle : "background-color:white",
            closeAction : "hide",
            loader : {
                url : this.address_prefix + "nets.php",
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
        Ext.getCmp(this.TabID).add(this.NetsWin);
    }
    this.NetsWin.show();
    this.NetsWin.center();
    record = this.grid.getSelectionModel().getLastSelected();
    this.NetsWin.loader.load({
        params : {
            ExtTabID : this.NetsWin.getEl().id,
            AssetID : record.data.AssetID,
            MenuID : this.MenuID
        }
    });
    this.get("excel").value = "";
}

//end new added

STO_Asset.prototype.Documents = function(ObjectType){

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
			ObjectType : ObjectType,
			ObjectID : record.data.AssetID
		}
	});
}

STO_Asset.prototype.ShowHistory = function(){

	if(!this.HistoryWin)
	{
		this.HistoryWin = new Ext.window.Window({
			title: 'سابقه گردش درخواست',
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
			AssetID : this.grid.getSelectionModel().getLastSelected().data.AssetID
		}
	});
}

STO_Asset.prototype.deleteAsset = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟",function(btn){
		if(btn == "no")
			return;
		
		me = STO_AssetObject;
		record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال ذخيره سازي...'});
		mask.show();  

		Ext.Ajax.request({
			methos : "post",
			url : me.address_prefix + "store.data.php",
			params : {
				task : "DeleteAsset",
				AssetID : record.data.AssetID
			},

			success : function(response){
				result = Ext.decode(response.responseText);
				mask.hide();
				if(result.success)
				{
					STO_AssetObject.grid.getStore().load();
					if(STO_AssetObject.commentWin)
						STO_AssetObject.commentWin.hide();
				}
				else
					Ext.MessageBox.alert("Error",result.data);
			}
		});
	});
}

STO_Asset.DeleteFlowRender = function(v,p,r){
	
	if (r.data.IsLock == "YES")
		return "";
	
	return "<div align='center' title='حذف' class='remove' "+
	"onclick='STO_AssetObject.DeleteFlow();' " +
	"style='background-repeat:no-repeat;background-position:center;" +
	"cursor:pointer;width:100%;height:16'></div>";
}

STO_Asset.prototype.DeleteFlow = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟",function(btn){
		if(btn == "no")
			return;
		
		me = STO_AssetObject;
		record = me.FlowGrid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.FlowGrid, {msg:'در حال ذخيره سازي...'});
		mask.show();  

		Ext.Ajax.request({
			methos : "post",
			url : me.address_prefix + "store.data.php",
			params : {
				task : "DeleteFlow",
				FlowID : record.data.FlowID
			},

			success : function(response){
				result = Ext.decode(response.responseText);
				mask.hide();
				if(result.success)
				{
					STO_AssetObject.FlowGrid.getStore().load();
				}
				else
					Ext.MessageBox.alert("Error",result.data);
			}
		});
	});
}

STO_Asset.prototype.AddNewFlow = function(){
	
	var modelClass = this.FlowGrid.getStore().model;
	var record = new modelClass({
		AssetID : this.FlowGrid.getStore().proxy.extraParams.AssetID,
		FlowID : null,
		IsLock : "NO"

	});
	this.FlowGrid.plugins[0].cancelEdit();
	this.FlowGrid.getStore().insert(0, record);
	this.FlowGrid.plugins[0].startEdit(0, 0);
}

STO_Asset.prototype.SaveFlow = function(store,record,op){
	
	mask = new Ext.LoadMask(this.FlowGrid, {msg:'در حال ذخيره سازي...'});
	mask.show();    
	Ext.Ajax.request({
		url: this.address_prefix + 'store.data.php?task=SaveFlow',
		params:{
			record : Ext.encode(record.data)
		},
		method: 'POST',
		success: function(response,option){
			mask.hide();
			STO_AssetObject.FlowGrid.getStore().load();
		},
		failure: function(){}
	});
}
STO_AssetObject = new STO_Asset();
</script>
<center><br>
	<div><div id="AssetInfo"></div></div>
	<div id="DivGridFlow" style="margin: 10px 0 10px"></div>
	<div id="DivGrid" style="width: 98%"></div>	
</center>