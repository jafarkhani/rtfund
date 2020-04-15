<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.10
//-----------------------------
require_once '../header.inc.php'; 
require_once inc_dataGrid;

$dg = new sadaf_datagrid("dg", $js_prefix_address . "letter.data.php?task=SelectAllLetter", "grid_div");

$dg->addColumn("", "LetterID", "", true);
$dg->addColumn("", "SendID", "", true);
$dg->addColumn("", "SendComment", "", true);

$col = $dg->addColumn("<img src=/office/icons/LetterType.gif>", "LetterType", "");
$col->renderer = "ManageLetter.LetterTypeRender";
$col->width = 30;

$col = $dg->addColumn("<img src=/office/icons/attach.gif>", "hasAttach", "");
$col->renderer = "function(v,p,r){if(v == 'YES') return '<img src=/office/icons/attach.gif>';}";
$col->width = 30;

$col = $dg->addColumn("شماره", "LetterID", "");
$col->width = 60;
$col->align = "center";

$col = $dg->addColumn("تاریخ نامه", "LetterDate", GridColumn::ColumnType_date);
$col->width = 90;
$col->align = "center";

$col = $dg->addColumn("موضوع نامه", "LetterTitle", "");

$col = $dg->addColumn("ثبت کننده", "RegName", "");
$col->width = 110;

/*$col = $dg->addColumn("فرستنده", "sender", "");
$col->width = 110;

$col = $dg->addColumn("گیرنده", "receiver", "");
$col->width = 110;

$col = $dg->addColumn("تاریخ ارجاع", "SendDate", GridColumn::ColumnType_date);
$col->width = 80;*/

$col = $dg->addColumn("سابقه", "");
$col->renderer = "function(v,p,r){return ManageLetter.OperationRender(v,p,r);}";
$col->width = 40;

$dg->emptyTextOfHiddenColumns = true;
$dg->height = 380;
$dg->width = 800;
$dg->title = "مدیریت نامه ها";
$dg->DefaultSortField = "LetterDate";
$dg->EnableSearch = false;
$dg->autoExpandColumn = "LetterTitle";
$grid = $dg->makeGrid_returnObjects();
?>
<script>
	
ManageLetter.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function ManageLetter(){
	
	this.SearchPanel = new Ext.form.Panel({
		renderTo : this.get("MainPanel"),
		width : 800,
		frame : true,
		layout : {
			type : "table",
			columns : 3
		},
		defaults : {
			width : 260
		},
		title : "فیلتر لیست",
		items : [{
			xtype :"container",
			html : "<input type=radio name=LetterType value=INNER> نامه داخلی &nbsp;&nbsp;&nbsp;" + 
				"<input type=radio name=LetterType value=OUTCOME> نامه صادره  &nbsp;&nbsp;&nbsp;" + 
				"<input type=radio name=LetterType value=INCOME> نامه وارده  "
		},{
			xtype : "numberfield",
			hideTrigger : true,
			fieldLabel : "شماره نامه",
			name : "LetterID"			
		},{
			xtype : "textfield",
			fieldLabel : "عنوان نامه",
			name : "LetterTitle"			
		},{
			xtype : "textfield",
			name : "organization",
			fieldLabel : "فرستنده/گیرنده"
		},{
			xtype : "combo",
			hiddenName : "PersonID",
			store: new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: '/framework/person/persons.data.php?task=selectPersons&UserType=IsStaff',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['PersonID','fullname'],
				autoLoad : true
			}),
			fieldLabel : "ایجاد کننده",
			queryMode : "local",
			displayField: 'fullname',
			valueField : "PersonID"		
		},{
			xtype : "combo",
			hiddenName : "SignerPersonID",
			store: new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: '/framework/person/persons.data.php?task=selectPersons&UserType=IsStaff',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['PersonID','fullname'],
				autoLoad : true
			}),
			fieldLabel : "امضا کننده",
			queryMode : "local",
			displayField: 'fullname',
			valueField : "PersonID"		
		},{
			xtype : "textfield",
			fieldLabel : "آدرس پستی گیرنده",
			name : "PostalAddress",
			colspan : 3,
			width : 520
		},{
			xtype : "shdatefield",
			fieldLabel : "تاریخ نامه از",
			name : "FromLetterDate"			
		},{
			xtype : "shdatefield",
			fieldLabel : "تا",
			name : "ToLetterDate"			
		},{
			xtype : "combo",
			hiddenName : "SendType",
			store: new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: this.address_prefix + 'letter.data.php?task=selectSendTypes',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['InfoID','InfoDesc'],
				autoLoad : true
			}),
			fieldLabel : "نوع ارجاع",
			queryMode : "local",
			displayField: 'InfoDesc',
			valueField : "InfoID"
		},{
			xtype : "shdatefield",
			fieldLabel : "تاریخ ارسال از",
			name : "FromSendDate"			
		},{
			xtype : "shdatefield",
			fieldLabel : "تا",
			name : "ToSendDate"			
		},{
			xtype : "combo",
			hiddenName : "IsUrgent",
			store: new Ext.data.SimpleStore({
				fields : ['id','title'],
				data : [ 
					['NO', "عادی"],
					["YES", "فوری"] 
				]
			}),  
			fieldLabel : "فوریت",
			displayField: 'title',
			valueField : "id"	
		},{
			xtype : "shdatefield",
			name : "FromInnerLetterDate",
			fieldLabel : "تاریخ نامه وارده از"
		},{
			xtype : "shdatefield",
			name : "ToInnerLetterDate",
			fieldLabel : "تا"
		},{
			xtype : "textfield",
			name : "InnerLetterNo",
			fieldLabel : "شماره نامه وارده"
		},{
			xtype : "combo",
			hiddenName : "FromPersonID",
			store: new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: '/framework/person/persons.data.php?task=selectPersons&UserType=IsStaff',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['PersonID','fullname'],
				autoLoad : true
			}),
			fieldLabel : "ارجاع از",
			queryMode : "local",
			displayField: 'fullname',
			valueField : "PersonID"		
		},{
			xtype : "combo",
			hiddenName : "ToPersonID",
			store: new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: '/framework/person/persons.data.php?task=selectPersons&UserType=IsStaff',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['PersonID','fullname'],
				autoLoad : true
			}),
			fieldLabel : "ارجاع به",
			queryMode : "local",
			displayField: 'fullname',
			valueField : "PersonID"		
		},{
			xtype : "textfield",
			name : "SendComment",
			fieldLabel : "شرح ارجاع"
		},{
			xtype : "textfield",
			name : "context",
			colspan : 2,
			width : 520,
			fieldLabel : "متن نامه"
		},{
			xtype : "textfield",
			name : "RefLetterID",
			fieldLabel : "نامه عطف"
		},{
			xtype : "textfield",
			name : "keywords",
			colspan : 3,
			width : 520,
			fieldLabel : "کلید واژگان"
		},{
			xtype : "combo",
			fieldLabel : "ذینفع",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../../framework/person/persons.data.php?' +
						"task=selectPersons&UserType=IsCustomer",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['PersonID','fullname']
			}),
			displayField : "fullname",
			pageSize : 20,
			colspan : 2,
			width : 520,
			hiddenName : "Customer",
			allowBlank : false,
			valueField : "PersonID"
		}],
		buttons : [{
			text:'جستجو',
			iconCls: 'search',
			handler: function(){ManageLetterObject.searching();}
		},{
			text : "پاک کردن فرم جستجو",
			iconCls : "clear",
			handler : function(){
				this.up('form').getForm().reset();
			}
		}]
	});
	this.SearchPanel.getEl().addKeyListener(Ext.EventObject.ENTER, function(keynumber,e){
		
		ManageLetterObject.searching();
		e.preventDefault();
		e.stopEvent();
		return false;
	});
	
	this.grid = <?= $grid ?>;
	
	this.grid.getView().getRowClass = function(record, index)
	{
		if(record.data.IsSeen == "NO")
			return "yellowRow";
		return "";
	}	
	
	/*this.grid.getView().on('render', function(view) {
        view.tip = Ext.create('Ext.tip.ToolTip', {
            target: view.el,
            delegate: view.itemSelector,
            trackMouse: true,
            listeners: {
                beforeshow: function updateTipBody(tip) {
                    tip.update(view.getRecord(tip.triggerElement).get('SendComment'));
                }
            }
        });
    });*/
	
	this.grid.on("itemdblclick", function(view, record){
			
		framework.OpenPage("/office/letter/LetterInfo.php", "مشخصات نامه", 
		{
			LetterID : record.data.LetterID,
			SendID : record.data.SendID,
			ForView : true
		});

	});
	
	this.grid.getStore().proxy.form = this.get("MainForm");
	//this.grid.render(this.get("DivGrid"));
}

ManageLetter.LetterTypeRender = function(v,p,r){
	
	if(v == 'INNER') 
		return "<img data-qtip='نامه داخلی' src=/office/icons/inner.gif>";
	if(v == 'INCOME') 
		return "<img data-qtip='نامه وارده' src=/office/icons/income.gif>";
	if(v == 'OUTCOME') 
		return "<img data-qtip='نامه صادره' src=/office/icons/outcome.gif>";
}

ManageLetterObject = new ManageLetter();

ManageLetter.OperationRender = function(v,p,r){
	
	return "<div  title='سابقه' class='history' "+
		" onclick='ManageLetterObject.ShowHistory();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;float:right;width:20px;height:16'></div>";
}

ManageLetter.prototype.ShowHistory = function(){

	if(!this.HistoryWin)
	{
		this.HistoryWin = new Ext.window.Window({
			title: 'سابقه گردش نامه',
			modal : true,
			autoScroll : true,
			width: 710,
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
			ExtTabID : this.HistoryWin.getEl().id,
			LetterID : this.grid.getSelectionModel().getLastSelected().data.LetterID
		}
	});
}

ManageLetter.prototype.searching = function(){

	if(this.grid.rendered)
		this.grid.getStore().loadPage(1);
	else
		this.grid.render(this.get("DivGrid"));
}
</script>
<center>
	<br>
	<form id="MainForm">
		<div id="MainPanel"></div>
	</form>
	<br>
	<div id="DivGrid"></div>
</center>