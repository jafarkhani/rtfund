<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.10
//-----------------------------
require_once '../../header.inc.php';
require_once inc_dataGrid;

$dg = new sadaf_datagrid("dg", $js_prefix_address . "letter.data.php?task=SelectDraftLetters", "grid_div");

$dg->addColumn("", "LetterID", "", true);
$dg->addColumn("", "LetterTitle", "", true);

$col = $dg->addColumn("نوع نامه", "LetterType", "");
$col->renderer = "function(v,p,r){return v == 'INNER' ? 'داخلی' : 'صادره';}";
$col->width = 80;

$col = $dg->addColumn("تاریخ ایجاد", "LetterDate", GridColumn::ColumnType_date);
$col->width = 90;

$col = $dg->addColumn("عنوان نامه", "LetterTitle");

$col = $dg->addColumn("حذف", "");
$col->renderer = "function(v,p,r){return DraftLetter.OperationRender(v,p,r);}";
$col->width = 50;

$dg->emptyTextOfHiddenColumns = true;
$dg->height = 420;
$dg->width = 770;
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$dg->title = "نامه های پیش نویس";
$dg->DefaultSortField = "LetterDate";
$dg->autoExpandColumn = "LetterTitle";
$grid = $dg->makeGrid_returnObjects();

?>

<script>

DraftLetter.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	MenuID : "<?= isset($_POST["MenuID"]) ? $_POST["MenuID"] : "0" ?>",
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function DraftLetter(){
	
	this.grid = <?= $grid?>;
	
	this.grid.on("itemdblclick", function(view, record){
			
		framework.OpenPage(DraftLetterObject.address_prefix + 
			"NewLetter.php", "ایجاد نامه", {
			MenuID : DraftLetterObject.MenuID,
			LetterID : record.data.LetterID});
		framework.CloseTab(DraftLetterObject.TabID);
	});
	
	this.grid.render(this.get("div_grid"));
}

DraftLetter.prototype.LoadDraftLetter = function(){
		
	this.store = new Ext.data.Store({
		proxy : {
			type: 'jsonp',
			url: this.address_prefix + "DraftLetter.data.php?task=SelectDraftLetter&LetterID=" + this.LetterID,
			reader: {root: 'rows',totalProperty: 'totalCount'}
		},
		fields : ["LetterID","DraftLetterType","DraftLetterTitle","SubjectID","summary","context"],
		autoLoad : true,
		listeners : {
			load : function(){
				me = DraftLetterObject;
				//..........................................................
				record = this.getAt(0);
				me.DraftLetterPanel.loadRecord(record);
				
				DraftLetterObject.mask.hide();
			}
		}
	});
}

DraftLetter.OperationRender = function(v,p,r){
	
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='DraftLetterObject.DeleteLetter();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"float:right;cursor:pointer;width:16px;height:16'></div>";
}

DraftLetterObject = new DraftLetter();

DraftLetter.prototype.DeleteLetter = function(){

	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟",function(btn){
		if(btn == "no")
			return;
		
		me = DraftLetterObject;
		record = me.grid.getSelectionModel().getLastSelected();
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'letter.data.php',
			method: "POST",
			params: {
				task: "DeleteLetter",
				LetterID : record.data.LetterID
			},
			success: function(response){
				result = Ext.decode(response.responseText);
				mask.hide();
				if(result.success)
					DraftLetterObject.grid.getStore().load();
				else
					Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد;")
			}
		});
	});
}

</script>
<center>
	<br>
	<div id="div_grid"></div>
</center>