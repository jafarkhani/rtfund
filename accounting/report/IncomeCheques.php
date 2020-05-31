<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 91.02
//-----------------------------

require_once '../../header.inc.php';
require_once "ReportGenerator.class.php";

$page_rpg = new ReportGenerator("mainForm","AccReport_IncomeChequeObj");
$page_rpg->addColumn("صاحب چک", "fullname");
$page_rpg->addColumn("موبایل", "mobile");
$page_rpg->addColumn("شماره پیامک", "mobile");
$page_rpg->addColumn("حساب", "CostDesc");
$page_rpg->addColumn("معرف", "ReqFullname");	
$page_rpg->addColumn("شماره چک", "ChequeNo");
$col = $page_rpg->addColumn("تاریخ چک", "ChequeDate");
$col->type = "date";
$col = $page_rpg->addColumn("تاریخ وصول چک", "PayedDate");
$col->type = "date";
$page_rpg->addColumn("مبلغ چک", "ChequeAmount");
$page_rpg->addColumn("بانک", "BankDesc");
$page_rpg->addColumn("شعبه", "ChequeBranch");
$page_rpg->addColumn("شرح", "description");
$page_rpg->addColumn("وضعیت چک", "ChequeStatusDesc");
	
function GetData(){
	
	$param = array();
	$userFields = ReportGenerator::UserDefinedFields();
	$query = "select i.*,
			case when i.CostID is null then group_concat(concat_ws(' ',p0.fname,p0.lname,p0.CompanyName) SEPARATOR '<br>')
				else t1.TafsiliDesc end fullname,
			case when i.CostID is null then group_concat(ifnull(p0.mobile,'') SEPARATOR '<br>')
				else p2.mobile end mobile,
			case when i.CostID is null then group_concat(concat_ws(' ',p1.fname,p1.lname,p1.CompanyName,'-',sa.SubDesc) SEPARATOR '<br>')
				else '' end ReqFullname,
			case when i.CostID is null then group_concat(concat_ws('-', bb1.blockDesc, bb2.blockDesc) SEPARATOR '<br>') 
				else concat_ws('-', b1.blockDesc, b2.blockDesc, b3.blockDesc, b4.blockDesc) end CostDesc,
			b.BankDesc, 
			r.RequestID,
			br.BranchName,
			t3.TafsiliDesc ChequeStatusDesc	,LoanPersonID	".
			/*t3.InfoDesc ChequeStatusDesc	,LoanPersonID	".*/
				($userFields != "" ? "," . $userFields : "")."	
			
		from ACC_IncomeCheques i
			left join ACC_tafsilis t1 using(TafsiliID)
			left join BSC_persons p2 on(t1.TafsiliType=" . TAFSILITYPE_PERSON ." AND t1.ObjectID=p2.PersonID)
			left join ACC_CostCodes cc using(CostID)
			left join ACC_blocks b1 on(cc.level1=b1.BlockID)
			left join ACC_blocks b2 on(cc.level2=b2.BlockID)
			left join ACC_blocks b3 on(cc.level3=b3.BlockID)
			left join ACC_blocks b4 on(cc.level4=b4.BlockID)
			
			left join LON_BackPays bp using(IncomeChequeID)
			left join LON_requests r using(RequestID)
			left join BSC_branches br on(i.BranchID=br.BranchID)
			left join LON_loans l using(LoanID)
			left join ACC_CostCodes cc2 on(cc2.level1=" . BLOCKID_LOAN . " AND cc2.level2=l.blockID)
			left join ACC_blocks bb1 on(cc2.level1=bb1.BlockID)
			left join ACC_blocks bb2 on(cc2.level2=bb2.BlockID)
			left join BSC_persons p0 on(LoanPersonID=p0.PersonID)
			left join BSC_persons p1 on(ReqPersonID=p1.PersonID)
			left join BSC_SubAgents sa on(sa.SubID=r.SubAgentID)
				
			left join ACC_banks b on(ChequeBank=BankID)
			left join ACC_tafsilis t3 on(t3.TafsiliType=".TAFTYPE_ChequeStatus." AND t3.TafsiliID=ChequeStatus)
			/*left join BaseInfo t3 on(t3.TypeID=4 AND t3.InfoID=ChequeStatus)*/
		where 1=1 ";
	
	//.........................................................
	if(session::IsPortal() && isset($_REQUEST["dashboard_show"]))
	{
		if($_REQUEST["DashboardType"] == "shareholder" || $_REQUEST["DashboardType"] == "agent")
			$where .= " AND r.ReqPersonID=" . $_SESSION["USER"]["PersonID"];
		if($_REQUEST["DashboardType"] == "customer")
			$where .= " AND r.LoanPersonID=" . $_SESSION["USER"]["PersonID"];
	}
	
	/*if($_POST["ChequeStatus"] != INCOMECHEQUE_CHANGE)
	{
		$query .= " AND ChequeStatus <> " . INCOMECHEQUE_CHANGE;
	}*/
	if(!empty($_POST["FromNo"]))
	{
		$query .= " AND ChequeNo >= :cfn";
		$param[":cfn"] = $_POST["FromNo"];
	}
	if(!empty($_POST["ToNo"]))
	{
		$query .= " AND ChequeNo <= :ctn";
		$param[":ctn"] = $_POST["ToNo"];
	}
	if(!empty($_POST["FromDate"]))
	{
		$query .= " AND ChequeDate >= :fd";
		$param[":fd"] = DateModules::shamsi_to_miladi($_POST["FromDate"], "-");
	}
	if(!empty($_POST["ToDate"]))
	{
		$query .= " AND ChequeDate <= :td";
		$param[":td"] = DateModules::shamsi_to_miladi($_POST["ToDate"], "-");
	}
	if(!empty($_POST["FromPayedDate"]))
	{
		$query .= " AND PayedDate >= :fpd";
		$param[":fpd"] = DateModules::shamsi_to_miladi($_POST["FromPayedDate"], "-");
	}
	if(!empty($_POST["ToPayedDate"]))
	{
		$query .= " AND PayedDate <= :tpd";
		$param[":tpd"] = DateModules::shamsi_to_miladi($_POST["ToPayedDate"], "-");
	}
	if(!empty($_POST["FromAmount"]))
	{
		$query .= " AND ChequeAmount >= :fa";
		$param[":fa"] = preg_replace('/,/', "", $_POST["FromAmount"]);
	}
	if(!empty($_POST["ToAmount"]))
	{
		$query .= " AND ChequeAmount <= :ta";
		$param[":ta"] = preg_replace('/,/', "", $_POST["ToAmount"]);
	}
	if(!empty($_POST["ChequeBank"]))
	{
		$query .= " AND ChequeBank = :cb";
		$param[":cb"] = $_POST["ChequeBank"];
	}
	if(!empty($_POST["ChequeBranch"]))
	{
		$query .= " AND ChequeBranch like :cb";
		$param[":cb"] = "%" . $_POST["ChequeBranch"] . "%";
	}
	if(!empty($_POST["ChequeStatus"]))
	{
		$query .= " AND ChequeStatus = :cst";
		$param[":cst"] = $_POST["ChequeStatus"];
	}
	if(!empty($_POST["BranchID"]))
	{
		$query .= " AND r.BranchID = :brnch";
		$param[":brnch"] = $_POST["BranchID"];
	}
	if(isset($_POST["ReqPersonID"]))
	{
		$query .= " AND r.ReqPersonID = :rp";
		$param[":rp"] = $_POST["ReqPersonID"];
	}
	
	//.........................................................
	$group = ReportGenerator::GetSelectedColumnsStr();
	$query .= $group == "" ? " group by i.IncomeChequeID" : " group by " . $group;
	$query .= $group == "" ? " order by i.IncomeChequeID" : " order by " . $group;	
	
	return  PdoDataAccess::runquery_fetchMode($query, $param);
}

function ListData($IsDashboard = false){
	
	$rpg = new ReportGenerator();
	$rpg->excel = !empty($_POST["excel"]);
    if($rpg->excel){
		$rpg->addColumn("PID", "LoanPersonID");
	}
	
	$rpg->addColumn("صاحب چک", "fullname");
	$rpg->addColumn("موبایل", "mobile");
	$rpg->addColumn("حساب", "CostDesc");
	$rpg->addColumn("شماره وام", "RequestID");
	$rpg->addColumn("شعبه وام", "BranchName");
	$rpg->addColumn("معرف", "ReqFullname");	
	$rpg->addColumn("شماره چک", "ChequeNo");
	$rpg->addColumn("تاریخ چک", "ChequeDate","ReportDateRender");
	$rpg->addColumn("تاریخ وصول چک", "PayedDate", "ReportDateRender");
	
	$col = $rpg->addColumn("مبلغ چک", "ChequeAmount", "ReportMoneyRender");
	$col->EnableSummary();
	
	$rpg->addColumn("بانک", "BankDesc");
	$rpg->addColumn("شعبه", "ChequeBranch");
	$rpg->addColumn("شرح", "description");
	$rpg->addColumn("وضعیت چک", "ChequeStatusDesc");
	
	$rpg->mysql_resource = GetData();
	
	if($_SESSION["USER"]["UserName"] == "admin")
	{
		BeginReport();
		print_r(ExceptionHandler::PopAllExceptions());
		echo PdoDataAccess::GetLatestQueryString ();
	}
	
	if(!$rpg->excel && !$IsDashboard)
	{
		BeginReport();
	
		echo "<div style=display:none>" . PdoDataAccess::GetLatestQueryString() . "</div>";
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='font-family:titr;font-size:15px'>
					گزارش چک های دریافتی
					";
		if(!empty($_POST["l_fromDate"]))
		{
			echo "<br>سررسید چک ها از تاریخ : " . $_POST["l_fromDate"] . ($_POST["l_toDate"] != "" ? " - " . $_POST["l_toDate"] : "");
		}
		if(!empty($_POST["checkStatus"]))
		{
			echo "<br>وضعیت : " . $_POST["statusName"];
		}
		echo	"</td>
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
AccReport_IncomeCheque.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

AccReport_IncomeCheque.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "IncomeCheques.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function AccReport_IncomeCheque()
{
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش چک های دریافتی",
		defaults : {
			labelWidth :150
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
			displayField : "BranchName",
			valueField : "BranchID",
			hiddenName : "BranchID"
		},{
			xtype : "numberfield",
			name : "FromNo",
			hideTrigger : true,
			fieldLabel : "از شماره چک"
		},{
			xtype : "numberfield",
			name : "ToNo",
			hideTrigger : true,
			fieldLabel : "تا شماره چک"
		},{
			xtype : "shdatefield",
			name : "FromDate",
			fieldLabel : "از تاریخ چک"
		},{
			xtype : "shdatefield",
			name : "ToDate",
			fieldLabel : "تا تاریخ چک"
		},{
			xtype : "shdatefield",
			name : "FromPayedDate",
			fieldLabel : "از تاریخ وصول"
		},{
			xtype : "shdatefield",
			name : "ToPayedDate",
			fieldLabel : "تا تاریخ وصول"
		},{
			xtype : "currencyfield",
			name : "FromAmount",
			hideTrigger : true,
			fieldLabel : "از مبلغ"
		},{
			xtype : "currencyfield",
			name : "ToAmount",
			hideTrigger : true,
			fieldLabel : "تا مبلغ"
		},{
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
			fieldLabel : "بانک",
			displayField : "BankDesc",
			queryMode : "local",
			valueField : "BankID",
			hiddenName :"ChequeBank"
		},{
			xtype : "textfield",
			name : "ChequeBranch",
			fieldLabel : "شعبه"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../cheque/cheques.data.php?task=SelectIncomeChequeStatuses',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['TafsiliID','TafsiliDesc'],
				autoLoad : true
			}),
			fieldLabel : "وضعیت چک",
			displayField : "TafsiliDesc",
			queryMode : "local",
			valueField : "TafsiliID",
			hiddenName :"ChequeStatus"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../../framework/person/persons.data.php?' +
						"task=selectPersons&UserTypes=IsAgent,IsSupporter&EmptyRow=true",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['PersonID','fullname']
			}),
			fieldLabel : "منبع",
			pageSize : 25,
			width : 370,
			displayField : "fullname",
			valueField : "PersonID",
			hiddenName : "ReqPersonID"
		},{
			xtype : "fieldset",
			title : "ستونهای گزارش",
			colspan :2,
			items :[<?= $page_rpg->ReportColumns() ?>]
		},{
			xtype : "fieldset",
			colspan :2,
			title : "رسم نمودار",
			items : [<?= $page_rpg->GetChartItems("AccReport_IncomeChequeObj","mainForm","IncomeCheques.php") ?>]
		}],
		buttons : [{
			text : "گزارش ساز",
			iconCls : "db",
			handler : function(){ReportGenerator.ShowReportDB(
						AccReport_IncomeChequeObj, 
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
					AccReport_IncomeChequeObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				AccReport_IncomeChequeObj.formPanel.getForm().reset();
				AccReport_IncomeChequeObj.get("mainForm").reset();
			}			
		}]
	});
}

AccReport_IncomeChequeObj = new AccReport_IncomeCheque();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>