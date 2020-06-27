<?php
//-----------------------------
//	Programmer	: Fatemipour
//	Date		: 96.02
//-----------------------------
require_once '../../header.inc.php';
require_once inc_dataGrid;

$DocType = $_REQUEST['DocType'];

$dg = new sadaf_datagrid("dg", $js_prefix_address . "dms.data.php?task=selectDocParams&DocType=" . $DocType, "div_dg");

$dg->addColumn("", "ParamID", "", true);
$dg->addColumn("", "DocType", "", true);

$col = $dg->addColumn("عنوان", "ParamDesc");
$col->editor = ColumnEditor::TextField();

$col = $dg->addColumn("نوع", "ParamType");
$col->editor = "this.ParamTypeCombo";

$col = $dg->addColumn("مقادیر لیست", "ParamValues");
$col->editor = ColumnEditor::TextField(true);
$col->width = 100;

$col = $dg->addColumn("حذف", "", "string");
$col->sortable = false;
$col->renderer = "function(v,p,r){return DMS_DocParams.deleteRender(v,p,r);}";
$col->width = 50;

$dg->addButton("", " ایجاد", "add", "function(){DMS_DocParamsObj.AddParam();}");

$dg->DefaultSortField = "ParamID";
$dg->DefaultSortDir = "desc";
$dg->EnablePaging = false;
$dg->EnableSearch = false;
$dg->autoExpandColumn = "ParamDesc";
$dg->enableRowEdit = true;
$dg->rowEditOkHandler = "function(v,p,r){ return DMS_DocParamsObj.SaveParam(v,p,r);}";

$dg->width = 590;
$dg->height = 460;
$dg->pageSize = 20;

$grid = $dg->makeGrid_returnObjects();
?>
<center>
    <div id="div_grid"></div>    
</center>
<script type='text/javascript'>
	
DMS_DocParams.prototype = {
	TabID: '<?= $_REQUEST["ExtTabID"] ?>',
	TplItemSeperator: '#',
	address_prefix: "<?= $js_prefix_address ?>",
	
	DocType : <?= $DocType ?>,
	
	get: function (elementID) {
		return findChild(this.TabID, elementID);
	}
};

function DMS_DocParams() {

	this.ParamTypeCombo = new Ext.form.ComboBox({
		store: new Ext.data.Store({
			fields: ["id", "name"],
			data: [
				{"id": "numberfield", "name": "عدد"},
				{"id": "currencyfield", "name": "مبلغ"},
				{"id": "textfield", "name": "متن کوتاه"},
				{"id": "textarea", "name": "متن بلند"},
				{"id": "shdatefield", "name": "تاریخ"},
				{"id": "combo", "name": "لیستی"}
			]
		}),
		emptyText: 'انتخاب ...',
		name: "name",
		valueField: "id",
		displayField: "name",
		allowBlank : false
	});
	
	this.grid = <?= $grid ?>;
	this.grid.addDocked({
		xtype : "toolbar", 
		dock : "bottom", 
		items :[{
			xtype : "container",
			html : "توجه : لطفا مقادیر مختلف لیست را با # جدا کنید"
		}]
	});
	this.grid.render(this.get("div_grid"));
}

DMS_DocParams.prototype.AddParam = function () {

	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		ParamID: 0,
		DocType : this.DocType
	});
	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
	this.grid.columns[1].getEditor().focus();
}

DMS_DocParams.prototype.SaveParam = function (store, record) {

	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg: 'در حال ذخیره سازی ...'});
	mask.show();
	Ext.Ajax.request({
		url: this.address_prefix + 'dms.data.php?task=saveDocParam',
		method: 'POST',
		params: {
			record: Ext.encode(record.data)
		},
		success: function (response) {
			mask.hide();
			var st = Ext.decode(response.responseText);
			if (st.success)
			{
				DMS_DocParamsObj.grid.getStore().load();
			}
			else
			{
				Ext.MessageBox.alert("خطا", st.data);
			}
		},
		failure: function () {
			mask.hide();
		}
	});
}

DMS_DocParams.deleteRender = function(v,p,r){
	
	return  "<div title='حذف اطلاعات' class='remove' onclick='DMS_DocParamsObj.removeParam();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;height:16'></div>";
};

DMS_DocParams.prototype.removeParam = function(){  

	Ext.MessageBox.confirm("","آیا مایل به حذف پارامتر می باشید؟", function(btn){
		if(btn == "no")
			return;

		me = DMS_DocParamsObj;
		mask = new Ext.LoadMask(me.grid, {msg: 'در حال ذخیره سازی ...'});
		mask.show();
		Ext.Ajax.request({
			url: me.address_prefix + 'dms.data.php?task=deleteDocParam',
			method: 'POST',
			params: {
				ParamID : me.grid.getSelectionModel().getLastSelected().data.ParamID
			},

			success: function(response){
				mask.hide();
				var st = Ext.decode(response.responseText);
				if(st.success)
				{
					DMS_DocParamsObj.grid.getStore().load();
				}
			},
			failure: function(){}
		});
	})

};

DMS_DocParamsObj = new DMS_DocParams();

</script>