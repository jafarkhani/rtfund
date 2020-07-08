<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	94.12
//-------------------------
require_once('../../header.inc.php');
require_once 'request.class.php';
require_once inc_dataGrid;
 
//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$RequestID = 0;
$editable = false;
if(session::IsFramework())
{
	if(!empty($_POST["RequestID"]))
	{
		$RequestID = $_POST["RequestID"];

		$ReqObj = new LON_requests($RequestID);
		if($ReqObj->IsEnded == "NO")
			$editable = $accessObj->EditFlag;
	}
	else
		$editable = $accessObj->AddFlag;
}	

$dg = new sadaf_datagrid("dg",$js_prefix_address . "request.data.php?task=GetBackPays","grid_div");

$dg->addColumn("", "BackPayID","", true);
$dg->addColumn("", "RequestID","", true);
$dg->addColumn("", "PayTypeDesc","", true);
$dg->addColumn("", "DocID","", true);
$dg->addColumn("", "LocalNo","", true);
$dg->addColumn("", "ChequeStatus","", true);
$dg->addColumn("", "IsGroup","", true);
$dg->addColumn("", "EqualizationID","", true);
$dg->addColumn("", "IncomeChequeID","", true);

if($editable)
{
	$col = $dg->addColumn("نحوه پرداخت", "PayType");
	$col->editor = ColumnEditor::ComboBox(PdoDataAccess::runquery("select * from BaseInfo where typeID=6"),
			"InfoID", "InfoDesc");
}
else
	$col = $dg->addColumn("نحوه پرداخت", "PayTypeDesc");
	
$col->width = 120;

$col = $dg->addColumn("تاریخ", "PayDate", GridColumn::ColumnType_date);
if($editable)
	$col->editor = ColumnEditor::SHDateField();
$col->width = 90;

$col = $dg->addColumn("مبلغ پرداخت", "PayAmount", GridColumn::ColumnType_money);
if($editable)
	$col->editor = ColumnEditor::CurrencyField();
$col->width = 80;

$col = $dg->addColumn("شناسه پیگیری", "PayRefNo");
$col->width = 100;

$col = $dg->addColumn("شماره فیش", "PayBillNo");
if($editable)
	$col->editor = ColumnEditor::TextField(true);
$col->width = 100;

$col = $dg->addColumn("شماره چک", "ChequeNo", "string");
$col->width = 100;

$col = $dg->addColumn("وضعیت چک", "ChequeStatusDesc", "");
$col->width = 80;


$col = $dg->addColumn("توضیحات", "details", "");
//$col->ellipsis = 30;
if($editable)
	$col->editor = ColumnEditor::TextField(true);

$col = $dg->addColumn("سند", "");
$col->sortable = false;
$col->renderer = "function(v,p,r){return LoanPay.RegDocRender(v,p,r);}";
$col->width = 40;
	
if($editable && $accessObj->AddFlag)
{
	$dg->enableRowEdit = true;
	$dg->rowEditOkHandler = "function(store,record){return LoanPayObject.SaveBackPay(record);}";
	
	$dg->addButton("AddBtn", "ایجاد ردیف پرداخت", "add", "function(){LoanPayObject.AddPay();}");
	
	
	if($accessObj->RemoveFlag)
	{
		$col = $dg->addColumn("حذف", "");
		$col->sortable = false;
		$col->renderer = "function(v,p,r){return LoanPay.DeleteRender(v,p,r);}";
		$col->width = 35;
	}
}
if(session::IsFramework())
{
	$dg->addButton("", "گزارش پرداخت", "report", 
			"function(){LoanPayObject.PayReport2();}");
}

$dg->height = 377;
$dg->emptyTextOfHiddenColumns = true;
$dg->EnableSearch = false;
$dg->HeaderMenu = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "PayDate";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "details";

$grid = $dg->makeGrid_returnObjects();

//-----------------------------------------------
require_once '../../accounting/cheque/cheque.class.php';
$dt = ACC_IncomeCheques::Get(" AND LoanRequestID=? order by ChequeDate", array($RequestID));
$dt = $dt->fetchAll();
$DelayCheques = "";
$index = 1;
foreach($dt as $row)
{
	if($DelayCheques == "")
		$DelayCheques = '<table width="98%" border="1" style="background-color:white;border-collapse: collapse;">
				<caption style="text-align: center;border: 1px solid black;background-color: gold;font-weight: bold;">چک های تنفس</caption>
				<tr class="TBLheader">
					<td>ردیف</td>
					<td>تاریخ پرداخت</td>
					<td>نوع پرداخت</td>
					<td>بابت</td>
					<td>وضعیت چک</td>
					<td>مبلغ</td>
					<td>شماره چک</td>
				</tr>';
	
	$color = $row["ChequeStatus"] != INCOMECHEQUE_VOSUL ? "gray" : "black";

	$DelayCheques .= "<tr><td>" . $index++ . 
		"</td><td>" . DateModules::miladi_to_shamsi($row["ChequeDate"]) .
		"</td><td> چک" .
		"</td><td>تنفس" .  
		"</td><td>" . $row["ChequeStatusDesc"] . 							
		"</td><td style=color:$color>" . number_format($row["ChequeAmount"]) . "</td>" . 
		"</td><td>" . $row["ChequeNo"] . 
		"</td></tr>";
}
$DelayCheques .= "</table>";
?>
<style>
	.TBLheader{
		background-color: gainsboro;
		font-weight: bold;
	}
</style>
<script type="text/javascript">

LoanPay.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	
	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,
	
	RequestID : <?= $RequestID ?>,
	PartID : <?= LON_ReqParts::GetValidPartObj($RequestID)->PartID ?>,
	PartRecord : null,
	
	EventID : <?= LON_requests::GetEventID($RequestID, EVENTTYPE_LoanBackPay) ?>,
	ChequeEventID : <?= LON_requests::GetEventID($RequestID, EVENTTYPE_LoanBackPayCheque) ?>,
	
	GroupPays : new Array(),
	GroupPaysTitles : new Array(),
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function LoanPay()
{
	this.grid = <?= $grid ?>;
	this.grid.getView().getRowClass = function(record, index)
	{
		if(record.data.EqualizationID*1 > 0)
			return "yellowRow";
		
		if(record.data.IsGroup == "YES")
			return "greenRow";
		
		return "";
	}	

	if(this.AddAccess && this.grid.plugins[0] != undefined)
	{
		this.grid.plugins[0].on("beforeedit", function(editor,e){
			
			if(LoanPayObject.PartRecord != null && LoanPayObject.PartRecord.data.IsEnded == "YES")
				return false;
			
			if(e.record.data.BackPayID == null)
				return true;
			
			if(e.record.data.PayType == "<?= BACKPAY_PAYTYPE_CHEQUE ?>")
				return false;
				
			if(e.record.data.PayType == "<?= BACKPAY_PAYTYPE_EPAY ?>" || 
				e.record.data.PayType == "<?= BACKPAY_PAYTYPE_CORRECT ?>")
				return false;
			
			if(e.record.data.StatusID != null && e.record.data.StatusID != "<?= ACC_STEPID_RAW ?>")
				return false;
			
			return true;			
		});
	}
		
	if(this.RequestID > 0)
	{
		this.grid.getStore().proxy.extraParams = {RequestID : this.RequestID};
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
					url: this.address_prefix + 'request.data.php?task=SelectAllRequests2',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['PartAmount',"IsEnded","RequestID","PartDate","loanFullname","InstallmentAmount",{
					name : "fullTitle",
					convert : function(value,record){
						return "کد وام : " + record.data.RequestID + " به مبلغ " + 
							Ext.util.Format.Money(record.data.PartAmount) + " مورخ " + 
							MiladiToShamsi(record.data.PartDate) + " " + record.data.loanFullname;
					}
				}]
			}),
			displayField: 'fullTitle',
			pageSize : 25,
			valueField : "RequestID",
			width : 600,
			tpl: new Ext.XTemplate(
				'<table cellspacing="0" width="100%"><tr class="x-grid-header-ct" style="height: 23px;">',
				'<td style="padding:7px">کد وام</td>',
				'<td style="padding:7px">وام گیرنده</td>',
				'<td style="padding:7px">مبلغ وام</td>',
				'<td style="padding:7px">تاریخ پرداخت</td>',
				'<td style="padding:7px"></td>',
				'</tr>',
				'<tpl for=".">',
					'<tpl if="IsEnded == \'YES\'">',
						'<tr class="x-boundlist-item pinkRow" style="border-left:0;border-right:0">',
					'<tpl else>',
						'<tr class="x-boundlist-item" style="border-left:0;border-right:0">',
					'</tpl>',
					'<td style="border-left:0;border-right:0" class="search-item">{RequestID}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">{loanFullname}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">',
						'{[Ext.util.Format.Money(values.PartAmount)]}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">{[MiladiToShamsi(values.PartDate)]}</td>',
					'<tpl if="IsEnded == \'NO\'">',
						'<td class="search-item"><div align=center title="اضافه به پرداخت گروهی" class=add ',
							'onclick="LoanPayObject.AddToGroupPay(event,\'{loanFullname}\',',
							'{RequestID},{InstallmentAmount});" ',
							'style=background-repeat:no-repeat;',
							'background-position:center;cursor:pointer;width:20px;height:16></div></td>',
					'<tpl else>',
						'<td class="search-item"></td>',
					'</tpl>',
				' </tr>',
				'</tpl>',
				'</table>'
			),
			itemId : "RequestID",
			listeners :{
				select : function(combo,records){
					me = LoanPayObject;
					
					me.grid.getStore().proxy.extraParams = {
						RequestID : this.getValue()
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
					me.RequestID = records[0].data.RequestID;
				}
			}
		},{
			xtype : "button",
			border : true,
			text : "پرداخت گروهی اقساط",
			iconCls : "list",
			handler : function(){ LoanPayObject.BeforeSaveGroupPay(); }
		}]
	});
	
}

LoanPay.DeleteRender = function(v,p,r){
	
	if(r.data.PayRefNo != null &&  r.data.PayRefNo != "")
		return "";
	
	if(r.data.PayType == "<?= BACKPAY_PAYTYPE_CHEQUE ?>" || r.data.PayType == "<?= BACKPAY_PAYTYPE_CORRECT ?>")
		return "";
		
	if(r.data.DocID*1 > 0)
		return "";
	
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='LoanPayObject.DeletePay();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

LoanPay.RegDocRender = function(v,p,r){
	
	st = "<a target=_blank href=/accounting/docs/print_doc.php?DocID=" + r.data.DocID + ">"+r.data.LocalNo+"</a>";
	
	if(r.data.DocID*1 > 0)
		return st;
	
	if(	r.data.PayType != "<?= BACKPAY_PAYTYPE_CHEQUE ?>" 
		&&  r.data.PayType != "<?= BACKPAY_PAYTYPE_EPAY ?>"
		&&  r.data.PayType != "<?= BACKPAY_PAYTYPE_CORRECT ?>")
		
		return "<div align='center' title='صدور سند' class='send' "+
		"onclick='LoanPayObject.ExecuteEvent();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";	

	if(<?= $_SESSION["USER"]["UserName"] == "admin" ? "true" : "false" ?>)
		return "<div align='center' title='صدور سند' class='send' "+
		"onclick='LoanPayObject.ExecuteEvent();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";	
}

var LoanPayObject = new LoanPay();
	
LoanPay.prototype.BeforeRegisterDoc = function(mode){
	
	if(!this.BankWin)
	{
		this.BankWin = new Ext.window.Window({
			width : 400,
			height : 350,
			bodyStyle : "background-color:white",
			modal : true,
			closeAction : "hide",
			items : [{
				xtype : "form",
				border : false,
				items :[{
					xtype : "combo",
					width : 385,
					fieldLabel : "حساب مربوطه",
					colspan : 2,
					store: new Ext.data.Store({
						fields:["CostID","CostCode","CostDesc", "TafsiliType1","TafsiliType2",{
							name : "fullDesc",
							convert : function(value,record){
								return "[ " + record.data.CostCode + " ] " + record.data.CostDesc
							}				
						}],
						proxy: {
							type: 'jsonp',
							url: '/accounting/baseinfo/baseinfo.data.php?task=SelectCostCode',
							reader: {root: 'rows',totalProperty: 'totalCount'}
						}
					}),
					typeAhead: false,
					name : "CostID",
					valueField : "CostID",
					displayField : "fullDesc",
					listeners : {
						select : function(combo,records){
							me = LoanPayObject;
							
							me.BankWin.down("[itemId=TafsiliID]").setValue();
							me.BankWin.down("[itemId=TafsiliID]").getStore().proxy.extraParams.TafsiliType = records[0].data.TafsiliType1;
							me.BankWin.down("[itemId=TafsiliID]").getStore().load();

							
							me.BankWin.down("[itemId=TafsiliID2]").setValue();
							me.BankWin.down("[itemId=TafsiliID2]").getStore().proxy.extraParams.TafsiliType = records[0].data.TafsiliType2;
							me.BankWin.down("[itemId=TafsiliID2]").getStore().load();
							
							if(this.getValue() == "<?= COSTID_Bank ?>")
							{
								me.BankWin.down("[itemId=TafsiliID]").setValue(
									"<?= $_SESSION["accounting"]["DefaultBankTafsiliID"] ?>");
								me.BankWin.down("[itemId=TafsiliID2]").setValue(
									"<?= $_SESSION["accounting"]["DefaultAccountTafsiliID"] ?>");
							}
						}
					}
				},{
					xtype : "combo",
					store: new Ext.data.Store({
						fields:["TafsiliID","TafsiliCode","TafsiliDesc"],
						proxy: {
							type: 'jsonp',
							url: '/accounting/baseinfo/baseinfo.data.php?task=GetAllTafsilis',
							reader: {root: 'rows',totalProperty: 'totalCount'}
						}
					}),
					emptyText:'انتخاب تفصیلی1 ...',
					typeAhead: false,
					pageSize : 10,
					width : 385,
					valueField : "TafsiliID",
					itemId : "TafsiliID",
					name : "TafsiliID",
					displayField : "TafsiliDesc",
					listeners : { 
						change : function(){
							t1 = this.getStore().proxy.extraParams["TafsiliType"];
							combo = LoanPayObject.BankWin.down("[itemId=TafsiliID2]");

							if(t1 == <?= TAFTYPE_BANKS ?>)
							{
								combo.getStore().proxy.extraParams["ParentTafsili"] = this.getValue();
								combo.getStore().load();
							}			
							else
								combo.getStore().proxy.extraParams["ParentTafsili"] = "";
						}
					}
				},{
					xtype : "combo",
					store: new Ext.data.Store({
						fields:["TafsiliID","TafsiliCode","TafsiliDesc"],
						proxy: {
							type: 'jsonp',
							url: '/accounting/baseinfo/baseinfo.data.php?task=GetAllTafsilis',
							reader: {root: 'rows',totalProperty: 'totalCount'}
						}
					}),
					emptyText:'انتخاب تفصیلی2 ...',
					typeAhead: false,
					pageSize : 10,
					width : 385,
					valueField : "TafsiliID",
					itemId : "TafsiliID2",
					name : "TafsiliID2",
					displayField : "TafsiliDesc"
				}]
			}],
			buttons :[{
				text : "ذخیره",
				iconCls : "save",
				itemId : "btn_save"
			},{
				text : "انصراف",
				iconCls : "undo",
				handler : function(){this.up('window').hide(); LoanPayObject.grid.getStore().load();}
			}]
		});
		Ext.getCmp(this.TabID).add(this.BankWin);
	}
	
	record =  this.grid.getSelectionModel().getLastSelected(); 
	/*if(record && mode != 3 && record.data.PayType == "<?= BACKPAY_PAYTYPE_CHEQUE ?>")
	{
		LoanPayObject.RegisterDoc(mode); 
		return;
	}*/
	
	this.BankWin.show();
	this.BankWin.down("[itemId=btn_save]").setHandler(function(){ 
		LoanPayObject.BankWin.hide();
		LoanPayObject.RegisterDoc(mode); 
	});
}
	
LoanPay.prototype.SaveBackPay = function(record){

	if(record.data.PayType == "<?= BACKPAY_PAYTYPE_CHEQUE ?>")
	{
		Ext.MessageBox.alert("Error", "برای ثبت چک از منوی چک های دریافتی اقدام نمایید");
		return false;
	}
	if(record.data.PayType == "<?= BACKPAY_PAYTYPE_CORRECT ?>")
	{
		Ext.MessageBox.alert("Error", "این گزینه تنها از طریق سیستم ثبت می گردد و شما قادر به ثبت آن نمی باشید");
		return false;
	}
	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'request.data.php',
		method: "POST",
		params: {
			task: "SaveBackPay",
			record: Ext.encode(record.data)
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				LoanPayObject.grid.getStore().load();
			}
			else
			{
				Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
			}
		},
		failure: function(){}
	});
}

LoanPay.prototype.RegisterDoc = function(mode){
	
	if(mode != 3)
		record = this.grid.getSelectionModel().getLastSelected(); 
	
	mask = new Ext.LoadMask(this.BankWin, {msg:'در حال ذخیره سازی ...'});
	mask.show();
	
	switch(mode){
		case 1 : task = "RegisterBackPayDoc"; break;
		case 2 : task = "EditBackPayDoc"; break;
		case 3 : task = "GroupSavePay"; break;
	}
	
	params = {
		task: task
	};
	
	if(record)
		params.BackPayID = record.data.BackPayID;
	
	params = mergeObjects(params, this.BankWin.down('form').getForm().getValues());
		
	if(mode == 3)
	{
		params.parts = Ext.encode(this.GroupPays);
		params = mergeObjects(params, this.groupWin.down('form').getForm().getValues());
	}
	
	Ext.Ajax.request({
		url: this.address_prefix +'request.data.php',
		method: "POST",
		params: params,
		
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				LoanPayObject.grid.getStore().load();
				if(record && record.data.ChequeNo*1 > 0 && record.data.ChequeStatus != "2")
					Ext.MessageBox.alert("","سند حسابداری هنگام وصول چک صادر می شود");
				if(mode == 1)
					Ext.MessageBox.alert("","سند حسابداری مربوطه صادر گردید");
				else if(mode == 2)
					Ext.MessageBox.alert("","سند حسابداری مربوطه ویرایش گردید");
				else
				{
					LoanPayObject.groupWin.hide();
					Ext.MessageBox.alert("","سند گروهی صادر گردید");
				}
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

LoanPay.prototype.AddPay = function(){

	if(this.PartRecord != null && this.PartRecord.data.IsEnded == "YES")
	{
		Ext.MessageBox.alert("","این وام خاتمه یافته است");
		return;
	}
	
	defaultAmount = 0;
	if(this.grid.getStore().totalCount > 0)
		defaultAmount = this.grid.getStore().getAt(0).data.PayAmount;
	
	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		BackPayID: null,
		RequestID : this.RequestID,
		PayAmount : defaultAmount
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
				BackPayID : record.data.BackPayID
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


LoanPay.prototype.PayReport2 = function(){

	window.open(this.address_prefix + "../report/LoanPayment.php?show=true&RequestID=" + this.RequestID);
}

LoanPay.prototype.AddToGroupPay = function(e ,loanFullname, RequestID, InstallmentAmount){

	if(!this.groupAmountWin)
	{
		this.groupAmountWin = new Ext.window.Window({
			width : 300,
			height : 100,
			modal : true,
			title : "نحوه پرداخت",
			bodyStyle : "background-color:white",
			items : [{
				xtype : "currencyfield",
				hideTrigger : true,
				fieldLabel : "مبلغ پرداخت"
			}],
			closeAction : "hide",
			buttons : [{
				text : "اضافه به پرداخت گروهی",				
				iconCls : "add",
				itemId : "btn_add"	
			}]

		});
	}
	this.groupAmountWin.down('currencyfield').setValue(InstallmentAmount);
	this.groupAmountWin.down("[itemId=btn_add]").setHandler(function(){
		amount = this.up('window').down('currencyfield').getValue();
		LoanPayObject.GroupPays.push(RequestID + "_" + amount);
		LoanPayObject.GroupPaysTitles.push(loanFullname);
		LoanPayObject.groupAmountWin.hide();
	})
	this.groupAmountWin.show();
	this.groupAmountWin.center();
	e.stopImmediatePropagation();	
}

LoanPay.prototype.BeforeSaveGroupPay = function(){

	if(this.GroupPays.length == 0)
	{
		Ext.MessageBox.alert("","تا کنون وامی به پرداخت گروهی اضافه نشده است");
		return;
	}
	if(!this.groupWin)
	{
		this.groupWin = new Ext.window.Window({
			width : 300,
			height : 370,
			modal : true,
			title : "نحوه پرداخت",
			bodyStyle : "background-color:white",
			items : new Ext.form.Panel({
				items : [{
					xtype : "combo",
					store : new Ext.data.Store({
						proxy:{
							type: 'jsonp',
							url: this.address_prefix + 'request.data.php?task=GetPayTypes',
							reader: {root: 'rows',totalProperty: 'totalCount'}
						},
						fields :  ["InfoID", "InfoDesc"]
					}),
					displayField: 'InfoDesc',
					valueField : "InfoID",
					name : "PayType",
					allowBlank : false,
					fieldLabel : "نوع پرداخت"
				},{
					xtype : "shdatefield",
					name : "PayDate",
					allowBlank : false,
					fieldLabel : "تاریخ پرداخت"
				},{
					xtype : "textfield",
					name : "PayBillNo",
					fieldLabel : "شماره فیش"
				},{
					xtype : "numberfield",
					name : "ChequeNo",
					hideTrigger : true,
					fieldLabel : "شماره چک"
				},{
					xtype : "combo",
					store : new Ext.data.Store({
						proxy:{
							type: 'jsonp',
							url: this.address_prefix + 'request.data.php?task=GetBanks',
							reader: {root: 'rows',totalProperty: 'totalCount'}
						},
						fields :  ["BankID", "BankDesc"]
					}),
					displayField: 'BankDesc',
					valueField : "BankID",
					name : "ChequeBank",
					fieldLabel : "بانک"
				},{
					xtype : "textfield",
					name : "ChequeBranch",
					fieldLabel : "شعبه"
				},{
					xtype : "multiselect",
					itemId : "GroupList",
					store : this.GroupPaysTitles,
					height : 100
				},{
					xtype : "button",
					text : "حذف از لیست",
					iconCls : "cross",
					handler : function(){
						
						me = LoanPayObject;
						el = me.groupWin.down("[itemId=GroupList]");
						index = el.getStore().indexOf(el.getSelected()[0]);
						if(index >= 0)
						{
							me.GroupPays.splice(index,1);
							me.GroupPaysTitles.splice(index,1);
							el.clearValue();
							el.bindStore(me.GroupPaysTitles);
						}
					}
				}]
			}),
			closeAction : "hide",
			buttons : [{
				text : "صدور گروهی اقساط",				
				iconCls : "save",
				itemId : "btn_save",
				handler : function(){
					if(!this.up('window').down('form').getForm().isValid())
						return;
					LoanPayObject.BeforeRegisterDoc(3);
				}		
			},{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){
					this.up('window').hide();
				}
			}]

		});
	}
	this.groupWin.down("[itemId=GroupList]").bindStore(this.GroupPaysTitles);
	this.groupWin.show();
	this.groupWin.center();
}

LoanPay.prototype.ExecuteEvent = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	framework.ExecuteEvent(this.EventID, new Array(
		this.RequestID,this.PartID,record.data.BackPayID));
}

</script>
<center>
	<?= $DelayCheques ?>
	<div id="div_loans"></div>
	<div style="display:none;color : red;font-weight: bold" id="DiVEnded">
		 این وام خاتمه یافته و قادر به تغییر در پرداخت های آن نمی باشید
		<br>&nbsp;</div>
	<div id="div_grid"></div>
	ردیف های زرد رنگ ردیف هایی هستند که از طریق مغایرت بانکی تایید شده اند
	<br>
	ردیف های سبز رنگ ردیف هایی هستند که از طریق پرداخت گروهی ثبت شده اند
	<br>
	
</center>
