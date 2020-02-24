<?php

require_once '../../header.inc.php';
require_once "../request/request.class.php";
require_once "../request/request.data.php";
require_once "ReportGenerator.class.php";

if(isset($_REQUEST["show"]))
{
	function MakeWhere(&$where, &$whereParam){
		
		foreach($_POST as $key => $value)
		{
			if($key == "excel" || $key == "OrderBy" || $key == "OrderByDirection" || 
					$value === "" || strpos($key, "combobox") !== false)
				continue;
			$prefix = "";
			switch($key)
			{
				case "fromRequestDate":
				case "toRequestDate":
					$value = DateModules::shamsi_to_miladi($value, "-");
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
	
	//.....................................
	$where = "";
	$whereParam = array();
	MakeWhere($where, $whereParam);
	
	$query = "select r.*,l.*,p.*,
				concat_ws(' ',p1.fname,p1.lname,p1.CompanyName) ReqFullname,
				concat_ws(' ',p2.fname,p2.lname,p2.CompanyName) LoanFullname,
				BranchName,
				tazamin
				
			from LON_ReqParts p 
			join ( 
				select RequestID from LON_requests join LON_ReqParts using(RequestID)
				group by RequestID having count(PartID)>1)t on(p.RequestID=t.RequestID)
			join LON_requests r on(r.RequestID=t.RequestID)
			left join LON_loans l using(LoanID)
			join BSC_branches using(BranchID)
			left join BSC_persons p1 on(p1.PersonID=r.ReqPersonID)
			left join BSC_persons p2 on(p2.PersonID=r.LoanPersonID)
			left join (
				select ObjectID,group_concat(title,' به شماره سريال ',num, ' و مبلغ ', 
					format(amount,2) separator '<br>') tazamin
				from (	
					select ObjectID,InfoDesc title,group_concat(if(KeyTitle='no',paramValue,'') separator '') num,
					group_concat(if(KeyTitle='amount',paramValue,'') separator '') amount
					from DMS_documents d
					join BaseInfo b1 on(InfoID=d.DocType AND TypeID=8)
					join DMS_DocParamValues dv  using(DocumentID)
					join DMS_DocParams using(ParamID)
				    where ObjectType='loan' AND b1.param1=1
					group by ObjectID, DocumentID
				)t
				group by ObjectID
			)t2 on(t2.ObjectID=r.RequestID)

			where 1=1 " . $where . " 
			
			group by p.PartID
			order by ReqDate,p.PartID";
	
	
	$dataTable = PdoDataAccess::runquery_fetchMode($query, $whereParam);
	$query = PdoDataAccess::GetLatestQueryString();
	//print_r(ExceptionHandler::PopAllExceptions());
	
	$rpg = new ReportGenerator();
	$rpg->excel = !empty($_POST["excel"]);
	$rpg->mysql_resource = $dataTable;
	
	function endedRender($row,$value){
		return ($value == "YES") ? "خاتمه" : "جاری";
	}
	
	$col = $rpg->addColumn("شماره وام", "RequestID");
	$col->rowspanByFields = array("RequestID");
	$col->rowspaning = true;
	$col = $rpg->addColumn("نوع وام", "LoanDesc");
	$col->rowspanByFields = array("RequestID");
	$col->rowspaning = true;
	$col = $rpg->addColumn("منبع ", "ReqFullname");
	$col->rowspanByFields = array("RequestID");
	$col->rowspaning = true;
	$col = $rpg->addColumn("تاریخ درخواست", "ReqDate", "ReportDateRender");
	$col->rowspanByFields = array("RequestID");
	$col->rowspaning = true;
	$col = $rpg->addColumn("مبلغ درخواست", "ReqAmount", "ReportMoneyRender");
	$col->rowspanByFields = array("RequestID");
	$col->rowspaning = true;
	$col = $rpg->addColumn("مشتری", "LoanFullname");
	$col->rowspanByFields = array("RequestID");
	$col->rowspaning = true;
	$col = $rpg->addColumn("شعبه", "BranchName");
	$col->rowspanByFields = array("RequestID");
	$col->rowspaning = true;
	$col = $rpg->addColumn("تضامین", "tazamin");
	$col->rowspanByFields = array("RequestID");
	$col->rowspaning = true;
	
	function intervslRender($row, $value){
		return $value . ($row["IntervalType"] == "DAY" ? " روز" : " ماه");
	}
	
	$rpg->addColumn("شرح", "PartDesc");
	$rpg->addColumn("مبلغ پرداخت", "PartAmount", "ReportMoneyRender");
	$rpg->addColumn("ماه تنفس", "DelayMonths");
	$rpg->addColumn("روز تنفس", "DelayDays");
	$rpg->addColumn("فاصله اقساط", "PayInterval", "intervslRender");
	$rpg->addColumn("تعداد اقساط", "InstallmentCount");
	$rpg->addColumn("کارمزد مشتری", "CustomerWage");
	$rpg->addColumn("کارمزد صندوق", "FundWage");
	$rpg->addColumn("درصد دیرکرد", "ForfeitPercent");
	
	if(!$rpg->excel)
	{
		BeginReport();
		echo "<div style=display:none>" . $query . "</div>";
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
					گزارش وام های چندشرایطی
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
LoanReport_multipart.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

LoanReport_multipart.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "MultiParts.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function LoanReport_multipart()
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
			fieldLabel : "منبع ",
			pageSize : 25,
			width : 370,
			displayField : "fullname",
			valueField : "PersonID",
			hiddenName : "ReqPersonID",
			listeners :{
				select : function(record){
					el = LoanReport_multipartObj.formPanel.down("[itemId=cmp_subAgent]");
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
			xtype : "shdatefield",
			name : "fromInstallmentDate",
			fieldLabel : "تاریخ درخواست از"
		},{
			xtype : "shdatefield",
			name : "toInstallmentDate",
			fieldLabel : "تا تاریخ"
		},{
			xtype : "currencyfield",
			name : "fromRequestID",
			hideTrigger : true,
			fieldLabel : "شماره درخواست از"
		},{
			xtype : "currencyfield",
			name : "toRequestID",
			hideTrigger : true,
			fieldLabel : "تا شماره"
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
					LoanReport_multipartObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				LoanReport_multipartObj.formPanel.getForm().reset();
				LoanReport_multipartObj.get("mainForm").reset();
			}			
		}]
	});
	
	this.formPanel.getEl().addKeyListener(Ext.EventObject.ENTER, function(keynumber,e){
		
		LoanReport_multipartObj.showReport();
		e.preventDefault();
		e.stopEvent();
		return false;
	});
}

LoanReport_multipartObj = new LoanReport_multipart();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>