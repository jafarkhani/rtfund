<?php
//---------------------------
// programmer:	Jafarkhani
// create Date: 94.06
//-----------------------
require_once '../header.inc.php';
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dgh = new sadaf_datagrid("dgh1",$js_prefix_address."letter.data.php?task=SelectTemplates","div_dg");

$dgh->addColumn("","TemplateID",'string',true);

$col = $dgh->addColumn("عنوان الگو", "TemplateTitle");

$col=$dgh->addColumn("متن الگو", "context");
$col->renderer="LetterTemplate.ContextRender";
$col->width = 60;

if($accessObj->RemoveFlag)
{
	$col = $dgh->addColumn("حذف", "", "string");
	$col->renderer = "LetterTemplate.deleteRender";
	$col->width = 40;
}
$dgh->emptyTextOfHiddenColumns=true;
$dgh->width = 600;
$dgh->DefaultSortField = "TemplateTitle";
$dgh->DefaultSortDir = "ASC";
$dgh->height = 400;
$dgh->EnableSearch = false;
$dgh->EnablePaging = false;
$dgh->pageSize=12;
$grid = $dgh->makeGrid_returnObjects();
?>
<script>

LetterTemplate.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : "<?= $js_prefix_address ?>",

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,
	
	TemplateID : 0,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function LetterTemplate()
{
	this.grid = <?= $grid ?>;                
	this.grid.render(this.get("div_dg"));
}

LetterTemplate.deleteRender = function(value, p, record)
{
	return "<div  title='حذف اطلاعات' class='remove' onclick='LetterTemplateObject.DeleteTemplate();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:20px;height:16'></div>";
}

LetterTemplate.ContextRender = function(value, p, record)
{
	return "<div  title='متن الگو' class='letter' onclick='LetterTemplateObject.ShowContext();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:20px;height:16'></div>";
}

var LetterTemplateObject = new LetterTemplate();

LetterTemplate.prototype.ShowContext = function(){
	
	if(!this.ContextWin)
	{
		this.ContextWin = new Ext.window.Window({
			width : 700,			
			height : 500,
			modal : true,
			bodyStyle : "background-color:white;",
			closeAction : "hide",
			items : [{
				xtype : "container",
				html : "<div id='TemplateLetterEditor'></div>"
			}],
			buttons :[{
				text : "ذخیره",
				disabled : this.AddAccess ? false : true,
				iconCls  : "save",
				handler : function(){
					me = LetterTemplateObject;
					mask = new Ext.LoadMask(me.ContextWin, {msg:'در حال ذخيره سازي...'});
					mask.show();
					
					Ext.Ajax.request({
						url : me.address_prefix + "letter.data.php?task=SaveTemplates",
						method : "post",
						params :{
							TemplateID : LetterTemplateObject.TemplateID,
							context : CKEDITOR.instances.TemplateLetterEditor.getData()
						},
						success : function(){
							me.grid.getStore().load();
							me.ContextWin.hide();							
							mask.hide();
						}
					});
				}
			},{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){
					this.up('window').hide();
				}
			}]
		});
		Ext.getCmp(this.TabID).add(this.ContextWin);
	}

	this.ContextWin.show();
	this.ContextWin.center();
	if(this.TemplateID == 0)
	{
		if ( CKEDITOR.env.ie && CKEDITOR.env.version < 9 )
			CKEDITOR.tools.enableHtml5Elements( document );

		if(CKEDITOR.instances.TemplateLetterEditor)
			CKEDITOR.instances.TemplateLetterEditor.destroy();
		CKEDITOR.config.width = 'auto';
		CKEDITOR.config.height = 330;
		CKEDITOR.config.autoGrow_minHeight = 170;
		CKEDITOR.replace('TemplateLetterEditor');	
		CKEDITOR.add;
	}
	record = this.grid.getSelectionModel().getLastSelected();
	LetterTemplateObject.TemplateID = record.data.TemplateID;

	CKEDITOR.instances.TemplateLetterEditor.on('instanceReady', function( ev ) {
		ev.editor.setData(record.data.context);
	});			
	CKEDITOR.instances.TemplateLetterEditor.setData(record.data.context);

}

LetterTemplate.prototype.DeleteTemplate = function()
{    
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		
		if(btn == "no")
			return;
		
		me = LetterTemplateObject;
		var record = me.grid.getSelectionModel().getLastSelected();

		mask = new Ext.LoadMask(me.grid, {msg:'در حال ذخيره سازي...'});
		mask.show();
		
		Ext.Ajax.request({
			url: me.address_prefix + 'letter.data.php?task=DeleteTemplate',
			params:{
				TemplateID : record.data.TemplateID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				LetterTemplateObject.grid.getStore().load();
			},
			failure: function(){}
		});		
	});
}

</script>

<center>
	<br>
	<div id="div_dg"></div>
</center>


