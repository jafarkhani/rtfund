<?php

require_once '../header.inc.php';
require_once "../request/request.class.php";
require_once "../request/request.data.php";
require_once "ReportGenerator.class.php";

if(isset($_REQUEST["show"]))
{
	function dateRender($row, $val){
		return DateModules::miladi_to_shamsi($val);
	}	
	
	function moneyRender($row, $val) {
		return number_format($val);
	}
	
	function MakeWhere(&$where, &$whereParam){
		
		foreach($_POST as $key => $value)
		{
			if($key == "excel" || $key == "OrderBy" || $key == "OrderByDirection" || 
					$value === "" || strpos($key, "combobox") !== false)
				continue;
			
			if($key == "IsEndedInclude")
				continue;
			
			$prefix = "";
			switch($key)
			{
				case "fromRequestID":
				case "toRequestID":
					$prefix = "i.";
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
		
		$where .= isset($_POST["IsEndedInclude"]) ? 
				" AND r.StatusID in('".LON_REQ_STATUS_CONFIRM."','".LON_REQ_STATUS_ENDED."')" : 
				" AND r.StatusID in('".LON_REQ_STATUS_CONFIRM."')";
	}	
	
	$rpg = new ReportGenerator();
	$rpg->excel = !empty($_POST["excel"]);
	ini_set("memory_limit", "1000M");
	ini_set("max_execution_time", "600");
	//.....................................
	$where = "";
	$whereParam = array();
	MakeWhere($where, $whereParam);
	
	$query = "select i.*,r.*,l.*,p.*,
				concat_ws(' ',p1.fname,p1.lname,p1.CompanyName) ReqFullname,
				concat_ws(' ',p2.fname,p2.lname,p2.CompanyName) LoanFullname,
				BranchName
				
			from LON_installments i
			join LON_requests r using(RequestID)
			join LON_ReqParts p on(r.RequestID=p.RequestID AND p.IsHistory='NO')
			left join LON_loans l using(LoanID)
			join BSC_branches using(BranchID)
			left join BSC_persons p1 on(p1.PersonID=r.ReqPersonID)
			left join BSC_persons p2 on(p2.PersonID=r.LoanPersonID)
			where i.history='NO' AND i.IsDelayed='NO' " . $where . " 
			
			group by r.RequestID, i.InstallmentID
			order by InstallmentDate";
	
	
	$dataTable = PdoDataAccess::runquery($query, $whereParam);
	//if($_SESSION["USER"]["UserName"] == "admin")
	//	echo PdoDataAccess::GetLatestQueryString();
	//-------------------- get the payed of installments -----------------------
	/*
	$computeArr = array();
	$returnArr = array();
	for($index=0; $index<count($dataTable); $index++)
	{
		$MainRow = &$dataTable[$index];
		$MainRow["PayedDate"] = "";
		$MainRow["PayedAmount"] = "";
		$MainRow["forfeit"] = 0;
		$MainRow["SumPayed"] = 0;
		$MainRow["TotalRemainder"] = $MainRow["InstallmentAmount"];
	
		if(!isset($computeArr[ $MainRow["RequestID"] ]))
		{
			$dt = array();
			$computeArr[ $MainRow["RequestID"] ] = array(
				"compute" => LON_requests::ComputePayments2($MainRow["RequestID"], $dt),
				"obj" => LON_ReqParts::GetValidPartObj($MainRow["RequestID"]),
				"pays" => array(),
				"computIndex" => 0,
				"PayIndex" => 0
				);
			
			$ref = & $computeArr[ $MainRow["RequestID"] ];
			foreach($ref["compute"] as $row)
			{
				if($row["ActionType"] == "pay")
					$ref["pays"][] = $row;
			}
		}
		
		$IsAdded = false;
		$ref = & $computeArr[ $MainRow["RequestID"] ];
		for(; $ref["computIndex"] < count($ref["compute"]); $ref["computIndex"]++)
		{
			$row = $ref["compute"][$ref["computIndex"]];
			$obj = $ref["obj"];
			if($row["ActionType"] != "installment")
				continue;
			if($row["ActionType"] == "installment")
			{
				$amount = $row["ActionAmount"]*1;
				if($obj->PayCompute != "installment")
				{
					$amount += $row["CurForfeitAmount"]*1;
					if($row["InstallmentID"] == $MainRow["InstallmentID"])
					{
						$MainRow["forfeit"] = $row["CurForfeitAmount"]*1;
						$MainRow["TotalRemainder"] += $row["CurForfeitAmount"]*1;
					}
				}
				
				for(; $ref["PayIndex"]<count($ref["pays"]); $ref["PayIndex"]++)
				{
					if($obj->PayCompute != "installment")
					{
						if($ref["pays"][$ref["PayIndex"]]["ActionAmount"]*1 < $amount)
						{
							$amount += $ref["pays"][$ref["PayIndex"]]["CurForfeitAmount"]*1;
							if($row["InstallmentID"] == $MainRow["InstallmentID"])
							{
								$MainRow["TotalRemainder"] += $ref["pays"][$ref["PayIndex"]]["CurForfeitAmount"]*1;
								if(!$IsAdded)
									$MainRow["forfeit"] += $ref["pays"][$ref["PayIndex"]]["CurForfeitAmount"]*1;
								else
									$MainRow["forfeit"] = $ref["pays"][$ref["PayIndex"]]["CurForfeitAmount"]*1;
							}
						}
						//$ref["pays"][$ref["PayIndex"]]["ActionAmount"] -= $ref["pays"][$ref["PayIndex"]]["CurForfeitAmount"]*1;
						//$ref["pays"][$ref["PayIndex"]]["CurForfeitAmount"] = 0;
						
					}
					$min = min($ref["pays"][$ref["PayIndex"]]["ActionAmount"]*1,$amount);
					if($min == 0)
						break;
					$ref["pays"][$ref["PayIndex"]]["ActionAmount"] -= $min;
					$amount -= $min;
					if($row["InstallmentID"] == $MainRow["InstallmentID"])
					{
							$MainRow["PayedDate"] = DateModules::miladi_to_shamsi($ref["pays"][$ref["PayIndex"]]["ActionDate"]) ;
							$MainRow["PayedAmount"] = number_format($min);
							$MainRow["SumPayed"] += $min;
							$MainRow["TotalRemainder"] -= $min; 
							$returnArr[] = $MainRow;
							$IsAdded = true;
					}
					if($ref["pays"][$ref["PayIndex"]]["ActionAmount"]*1 > 0)
						break;
				}
			}
			if($row["InstallmentID"] == $MainRow["InstallmentID"])
			{
				$ref["computIndex"]++;
				break;
			}
		}
		if(!$IsAdded)
			$returnArr[] = $MainRow;
	}*/
	//--------------------------------------------------------------------------
	
	$computeArr = array();
	$returnArr = array();
	for($index=0; $index<count($dataTable); $index++)
	{
		$MainRow = &$dataTable[$index];
		$MainRow["PayedDate"] = "";
		$MainRow["PayedAmount"] = "";
		$MainRow["forfeit"] = 0;
		$MainRow["TotalRemainder"] = $MainRow["InstallmentAmount"];
	
		if(!isset($computeArr[ $MainRow["RequestID"] ]))
		{
			$dt = array();
			$computeArr[ $MainRow["RequestID"] ] = array(
				"compute" => LON_requests::ComputePayments2($MainRow["RequestID"], $dt),
				"computIndex" => 0,
				"PayIndex" => 0
				);
		}
		$ref = & $computeArr[ $MainRow["RequestID"] ];
		
		for(; $ref["computIndex"] < count($ref["compute"]); $ref["computIndex"]++)
		{
			$row = $ref["compute"][$ref["computIndex"]];
			if($row["ActionType"] != "installment")
				continue;
			if($row["InstallmentID"] == $MainRow["InstallmentID"])
			{
				for($k=0; $k < count($row["pays"]); $k++)
				{  
					$payRow = $row["pays"][$k];
					$MainRow["forfeit"] = $payRow["forfeit"];
					$MainRow["TotalRemainder"] = $payRow["remain"];
					$MainRow["PayedDate"] = $payRow["PayedDate"];
					$MainRow["PayedAmount"] = $payRow["PayedAmount"];
					$MainRow["TotalRemainder"] = $payRow["remain"];
					$returnArr[] = $MainRow;
				}
				
				if(count($row["pays"]) == 0)
					$returnArr[] = $MainRow;
				
				$ref["computIndex"]++;
				break;
				
			}
		}
	}
	//--------------------------------------------------------------------------
	$rpg->mysql_resource = $returnArr;
	
	function endedRender($row,$value){
		return ($value == "YES") ? "خاتمه" : "جاری";
	}
	
	$col = $rpg->addColumn("شماره وام", "RequestID");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RequestID");
	$col = $rpg->addColumn("نوع وام", "LoanDesc");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RequestID");
	$col = $rpg->addColumn("معرفی کننده", "ReqFullname");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RequestID");
	$col = $rpg->addColumn("تاریخ درخواست", "ReqDate", "dateRender");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RequestID");
	$col = $rpg->addColumn("مبلغ درخواست", "ReqAmount", "moneyRender");
	$col->EnableSummary();
	$col->rowspaning = true;
	$col->rowspanByFields = array("RequestID");
	$col = $rpg->addColumn("مشتری", "LoanFullname");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RequestID");
	$col = $rpg->addColumn("شعبه", "BranchName");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RequestID");
	
	$col = $rpg->addColumn("تاریخ قسط", "InstallmentDate", "dateRender");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RequestID","InstallmentDate");
	$col = $rpg->addColumn("مبلغ قسط", "InstallmentAmount", "moneyRender");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RequestID","InstallmentDate");
	$col->EnableSummary();
	
	$col = $rpg->addColumn("مبلغ تاخیر", "forfeit", "moneyRender");	
	$col->rowspaning = true;
	$col->rowspanByFields = array("RequestID","InstallmentDate");
	$col->EnableSummary();
	
	$rpg->addColumn("تاریخ پرداخت", "PayedDate");
	$rpg->addColumn("مبلغ پرداخت", "PayedAmount");
	$col = $rpg->addColumn("مانده قسط", "TotalRemainder", "ReportMoneyRender");
	$col->EnableSummary();
	
	
	if(!$rpg->excel)
	{
		BeginReport();
		echo "<div style=display:none>" . $query . "</div>";
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family: titr;font-size:15px'>
					گزارش اقساط وام ها 
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
	$rpg->generateReport();
	die();
}
?>
<script>
LoanReport_installments.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

LoanReport_installments.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "installments.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function LoanReport_installments()
{		
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :2
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش اقساط وام ها",
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
					el = LoanReport_installmentsObj.formPanel.down("[itemId=cmp_subAgent]");
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
			name : "fromInstallmentDate",
			fieldLabel : "تاریخ قسط از"
		},{
			xtype : "shdatefield",
			name : "toInstallmentDate",
			fieldLabel : "تا تاریخ"
		},{
			xtype : "currencyfield",
			name : "fromInstallmentAmount",
			hideTrigger : true,
			fieldLabel : "از مبلغ قسط"
		},{
			xtype : "currencyfield",
			name : "toInstallmentAmount",
			hideTrigger : true,
			fieldLabel : "تا مبلغ قسط"
		},{
			xtype : "container",
			html : "<input type=checkbox name=IsEndedInclude >  گزارش شامل وام های خاتمه یافته نیز باشد"
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
					LoanReport_installmentsObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				LoanReport_installmentsObj.formPanel.getForm().reset();
				LoanReport_installmentsObj.get("mainForm").reset();
			}			
		}]
	});
	
	this.formPanel.getEl().addKeyListener(Ext.EventObject.ENTER, function(keynumber,e){
		
		LoanReport_installmentsObj.showReport();
		e.preventDefault();
		e.stopEvent();
		return false;
	});
}

LoanReport_installmentsObj = new LoanReport_installments();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>