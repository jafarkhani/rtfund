<?php

require_once '../header.inc.php';
require_once "../request/request.class.php";
require_once "../request/request.data.php";
require_once "ReportGenerator.class.php";

function ReqPersonRender($row,$value){
	return $value == "" ? "منابع داخلی" : $value;
}

$page_rpg = new ReportGenerator("mainForm","LoanReport_BackaysObj");
$page_rpg->addColumn("شماره وام", "RequestID");
$page_rpg->addColumn("نوع وام", "LoanDesc");
$page_rpg->addColumn("معرفی کننده", "ReqFullname", "ReqPersonRender");
$col = $page_rpg->addColumn("تاریخ درخواست", "ReqDate");
$col->type = "date";
$page_rpg->addColumn("مبلغ درخواست", "ReqAmount");
$page_rpg->addColumn("مشتری", "LoanFullname");
$page_rpg->addColumn("شعبه", "BranchName");
$col = $page_rpg->addColumn("تاریخ پرداخت", "realPayDate");
$col->type = "date";
$page_rpg->addColumn("مبلغ پرداخت", "PayAmount");
$page_rpg->addColumn("نوع پرداخت", "PayTypeDesc");
$page_rpg->addColumn("شماره فیش", "PayBillNo");
$page_rpg->addColumn("کد پیگیری", "PayRefNo");
$page_rpg->addColumn("شماره چک", "ChequeNo");
$page_rpg->addColumn("شماره سند", "LocalNo");

function MakeWhere(&$where, &$whereParam){

	if(isset($_SESSION["USER"]["portal"]) && isset($_REQUEST["dashboard_show"]))
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
		
		if($key == "fromPayDate")
		{
			$value = DateModules::shamsi_to_miladi($value, "-");
			$where .= " AND if(PayType=" . BACKPAY_PAYTYPE_CHEQUE . ",i.PayedDate,b.PayDate) >= :$key";
			$whereParam[":$key"] = $value;
			continue;
		}
		if($key == "toPayDate")
		{
			$value = DateModules::shamsi_to_miladi($value, "-");
			$where .= " AND if(PayType=" . BACKPAY_PAYTYPE_CHEQUE . ",i.PayedDate,b.PayDate) <= :$key";
			$whereParam[":$key"] = $value;
			continue;
		}

		$prefix = "";
		switch($key)
		{
			case "fromRequestID":
			case "toRequestID":
				$prefix = "b.";
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
	
	$query = "select b.*,r.*,l.*,p.*,
				concat_ws(' ',p1.fname,p1.lname,p1.CompanyName) ReqFullname,
				concat_ws(' ',p2.fname,p2.lname,p2.CompanyName) LoanFullname,
				BranchName,
				i.ChequeNo,
				d.LocalNo,
				if(PayType=" . BACKPAY_PAYTYPE_CHEQUE . ",i.PayedDate,b.PayDate) as realPayDate,
				bi.InfoDesc PayTypeDesc".
				($userFields != "" ? "," . $userFields : "")."
				
			from LON_BackPays b
			join LON_requests r using(RequestID)
			join LON_ReqParts p on(r.RequestID=p.RequestID AND p.IsHistory='NO')
			left join LON_loans l using(LoanID)
			left join BaseInfo bi on(bi.TypeID=6 AND bi.InfoID=b.PayType)
			left join ACC_IncomeCheques i using(IncomeChequeID)
			join BSC_branches using(BranchID)
			left join BSC_persons p1 on(p1.PersonID=r.ReqPersonID)
			left join BSC_persons p2 on(p2.PersonID=r.LoanPersonID)
			
			left join ( select d.LocalNo,SourceID,SourceID2
				from ACC_DocItems di join ACC_docs d on(di.DocID=d.DocID)
				where SourceType in(".DOCTYPE_INSTALLMENT_PAYMENT.")
				group by SourceID,SourceID2
			)d on(d.SourceID=r.RequestID AND d.SourceID2=BackPayID )

			where if(PayType=" . BACKPAY_PAYTYPE_CHEQUE . ",ChequeStatus=".INCOMECHEQUE_VOSUL.",1=1)" . $where ;
	
	$group = ReportGenerator::GetSelectedColumnsStr();
	$query .= $group == "" ? " group by b.BackPayID" : " group by " . $group;
	$query .= $group == "" ? " order by PayDate" : " order by " . $group;
	
	$dataTable = PdoDataAccess::runquery_fetchMode($query, $whereParam);
	$query = PdoDataAccess::GetLatestQueryString();
	//print_r(ExceptionHandler::PopAllExceptions());
	
	return $dataTable;
}
	
function ListDate($IsDashboard = false){
	
	$rpg = new ReportGenerator();
	$rpg->excel = !empty($_POST["excel"]);
	$rpg->mysql_resource = GetData();
	
	//if($_SESSION["USER"]["UserName"] == "admin")
	//	echo PdoDataAccess::GetLatestQueryString ();
		
	function endedRender($row,$value){
		return ($value == "YES") ? "خاتمه" : "جاری";
	}
	
	$rpg->addColumn("شماره وام", "RequestID");
	$rpg->addColumn("نوع وام", "LoanDesc");
	$rpg->addColumn("معرفی کننده", "ReqFullname");
	$rpg->addColumn("تاریخ درخواست", "ReqDate", "ReportDateRender");
	$col = $rpg->addColumn("مبلغ درخواست", "ReqAmount", "ReportMoneyRender");
	$col->EnableSummary();
	$rpg->addColumn("مشتری", "LoanFullname");
	$rpg->addColumn("شعبه", "BranchName");
	$rpg->addColumn("تاریخ پرداخت", "realPayDate", "ReportDateRender");
	$col = $rpg->addColumn("مبلغ پرداخت", "PayAmount", "ReportMoneyRender");
	$col->EnableSummary();
	$rpg->addColumn("نوع پرداخت", "PayTypeDesc");
	$rpg->addColumn("شماره فیش", "PayBillNo");
	$rpg->addColumn("کد پیگیری", "PayRefNo");
	$rpg->addColumn("شماره چک", "ChequeNo");
	$rpg->addColumn("شماره سند", "LocalNo");
	
	if(!$rpg->excel && !$IsDashboard)
	{
		BeginReport();
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
					گزارش پرداخت های مشتریان
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

if(isset($_REQUEST["show"]))
{
	ListDate();
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
LoanReport_Backays.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

LoanReport_Backays.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "BackPays.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function LoanReport_Backays()
{		
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :2
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش پرداخت های مشتریان",
		width : 780,
		items :[{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../../framework/person/persons.data.php?' +
						"task=selectPersons&UserTypes=IsAgent,IsSupporter",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['PersonID','fullname']
			}),
			fieldLabel : "معرفی کننده",
			pageSize : 25,
			width : 370,
			displayField : "fullname",
			valueField : "PersonID",
			hiddenName : "ReqPersonID",
			listeners :{
				select : function(record){
					el = LoanReport_BackaysObj.formPanel.down("[itemId=cmp_subAgent]");
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
			colspan : 2,
			displayField : "BranchName",
			valueField : "BranchID",
			hiddenName : "BranchID"
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
			fieldLabel : "مبلغ پرداخت از"
		},{
			xtype : "currencyfield",
			name : "toPayAmount",
			hideTrigger : true,
			fieldLabel : "تا مبلغ"
		},{
			xtype : "fieldset",
			colspan :2,
			title : "ستونهای گزارش",
			items :[<?= $page_rpg->ReportColumns() ?>]
		},{
			xtype : "fieldset",
			colspan :2,
			title : "رسم نمودار",
			items : [<?= $page_rpg->GetChartItems("LoanReport_BackaysObj","mainForm","BackPays.php") ?>]
		}],
		buttons : [{
			text : "گزارش ساز",
			iconCls : "db",
			handler : function(){ReportGenerator.ShowReportDB(
						LoanReport_BackaysObj, 
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
					LoanReport_BackaysObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				LoanReport_BackaysObj.formPanel.getForm().reset();
				LoanReport_BackaysObj.get("mainForm").reset();
			}			
		}]
	});
	
	this.formPanel.getEl().addKeyListener(Ext.EventObject.ENTER, function(keynumber,e){
		
		LoanReport_BackaysObj.showReport();
		e.preventDefault();
		e.stopEvent();
		return false;
	});
}

LoanReport_BackaysObj = new LoanReport_Backays();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>