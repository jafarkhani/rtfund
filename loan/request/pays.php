<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	94.12
//-------------------------
include('../header.inc.php');
require_once 'request.class.php';
include_once inc_dataGrid;

$framework = isset($_SESSION["USER"]["framework"]);
$PartID = 0;
$editable = false;
if($framework)
{
	if(!empty($_POST["PartID"]))
	{
		$PartID = $_POST["PartID"];

		$obj = new LON_ReqParts($PartID);
		$ReqObj = new LON_requests($obj->RequestID);

		if($ReqObj->IsEnded == "NO")
			$editable = true;
	}
	else
		$editable = true;
}	

$dg = new sadaf_datagrid("dg",$js_prefix_address . "request.data.php?task=GetPartPays","grid_div");

$dg->addColumn("", "PayID","", true);
$dg->addColumn("", "PartID","", true);
$dg->addColumn("", "PayTypeDesc","", true);

if($editable)
{
	$col = $dg->addColumn("نحوه پرداخت", "PayType");
	$col->editor = ColumnEditor::ComboBox(PdoDataAccess::runquery("select * from BaseInfo where typeID=6"), 
		"InfoID", "InfoDesc");
}
else
	$col = $dg->addColumn("نحوه پرداخت", "PayTypeDesc");
	
$col->width = 80;

$col = $dg->addColumn("تاریخ", "PayDate", GridColumn::ColumnType_date);
if($editable)
	$col->editor = ColumnEditor::SHDateField();
$col->width = 70;

$col = $dg->addColumn("مبلغ پرداخت", "PayAmount", GridColumn::ColumnType_money);
if($editable)
	$col->editor = ColumnEditor::CurrencyField();
$col->width = 80;

$col = $dg->addColumn("شناسه پیگیری", "PayRefNo");
$col->width = 100;

$col = $dg->addColumn("شماره فیش", "PayBillNo");
if($editable)
	$col->editor = ColumnEditor::TextField(true);
$col->width = 80;

$col = $dg->addColumn("شماره چک", "ChequeNo", "string");
$col->editor = ColumnEditor::NumberField(true);
$col->width = 80;

if($editable)
{
	$col = $dg->addColumn("بانک", "ChequeBank", "");
	$col->editor = ColumnEditor::ComboBox(PdoDataAccess::runquery("select * from ACC_banks"), 
	"BankID", "BankDesc", "", "", true);
}
else
	$col = $dg->addColumn("بانک", "BankDesc", "");
$col->width = 70;

$col = $dg->addColumn("شعبه", "ChequeBranch", "");
if($editable)
	$col->editor = ColumnEditor::TextField(true);
$col->width = 80;

if($editable)
{
	$col = $dg->addColumn("وضعیت چک", "ChequeStatus", "");
	$col->editor = ColumnEditor::ComboBox(PdoDataAccess::runquery("select * from BaseInfo where typeID=16"), 
	"InfoID", "InfoDesc", "", "", true);
}
else
	$col = $dg->addColumn("وضعیت چک", "ChequeStatusDesc", "");
$col->width = 80;


$col = $dg->addColumn("توضیحات", "details", "");
//$col->ellipsis = 30;
if($editable)
	$col->editor = ColumnEditor::TextField(true);

if($editable)
{
	$dg->enableRowEdit = true;
	$dg->rowEditOkHandler = "function(store,record){return LoanPayObject.SavePartPayment(store,record);}";
	
	$dg->addButton("AddBtn", "ایجاد ردیف پرداخت", "add", "function(){LoanPayObject.AddPay();}");
	
	$col = $dg->addColumn("حذف", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return LoanPay.DeleteRender(v,p,r);}";
	$col->width = 35;
}
if($framework)
{
	$dg->addButton("cmp_report", "گزارش پرداخت", "report", 
			"function(){LoanPayObject.PayReport();}");
}

$dg->height = 377;
$dg->width = 855;
$dg->emptyTextOfHiddenColumns = true;
$dg->EnableSearch = false;
$dg->HeaderMenu = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "PayDate";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "details";

$grid = $dg->makeGrid_returnObjects();

?>
<script type="text/javascript">

LoanPay.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	
	framework : <?= $framework ? "true" : "false" ?>,
	PartID : <?= $PartID ?>,
	PartRecord : null,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function LoanPay()
{
	this.grid = <?= $grid ?>;
	this.grid.getView().getRowClass = function(record, index)
	{
		if(record.data.ChequeNo*1>0 && record.data.ChequeStatus != "2")
			return "yellowRow";
		return "";
	}	

	if(this.grid.plugins[0] != undefined)
		this.grid.plugins[0].on("beforeedit", function(editor,e){
			
			if(LoanPayObject.PartRecord != null && LoanPayObject.PartRecord.data.IsEnded == "YES")
				return false;
			
			if(e.record.data.PayID == null)
				return true;
			
			if(e.record.data.ChequeNo != null && e.record.data.ChequeStatus != "2")
				return true;
			
			return false;			
		});
		
	if(this.PartID > 0)
	{
		this.grid.getStore().proxy.extraParams = {PartID : this.PartID};
		this.grid.render(this.get("div_grid"));
		return;
	}
		
	this.PartPanel = new Ext.form.FieldSet({
		title: "انتخاب وام",
		width: 700,
		renderTo : this.get("div_loans"),
		frame: true,
		items : [{
			xtype : "combo",
			store: new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: this.address_prefix + 'request.data.php?task=selectParts',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['PartAmount',"IsEnded",'PartDesc',"RequestID","PartDate", "PartID","loanFullname",{
					name : "fullTitle",
					convert : function(value,record){
						return "کد وام : " + record.data.RequestID + "  " + record.data.PartDesc + " به مبلغ " + 
							Ext.util.Format.Money(record.data.PartAmount) + " مورخ " + 
							MiladiToShamsi(record.data.PartDate) + " " + record.data.loanFullname;
					}
				}]
			}),
			displayField: 'fullTitle',
			pageSize : 25,
			valueField : "PartID",
			width : 600,
			tpl: new Ext.XTemplate(
				'<table cellspacing="0" width="100%"><tr class="x-grid-header-ct" style="height: 23px;">',
				'<td style="padding:7px">کد وام</td>',
				'<td style="padding:7px">فاز وام</td>',
				'<td style="padding:7px">وام گیرنده</td>',
				'<td style="padding:7px">مبلغ وام</td>',
				'<td style="padding:7px">تاریخ پرداخت</td> </tr>',
				'<tpl for=".">',
					'<tr class="x-boundlist-item" style="border-left:0;border-right:0">',
					'<td style="border-left:0;border-right:0" class="search-item">{RequestID}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">{PartDesc}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">{loanFullname}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">',
						'{[Ext.util.Format.Money(values.PartAmount)]}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">{[MiladiToShamsi(values.PartDate)]}</td> </tr>',
				'</tpl>',
				'</table>'
			),
			itemId : "PartID",
			listeners :{
				select : function(combo,records){
					me = LoanPayObject;
					
					me.grid.getStore().proxy.extraParams = {
						PartID : this.getValue()
					};
					if(me.grid.rendered)
						me.grid.getStore().load();
					else
						me.grid.render(me.get("div_grid"));					
					
					if(records[0].data.IsEnded == "YES")
					{
						me.grid.down("[itemId=AddBtn]").hide();
						me.grid.columns[13].hide();
						me.get("DiVEnded").style.display = "block";
					}
					else
					{
						me.grid.down("[itemId=AddBtn]").show();
						me.get("DiVEnded").style.display = "none";
						me.grid.columns[13].show();
					}
					
					me.PartRecord = records[0];
					me.PartID = records[0].data.PartID;
				}
			}
		}]
	});
	
}

LoanPay.DeleteRender = function(v,p,r){
	
	if(r.data.PayRefNo != null &&  r.data.PayRefNo != "")
		return "";
	
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='LoanPayObject.DeletePay();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

var LoanPayObject = new LoanPay();
	
LoanPay.prototype.SavePartPayment = function(store, record){

	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'request.data.php',
		method: "POST",
		params: {
			task: "SavePartPay",
			record: Ext.encode(record.data)
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				LoanPayObject.grid.getStore().load();
				if(record.data.ChequeNo*1 > 0 && record.data.ChequeStatus != "2")
					Ext.MessageBox.alert("","برگه حسابداری هنگام وصول چک صادر می شود");
				else
					Ext.MessageBox.alert("","برگه حسابداری مربوطه صادر گردید");
			}
			else
			{
				Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
			}
		},
		failure: function(){}
	});
}

LoanPay.prototype.AddPay = function(){

	if(this.PartRecord != null && this.PartRecord.data.IsEnded == "YES")
	{
		Ext.MessageBox.alert("","این وام خاتمه یافته است");
		return;
	}
	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		PayID: null,
		PartID : this.PartID
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

LoanPay.prototype.DeletePay = function(){
	
	Ext.MessageBox.confirm("","در صورت حذف سند مربوطه نیز حذف خواهد شد. <br>"+"آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = LoanPayObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'request.data.php',
			params:{
				task: "DeletePay",
				PayID : record.data.PayID
			},
			method: 'POST',

			success: function(response,option){
				result = Ext.decode(response.responseText);
				if(result.success)
					LoanPayObject.grid.getStore().load();
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

LoanPay.prototype.PayReport = function(){

	window.open(this.address_prefix + "../report/LoanPayment.php?show=true&PartID=" + this.PartID);
}

</script>
<center>
	<div id="div_loans"></div>
	<div style="display:none;color : red;font-weight: bold" id="DiVEnded">
		 این وام خاتمه یافته و قادر به تغییر در پرداخت های آن نمی باشید
		<br>&nbsp;</div>
	<div id="div_grid"></div>
</center>