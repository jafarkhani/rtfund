<?php
//-----------------------------
//	Date		: 1395.06
//-----------------------------
 
require_once '../header.inc.php';
require_once inc_dataGrid;

$LetterID = $_REQUEST["LetterID"];
if(empty($LetterID))
	die();

$editable = isset($_REQUEST["editable"]) && $_REQUEST["editable"] == "false" ? false : true;
$ReadOnly = isset($_REQUEST["ReadOnly"]) && $_REQUEST["ReadOnly"] == "true" ? true : false;
$editable = true;

$dg = new sadaf_datagrid("dg", $js_prefix_address . "letter.data.php?task=GetLetterCustomerss&LetterID=" . $LetterID, "grid_div");

$dg->addColumn("", "RowID","", true);
$dg->addColumn("", "fullname","", true);
$dg->addColumn("", "LetterID","", true);

$col = $dg->addColumn("<div title=مشاهده‌توسط‌ذینفع style=float:right;background-repeat:no-repeat;background-position:center;cursor:pointer;width:28px;height:16px;margin-top:-20px; class=letter></div>", "IsSeen", "");
$col->renderer = "function(v,p,r){return v == 'YES' ? '<img title=مشاهده style=width:16px;height:16px;float:right; src=/office/icons/open-envelope.png>' : '<img title=عدم‌مشاهده style=width:16px;height:16px;float:right;  src=/office/icons/close-envelope.png>';}";
$col->width = 30;

$col = $dg->addColumn("مشتری", "PersonID");
$col->renderer = "function(v,p,r){return r.data.fullname;}";
$col->editor = "this.PersonCombo";

$col = $dg->addColumn("عنوان نامه", "LetterTitle");
$col->editor = ColumnEditor::TextField(true);
$col->width = 200;

$col = $dg->addColumn("عدم مشاهده ذینفع", "IsHide");
$col->editor = ColumnEditor::CheckField("","YES");
$col->renderer = "function(v,p,r){return v == 'YES' ? '<span style=color:green;font-weight:bold >√</span>' : ''}";
$col->width = 100;
$col->align = "center";

if(!$ReadOnly)
{
	$col = $dg->addColumn("پیامک", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return LetterCustomers.SmsRender(v,p,r);}";
	$col->width = 35;
	
	$col = $dg->addColumn("ایمیل", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return LetterCustomers.EmailRender(v,p,r);}";
	$col->width = 35;
}
if($editable  && !$ReadOnly)
{
	$dg->enableRowEdit = true;
	$dg->rowEditOkHandler = "function(store,record){return LetterCustomersObject.SaveLetterCustomers(record);}";

	$dg->addButton("AddBtn", "اضافه ذینفع", "add", "function(){LetterCustomersObject.Add();}");

	$col = $dg->addColumn("حذف", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return LetterCustomers.DeleteRender(v,p,r);}";
	$col->width = 35;
}
$dg->autoExpandColumn = "PersonID";
$dg->emptyTextOfHiddenColumns = true;
$dg->height = 400;
$dg->width = 540;
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "PayDate";
$dg->DefaultSortDir = "ASC";

$grid = $dg->makeGrid_returnObjects();

?>
<script>
LetterCustomers.prototype = {
	TabID : "<?= $_REQUEST["ExtTabID"] ?>",
	address_prefix : "<?= $js_prefix_address?>",

	LetterID : "<?= $LetterID ?>",
	editable : <?= $editable ? "true" : "false" ?>,
	ReadOnly : <?= $ReadOnly ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function LetterCustomers()
{
	this.PersonCombo = new Ext.form.ComboBox({
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
		itemId : "Customer",
		allowBlank : false,
		valueField : "PersonID"
	});
	
	this.grid = <?= $grid ?>;
	if(this.editable && !this.ReadOnly)
		this.grid.plugins[0].on("beforeedit", function(editor,e){

			editor = LetterCustomersObject.grid.plugins[0].getEditor();

			if(e.record.data.PersonID*1 > 0)
				editor.down("[itemId=Customer]").disable();
			else
				editor.down("[itemId=Customer]").enable();
			return true;
		});
	this.grid.render(this.get("div_grid"));
	
}

LetterCustomers.DeleteRender = function(v,p,r){
	
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='LetterCustomersObject.Delete();' " +
		"style='float:right;background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:18px;height:16'></div>";
}

LetterCustomers.EmailRender = function(v,p,r){
	
	return "<div align='center' title='ارسال نامه به ایمیل ذینفع' class='email' "+
		"onclick='LetterCustomersObject.Email();' " +
		"style='float:right;background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:18px;height:16'></div>";
}

LetterCustomers.SmsRender = function(v,p,r){
	
	if(r.data.IsHide == "YES")
		return "";
	return "<div align='center' title='ارسال پیامک به ایمیل ذینفع' class='letter' "+
		"onclick='LetterCustomersObject.Sms();' " +
		"style='float:right;background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:18px;height:16'></div>";
}

LetterCustomersObject = new LetterCustomers();
	
LetterCustomers.prototype.Add = function(){

	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		PersonID: null,
		LetterID : this.LetterID
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}
	
LetterCustomers.prototype.SaveLetterCustomers = function(record){

	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'letter.data.php',
		method: "POST",
		params: {
			task: "SaveLetterCustomer",
			record: Ext.encode(record.data)
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(!st.success)
			{
				if(st.data == "")
					Ext.MessageBox.alert("Error","عملیات مورد نظر با شکست مواجه شد");
				else
					Ext.MessageBox.alert("Error",st.data);
			}
			else
				LetterCustomersObject.grid.getStore().load();
		},
		failure: function(){}
	});
}

LetterCustomers.prototype.Delete = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = LetterCustomersObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'letter.data.php',
			params:{
				task: "DeleteLetterCustomer",
				RowID : record.data.RowID
			},
			method: 'POST',

			success: function(response,option){
				result = Ext.decode(response.responseText);
				if(result.success)
					LetterCustomersObject.grid.getStore().load();
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

LetterCustomers.prototype.Email = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به ایمیل نامه به ذینفع می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = LetterCustomersObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال ارسال نامه ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'letter.data.php',
			params:{
				task: "EmailLetter",
				RowID  : record.data.RowID,
				LetterID : record.data.LetterID,
				PersonID : record.data.PersonID
			},
			method: 'POST',

			success: function(response,option){ 
				result = Ext.decode(response.responseText);
				if(result.success)
					Ext.MessageBox.alert("","نامه با موفقیت ارسال شد");
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

LetterCustomers.prototype.Sms = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به ارسال پیامک به ذینفع می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = LetterCustomersObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال ارسال پیامک ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'letter.data.php',
			params:{
				task: "SmsCustomer",
				RowID  : record.data.RowID,
				LetterID : record.data.LetterID,
				PersonID : record.data.PersonID
			},
			method: 'POST',

			success: function(response,option){ 
				result = Ext.decode(response.responseText);
				if(result.success)
					Ext.MessageBox.alert("","پیامک با موفقیت ارسال شد");
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
</script>
<center>
	<br>
	<div id="div_grid"></div>
</center>