<?php

require_once '../../header.inc.php';
require_once "../request/request.class.php";
require_once "../request/request.data.php";
require_once "ReportGenerator.class.php";

function ReqPersonRender($row,$value){
	return $value == "" ? "منابع داخلی" : $value;
}
function IsDocRegisteredRender($row,$value){
	return $value == "YES" ? "*" : "";
}
		
$page_rpg = new ReportGenerator("mainForm","LoanReport_paymentsObj");
$page_rpg->addColumn("شماره وام", "RequestID");
$page_rpg->addColumn("نوع وام", "LoanDesc");
$page_rpg->addColumn("منبع", "ReqFullname", "ReqPersonRender"); 
$page_rpg->addColumn("زیرواحد سرمایه گذار", "SubDesc");
$col = $page_rpg->addColumn("تاریخ درخواست", "ReqDate");
$col->type = "date";
$page_rpg->addColumn("مبلغ درخواست", "ReqAmount");
$page_rpg->addColumn("مشتری", "LoanFullname");
$page_rpg->addColumn("شعبه", "BranchName");

$page_rpg->addColumn("تاریخ پرداخت مصوب", "PayDate");
$page_rpg->addColumn("تاریخ پرداخت به مشتری", "RealPayedDate");

$page_rpg->addColumn("مبلغ پرداخت", "PayAmount");

$page_rpg->addColumn("صدور سند", "IsDocRegistered", "IsDocRegisteredRender");
$page_rpg->addColumn("شماره سند", "LocalNo");

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
				strpos($key, "reportcolumn_fld") !== false || strpos($key, "reportcolumn_ord") !== false)
			continue;

		if($key == "ZeroRemain")
			continue;

		$prefix = "";
		switch($key)
		{
			case "fromRequestID":
			case "toRequestID":
				$prefix = "py.";
				break;
			case "StatusID":
				$prefix = "r.";
				break;
			case "fromPayDate":
			case "toPayDate":
				$value = DateModules::shamsi_to_miladi($value, "-");
				break;
			case "fromPayAmount":
			case "toPayAmount":
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
	
	$where = "";
	$whereParam = array();
	$userFields = ReportGenerator::UserDefinedFields();
	MakeWhere($where, $whereParam);
	
	$query = "select py.*,r.*,l.*,p.*,
				PayAmount - ifnull(OldFundDelayAmount,0) 
						- ifnull(OldAgentDelayAmount,0)
						- ifnull(OldFundWage,0)
						- ifnull(OldAgentWage,0)as PurePayAmount,
				concat_ws(' ',p1.fname,p1.lname,p1.CompanyName) ReqFullname,
				concat_ws(' ',p2.fname,p2.lname,p2.CompanyName) LoanFullname,
				if(pd.DocID is not null, 'YES', 'NO') IsDocRegistered,
				pd.LocalNo,
				sb.SubDesc,
				BranchName".
				($userFields != "" ? "," . $userFields : "")."
				
			from LON_payments py
			join LON_requests r using(RequestID)
			join LON_ReqParts p on(r.RequestID=p.RequestID AND p.IsHistory='NO')
			left join LON_loans l using(LoanID)
			left join BSC_SubAgents sb on(sb.SubID=SubAgentID)
			join BSC_branches using(BranchID)
			left join BSC_persons p1 on(p1.PersonID=r.ReqPersonID)
			left join BSC_persons p2 on(p2.PersonID=r.LoanPersonID)
			left join LON_PayDocs pd on(py.PayID=pd.PayID)

			where 1=1 " . $where;
	
	$group = ReportGenerator::GetSelectedColumnsStr();
	$query .= $group == "" ? " group by py.PayID" : " group by " . $group;
	$query .= $group == "" ? " order by py.PayDate" : " order by " . $group;		
	
	return PdoDataAccess::runquery($query, $whereParam);
	
}	
	
function ListData($IsDashboard = false){
	
	$rpg = new ReportGenerator();
	$rpg->excel = !empty($_POST["excel"]);
	$rpg->mysql_resource = GetData();
	
	if($_SESSION["USER"]["UserName"] == "admin")
	{
		if(ExceptionHandler::GetExceptionCount() > 0)
			print_r(ExceptionHandler::PopAllExceptions ());
	}
	
	function endedRender($row,$value){
		return ($value == "YES") ? "خاتمه" : "جاری";
	}
	
	function LoanReportRender($row,$value){
		return "<a href=LoanPayment.php?show=true&RequestID=" . $value . " target=blank >" . $value . "</a>";
	}

	$col = $rpg->addColumn("شماره وام", "RequestID", "LoanReportRender");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RequestID");
	
	$col = $rpg->addColumn("نوع وام", "LoanDesc");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RequestID");
	
	$col = $rpg->addColumn("منبع", "ReqFullname","ReqPersonRender");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RequestID");
	
	$col = $rpg->addColumn("زیرواحد سرمایه گذار", "SubDesc");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RequestID");
	
	$col = $rpg->addColumn("تاریخ درخواست", "ReqDate", "ReportDateRender");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RequestID");
	
	$col = $rpg->addColumn("مبلغ درخواست", "ReqAmount", "ReportMoneyRender");
	$col->EnableSummary();
	$col->rowspaning = true;
	$col->rowspanByFields = array("RequestID");
	
	$col = $rpg->addColumn("مشتری", "LoanFullname");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RequestID");
	
	$col = $rpg->addColumn("شعبه", "BranchName");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RequestID");
	
	$rpg->addColumn("تاریخ پرداخت مصوب", "PayDate", "ReportDateRender");
	$rpg->addColumn("تاریخ پرداخت به مشتری", "RealPayedDate", "ReportDateRender");
	$rpg->addColumn("مبلغ پرداخت مصوب", "PayAmount", "ReportMoneyRender");
	$rpg->addColumn("مبلغ پرداخت به مشتری", "PurePayAmount", "ReportMoneyRender");
		
	$col = $rpg->addColumn("صدور سند", "IsDocRegistered" , "IsDocRegisteredRender");
	$col->align = "center";
	$rpg->addColumn("شماره سند", "LocalNo");
	
	if(!$rpg->excel && !$IsDashboard)
	{
		BeginReport();
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family: titr;font-size:15px'>
					گزارش  پرداخت وام ها
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
		$rpg->generateReport();
		echo "</div>";
	}
	else
		$rpg->generateReport();
	die();
}

function p2(){
	
	//$SubAgent = 15;
	//$minDate = '2019-06-27';
	
	$SubAgent = 16;
	$minDate = '2019-03-11';
	
	$result = PdoDataAccess::runquery("
		select GDate,JDate,totalPay,totalWage,0 totalPureBackPay, 0 totalWageBackPay 
			, 0 totalLateBackPay , 0 totalPenaltyBackPay 
		from dates
			left join (select PayDate, sum(PayAmount) totalPay, sum(totalWage) totalWage from LON_payments p
						join LON_requests r using(requestID) 
						join (select sum(wage) totalWage,RequestID 
								from LON_installments group by RequestID)i on(p.RequestID=i.RequestID)
						where SubAgentID=$SubAgent group by PayDate)t  on(gdate=PayDate)
		where  Gdate>='$minDate' AND GDate<='2020-06-20'");
	
	$refArr = array();
	for($i=0; $i<count($result); $i++)
		$refArr[ $result[$i]["GDate"] ] = &$result[$i];
	
	
	$ComputArrs = array();
	$temp = PdoDataAccess::runquery("select substr(PayDate,1,10) PayDate,BackPayID ,RequestID
		from LON_BackPays join LON_requests using(RequestID)
		left join ACC_IncomeCheques i using(IncomeChequeID)
		where SubAgentID=$SubAgent 
			AND if(PayType=" . BACKPAY_PAYTYPE_CHEQUE . ",ChequeStatus=".INCOMECHEQUE_VOSUL.",1=1)");

	foreach($temp as $row)
	{
		if(!isset($ComputArrs[ $row["RequestID"] ]))
			$ComputArrs[ $row["RequestID"] ] = LON_Computes::ComputePayments ($row["RequestID"]);
		
		foreach($ComputArrs[ $row["RequestID"] ] as $record)
		{
			if($record["type"] == "pay" && $record["id"] == $row["BackPayID"])
			{
				$refArr[ $row["PayDate"] ]["totalPureBackPay"] += $record["pure"]*1;
				$refArr[ $row["PayDate"] ]["totalWageBackPay"] += $record["wage"]*1;
				$refArr[ $row["PayDate"] ]["totalLateBackPay"] += $record["late"]*1;
				$refArr[ $row["PayDate"] ]["totalPenaltyBackPay"] += $record["pnlt"]*1;
				
				break;
			}
		}
	}
	
	$rpg = new ReportGenerator();
	$rpg->excel = true;
	$rpg->mysql_resource = $result;
	
	if($_SESSION["USER"]["UserName"] == "admin")
	{
		if(ExceptionHandler::GetExceptionCount() > 0)
			print_r(ExceptionHandler::PopAllExceptions ());
	}
	
	$rpg->addColumn("تاریخ", "JDate");
	$rpg->addColumn("پرداخت به مشتری", "totalPay","ReportMoneyRender");
	$rpg->addColumn("مطالبات کارمزد", "totalWage","ReportMoneyRender");
	$rpg->addColumn("پرداخت اصل توسط مشتری", "totalPureBackPay","ReportMoneyRender");
	$rpg->addColumn("پرداخت کارمزد توسط مشتری", "totalWageBackPay","ReportMoneyRender");
	$rpg->addColumn("پرداخت کارمزد تاخیر توسط مشتری", "totalLateBackPay","ReportMoneyRender");
	$rpg->addColumn("پرداخت جریمه توسط مشتری", "totalPenaltyBackPay","ReportMoneyRender");
	
	if(!$rpg->excel)
		BeginReport();
	$rpg->generateReport();
	die();
}


if(isset($_REQUEST["show"]))
{	
	ListData();
}
if(isset($_REQUEST["p2"]))
{	
	p2();
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
LoanReport_payments.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

LoanReport_payments.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "payments.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

LoanReport_payments.prototype.showReport2 = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "payments.php?p2=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function LoanReport_payments()
{		
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :2
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش پرداخت وام ها",
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
			fieldLabel : "منبع",
			pageSize : 25,
			width : 370,
			displayField : "fullname",
			valueField : "PersonID",
			hiddenName : "ReqPersonID",
			listeners :{
				select : function(record){
					el = LoanReport_paymentsObj.formPanel.down("[itemId=cmp_subAgent]");
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
			name : "fromPayDate",
			fieldLabel : "تاریخ پرداخت از"
		},{
			xtype : "shdatefield",
			name : "toPayDate",
			fieldLabel : "تا تاریخ"
		},{
			xtype : "currencyfield",
			name : "fromPayAmount",
			hideTrigger : true,
			fieldLabel : "از مبلغ پرداخت"
		},{
			xtype : "currencyfield",
			name : "toPayAmount",
			hideTrigger : true,
			fieldLabel : "تا مبلغ پرداخت"
		},{
			xtype : "fieldset",
			title : "ستونهای گزارش",
			colspan :2,
			items :[<?= $page_rpg->ReportColumns() ?>]
		},{
			xtype : "fieldset",
			colspan :2,
			title : "رسم نمودار",
			items : [<?= $page_rpg->GetChartItems("LoanReport_paymentsObj","mainForm","installments.php") ?>]
		}],
		buttons : [{
			text : "گزارش ساز",
			iconCls : "db",
			handler : function(){ReportGenerator.ShowReportDB(
						LoanReport_paymentsObj, 
						<?= $_REQUEST["MenuID"] ?>,
						"mainForm",
						"formPanel"
						);}
		},'->',{
			text : "مشاهده گزارش",
			handler : Ext.bind(this.showReport,this),
			iconCls : "report"
		},{
			text : "گزارش2",
			handler : Ext.bind(this.showReport2,this),
			iconCls : "report"
		},{
			text : "خروجی excel",
			handler : Ext.bind(this.showReport,this),
			listeners : {
				click : function(){
					LoanReport_paymentsObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				LoanReport_paymentsObj.formPanel.getForm().reset();
				LoanReport_paymentsObj.get("mainForm").reset();
			}			
		}]
	});
	
	this.formPanel.getEl().addKeyListener(Ext.EventObject.ENTER, function(keynumber,e){
		
		LoanReport_paymentsObj.showReport();
		e.preventDefault();
		e.stopEvent();
		return false;
	});
}

LoanReport_paymentsObj = new LoanReport_payments();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>
