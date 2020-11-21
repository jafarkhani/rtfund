<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 98.02
//-----------------------------
ini_set("display_errors", "On");
require_once '../../header.inc.php';
require_once "../request/request.class.php";
require_once "../request/request.data.php";
require_once "ReportGenerator.class.php";

define("PenaltyDays", 3);

function ReqPersonRender($row,$value){
	return $value == "" ? "منابع داخلی" : $value;
}
	
$page_rpg = new ReportGenerator("mainForm","LoanReport_GoodCustomersObj");

$page_rpg->addColumn("شماره وام", "RequestID");
$page_rpg->addColumn("شعبه وام", "BranchName");
$page_rpg->addColumn("نوع وام", "LoanDesc");
$page_rpg->addColumn("معرف", "ReqPersonName");
$page_rpg->addColumn("وام گیرنده", "LoanPersonName");
$page_rpg->addColumn("موبایل", "mobile");
$page_rpg->addColumn("وضعیت", "StatusDesc");
$col = $page_rpg->addColumn("تاریخ خاتمه", "EndDate");
$col->type = "date";
$page_rpg->addColumn("درصد دیرکرد", "ForfeitPercent");

$page_rpg->addColumn("مبلغ وام", "PartAmount");
$page_rpg->addColumn("جمع وام و کارمزد", "TotalInstallmentAmount");
$col = $page_rpg->addColumn("جمع کل پرداختی تاکنون", "TotalPayAmount", "ReportMoneyRender");
$col = $page_rpg->addColumn("مانده کل تا انتها", "TotalRemainder","ReportMoneyRender");	 $col->IsQueryField = false;

function MakeWhere(&$where, &$whereParam){

	if(session::IsPortal() && isset($_REQUEST["dashboard_show"]))
	{
		if($_REQUEST["DashboardType"] == "shareholder" || $_REQUEST["DashboardType"] == "agent")
			$where .= " AND ReqPersonID=" . $_SESSION["USER"]["PersonID"];
		if($_REQUEST["DashboardType"] == "customer")
			$where .= " AND LoanPersonID=" . $_SESSION["USER"]["PersonID"];
	}
	
	foreach($_POST as $key => $value)
	{
		if($key == "excel" || $key == "OrderBy" || $key == "OrderByDirection" || 
				$value === "" || strpos($key, "combobox") !== false || strpos($key, "rpcmp") !== false ||
				strpos($key, "reportcolumn_fld") !== false || strpos($key, "reportcolumn_ord") !== false ||
				strpos($key, "checkcombo") !== false )
			continue;

		if($key == "ForfeitDays" || $key == "ComputeDate" || $key == "RemainPercent" || $key == "FollowLevelID")
			continue;
		
		if($key == "FollowStatusID")
		{
			$where .= " AND FollowStatusID in(" . $value . ")";
			continue;
		}
		
		$prefix = "";
		switch($key)
		{
			case "fromRequestID":
			case "toRequestID":
				$prefix = "r.";
				break;
			case "fromInstallmentDate":
			case "toInstallmentDate":
				$value = DateModules::shamsi_to_miladi($value, "-");
				break;
			case "fromInstallmentAmount":
			case "toInstallmentAmount":
				$value = preg_replace('/,/', "", $value);
				break;
		}
		if(strpos($key, "from") === 0)
			$where .= " AND " . $prefix . substr($key,4) . " >= :$key";
		else if(strpos($key, "to") === 0)
			$where .= " AND " . $prefix . substr($key,2) . " <= :$key";
		else
			$where .= " AND " . $prefix . $key . " = :$key";
		$whereParam[":$key"] = $value;
	}
}	

function GetData(){
	
	ini_set("memory_limit", "1000M");
	ini_set("max_execution_time", "600");
	
	$where = "";
	$whereParam = array();
	$userFields = ReportGenerator::UserDefinedFields();
	MakeWhere($where, $whereParam);
			
	$query = "select p.*,
				r.EndDate,
				l.LoanDesc,
				bi.InfoDesc StatusDesc,
				r.RequestID,LoanPersonID,p1.mobile,
				concat_ws(' ',p1.fname,p1.lname,p1.CompanyName) LoanPersonName,
				concat_ws(' ',p2.fname,p2.lname,p2.CompanyName) ReqPersonName,
				BranchName,
				t4.TotalInstallmentAmount,
				t3.TotalPayAmount,
				t3.MaxPayDate" .
				($userFields != "" ? "," . $userFields : "")."
				
			from LON_requests r 
			left join BaseInfo bi on(bi.TypeID=5 AND bi.InfoID=StatusID)
			join LON_loans l using(LoanID)
			join BSC_persons p1 on(LoanPersonID=p1.PersonID)
			left join BSC_persons p2 on(ReqPersonID=p2.PersonID)
			join LON_ReqParts p on(p.RequestID=r.RequestID AND p.IsHistory='NO')
			join BSC_branches using(BranchID)
			
			left join (
				select RequestID,sum(PayAmount) TotalPayAmount , max(PayDate) MaxPayDate
				from LON_BackPays
				left join ACC_IncomeCheques i using(IncomeChequeID)
				where if(PayType=" . BACKPAY_PAYTYPE_CHEQUE . ",ChequeStatus=".INCOMECHEQUE_VOSUL.",1=1)
				group by RequestID			
			)t3 on(r.RequestID=t3.RequestID)
			
			left join (
				select RequestID,sum(InstallmentAmount) TotalInstallmentAmount 
				from LON_installments
				where  history='NO' AND IsDelayed='NO'
				group by RequestID			
			)t4 on(r.RequestID=t4.RequestID)
			
			where 1=1  " . $where . "
		
			group by r.RequestID
			order by r.RequestID,p.PartID";
	
	$dt = PdoDataAccess::runquery_fetchMode($query, $whereParam);
	if($_SESSION["USER"]["UserName"] == "admin")
	{
		//print_r(ExceptionHandler::PopAllExceptions()); 
		//echo PdoDataAccess::GetLatestQueryString();
	}
	$ComputeDate = !empty($_POST["ComputeDate"]) ? 
			DateModules::shamsi_to_miladi($_POST["ComputeDate"],"-") : DateModules::now();
	
	$result = array();
	while($row = $dt->fetch())
	{
		if($row["TotalPayAmount"]*1 < $row["TotalInstallmentAmount"]*1/2){
			continue;
		}
		
		$computeArr = LON_Computes::ComputePayments($row["RequestID"], $ComputeDate);
		$totalRemain = LON_Computes::GetTotalRemainAmount($row["RequestID"],$computeArr);
		
		$row["TotalRemainder"] = $totalRemain;
		
		$overflow = false;		
		foreach($computeArr as $crow){
			
			if($crow["type"] != "installment" || $crow["id"]*1 == 0)
				continue;
			
			foreach($crow["pays"] as $prow){
				if($prow["PnltDays"]*1 > PenaltyDays){
					$overflow = true;
					break;
				}					
			}
			
			if($overflow)
				break;			
		}
		
		if(!$overflow)
			$result[] = $row;
	}
	
	return $result;
}	
	
function ListData($IsDashboard = false){
	
	$rpt = new ReportGenerator();
	$rpt->excel = !empty($_POST["excel"]);
	$rpt->mysql_resource = GetData();
	
	function LoanReportRender($row,$value){
		return "<a href=DebitReport.php?show=tru&RequestID=" . $value . " target=blank >" . $value . "</a>";
	}

	$col = $rpt->addColumn("شماره وام", "RequestID", "LoanReportRender");
	$col->ExcelRender = false;
	$rpt->addColumn("شعبه وام", "BranchName");
	$rpt->addColumn("نوع وام", "LoanDesc");
	$rpt->addColumn("معرف", "ReqPersonName");
	$rpt->addColumn("وام گیرنده", "LoanPersonName");
	$rpt->addColumn("موبایل", "mobile");
	$rpt->addColumn("وضعیت", "StatusDesc");
	$rpt->addColumn("تاریخ خاتمه", "EndDate", "ReportDateRender");
	$rpt->addColumn("درصد دیرکرد", "ForfeitPercent");
	
	$rpt->addColumn("مبلغ وام", "PartAmount", "ReportMoneyRender");
	$col = $rpt->addColumn("جمع وام و کارمزد", "TotalInstallmentAmount", "ReportMoneyRender");
	$col = $rpt->addColumn("جمع کل پرداختی تاکنون", "TotalPayAmount", "ReportMoneyRender");
	
	function TotalRemainderRender($row,$value){
		return "<a href=DebitReport.php?show=tru&RequestID=" . $row["RequestID"] . 
				" target=blank >" . number_format($value) . "</a>";
	}
	$col = $rpt->addColumn("مانده کل تا انتها", "TotalRemainder","TotalRemainderRender");	
	$col->EnableSummary();
	$col->ExcelRender = false;
	
	if(!$rpt->excel && !$IsDashboard)
	{
		BeginReport();
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family: titr;font-size:15px'>
					گزارش مشتریان خوش حساب
				</td>
				<td width='200px' align='center' style='font-family:tahoma;font-size:11px'>تاریخ تهیه گزارش : " 
			. DateModules::shNow() . "<br>";
		if(!empty($_POST["fromReqDate"]))
		{
			echo "<br>گزارش از تاریخ : " . $_POST["fromReqDate"] . 
				($_POST["toReqDate"] != "" ? " - " . $_POST["toReqDate"] : "");
		}
		echo "</td></tr></table>";
	}
	if($IsDashboard)
	{
		echo "<div style=direction:rtl;padding-right:10px>";
		$rpt->generateReport();
		echo "</div>";
	}
	else
		$rpt->generateReport();
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
		ListDate(true);	
	
	$page_rpg->mysql_resource = GetData();
	$page_rpg->GenerateChart(false, $_REQUEST["rpcmp_ReportID"]);
	die();	
}
?>
<script>
LoanReport_GoodCustomers.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

LoanReport_GoodCustomers.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "GoodCustomers.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function LoanReport_GoodCustomers()
{		
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :2
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش مشتریان خوش حساب",
		width : 780,
		items :[{
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
			fieldLabel : "منبع ",
			pageSize : 25,
			width : 370,
			displayField : "fullname",
			valueField : "PersonID",
			hiddenName : "ReqPersonID",
			listeners :{
				select : function(record){
					el = LoanReport_GoodCustomersObj.formPanel.down("[itemId=cmp_subAgent]");
					el.getStore().proxy.extraParams["PersonID"] = this.getValue();
					el.getStore().load();
				}
			}
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../../framework/person/persons.data.php?' +
						"task=selectSubAgents",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['SubID','SubDesc']
			}),
			fieldLabel : "زیر واحد سرمایه گذار",
			queryMode : "local",
			width : 370,
			displayField : "SubDesc",
			valueField : "SubID",
			hiddenName : "SubAgentID",
			itemId : "cmp_subAgent"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../../framework/person/persons.data.php?' +
						"task=selectPersons&UserType=IsCustomer",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['PersonID','fullname']
			}),
			fieldLabel : "مشتری",
			displayField : "fullname",
			pageSize : 20,
			width : 370,
			valueField : "PersonID",
			hiddenName : "LoanPersonID"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../loan/loan.data.php?task=GetAllLoans',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['LoanID','LoanDesc'],
				autoLoad : true					
			}),
			fieldLabel : "نوع وام",
			queryMode : 'local',
			width : 370,
			displayField : "LoanDesc",
			valueField : "LoanID",
			hiddenName : "LoanID"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../../framework/baseInfo/baseInfo.data.php?' +
						"task=SelectBranches",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['BranchID','BranchName'],
				autoLoad : true					
			}),
			fieldLabel : "شعبه اخذ وام",
			queryMode : 'local',
			width : 370,
			displayField : "BranchName",
			valueField : "BranchID",
			hiddenName : "BranchID"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../request/request.data.php?' +
						"task=GetAllStatuses",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['InfoID','InfoDesc'],
				autoLoad : true					
			}),
			fieldLabel : "وضعیت وام",
			queryMode : 'local',
			width : 370,
			displayField : "InfoDesc",
			valueField : "InfoID",
			value : "70",
			hiddenName : "StatusID"
		},{
			xtype : "numberfield",
			name : "fromRequestID",
			hideTrigger : true,
			fieldLabel : "شماره وام از"
		},{
			xtype : "numberfield",
			name : "toRequestID",
			hideTrigger : true,
			fieldLabel : "تا شماره"
		},{
			xtype : "shdatefield",
			name : "ComputeDate",
			labelWidth : 120,
			fieldLabel : "محاسبه تا تاریخ"
		},{
			xtype : "container",
			html : "وضعیت خاتمه&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"+
				"<input name=IsEnded type=radio value='YES' > خاتمه یافته &nbsp;&nbsp;" +
				"<input name=IsEnded type=radio value='NO' checked> جاری &nbsp;&nbsp;" +
				"<input name=IsEnded type=radio value=''  > هردو " 
		},{
			xtype : "fieldset",
			title : "ستونهای گزارش",
			colspan :2,
			items :[<?= $page_rpg->ReportColumns() ?>]
		},{
			xtype : "fieldset",
			colspan :2,
			title : "رسم نمودار",
			items : [<?= $page_rpg->GetChartItems("LoanReport_GoodCustomersObj","mainForm","installments.php") ?>]
		}],
		buttons : [{
			text : "گزارش ساز",
			iconCls : "db",
			handler : function(){ReportGenerator.ShowReportDB(
						LoanReport_GoodCustomersObj, 
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
					LoanReport_GoodCustomersObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				LoanReport_GoodCustomersObj.formPanel.getForm().reset();
				LoanReport_GoodCustomersObj.get("mainForm").reset();
			}			
		}]
	});
	
	this.formPanel.getEl().addKeyListener(Ext.EventObject.ENTER, function(keynumber,e){
		
		LoanReport_GoodCustomersObj.showReport();
		e.preventDefault();
		e.stopEvent();
		return false;
	});
}

LoanReport_GoodCustomersObj = new LoanReport_GoodCustomers();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>
 