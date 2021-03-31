<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	97.09
//-------------------------
ini_set("display_errors", "On");
require_once '../../../header.inc.php';
require_once "ReportGenerator.class.php";

$page_rpg = new ReportGenerator("mainForm","WarReport_tazaminObj");

$page_rpg->addColumn("نوع تضمین", "DocumentTitle");
$page_rpg->addColumn("صاحب تضمین", "DocumentOwner"); /*new added*/
$page_rpg->addColumn("شماره سریال", "DocumentNo");
$page_rpg->addColumn("مبلغ تضمین", "DocumentAmount");
$page_rpg->addColumn("سایر اطلاعات تضمین", "DocumentOtherInfo");

$page_rpg->addColumn("شماره تضمین", "RequestID");
$page_rpg->addColumn("شعبه", "BranchName");
$page_rpg->addColumn("نوع درخواست", "TypeDesc");	
$col = $page_rpg->addColumn("تاریخ شروع", "StartDate");
$col->type = "date";
$col = $page_rpg->addColumn("تاریخ پایان", "EndDate");
$col->type = "date";
$page_rpg->addColumn("مبلغ ضمانتنمه", "amount");
$page_rpg->addColumn("مشتری", "fullname");
$page_rpg->addColumn("سازمان مربوطه", "organization");
$page_rpg->addColumn("کارمزد", "wage");
$page_rpg->addColumn("شماره نامه معرفی", "LetterNo");
$col = $page_rpg->addColumn("تاریخ نامه معرفی", "LetterDate");
$col->type = "date";

function MakeWhere(&$where, &$whereParam){

	if(session::IsPortal() && isset($_REQUEST["dashboard_show"]))
	{
		$where .= " AND r.PersonID=" . $_SESSION["USER"]["PersonID"];
	}
	foreach($_POST as $key => $value)
	{
		if($key == "excel" || 
				$value === "" || strpos($key, "-inputEl") !== false || strpos($key, "rpcmp") !== false ||
				strpos($key, "reportcolumn_fld") !== false || strpos($key, "reportcolumn_ord") !== false)
			continue;
		
		if(strpos($key, "FILTERPERSON_") !== false)
		{
			$prefix = "p.";
			$key = str_replace("FILTERPERSON_", "", $key);
			InputValidation::validate($value, InputValidation::Pattern_NumComma);
			if($key == "DomainID")
			{
				$where .= " AND " . $prefix . $key . " in(" . $value . ")";
			}
			else
			{
				$where .= " AND " . $prefix . $key . " = :$key";
				$whereParam[":$key"] = $value;
			}
			continue;
		}
		
		$prefix = "";
		switch($key)
		{
			case "PersonID":
			case "TypeID":
				$prefix = "r.";
				break;
			case "FromStartDate":
			case "ToStartDate":
			case "FromEndDate":
			case "ToEndDate":
			case "FromLetterDate":
			case "ToLetterDate":
				$value = DateModules::shamsi_to_miladi($value, "-");
				break; 
			case "FromAmount":
			case "ToAmount":
				$value = preg_replace('/,/', "", $value);
				break;
		}
		if(strpos($key, "From") === 0)
			$where .= " AND " . $prefix . substr($key,4) . " >= :$key";
		else if(strpos($key, "To") === 0)
			$where .= " AND " . $prefix . substr($key,2) . " <= :$key";
		else
			$where .= " AND " . $prefix . $key . " = :$key";
		$whereParam[":$key"] = $value;
	}
}	
	
function GetData($mode = "list"){
	
	//.....................................
	$where = "";
	$userFields = ReportGenerator::UserDefinedFields();
	$whereParam = array();
	MakeWhere($where, $whereParam);
		
	$query = "select
		
				b1.InfoDesc DocumentTitle,
				t_params.DocumentNo,
				t_params.DocumentAmount,
				t_params.DocumentOtherInfo,
                       t_params.DocumentOwner,
				
				r.* , 
				concat_ws(' ',fname,lname,CompanyName) fullname, 
				sp.StepDesc,
				bf.InfoDesc TypeDesc ,
				BranchName
				".
				($mode == "list" && $userFields != "" ? "," . $userFields : "")."
				
			from DMS_documents d
			join BaseInfo b1 on(b1.InfoID=d.DocType AND TypeID=8)
			left join ( 
				select DocumentID,
					group_concat(if(KeyTitle='no',paramValue,'') separator '') DocumentNo,
					group_concat(if(KeyTitle='amount',paramValue,'') separator '') DocumentAmount,
                        group_concat(if(KeyTitle='Owner',paramValue,'') separator '') DocumentOwner,
					group_concat(if((KeyTitle<>'amount' AND KeyTitle<>'no') or KeyTitle is null,
						concat(ParamDesc,' : ', paramValue, '<br>'),'') separator '') DocumentOtherInfo
					from DMS_DocParamValues 
					join DMS_DocParams using(ParamID)
					group by DocumentID 
				)t_params on(t_params.DocumentID=d.DocumentID)
			
			join WAR_requests r on(d.ObjectID=r.RequestID)
			left join BSC_persons p using(PersonID)
			join BSC_branches b using(BranchID)
			left join BaseInfo bf on(bf.TypeID=74 AND bf.InfoID=r.TypeID)
			join WFM_FlowSteps sp on(sp.FlowID=" . FLOWID_WARRENTY . " AND sp.StepID=r.StatusID)
				
			where d.ObjectType='warrenty' AND b1.param1=1 " . $where;
	
	$group = ReportGenerator::GetSelectedColumnsStr();
	$query .= $group == "" || $mode == "chart" ? " group by d.DocumentID" : " group by " . $group;
	$query .= $group == "" || $mode == "chart" ? " order by r.RequestID" : " order by " . $group;	
	
	$dataTable = PdoDataAccess::runquery($query, $whereParam);
	$query = PdoDataAccess::GetLatestQueryString();
	if($_SESSION["USER"]["UserName"] == "admin")
	{
		//BeginReport();
		print_r(ExceptionHandler::PopAllExceptions());
		echo PdoDataAccess::GetLatestQueryString();
		
	}
	
	return $dataTable; 
}

function ListData($IsDashboard = false){
	
	$rpg = new ReportGenerator();
	$rpg->excel = !empty($_POST["excel"]);
	$rpg->mysql_resource = GetData();
	
	function endedRender($row,$value){
		return ($value == "YES") ? "خاتمه" : "جاری";
	}
	
	$rpg->addColumn("شماره تضمین", "RefRequestID");
	
	$rpg->addColumn("نوع تضمین", "DocumentTitle");
    $rpg->addColumn("صاحب تضمین", "DocumentOwner"); /*new added*/
	$rpg->addColumn("شماره سریال", "DocumentNo");
	$rpg->addColumn("مبلغ تضمین", "DocumentAmount", "ReportMoneyRender");
	$rpg->addColumn("سایر اطلاعات تضمین", "DocumentOtherInfo");
		
	$rpg->addColumn("شعبه", "BranchName");
	$rpg->addColumn("نوع درخواست", "TypeDesc");	
	$rpg->addColumn("تاریخ شروع", "StartDate", "ReportDateRender");
	$rpg->addColumn("تاریخ پایان", "EndDate", "ReportDateRender");
	$col = $rpg->addColumn("مبلغ ضمانتنمه", "amount", "ReportMoneyRender");
	$col->EnableSummary();
	$rpg->addColumn("مشتری", "fullname");
	$rpg->addColumn("سازمان مربوطه", "organization");
	$rpg->addColumn("کارمزد", "wage");
	$rpg->addColumn("شماره نامه معرفی", "LetterNo");
	$rpg->addColumn("تاریخ نامه معرفی", "LetterDate", "ReportDateRender");
	
	if(!$rpg->excel && !$IsDashboard)
	{
		BeginReport();
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
					گزارش کلی تضامین ضمانتنامه ها
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
	ListData();	
}

if(isset($_REQUEST["rpcmp_chart"]))
{
	$page_rpg->mysql_resource = GetData("chart");
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
require_once getenv("DOCUMENT_ROOT") . '/framework/ReportDB/Filter_person.php';
?>
<script type="text/javascript" src="/generalUI/ReportGenerator.js"></script>
<script>
WarReport_tazamin.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

WarReport_tazamin.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "tazamin.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function WarReport_tazamin()
{		
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :2
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش کلی تضامین ضمانتنامه ها",
		width : 760,
		items :[{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: '/framework/baseInfo/baseInfo.data.php?' +
						"task=SelectBranches",
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
			xtype : "combo",
			store : new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: this.address_prefix + '../request.data.php?task=GetWarrentyTypes',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ["InfoID", "InfoDesc"],
				autoLoad : true
			}),
			displayField: 'InfoDesc',
			valueField : "InfoID",
			hiddenName : "TypeID",
			allowBlank : false,
			fieldLabel : "نوع درخواست"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: '/framework/person/persons.data.php?' +
						"task=selectPersons&UserType=IsCustomer",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['PersonID','fullname']
			}),
			fieldLabel : "مشتری",
			displayField : "fullname",
			pageSize : 20,
			valueField : "PersonID",
			hiddenName : "PersonID"
		},{
			xtype : "textfield",
			name : "organization",
			fieldLabel : "سازمان مربوطه"
		},{
			xtype : "currencyfield",
			name : "FromAmount",
			hideTrigger : true,
			fieldLabel : "مبلغ ضمانت نامه از"
		},{
			xtype : "currencyfield",
			name : "ToAmount",
			hideTrigger : true,
			fieldLabel : "مبلغ ضمانت نامه تا"
		},{
			xtype : "shdatefield",
			name : "FromStartDate",
			fieldLabel : "تاریخ شروع از"
		},{
			xtype : "shdatefield",
			name : "ToStartDate",
			fieldLabel : "تاریخ شروع تا"
		},{
			xtype : "shdatefield",
			name : "FromEndDate",
			fieldLabel : "تاریخ پایان از"
		},{
			xtype : "shdatefield",
			name : "ToEndDate",
			allowBlank : false,
			fieldLabel : "تاریخ پایان تا"
		},{
			xtype : "shdatefield",
			fieldLabel : "تاریخ نامه معرفی از",
			name : "FromLetterDate"
		},{
			xtype : "shdatefield",
			fieldLabel : "تاریخ نامه معرفی تا",
			name : "ToLetterDate"
		},{
			xtype : "textfield",
			fieldLabel : "شماره نامه معرفی",
			name : "LetterNo"
		},{
			xtype : "numberfield",
			fieldLabel : "کارمزد",
			name : "wage",
			width : 150,
			afterSubTpl : "%",
			hideTrigger : true
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				data : [
					[100 , "خام" ],
					[110 , "تایید شده" ],
					[120 , "خاتمه یافته" ],
					[130 , "ابطال شده" ]
				],
				fields : ['id','value']
			}),
			fieldLabel : "وضعیت",
			displayField : "value",
			valueField : "id",
			hiddenName : "StatusID"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				data : [
					['MAIN' , "اصل" ],
					['EXTEND' , "تمدید" ],
					['CHANGE' , "متمم" ]
				],
				fields : ['id','value']
			}),
			fieldLabel : "نسخه ضمانتنامه",
			displayField : "value",
			valueField : "id",
			hiddenName : "version"
		},{
			xtype : "checkcombo",
			fieldLabel : "نوع تضمین",
			hiddenName: "DocType",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../request.data.php?' +
						"task=GetTazminDocTypes",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['InfoID','InfoDesc'],
				autoLoad : true					
			}),
			displayField : "InfoDesc",
			valueField : "InfoID",
			width : 370,
			colspan : 2
		},{
			xtype : "fieldset",
			width : 730,
			title : "اطلاعات مشتری",
			colspan : 2,
			layout : {
				type : "table",
				columns : 2,
			},
			defaults : {
				width : 350
			},
			items : framework.PersonFilterList
		},{
			xtype : "fieldset",
			title : "ستونهای گزارش",
			colspan :2,
			items :[<?= $page_rpg->ReportColumns() ?>]
		},{
			xtype : "fieldset",
			colspan :2,
			title : "رسم نمودار",
			items : [<?= $page_rpg->GetChartItems("WarReport_tazaminObj","mainForm","total.php") ?>]
		}],
		buttons : [{
			text : "گزارش ساز",
			iconCls : "db",
			handler : function(){ReportGenerator.ShowReportDB(
						WarReport_tazaminObj, 
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
					WarReport_tazaminObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				WarReport_tazaminObj.formPanel.getForm().reset();
				WarReport_tazaminObj.get("mainForm").reset();
			}			
		}]
	});
	
	if(<?= session::IsPortal() ? "true" : "false" ?>)
	{
		this.formPanel.down("[hiddenName=ReqPersonID]").getStore().load({
			params : {
				PersonID : "<?= $_SESSION["USER"]["PersonID"] ?>"
			},
			callback : function(){
				me = WarReport_tazaminObj;
				me.formPanel.add({
					xtype : "hidden",
					name : "ReqPersonID",
					value : this.getAt(0).data.PersonID
				});
				me.formPanel.down("[hiddenName=ReqPersonID]").setValue(this.getAt(0).data.PersonID);
				me.formPanel.down("[hiddenName=ReqPersonID]").disable();
				
				el = me.formPanel.down("[itemId=cmp_subAgent]");
				el.getStore().proxy.extraParams["PersonID"] = this.getAt(0).data.PersonID;
				el.getStore().load();
			}
		});
	
	}
	
	this.formPanel.getEl().addKeyListener(Ext.EventObject.ENTER, function(keynumber,e){
		
		WarReport_tazaminObj.showReport();
		e.preventDefault();
		e.stopEvent();
		return false;
	});
}

WarReport_tazamin.prototype.ShowChart = function()
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "tazamin.php?chart=true";
	this.form.submit();
	return;
}

WarReport_tazaminObj = new WarReport_tazamin();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>
