<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	98.10
//-------------------------
require_once('../../header.inc.php');
require_once inc_dataGrid;
 
//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess(1145);
//...................................................

$RequestID = isset($_REQUEST["RequestID"]) ? $_REQUEST["RequestID"] : 0;

$dg = new sadaf_datagrid("dg",$js_prefix_address . "request.data.php?task=GetFollows&RequestID=" .$RequestID,"grid_div");

$dg->addColumn("", "FollowID","", true);
$dg->addColumn("", "StatusDesc","", true);
$dg->addColumn("", "IsPartDiff","", true);

$col = $dg->addColumn("شماره وام", "RequestID");
$col->width = 80;
	
if($RequestID == 0)
{
	$col->renderer = "LoanFollow.LoanRender";
	$col->editor = "this.LoanCombo";
	
	$col = $dg->addColumn("مشتری", "LoanFullname");
	$col->width = 80;
	
	$col = $dg->addColumn("منبع", "ReqFullname");
	$col->width = 80;
}

$col = $dg->addColumn("مرحله پیگیری", "StatusID");
$col->editor = ColumnEditor::ComboBox(PdoDataAccess::runquery("select * from BaseInfo where TypeID=98"),"InfoID","InfoDesc");
$col->width = 150;

$col = $dg->addColumn("تاریخ", "RegDate", GridColumn::ColumnType_date);
$col->editor = ColumnEditor::SHDateField();
$col->width = 80;

$col = $dg->addColumn("ثبت کننده", "RegPersonName");
$col->width = 100;

$col = $dg->addColumn("نام وکیل", "LawerName");
$col->editor = ColumnEditor::TextField(true);
$col->width = 100;

$col = $dg->addColumn("تاریخ ارجاع", "RefDate", GridColumn::ColumnType_date);
$col->editor = ColumnEditor::SHDateField(true);
$col->width = 80;

$col = $dg->addColumn("اسناد ارجاعی به وکیل", "LawerDoc");
$col->editor = ColumnEditor::TextField(true);
$col->width = 120;

$col = $dg->addColumn("جزئیات", "details");
$col->editor = ColumnEditor::TextField(true);

$col = $dg->addColumn("نامه های صادره", "letters");
$col->renderer = "LoanFollow.LettersRender";
$col->width = 120;

if($accessObj->AddFlag)
{
	$dg->enableRowEdit = true;
	$dg->rowEditOkHandler = "function(store,record){return LoanFollowObject.SaveFollow(record);}";
	$dg->addButton("AddBtn", "ایجاد ردیف", "add", "function(){LoanFollowObject.AddFollow();}");
}
$col = $dg->addColumn('عملیات', '', 'string');
$col->renderer = "LoanFollow.OperationRender";
$col->width = 50;
$col->align = "center";

if($RequestID == 0)
	$dg->title = "لیست پیگیری های معوقات تسهیلات";
$dg->height = 336;
$dg->emptyTextOfHiddenColumns = true;
$dg->DefaultSortField = "RegDate";
$dg->DefaultSortDir = "DESC";
$dg->autoExpandColumn = "details";

$grid = $dg->makeGrid_returnObjects();

?>
<script type="text/javascript">

LoanFollow.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	
	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	RequestID : <?= $RequestID ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function LoanFollow()
{
	this.LoanCombo = new Ext.form.ComboBox({
		store: new Ext.data.Store({
			proxy:{
				type: 'jsonp',
				url: this.address_prefix + 'request.data.php?task=SelectAllRequests',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			},
			fields :  ['PartAmount',"RequestID","ReqAmount","ReqDate", "RequestID", "CurrentRemain",
						"IsEnded","StatusID","ReqFullname","LoanFullname"]
		}),
		displayField: 'RequestID',
		valueField : "RequestID",
		listConfig: {width: 'auto'},
		matchFieldWidth : false,
		tpl: new Ext.XTemplate(
			'<table cellspacing="0" width="100%"><tr class="x-grid-header-ct" style="height: 23px;">',
			'<td style="padding:7px">کد وام</td>',
			'<td style="padding:7px">مشتری</td>',
			'<td style="padding:7px">معرف</td> ',
			'<td style="padding:7px">مبلغ وام</td>',
			'<td style="padding:7px">تاریخ پرداخت</td> </tr>',
			'<tpl for=".">',
				'<tr class="x-boundlist-item" style="border-left:0;border-right:0">',
				'<td style="border-left:0;border-right:0" class="search-item">{RequestID}</td>',
				'<td style="border-left:0;border-right:0" class="search-item">{LoanFullname}</td>',
				'<td style="border-left:0;border-right:0" class="search-item">{ReqFullname}</td>',
				'<td style="border-left:0;border-right:0" class="search-item">',
					'{[Ext.util.Format.Money(values.ReqAmount)]}</td>',
				'<td style="border-left:0;border-right:0" class="search-item">{[MiladiToShamsi(values.ReqDate)]}</td> </tr>',
			'</tpl>',
			'</table>'
		)
	});
	
	this.grid = <?= $grid ?>;
	this.grid.render(this.get("div_grid"));	
	
	this.LetterStore = new Ext.data.Store({
		proxy:{
			type: 'jsonp',
			url: this.address_prefix + "request.data.php?task=GetFollowTemplates",
			reader: {root: 'rows',totalProperty: 'totalCount'}
		},
		fields : ["StatusID", "TemplateID", "LetterSubject"],
		autoLoad : true		
	});
}

LoanFollow.OperationRender = function(value, p, record){
	
	return "<div  title='عملیات' class='setting' onclick='LoanFollowObject.OperationMenu(event);' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

LoanFollow.LoanRender = function(value, p, record){
	
	return "<a href='javascript:void(0)' onclick=LoanFollow.OpenLoan("+value+")>" + value + "<a>";
}

LoanFollow.OpenLoan = function(RequestID){
	framework.OpenPage("../loan/request/RequestInfo.php", "اطلاعات درخواست", 
		{RequestID : RequestID});
}

LoanFollow.LettersRender = function(value, p, record){
	
	if(value == null)
		return "";
	
	letters = value.split(",");
	returnStr = "";
	for(i=0; i<letters.length; i++)
	{
		returnStr += "<a href='javascript:void(0)' onclick=LoanFollow.OpenLetter("+letters[i]+")>" + 
				letters[i] + "<a>&nbsp;-&nbsp;";
	}
	
	return returnStr;
}

LoanFollow.OpenLetter = function(LetterID){
	
	framework.OpenPage("/office/letter/LetterInfo.php", "مشخصات نامه", 
	{
		LetterID : LetterID
	});
}

LoanFollow.prototype.OperationMenu = function(e){

	record = this.grid.getSelectionModel().getLastSelected();
	var op_menu = new Ext.menu.Menu();
	
	if(this.RemoveAccess)
		op_menu.add({text: 'حذف ردیف	',iconCls: 'remove', 
			handler : function(){ return LoanFollowObject.DeleteFollow(); }});
	
	menu = [];
	for(i=0; i<this.LetterStore.totalCount; i++)
	{
		r = this.LetterStore.getAt(i);
		if(r.data.StatusID != record.data.StatusID)
			continue;
		
		menu.push({
			text : r.data.LetterSubject,
			iconCls: 'letter',
			handler : Ext.bind(LoanFollowObject.RegisterLetter, LoanFollowObject, 
				[record.data.RequestID,r.data.TemplateID,record.data.FollowID])
		});
	}
	if(menu.length > 0)
		op_menu.add({text: 'ارسال نامه',iconCls: 'letter', menu : menu});
	op_menu.showAt(e.pageX-120, e.pageY);
}

LoanFollow.prototype.SaveFollow = function(record){

	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();
	
	Ext.Ajax.request({
		url: this.address_prefix +'request.data.php',
		method: "POST",
		params : {
			task: "SaveFollows",
			record: Ext.encode(record.data)
		},
		
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				LoanFollowObject.grid.getStore().load();
			}
			else
			{
				if(st.data == "")
					Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
				else
					Ext.MessageBox.alert("",st.data);
			}
		},
		failure: function(){}
	});
}

LoanFollow.prototype.AddFollow = function(){

	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		FollowID: null,
		RequestID : this.RequestID
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

LoanFollow.prototype.DeleteFollow = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = LoanFollowObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'request.data.php',
			params:{
				task: "DeleteFollows",
				FollowID : record.data.FollowID
			},
			method: 'POST',

			success: function(response,option){
				result = Ext.decode(response.responseText);
				if(result.success)
					LoanFollowObject.grid.getStore().load();
				else if(result.data == "")
					Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
				else
					Ext.MessageBox.alert("",result.data);
				mask.hide();
				
			},
			failure: function(){}
		});
	});
}

LoanFollow.prototype.RegisterLetter = function(RequestID, TemplateID, FollowID){
	
	mask = new Ext.LoadMask(this.grid, {msg:'در حال ایجاد نامه ...'});
	mask.show();
		
	Ext.Ajax.request({
		url: this.address_prefix + 'request.data.php',
		params:{
			task: "RegisterLetter",
			RequestID : RequestID,
			TemplateID : TemplateID,
			FollowID : FollowID			
		},
		method: 'POST',

		success: function(response,option){
			mask.hide();
			result = Ext.decode(response.responseText);
			if(result.success)
			{
				if(result.data == "0")
				{
					Ext.MessageBox.alert("","ایجاد نامه با شکست مواجه شده است");
					return;
				}
				LoanFollowObject.grid.getStore().load({
					callback : function(){LoanFollow.OpenLetter(result.data);}
				});
			}
			else if(result.data == "")
				Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
			else
				Ext.MessageBox.alert("",result.data);
			

		},
		failure: function(){}
	});
}


var LoanFollowObject = new LoanFollow();

</script>
<center>
	<div id="div_grid" style="<? if($RequestID==0){?>margin:10px;<?}?>width:98%"></div>
</center>