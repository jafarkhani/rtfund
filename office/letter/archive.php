<?php
//-----------------------------
// programmer: SH.Jafarkhani
// create Date: 94.10
//-----------------------------
require_once '../header.inc.php';
require_once inc_dataGrid;

$AddMode = isset($_REQUEST["mode"]) && $_REQUEST["mode"] == "adding" ? true : false;
$LetterID = isset($_POST["LetterID"]) ? $_POST["LetterID"] : "";

if($AddMode && $LetterID == "")
{
	echo "دسترسی نا معتبر";
	die();
}
	
if(!$AddMode)
{
	$dg = new sadaf_datagrid("dg", $js_prefix_address . "letter.data.php?task=SelectArchiveLetters", "grid_div");

	$dg->addColumn("", "LetterID", "", true);
	$dg->addColumn("", "FolderID", "", true);
	
	$col = $dg->addColumn("<img src=/office/icons/LetterType.gif>", "LetterType", "");
	$col->renderer = "LetterArchive.LetterTypeRender";
	$col->width = 30;

	$col = $dg->addColumn("<img src=/office/icons/attach.gif>", "hasAttach", "");
	$col->renderer = "function(v,p,r){if(v == 'YES') return '<img src=/office/icons/attach.gif>';}";
	$col->width = 30;

	$col = $dg->addColumn("شماره", "LetterID", "");
	$col->width = 60;
	$col->align = "center";

	$col = $dg->addColumn("تاریخ نامه", "LetterDate", GridColumn::ColumnType_date);
	$col->width = 120;
	$col->align = "center";
	
	$col = $dg->addColumn("موضوع نامه", "LetterTitle", "");

	$dg->addButton("", "حذف از بایگانی", "remove", "function(){LetterArchiveObject.RemoveFromFolder();}");
	
	$dg->emptyTextOfHiddenColumns = true;
	$dg->height = 300;
	$dg->width = 750;
	$dg->title = "نامه های";
	$dg->DefaultSortField = "LetterDate";
	$dg->autoExpandColumn = "LetterTitle";
	$grid = $dg->makeGrid_returnObjects();
}
else
	$grid = 0;
?>
<script>
LetterArchive.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : "<?= $js_prefix_address ?>",
	AddMode : <?= $AddMode ? "true" : "false" ?>,

	parent : <?= isset($_REQUEST["parent"]) ? $_REQUEST["parent"] : "null" ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function LetterArchive()
{
	this.tree = Ext.create('Ext.tree.Panel', {
		title : "بایگانی شخصی",
        store: new Ext.data.TreeStore({
			proxy: {
				type: 'ajax',
				url: this.address_prefix + 'letter.data.php?task=SelectArchiveNodes'
			},
			root: {
				text: 'بایگانی شخصی',
				id: 'src',
				expanded: true
			}
		}),
        width: this.AddMode ? 400 : 750,
        height: this.AddMode ? 500 : 200,
        renderTo: this.get("div_tree")
    });

	if(this.AddMode)
		this.tree.addDocked({
			xtype:'toolbar',
			dock:'top',
			items:[{
				text:'اضافه نامه به بایگانی',
				iconCls : "add",
				handler : function(){
					LetterArchiveObject.AddLetterToFolder();
				}			
			}]
		});
	else
		this.tree.addDocked({
			xtype:'toolbar',
			dock:'top',
			items:[{
				text:'بارگذاری نامه ها',
				iconCls : "refresh",
				handler : function(){
					LetterArchiveObject.LoadLetters();
				}			
			}]
		})
	//--------------------------------------------------------------------------
	
	this.tree.on("itemcontextmenu", function(view, record, item, index, e)
	{
		e.stopEvent();
		e.preventDefault();
		view.select(index);
		
		this.Menu = new Ext.menu.Menu();
		
		this.Menu.add({
			text: 'ایجاد زیر پوشه',
			iconCls: 'add',
			handler : function(){LetterArchiveObject.BeforeSaveFolder(false);}
		});
		
		if(record.data.id != "src")
		{
			this.Menu.add({
				text: 'ویرایش عنوان',
				iconCls: 'edit',
				handler : function(){LetterArchiveObject.BeforeSaveFolder(true);}
			});
			
			this.Menu.add({
				text: 'حذف پوشه',
				iconCls: 'remove',
				handler : function(){LetterArchiveObject.DeleteFolder();}
			});
		}

		var coords = e.getXY();
		this.Menu.showAt([coords[0]-120, coords[1]]);
	});
	
	if(this.AddMode)
		return;
	//------------------------------------------------------------------------
	this.grid = <?= $grid ?>;
	this.grid.on("itemdblclick", function(view, record){
			
		framework.OpenPage("/office/letter/LetterInfo.php", "مشخصات نامه", 
		{
			LetterID : record.data.LetterID
		});

	});
	this.grid.render(this.get("div_grid"));
}

LetterArchive.LetterTypeRender = function(v,p,r){
	
	if(v == 'INNER') 
		return "<img data-qtip='نامه داخلی' src=/office/icons/inner.gif>";
	if(v == 'INCOME') 
		return "<img data-qtip='نامه وارده' src=/office/icons/income.gif>";
	if(v == 'OUTCOME') 
		return "<img data-qtip='نامه صادره' src=/office/icons/outcome.gif>";
}

var LetterArchiveObject = new LetterArchive();

LetterArchive.prototype.BeforeSaveFolder = function(EditMode){

	if(!this.infoWin)
	{
		this.infoWin = new Ext.window.Window({
            applyTo: this.get("NewWIN"),
            modal : true,
            title: "زیر پوشه",
            width : 500,
            closeAction : "hide",
            items : new Ext.form.Panel({
                bodyStyle : "text-align:right;padding:5px",
                items :[{
					xtype : "textfield",
					name : "FolderName",
					itemId : "FolderName",
					fieldLabel : "عنوان",
					anchor : "100%"
				},{
					xtype : "hidden",
					itemId : "ParentID",
					name : "ParentID"
				},{
					xtype : "hidden",
					itemId : "FolderID",
					name : "FolderID"
				}],
                buttons :[{
					text : "ذخیره",
					handler : function(){LetterArchiveObject.SaveFolder();},
					iconCls : "save"
				},{
					text : "انصراف",
					handler : function(){
						this.up('window').hide();
					},
					iconCls : "undo"
				}]
            })
        });
	}
	
	var record = this.tree.getSelectionModel().getSelection()[0];
	this.infoWin.down('form').getForm().reset();
	this.infoWin.show();
	this.infoWin.down('form').getComponent("ParentID").setValue(record.data.id);

	if(EditMode)
	{
		this.infoWin.down('form').getComponent("FolderID").setValue(record.data.id);
		this.infoWin.down('form').getComponent("FolderName").setValue(record.data.text);
		this.infoWin.down('form').getComponent("ParentID").setValue(record.data.parentId);
	}
}

LetterArchive.prototype.SaveFolder = function(){

	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخيره سازي...'});
	mask.show();

	this.infoWin.down('form').getForm().submit({
		clientValidation: true,
		url: this.address_prefix + 'letter.data.php?task=SaveFolder',
		method : "POST",

		success : function(form,action){                

			me = LetterArchiveObject;
			FolderID = me.infoWin.down('form').getComponent("FolderID").getValue();
			mode = FolderID == "" ? "new" : "edit";

			if(mode == "new")
			{
				ParentID = me.infoWin.down('form').getComponent("ParentID").getValue();
				Parent = ParentID == "src" ? me.tree.getRootNode() :
											 me.tree.getRootNode().findChild("id",ParentID,true);
				Parent.set('leaf', false);
				Parent.appendChild({
					id : action.result.data,
					text :  me.infoWin.down('form').getComponent("FolderName").getValue(),
					leaf : true
				});  
				Parent.expand();
			}
			else
			{
				node = me.tree.getRootNode().findChild("id", FolderID, true);
				node.set('text', me.infoWin.down('form').getComponent("FolderName").getValue());
			}

			me.infoWin.down('form').getForm().reset();
			me.infoWin.hide();

			mask.hide();

		},
		failure : function(form,action)
		{
			Ext.MessageBox.alert("Error","عملیات مورد نظر با شکست مواجه شد");
			mask.hide();
		}
	});
}

LetterArchive.prototype.DeleteFolder = function(){

	Ext.MessageBox.confirm("","با حذف پوشه کلیه نامه های درون آن حذف می شوند. آیا مایل به حذف می باشید؟",
		function(btn){
			if(btn == "no")
				return;
			me = LetterArchiveObject;
			var record = me.tree.getSelectionModel().getSelection()[0];
			if(record.hasChildNodes())
			{
				Ext.MessageBox.alert("","این پوشه دارای زیر پوشه می باشد و قادر به حذف آن نمی باشید.");
				return;
			}
			Ext.Ajax.request({
				url : me.address_prefix + "letter.data.php",
				method : "POST",
				params : {
					task : "DeleteFolder",
					FolderID : record.data.id
				},
				success : function(response){
					result = Ext.decode(response.responseText);
					if(!result.success)
					{
						Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
						return;
					}				
					record.remove();
				}
			})
		});
}

LetterArchive.prototype.AddLetterToFolder = function(){

	var record = this.tree.getSelectionModel().getSelection()[0];
	if(record == null || record.data.id == "src")
	{
		Ext.MessageBox.alert("","پوشه مورد نظر خود را انتخاب کنید");
		return;
	}
	Ext.Ajax.request({
		url : this.address_prefix + "letter.data.php",
		method : "POST",
		params : {
			task : "AddLetterToFolder",
			LetterID : "<?= $LetterID ?>",
			FolderID : record.data.id
		},
		success : function(response){
			result = Ext.decode(response.responseText);
			if(!result.success)
			{
				Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
				return;
			}				
			LetterArchiveObject.parent.hide();
		}
	})
}

LetterArchive.prototype.RemoveFromFolder = function(){

	var record = this.grid.getSelectionModel().getLastSelected();
	if(record == null)
	{
		Ext.MessageBox.alert("","نامه مورد نظر خود را انتخاب کنید");
		return;
	}
	Ext.MessageBox.confirm("","آیا مایل به حذف نامه از پوشه می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		Ext.Ajax.request({
			url : LetterArchiveObject.address_prefix + "letter.data.php",
			method : "POST",
			params : {
				task : "RemoveLetterFromFolder",
				LetterID : record.data.LetterID,
				FolderID : record.data.FolderID
			},
			success : function(response){
				result = Ext.decode(response.responseText);
				if(!result.success)
				{
					Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
					return;
				}				
				LetterArchiveObject.grid.getStore().load();
			}
		});
	});	
}

LetterArchive.prototype.LoadLetters = function(){

	var record = this.tree.getSelectionModel().getSelection()[0];
	if(record == null)
	{
		Ext.MessageBox.alert("","پوشه مورد نظر خود را انتخاب کنید");
		return;
	}
	this.grid.getStore().proxy.extraParams.FolderID = record.data.id;
	this.grid.getStore().load();
	this.grid.setTitle("نامه های پوشه " + "<b>" + record.data.text + "</b>");
}

</script>
<div id="div_body" style="<? if(!$AddMode){ ?>margin: 20 20 0 0<?}?>">
	<div id="div_tree"></div>
	<div id="div_grid"></div>
</div>
