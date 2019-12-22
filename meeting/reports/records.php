<?php

require_once '../../header.inc.php';
require_once "ReportGenerator.class.php";

function statusRender($row,$value){
	switch($value)
	{
		case "CUR": return "جاری";
		case "END": return "مختومه";
		case "REF": return "ارجاعی";
	}
}

$page_rpg = new ReportGenerator("mainForm","MeetingReport_recordsObj");
$page_rpg->addColumn("نوع جلسه", "MeetingTypeDesc");
$page_rpg->addColumn("شماره جلسه", "MeetingNo");
$page_rpg->addColumn("موضوع", "subject");
$page_rpg->addColumn("توضیحات", "details");
$page_rpg->addColumn("کلمات کلیدی", "keywords");
$page_rpg->addColumn("مسئول اجرا", "fullname");
$col = $page_rpg->addColumn("تاریخ پیگیری", "FollowUpDate");
$col->type = "date";
$page_rpg->addColumn("وضعیت", "RecordStatus", "statusRender");

function MakeWhere(&$where, &$whereParam){

	foreach($_POST as $key => $value)
	{
		if($key == "excel" || $key == "OrderBy" || $key == "OrderByDirection" || 
				$value === "" || strpos($key, "combobox") !== false || strpos($key, "rpcmp") !== false ||
				strpos($key, "reportcolumn_fld") !== false || strpos($key, "reportcolumn_ord") !== false)
			continue;
		
		$prefix = "mr.";
		
		if($key == "fromFollowUpDate" || $key == "toFollowUpDate")
			$value = DateModules::shamsi_to_miladi($value, "-");
		if($key == "MeetingType" )
			$prefix = "m.";
		
		if(strpos($key, "from") === 0)
		{
			$where .= " AND " . $prefix . substr($key,4) . " >= :$key";
			$whereParam[":$key"] = $value;
			continue;
		}
		else if(strpos($key, "to") === 0)
		{
			$where .= " AND " . $prefix . substr($key,2) . " <= :$key";
			$whereParam[":$key"] = $value;
			continue;
		}
		else
			$where .= " AND " . $prefix . $key . " like :$key";
		$whereParam[":$key"] = "%" . $value . "%";
	}
}	

function GetData(){
	$where = "";
	$whereParam = array();
	$userFields = ReportGenerator::UserDefinedFields();
	MakeWhere($where, $whereParam);
	
	$query = "select mr.*,m.MeetingNo, b.InfoDesc MeetingTypeDesc,
		concat_ws(' ',fname,lname,CompanyName) fullname" . 
		($userFields != "" ? "," . $userFields : "")."
			from MTG_MeetingRecords mr 
			join MTG_meetings m using(meetingID)
			join BaseInfo b on(m.MeetingType=InfoID and TypeID=".TYPEID_MeetingType.")
			left join BSC_persons p using(PersonID)
			where 1=1 " . $where ;
	
	$group = ReportGenerator::GetSelectedColumnsStr();
	$query .= $group == "" ? " " : " group by " . $group;
	$query .= $group == "" ? " order by FollowUpDate" : " order by " . $group;
	
	$dataTable = PdoDataAccess::runquery_fetchMode($query, $whereParam);
	
	if($_SESSION["USER"]["UserName"] == "admin")
	{
		//echo PdoDataAccess::GetLatestQueryString();
		print_r(ExceptionHandler::PopAllExceptions());
	}
	return $dataTable;
}
	/**/
function ListDate($IsDashboard = false){
	
	$rpg = new ReportGenerator();
	$rpg->excel = !empty($_POST["excel"]);
	$rpg->mysql_resource = GetData();
	
	if($_SESSION["USER"]["UserName"] == "admin")
		echo PdoDataAccess::GetLatestQueryString ();
		
	$rpg->addColumn("نوع جلسه", "MeetingTypeDesc");
	$rpg->addColumn("شماره جلسه", "MeetingNo");
	$rpg->addColumn("موضوع", "subject");
	$rpg->addColumn("توضیحات", "details");
	$rpg->addColumn("کلمات کلیدی", "keywords");
	$rpg->addColumn("مسئول اجرا", "fullname");
	$rpg->addColumn("تاریخ پیگیری", "FollowUpDate", "ReportDateRender");
	$rpg->addColumn("وضعیت", "RecordStatus", "statusRender");
	
	if(!$rpg->excel && !$IsDashboard)
	{
		BeginReport();
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
					گزارش مصوبات
				</td>
				<td width='200px' align='center' style='font-family:tahoma;font-size:11px'>تاریخ تهیه گزارش : " 
			. DateModules::shNow() . "<br>";
		if(!empty($_POST["fromFollowUpDate"]))
		{
			echo "<br>گزارش از تاریخ : " . $_POST["fromFollowUpDate"];
		}
		if(!empty($_POST["toFollowUpDate"]))
		{
			echo "<br>گزارش تا تاریخ : " . $_POST["toFollowUpDate"];
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
MeetingReport_records.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

MeetingReport_records.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "records.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function MeetingReport_records()
{		
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :2
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش مصوبات",
		width : 700,
		defaults : {
			width : 300
		},
		items :[{
			xtype : "combo",
			hiddenName : "MeetingType",
			store: new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: this.address_prefix + '../meeting.data.php?task=selectMeetingTypes',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['InfoID','InfoDesc'],
				autoLoad : true
			}),
			fieldLabel : "نوع جلسه",
			queryMode : "local",
			displayField: 'InfoDesc',
			valueField : "InfoID"
		},{
			xtype : "combo",
			hiddenName : "PersonID",
			fieldLabel : "مسئول اجرا",
			store: new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: '/framework/person/persons.data.php?task=selectPersons&UserType=IsStaff',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['PersonID','fullname']
			}),
			displayField: 'fullname',
			valueField : "PersonID"
		},{
			xtype : "textfield",
			name : "subject",
			fieldLabel : "موضوع"
		},{
			xtype : "textfield",
			name : "keywords",
			fieldLabel : "کلمات کلیدی"
		},{
			xtype : "textfield",
			name : "details",
			colspan : 2,
			fieldLabel : "شرح مصوبه"
		},{
			xtype : "shdatefield",
			name : "fromFollowUpDate",
			fieldLabel : "تاریخ پیگیری از"
		},{
			xtype : "shdatefield",
			name : "toFollowUpDate",
			fieldLabel : "تا تاریخ"
		},{
			xtype : "combo",
			hiddenName : "RecordStatus",
			fieldLabel : "وضعیت",
			colspan : 2,
			store : new Ext.data.SimpleStore({
				data : [
					['CUR' , "جاری" ],
					['END' , "مختومه" ],
					['REF' , "ارجاعی" ]
				],
				fields : ['id','value']
			}),
			displayField : "value",
			valueField : "id"
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
					MeetingReport_recordsObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				MeetingReport_recordsObj.formPanel.getForm().reset();
				MeetingReport_recordsObj.get("mainForm").reset();
			}			
		}]
	});
	
	this.formPanel.getEl().addKeyListener(Ext.EventObject.ENTER, function(keynumber,e){
		
		MeetingReport_recordsObj.showReport();
		e.preventDefault();
		e.stopEvent();
		return false;
	});
}

MeetingReport_recordsObj = new MeetingReport_records();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>