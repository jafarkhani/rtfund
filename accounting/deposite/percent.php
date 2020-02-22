<?php
//---------------------------
// programmer:	Jafarkhani
// create Date: 94.06
//-----------------------
require_once '../../header.inc.php';
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dgh = new sadaf_datagrid("dgh1",$js_prefix_address."deposite.data.php?task=SelectPercents","div_dg");

$dgh->addColumn("","RowID",'string',true);
$dgh->addColumn("","TafsiliDesc",'string',true);

$col = $dgh->addColumn("تفصیلی", "TafsiliID");
$col->renderer="function(v,p,r){return r.data.TafsiliDesc;}";
$col->editor = "this.TafCombo";

$col=$dgh->addColumn("از تاریخ", "FromDate", GridColumn::ColumnType_date);
$col->editor = ColumnEditor::SHDateField();
$col->width = 90;

$col=$dgh->addColumn("تا تاریخ", "ToDate", GridColumn::ColumnType_date);
$col->editor = ColumnEditor::SHDateField();
$col->width = 90;

$col=$dgh->addColumn("درصد سود پرداختی", "percent");
$col->editor = ColumnEditor::NumberField();
$col->width = 70;

$col=$dgh->addColumn("درصد سود دریافتی", "ReturnPercent");
$col->editor = ColumnEditor::NumberField();
$col->width = 70;

$col=$dgh->addColumn("سقف مبلغ پراخت سود", "MaxAmount", GridColumn::ColumnType_money);
$col->editor = ColumnEditor::CurrencyField();
$col->width = 130;

if($accessObj->RemoveFlag)
{
	$col = $dgh->addColumn("حذف", "", "string");
	$col->renderer = "DepositePercent.deleteRender";
	$col->width = 40;
}
if($accessObj->AddFlag)
{
	$dgh->addButton = true;
	$dgh->addHandler = "function(v,p,r){ return DepositePercentObject.Add(v,p,r);}";
	$dgh->enableRowEdit = true ;
	$dgh->rowEditOkHandler = "function(v,p,r){ return DepositePercent.Save(v,p,r);}";
}
$dgh->title ="درصد سود سپرده ها";

$dgh->emptyTextOfHiddenColumns=true;
$dgh->width = 780;
$dgh->DefaultSortField = "TafsiliDesc";
$dgh->DefaultSortDir = "ASC";
$dgh->height = 400;
$dgh->EnableSearch = false;
$grid = $dgh->makeGrid_returnObjects();
?>
<script>

DepositePercent.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : "<?= $js_prefix_address ?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function DepositePercent()
{
	this.TafCombo = new Ext.form.ComboBox({
		store: new Ext.data.Store({
			proxy:{
				type: 'jsonp',
				url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=GetAllTafsilis&'+
					'TafsiliType=<?= TAFSILITYPE_PERSON ?>',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			},
			fields :  ['TafsiliID','TafsiliDesc']
		}),
		displayField: 'TafsiliDesc',
		valueField : "TafsiliID"
	});

	this.grid = <?= $grid ?>;                
	this.grid.render(this.get("div_dg"));
	
}

DepositePercent.deleteRender = function(value, p, record)
{
	return "<div  title='حذف اطلاعات' class='remove' onclick='DepositePercentObject.Delete();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:20px;height:16'></div>";
}

var DepositePercentObject = new DepositePercent();

DepositePercent.Save = function(store,record,op)
{    
	mask = new Ext.LoadMask(Ext.getCmp(DepositePercentObject.TabID), {msg:'در حال ذخيره سازي...'});
	mask.show();    
	
	Ext.Ajax.request({
		url:  DepositePercentObject.address_prefix + 'deposite.data.php?task=SavePercent',
		params:{
			record : Ext.encode(record.data)
		},
		method: 'POST',
		success: function(response,option){
			mask.hide();
			DepositePercentObject.grid.getStore().load();
		},
		failure: function(){}
	});
}

DepositePercent.prototype.Add = function()
{  
	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		RowID:null,
		TafsiliID:null		

	});
	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

DepositePercent.prototype.Delete = function()
{    
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		
		if(btn == "no")
			return;
		
		me = DepositePercentObject;
		var record = me.grid.getSelectionModel().getLastSelected();

		mask = new Ext.LoadMask(me.grid, {msg:'در حال ذخيره سازي...'});
		mask.show();
		
		Ext.Ajax.request({
			url: me.address_prefix + 'deposite.data.php?task=DeletePercent',
			params:{
				RowID : record.data.RowID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				DepositePercentObject.grid.getStore().load();
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


