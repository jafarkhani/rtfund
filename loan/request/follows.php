<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	98.10
//-------------------------
require_once('../header.inc.php');
require_once inc_dataGrid;
 
//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$RequestID = $_REQUEST["RequestID"];

$dg = new sadaf_datagrid("dg",$js_prefix_address . "request.data.php?task=GetFollows&RequestID=" .$RequestID,"grid_div");

$dg->addColumn("", "FollowID","", true);
$dg->addColumn("", "RequestID","", true);
$dg->addColumn("", "StatusDesc","", true);
$dg->addColumn("", "IsPartDiff","", true);

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

$dg->height = 336;
$dg->emptyTextOfHiddenColumns = true;
$dg->EnableSearch = false;
$dg->HeaderMenu = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "FollowID";
$dg->DefaultSortDir = "ASC";
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
				[r.data.TemplateID,record.data.FollowID])
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

LoanFollow.prototype.RegisterLetter = function(TemplateID, FollowID){
	
	Ext.Ajax.request({
		url: this.address_prefix + 'request.data.php',
		params:{
			task: "RegisterLetter",
			RequestID : this.RequestID,
			TemplateID : TemplateID,
			FollowID : FollowID			
		},
		method: 'POST',

		success: function(response,option){
			result = Ext.decode(response.responseText);
			if(result.success)
			{
				LoanFollow.OpenLetter(result.data);
			}
			else if(result.data == "")
				Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
			else
				Ext.MessageBox.alert("",result.data);
			mask.hide();

		},
		failure: function(){}
	});
}


var LoanFollowObject = new LoanFollow();

</script>
<center>
	<div id="div_grid" style="width:100%"></div>
</center>