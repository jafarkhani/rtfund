<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 91.02
//-----------------------------

require_once '../../header.inc.php';
require_once "ReportGenerator.class.php";

$page_rpg = new ReportGenerator("mainForm","AccReport_checksObj");
$page_rpg->addColumn("شماره سند", "LocalNo");
$col = $page_rpg->addColumn("تاریخ سند", "DocDate");
$col->type = "date";
$page_rpg->addColumn("شماره چک", "CheckNo");
$page_rpg->addColumn("بانک", "bankDesc");
$page_rpg->addColumn("شماره حساب", "AccountNo");
$page_rpg->addColumn("حساب", "AccountDesc");
$page_rpg->addColumn("سریال دسته چک", "SerialNo");
$col = $page_rpg->addColumn("تاریخ چک", "CheckDate");
$col->type = "date";
$page_rpg->addColumn("وضعیت چک", "checkStatus");
$page_rpg->addColumn("مبلغ", "amount");
$page_rpg->addColumn("تفصیلی گیرنده", "TafsiliDesc");
$page_rpg->addColumn("بابت", "description");

function GetData(){
	
	$userFields = ReportGenerator::UserDefinedFields();
	$Year = $_SESSION["accounting"]["CycleYear"];
	$query = "
	select c.*,d.LocalNo,br.BranchName,d.DocDate,a.AccountDesc,a.AccountNo,
		b.InfoDesc as checkStatus,t.TafsiliDesc,bankDesc,cb.SerialNo".
				($userFields != "" ? "," . $userFields : "")."

	from ACC_DocCheques c
	left join ACC_tafsilis t using(tafsiliID)
	left join ACC_docs d using(DocID)
	left join BSC_branches br using(BranchID)
	left join ACC_accounts a using(AccountID)
	left join ACC_banks bb using(BankID)
	left join BaseInfo b on(b.typeID=4 AND b.infoID=CheckStatus)
	left join ACC_ChequeBooks cb on(a.AccountID=cb.AccountID and c.CheckNo between MinNo and MaxNo)
	
	where d.CycleID=" . $_SESSION["accounting"]["CycleID"];

	$whereParam = array();
	
	if(session::IsPortal() && isset($_REQUEST["dashboard_show"]))
	{
		$query .= " AND t.TafsiliType=".TAFSILITYPE_PERSON." AND t.ObjectID=" . $_SESSION["USER"]["PersonID"];
	}
	if(!empty($_POST["BranchID"]))
	{
		$query .= " AND d.BranchID=:b";
		$whereParam[":b"] = $_POST["BranchID"];
	}		
	if(!empty($_POST["fromDate"]))
	{
		$query .= " AND substring(d.docDate,1,10) >= :q1 ";
		$whereParam[":q1"] = DateModules::shamsi_to_miladi($_POST["fromDate"], "-");
	}
	if(!empty($_POST["toDate"]))
	{
		$query .= " AND substring(d.docDate,1,10) <= :q2 ";
		$whereParam[":q2"] = DateModules::shamsi_to_miladi($_POST["toDate"], "-");
	}
	if(!empty($_POST["l_fromDate"]))
	{
		$query .= " AND c.checkDate >= :fd ";
		$whereParam[":fd"] = DateModules::shamsi_to_miladi($_POST["l_fromDate"], "-");
	}
	if(!empty($_POST["l_toDate"]))
	{
		$query .= " AND c.checkDate <= :td ";
		$whereParam[":td"] = DateModules::shamsi_to_miladi($_POST["l_toDate"], "-");
	}
	if(!empty($_POST["checkStatus"]))
	{
		$query .= " AND c.checkStatus = :cs ";
		$whereParam[":cs"] = $_POST["checkStatus"];
	}
	if(!empty($_POST["FromCheckNo"]))
	{
		$query .= " AND c.checkNo >= :cn ";
		$whereParam[":cn"] = $_POST["FromCheckNo"];
	}
	if(!empty($_POST["ToCheckNo"]))
	{
		$query .= " AND c.checkNo <= :tcn ";
		$whereParam[":tcn"] = $_POST["ToCheckNo"];
	}
	if(!empty($_POST["BankID"]))
	{
		$query .= " AND a.bankID = :b ";
		$whereParam[":b"] = $_POST["BankID"];
	}
	if(!empty($_POST["AccountID"]))
	{
		$query .= " AND c.accountID = :ac ";
		$whereParam[":ac"] = $_POST["AccountID"];
	}
	if(!empty($_POST["ChequeBookID"]))
	{
		$query .= " AND cb.ChequeBookID = :cb ";
		$whereParam[":cb"] = $_POST["ChequeBookID"];
	}
	if(!empty($_POST["TafsiliID"]))
	{
		$query .= " AND c.tafsiliID = :taf ";
		$whereParam[":taf"] = $_POST["TafsiliID"];
	}
	
	$group = ReportGenerator::GetSelectedColumnsStr();
	$query .= $group == "" ? " " : " group by " . $group;
	$query .= $group == "" ? " order by checkDate" : " order by " . $group;

	$dataTable = PdoDataAccess::runquery($query, $whereParam);
	return $dataTable;
}

function ListData($IsDashboard = false){
	
	$rpg = new ReportGenerator();
	$rpg->excel = !empty($_POST["excel"]);
	
	$rpg->addColumn("شماره سند", "LocalNo");
	$rpg->addColumn("تاریخ سند", "DocDate","ReportDateRender");
	$rpg->addColumn("شماره چک", "CheckNo");
	$rpg->addColumn("بانک", "bankDesc");
	$rpg->addColumn("شماره حساب", "AccountNo");
	$rpg->addColumn("حساب", "AccountDesc");
	$rpg->addColumn("سریال دسته چک", "SerialNo");
	
	$rpg->addColumn("تاریخ چک", "CheckDate","ReportDateRender");
	$rpg->addColumn("وضعیت چک", "checkStatus");
	$col = $rpg->addColumn("مبلغ", "amount","ReportMoneyRender");
	$col->EnableSummary();
	$rpg->addColumn("تفصیلی گیرنده", "TafsiliDesc");
	$rpg->addColumn("بابت", "description");
	
	$rpg->mysql_resource = GetData();
	if(!$rpg->excel && !$IsDashboard)
	{
		BeginReport();
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
					گزارش چک های پرداختی
					 <br> "."دوره سال " . $_SESSION["accounting"]["CycleID"] .
				"</td>
				<td width='200px' align='center' style='font-family:tahoma;font-size:11px'>تاریخ تهیه گزارش : " 
			. DateModules::shNow() . "<br>";
		if(!empty($_POST["fromDate"]))
		{
			echo "<br>گزارش از تاریخ : " . $_POST["fromDate"] . ($_POST["toDate"] != "" ? " - " . $_POST["toDate"] : "");
		}
		echo "</td></tr></table>";
	}
	if($IsDashboard)
	{
		echo "<div style=direction:rtl;padding-right:10px>";
		$rpg->generateReport();
		echo "</div>";
	}
	else
		$rpg->generateReport();
	die();
}

if(isset($_REQUEST["show"]))
{
	ListData();	
}

if(isset($_REQUEST["rpcmp_chart"]))
{
	$page_rpg->mysql_resource = GetData();
	$page_rpg->GenerateChart();
	die();
}

if(isset($_REQUEST["dashboard_show"]))
{
	$chart = ReportGenerator::DashboardSetParams($_REQUEST["rpcmp_ReportID"]);
	if(!$chart)
		ListData(true);	
	
	$page_rpg->mysql_resource = GetData();
	$page_rpg->GenerateChart(false, $_REQUEST["rpcmp_ReportID"]);
	die();	
}
?>
<script>
AccReport_checks.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

AccReport_checks.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "cheques.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function AccReport_checks()
{
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش چک های پرداختی",
		defaults : {
			labelWidth :110,
			width : 350
		},
		layout :{
			type : "table",
			columns :2
		},
		width : 750,
		items :[{
			xtype : "combo",
			colspan : 2,
			width : 400,
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: "/accounting/global/domain.data.php?task=GetAccessBranches",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['BranchID','BranchName'],
				autoLoad : true					
			}),
			fieldLabel : "شعبه",
			queryMode : 'local',
			value : "<?= !isset($_SESSION["accounting"]["BranchID"]) ? "" : $_SESSION["accounting"]["BranchID"] ?>",
			displayField : "BranchName",
			valueField : "BranchID",
			hiddenName : "BranchID"
		},{
			xtype : "shdatefield",
			name : "fromDate",
			fieldLabel : "سند از تاریخ"
		},{
			xtype : "shdatefield",
			name : "toDate",
			fieldLabel : "تا تاریخ"
		},{
			xtype : "shdatefield",
			name : "l_fromDate",
			fieldLabel : "سررسید چک از تاریخ"
		},{
			xtype : "shdatefield",
			name : "l_toDate",
			fieldLabel : "تا تاریخ"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + "../baseinfo/baseinfo.data.php?task=SelectChequeStatuses",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['InfoID','InfoDesc'],
				autoLoad : true
			}),
			displayField : 'InfoDesc',
			valueField : 'infoID',
			hiddenName : "checkStatus",
			inputId : "statusName",
			fieldLabel : "وضعیت چک"
		},
		{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?' +
						"task=GetBankData",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['BankID','BankDesc'],
				autoLoad : true
			}),
			queryMode: 'local',
			displayField : 'BankDesc',
			valueField : 'bankID',
			hiddenName : "bankID",
			fieldLabel : "نام بانک",
			listeners : { 
				select : function(el,records){
					combo = AccReport_checksObj.formPanel.down("[hiddenName=AccountID]");
					combo.setValue();
					combo.getStore().proxy.extraParams["BankID"] = records[0].data.BankID;
					combo.getStore().load();					
				}
			}
		},
		{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?' +
						"task=SelectAccounts",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['AccountID','AccountDesc']
			}),
			displayField : 'AccountDesc',
			valueField : 'AccountID',
			hiddenName : "AccountID",
			fieldLabel : "حساب",
			queryMode: 'local',
			listeners : { 
				select : function(el,records){
					combo = AccReport_checksObj.formPanel.down("[hiddenName=ChequeBookID]");
					combo.setValue();
					combo.getStore().proxy.extraParams["BAccId"] = records[0].data.AccountID;
					combo.getStore().load();					
				}
			}
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?' +
						"task=SelectCheques",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['ChequeBookID','SerialNo']
			}),
			displayField : 'SerialNo',
			valueField : 'ChequeBookID',
			hiddenName : "ChequeBookID",
			queryMode: 'local',
			fieldLabel : "دسته چک"
		},{
			xtype : "numberfield",
			fieldLabel : "از شماره چک",
			name : "fromCheckNo",
			hideTrigger : true
		},{
			xtype : "numberfield",
			fieldLabel : "تا شماره چک",
			name : "ToCheckNo",
			hideTrigger : true
		},{
			xtype : "combo",
			displayField : "tafsiliTitle",
			fieldLabel : "حساب تفصیلی",
			colspan : 2,
			valueField : "TafsiliID",
			hiddenName : "TafsiliID",
			store : new Ext.data.Store({
				fields:["TafsiliID","tafsiliTitle"],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../account/data/tafsilis.data.php?task=selectTafsili',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				}
			}),
			tpl: new Ext.XTemplate(
				'<table cellspacing="0" width="100%"><tr class="x-grid-header-ct">'
				,'<td>کد</td><td>عنوان</td>'
				,'<tpl for=".">'
				,'<tr class="x-boundlist-item" style="border-left:0;border-right:0">'
				,'<td style="border-left:0;border-right:0" class="search-item">{tafsiliID}</td>'
				,'<td style="border-left:0;border-right:0" class="search-item">{tafsiliTitle}</td>'
				,'</tpl>'
				,'</table>')
		},{
			xtype : "fieldset",
			title : "ستونهای گزارش",
			colspan :2,
			width : 730,
			items :[<?= $page_rpg->ReportColumns() ?>]
		},{
			xtype : "fieldset",
			colspan :2,
			width : 730,	
			title : "رسم نمودار",
			items : [<?= $page_rpg->GetChartItems("AccReport_checksObj","mainForm","cheques.php") ?>]
		}],
		buttons : [{
			text : "گزارش ساز",
			iconCls : "db",
			handler : function(){ReportGenerator.ShowReportDB(
						AccReport_checksObj, 
						<?= $_REQUEST["MenuID"] ?>,
						"mainForm",
						"formPanel"
						);}
		},'->',{
			text : "مشاهده گزارش",
			handler : Ext.bind(this.showReport,this),
			iconCls : "report"
		},{
			text : "خروجی excel",
			handler : Ext.bind(this.showReport,this),
			listeners : {
				click : function(){
					AccReport_checksObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				AccReport_checksObj.formPanel.getForm().reset();
				AccReport_checksObj.get("mainForm").reset();
			}			
		}]
	});
}

AccReport_checksObj = new AccReport_checks();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>