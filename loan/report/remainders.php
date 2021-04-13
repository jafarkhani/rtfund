<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	97.07
//-------------------------

require_once '../../header.inc.php';
require_once inc_reportGenerator;
require_once '../request/request.class.php';
require_once '../request/request.data.php';

if(isset($_REQUEST["show"]))
{
	showReport();
}

function MakeWhere(&$where, &$whereParam){

	foreach($_POST as $key => $value)
	{
		if($key == "excel" || $key == "OrderBy" || $key == "OrderByDirection" || 
				$value === "" || 
				
				strpos($key, "combobox") !== false || 
				strpos($key, "rpcmp") !== false ||
				strpos($key, "checkcombo") !== false || 
				strpos($key, "treecombo") !== false || 
				strpos($key, "reportcolumn_fld") !== false || 
				strpos($key, "reportcolumn_ord") !== false ||
				
				$key == "ComputeDate")
			continue;
		
		if($key == "SubAgentID")
		{
			InputValidation::validate($value, InputValidation::Pattern_NumComma);
			$where .= " AND SubAgentID in(" . $value . ")";
			continue;
		}
                if($key == "StatusID")
		{
			$where .= " AND StatusID in(" . $value . ")";
			continue;
		}
                if($key == "fromEndReqDate" || $key == "toEndReqDate")
			continue;
	
		$prefix = "";
		switch($key)
		{
			case "CustomerWage":
				$prefix = "p.";
				break;
			case "fromRequestID":
			case "toRequestID":
				$prefix = "r.";
				break;
			case "fromReqDate":
			case "toReqDate":
			case "fromPartDate":
			case "toPartDate":
			case "fromEndReqDate":
			case "toEndReqDate":
				$value = DateModules::shamsi_to_miladi($value, "-");
				break;
			case "fromReqAmount":
			case "toReqAmount":
			case "fromPartAmount":
			case "toPartAmount":
				$value = preg_replace('/,/', "", $value);
				break;
		}
		if(strpos($key, "from") === 0)
			$where_temp = " AND " . $prefix . substr($key,4) . " >= :$key";
		else if(strpos($key, "to") === 0)
			$where_temp = " AND " . $prefix . substr($key,2) . " <= :$key";
		else
			$where_temp = " AND " . $prefix . $key . " = :$key";
	
		$where .= $where_temp;
		$whereParam[":$key"] = $value;
	}
    if(!empty($_POST["fromEndReqDate"]) || !empty($_POST["toEndReqDate"]))
	{
            $where .= " AND (EndDate is null or EndDate='0000-00-00' ";
            if(!empty($_POST["fromEndReqDate"]))
            {
                    $where .= " or EndDate >= :fromEndReqDate";
                    $whereParam[":fromEndReqDate"] = DateModules::shamsi_to_miladi($_POST["fromEndReqDate"], "-");
            }
            if(!empty($_POST["toEndReqDate"]))
            {
                    $where .= " or EndDate <= :toEndReqDate";
                    $whereParam[":toEndReqDate"] = DateModules::shamsi_to_miladi($_POST["toEndReqDate"], "-");				
            }
            $where .= " )";
	}
	
	$ComputeDate = $_REQUEST["ComputeDate"];
	if(!empty($ComputeDate)){
		$where .= " AND case r.StatusID when " . LON_REQ_STATUS_CONFIRM . " then 1=1
										when " . LON_REQ_STATUS_DEFRAY . " then DefrayDate > :cd 
										when " . LON_REQ_STATUS_ENDED . "  then EndDate > :cd 
										else 1=0 end				
				AND ReqDate < :cd";

		$whereParam[":cd"] = DateModules::shamsi_to_miladi($ComputeDate,"-");
	}
	
}	

function showReport(){
	
	$where = "";
	$whereParam = array();
	MakeWhere($where, $whereParam);
	
	$dt = PdoDataAccess::runquery("
		select r.*,p.*,
                        bi.InfoDesc StatusDesc,
			concat_ws(' ',p1.fname,p1.lname,p1.CompanyName) ReqFullname,
			concat_ws(' ',p2.fname,p2.lname,p2.CompanyName) LoanFullname
		from LON_requests r
                        left join BaseInfo bi on(bi.TypeID=5 AND bi.InfoID=StatusID)
			join LON_ReqParts p on(r.RequestID=p.RequestID AND p.IsHistory='NO')
			left join BSC_persons p1 on(p1.PersonID=r.ReqPersonID)
			left join BSC_persons p2 on(p2.PersonID=r.LoanPersonID)
		where r.RequestID>0 $where
		group by r.RequestID ,p.PartID
		order by r.RequestID", $whereParam);
	
	if($_SESSION["USER"]["UserName"] == "admin")
	{
		//ini_set("display_errors", "On");
		echo PdoDataAccess::GetLatestQueryString();
		//print_r(ExceptionHandler::PopAllExceptions());
	}
	
	$returnArr = array();
	foreach($dt as $row)
	{
		$RequestID = $row["RequestID"];
		$ComputeDate = !empty($_POST["ComputeDate"]) ? 
				DateModules::shamsi_to_miladi($_POST["ComputeDate"],"-") : DateModules::Now();
		
		$ComputeArr = LON_Computes::ComputePayments($RequestID, $ComputeDate);
		//............ get remain untill now ......................
		$CurrentRemain = LON_Computes::GetCurrentRemainAmount($RequestID, $ComputeArr);
		$TotalRemain = LON_Computes::GetTotalRemainAmount($RequestID, $ComputeArr);
		
		$row = array(
			"RequestID" => $RequestID,
			"StatusDesc" =>  $row["StatusDesc"],
			"EndDate" =>  $row["EndDate"],
			"DefrayDate" =>  $row["DefrayDate"],
			"ComputeMode" => $row["ComputeMode"],
			"ReqFullname" => $row["ReqFullname"],
			"LoanFullname" => $row["LoanFullname"],
			"PartAmount" => $row["PartAmount"],
			"CurrentRemain" => $CurrentRemain,
			"TotalRemain" => $TotalRemain,
			"DefrayAmount" => 0//$DefrayAmount
		);
		
		$totalCompute = array(
			"pure" => 0,
			"wage" => 0,
			"late" => 0,
			"pnlt" => 0
		);
		$totalPay = array(
			"pure" => 0,
			"wage" => 0,
			"late" => 0,
			"pnlt" => 0
		);
		for($i=0; $i<count($ComputeArr); $i++)
		{
			if($ComputeArr[$i]["type"] == "pay")
			{
				$totalPay["pure"] += $ComputeArr[$i]["pure"];
				$totalPay["wage"] += $ComputeArr[$i]["wage"];
				$totalPay["late"] += $ComputeArr[$i]["totallate"];
				$totalPay["pnlt"] += $ComputeArr[$i]["totalpnlt"];
				continue;
			}			
			
			$totalCompute["pure"] += $ComputeArr[$i]["pure"];
			$totalCompute["wage"] += $ComputeArr[$i]["wage"];
			$totalCompute["late"] += $ComputeArr[$i]["totallate"];
			$totalCompute["pnlt"] += $ComputeArr[$i]["totalpnlt"];
			
		}
		$row["compute_pure"] = $totalCompute["pure"];
		$row["compute_wage"] = $totalCompute["wage"];
		$row["compute_late"] = $totalCompute["late"];
		$row["compute_pnlt"] = $totalCompute["pnlt"];
		
		$row["pay_pure"] = $totalPay["pure"];
		$row["pay_wage"] = $totalPay["wage"];
		$row["pay_late"] = $totalPay["late"];
		$row["pay_pnlt"] = $totalPay["pnlt"];
		
		$row["remain_pure"] = $totalCompute["pure"] - $totalPay["pure"];
		$row["remain_wage"] = $totalCompute["wage"] - $totalPay["wage"];
		$row["remain_late"] = $totalCompute["late"] - $totalPay["late"];
		$row["remain_pnlt"] = $totalCompute["pnlt"] - $totalPay["pnlt"];
		
		$returnArr[] = $row;
	}

	$rpg = new ReportGenerator();
	$rpg->excel = !empty($_POST["excel"]);
	$rpg->mysql_resource = $returnArr;

	function MoneyRender($row,$value){
		if($value*1 < 0)
			return "<font color=red>" . number_format($value) . "</font>";
		return number_format($value);
	}
	function ReqPersonRender($row,$value){
		return $value == "" ? "منابع داخلی" : $value;
	}
	function ComputeRender($row,$value){
		if($value == "BANK") return "فرمول بانک مرکزی";
		if($value == "NEW") return 'فرمول تنزیل اقساط';
		if($value == "NOAVARI") return 'فرمول صندوق نوآوری';
	}
	function reportRender($row, $value){
		return "<a href=LoanPayment.php?show=tru&RequestID=" . $value .
			(!empty($_POST["ComputeDate"]) ? "&ComputeDate=" . $_POST["ComputeDate"] : '') . 
			" target=blank >" . $value . "</a>";
	}

	$col = $rpg->addColumn("شماره وام", "RequestID", "reportRender");
	$col->ExcelRender = false;
	$rpg->addColumn("منبع", "ReqFullname","ReqPersonRender");
	$rpg->addColumn("مشتری", "LoanFullname");
	$rpg->addColumn('مبنای محاسبه', "ComputeMode", "ComputeRender");
    $rpg->addColumn("وضعیت", "StatusDesc");
    $rpg->addColumn("تاریخ خاتمه", "EndDate", "ReportDateRender");
	$rpg->addColumn("تاریخ تسویه", "DefrayDate", "ReportDateRender");
	
	$col = $rpg->addColumn("مبلغ وام", "PartAmount","ReportMoneyRender");
	$col->EnableSummary();
	$col = $rpg->addColumn("مانده قابل پرداخت معوقه", "CurrentRemain", "ReportMoneyRender");
	$col->EnableSummary();
	$col = $rpg->addColumn("مانده تا انتها", "TotalRemain", "ReportMoneyRender");
	$col->EnableSummary();
	
	$col = $rpg->addColumn("اصل", "compute_pure","ReportMoneyRender");
	$col->GroupHeader = "جمع محاسبه شده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#a2ff9c";
	$col->EnableSummary();
	
	$col = $rpg->addColumn("کارمزد", "compute_wage","ReportMoneyRender");
	$col->GroupHeader = "جمع محاسبه شده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#a2ff9c";
	$col->EnableSummary();
	
	$col = $rpg->addColumn("تاخیر", "compute_late","ReportMoneyRender");
	$col->GroupHeader = "جمع محاسبه شده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#a2ff9c";
	$col->EnableSummary();
	
	$col = $rpg->addColumn("جریمه", "compute_pnlt","ReportMoneyRender");
	$col->GroupHeader = "جمع محاسبه شده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#a2ff9c";
	$col->EnableSummary();
	
	//.............................
	
	$col = $rpg->addColumn("اصل", "pay_pure","ReportMoneyRender");
	$col->GroupHeader = "جمع پرداخت شده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#b0edff";
	$col->EnableSummary();
	
	$col = $rpg->addColumn("کارمزد", "pay_wage","ReportMoneyRender");
	$col->GroupHeader = "جمع پرداخت شده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#b0edff";
	$col->EnableSummary();
	
	$col = $rpg->addColumn("تاخیر", "pay_late","ReportMoneyRender");
	$col->GroupHeader = "جمع پرداخت شده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#b0edff";
	$col->EnableSummary();
	
	$col = $rpg->addColumn("جریمه", "pay_pnlt","ReportMoneyRender");
	$col->GroupHeader = "جمع پرداخت شده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#b0edff";
	$col->EnableSummary();
	
	//.............................
	
	$col = $rpg->addColumn("اصل", "remain_pure","ReportMoneyRender");
	$col->GroupHeader = "مانده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#fdff9c";	
	$col->EnableSummary();
	
	$col = $rpg->addColumn("کارمزد", "remain_wage","ReportMoneyRender");
	$col->GroupHeader = "مانده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#fdff9c";	
	$col->EnableSummary();
	
	$col = $rpg->addColumn("تاخیر", "remain_late","ReportMoneyRender");
	$col->GroupHeader = "مانده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#fdff9c";	
	$col->EnableSummary();
	
	$col = $rpg->addColumn("جریمه", "remain_pnlt","ReportMoneyRender");
	$col->GroupHeader = "مانده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#fdff9c";	
	$col->EnableSummary();
	
	if(!$rpg->excel)
	{
		BeginReport();
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
					گزارش مانده وام ها
				</td>
				<td width='200px' align='center' style='font-family:tahoma;font-size:11px'>تاریخ تهیه گزارش : " 
			. DateModules::shNow() . "<br>";
		if(!empty($_POST["fromReqDate"]))
			echo "<br>گزارش از تاریخ : " . $_POST["fromReqDate"];
		if(!empty($_POST["toReqDate"]))
				echo "<br>گزارش تا تاریخ : " . $_POST["toReqDate"];
		
		echo "</td></tr></table>";
		
	}
	$rpg->generateReport();
	die();
}
?>
<script>
LoanReport_remainders.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

LoanReport_remainders.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "remainders.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function LoanReport_remainders()
{		
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :2
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش مانده وام ها",
		width : 760,
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
					el = LoanReport_remaindersObj.formPanel.down("[itemId=cmp_subAgent]");
					el.getStore().proxy.extraParams["PersonID"] = this.getValue();
					el.getStore().load();
				}
			}
		},{
			xtype : "checkcombo",
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
			xtype : "container",
			html : "وام بلاعوض &nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"+
				"<input name=IsFree type=radio value='YES' > بلی &nbsp;&nbsp;" +
				"<input name=IsFree type=radio value='NO' > خیر &nbsp;&nbsp;" +
				"<input name=IsFree type=radio value='' checked > هردو " 
		},{
			xtype : "numberfield",
			hideTrigger : true,
			name : "fromRequestID",
			fieldLabel : "از شماره"
		},{
			xtype : "numberfield",
			hideTrigger : true,
			name : "toRequestID",
			fieldLabel : "تا شماره"
		},{
			xtype : "shdatefield",
			name : "fromReqDate",
			fieldLabel : "تاریخ درخواست از"
		},{
			xtype : "shdatefield",
			name : "toReqDate",
			fieldLabel : "تا تاریخ"
		},{
			xtype : "currencyfield",
			name : "fromReqAmount",
			hideTrigger : true,
			fieldLabel : "از مبلغ درخواست"
		},{
			xtype : "currencyfield",
			name : "toReqAmount",
			hideTrigger : true,
			fieldLabel : "تا مبلغ درخواست"
		},{
			xtype : "currencyfield",
			name : "fromPartAmount",
			hideTrigger : true,
			fieldLabel : "از مبلغ تایید پرداخت"
		},{
			xtype : "currencyfield",
			name : "toPartAmount",
			hideTrigger : true,
			fieldLabel : "تا مبلغ تایید پرداخت"
		},{
			xtype : "shdatefield",
			name : "fromPartDate",
			fieldLabel : "تاریخ پرداخت از"
		},{
			xtype : "shdatefield",
			name : "toPartDate",
			fieldLabel : "تا تاریخ"
		},{
			xtype : "numberfield",
			name : "fromInstallmentCount",
			hideTrigger : true,
			fieldLabel : "تعداد اقساط از"
		},{
			xtype : "numberfield",
			name : "toInstallmentCount",
			hideTrigger : true,
			fieldLabel : "تعداد اقساط تا"
		},{
			xtype : "numberfield",
			name : "fromDelayMonths",
			hideTrigger : true,
			fieldLabel : "تنفس از "
		},{
			xtype : "numberfield",
			name : "toDelayMonths",
			hideTrigger : true,
			fieldLabel : "تنفس تا"
		},{
			xtype : "numberfield",
			name : "CustomerWage",
			hideTrigger : true,
			fieldLabel : "کارمزد مشتری"
		},{
			xtype : "numberfield",
			name : "FundWage",
			hideTrigger : true,
			fieldLabel : "کارمزد صندوق"
		},{
			xtype : "numberfield",
			name : "ForfeitPercent",
			hideTrigger : true,
			fieldLabel : "درصد دیرکرد"
		},{
			xtype : "numberfield",
			name : "DelayPercent",
			hideTrigger : true,
			fieldLabel : "کارمزد تنفس"
		},{
			xtype : "checkcombo",
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
			xtype : "container",
			html : "وضعیت خاتمه&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"+
				"<input name=IsEnded type=radio value='YES' > خاتمه یافته &nbsp;&nbsp;" +
				"<input name=IsEnded type=radio value='NO' > جاری &nbsp;&nbsp;" +
				"<input name=IsEnded type=radio value='' checked > هردو " 
		},{
			xtype : "shdatefield",
			name : "fromEndReqDate",
			fieldLabel : "تاریخ خاتمه از"
		},{
			xtype : "shdatefield",
			name : "toEndReqDate",
			fieldLabel : "تا تاریخ"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				data : [
					["BANK" , "فرمول بانک مرکزی" ],
					["NEW" , "فرمول تنزیل اقساط" ],
					["NOAVARI", 'فرمول صندوق نوآوری']
				],
				fields : ['id','value']
			}),
			displayField : "value",
			valueField : "id",
			fieldLabel : "فرمول محاسبه",
			queryMode : 'local',
			width : 370,
			hiddenName : "ComputeMode"
		},{
			xtype : "shdatefield",
			fieldLabel : "محاسبه تا تاریخ",
			name : "ComputeDate"
		}],
		buttons : [{
			text : "مشاهده گزارش",
			handler : Ext.bind(this.showReport,this),
			iconCls : "report"
		},{
			text : "خروجی excel",
			handler : Ext.bind(this.showReport,this),
			listeners : {
				click : function(){
					LoanReport_remaindersObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				LoanReport_remaindersObj.formPanel.getForm().reset();
				LoanReport_remaindersObj.get("mainForm").reset();
			}			
		}]
	});
		
	this.formPanel.getEl().addKeyListener(Ext.EventObject.ENTER, function(keynumber,e){
		
		LoanReport_remaindersObj.showReport();
		e.preventDefault();
		e.stopEvent();
		return false;
	});
}

LoanReport_remaindersObj = new LoanReport_remainders();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>