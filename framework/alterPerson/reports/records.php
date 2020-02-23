<?php

ini_set("display_errors", "On");
require_once '../../../request/header.inc.php';
require_once "ReportGenerator.class.php";


function SexRender($row,$value){
    switch($value)
    {
        case "MALE": return "مرد";
        case "WOMAN": return "زن";
    }
}
function MaritalRender($row,$value){
    switch($value)
    {
        case "Married": return "متاهل";
        case "Single": return "مجرد";
    }
}

$page_rpg = new ReportGenerator("mainForm","AlterPersonReport_recordsObj");

$page_rpg->addColumn("PID","AlterPersonID");
$page_rpg->addColumn("نام و نام خانوادگی","fullname");
$page_rpg->addColumn("کد ملی","NationalID");
$col = $page_rpg->addColumn("تاریخ تولد", "BirthDate");
$col->type = "date";
$col=$page_rpg->addColumn("جنسیت","sex","SexRender");

$page_rpg->addColumn("آخرین مدرک تحصیلی","educationDeg");
$page_rpg->addColumn("سابقه کاری(سال)","WorkExp");
$page_rpg->addColumn("حقوق درخواستی(تومان)","reqWage");



function MakeWhere(&$where, &$whereParam){

	foreach($_POST as $key => $value)
	{
		if($key == "excel" || $key == "OrderBy" || $key == "OrderByDirection" || 
				$value === "" || strpos($key, "combobox") !== false || strpos($key, "rpcmp") !== false ||
				strpos($key, "reportcolumn_fld") !== false || strpos($key, "reportcolumn_ord") !== false)
			continue;
		
		$prefix = "mr.";

		/*if($key == "fromReqWage" || $key == "toReqWage")
			$value = DateModules::shamsi_to_miladi($value, "-");*/
		/*var_dump($value);*/
		/*if($key == "MeetingType" )
			$prefix = "m.";*/
		
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
		$whereParam[":$key"] =  '%'.$value.'%' ;
	}
}	

function GetData(){
	$where = "";
	$whereParam = array();
	$userFields = ReportGenerator::UserDefinedFields();
	MakeWhere($where, $whereParam);
	
	/*$query = "select mr.*,m.MeetingNo, b.InfoDesc MeetingTypeDesc,
		concat_ws(' ',fname,lname,CompanyName) fullname" . 
		($userFields != "" ? "," . $userFields : "")."
			from MTG_MeetingRecords mr 
			join MTG_meetings m using(meetingID)
			join BaseInfo b on(MeetingType=InfoID and TypeID=".TYPEID_MeetingType.")
			left join BSC_persons p using(PersonID)
			where 1=1 " . $where ;*/
    $query = " select mr.* ".($userFields != "" ? "," . $userFields : ""). 
			"from BSC_AlterPersons mr 
			where 1=1 " . $where ;
    /*$query = "select tTable.*, askerName, askerMob from
			(select fTable.*, concat_ws(' ',fname, lname,CompanyName) refername
			 FROM (select f.*,
				concat_ws(' ',fname, lname,CompanyName) fullname 
			from request f 
				left join BSC_persons b using(PersonID)) AS fTable
				left join BSC_persons b ON fTable.referPersonID = b.PersonID) AS tTable
				left join askerPerson a ON tTable.askerID = a.askerID
			where 1=1 " . $where;*/
	
	$group = ReportGenerator::GetSelectedColumnsStr();
	/*$query .= $group == "" ? " " : " group by " . $group;
	$query .= $group == "" ? " order by referalDate" : " order by " . $group;*/
	
	$dataTable = PdoDataAccess::runquery_fetchMode($query, $whereParam);
	/*var_dump($dataTable);*/
	if($_SESSION["USER"]["UserName"] == "admin")
	{
		//echo PdoDataAccess::GetLatestQueryString();
		print_r(ExceptionHandler::PopAllExceptions());
	}
	return $dataTable;
}
	
function ListDate($IsDashboard = false){
	
	$rpg = new ReportGenerator();
	$rpg->excel = !empty($_POST["excel"]);
	$rpg->mysql_resource = GetData();
	
	if($_SESSION["USER"]["UserName"] == "admin")
		echo PdoDataAccess::GetLatestQueryString ();

    $rpg->addColumn("PID","AlterPersonID");
     
    $col = $rpg->addColumn("تاریخ تکمیل درخواست", "fillDate","ReportDateRender");
    $col->type = "date";
    $col->ExcelRender = false;
    
    $rpg->addColumn("نام و نام خانوادگی","fullname");
    $rpg->addColumn("کد ملی","NationalID");
    $rpg->addColumn("تلفن همراه ","mobile");
    
    $col = $rpg->addColumn("تاریخ تولد", "BirthDate","ReportDateRender");
    $col->type = "date";
    $col->ExcelRender = false;
    $rpg->addColumn("جنسیت","sex","SexRender");
    $rpg->addColumn("وضعیت تاهل","marital","MaritalRender");
    
    $rpg->addColumn("آخرین مدرک تحصیلی","educationDeg");
    $rpg->addColumn("حوزه کاری","assistPart");
    $rpg->addColumn("سابقه کاری(سال)","WorkExp");
    $rpg->addColumn("حقوق درخواستی(تومان)","reqWage");
    $col = $rpg->addColumn("تاریخ آمادگی به اشتغال", "readyDate","ReportDateRender");
    $col->type = "date";
    $col->ExcelRender = false;
    $rpg->addColumn("محدوده سکونت","habitRange");
    $rpg->addColumn("نتیجه","result");

	
	if(!$rpg->excel && !$IsDashboard)
	{
	    BeginReport();
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
					گزارش افراد جانشین
				</td>
				<td width='200px' align='center' style='font-family:tahoma;font-size:11px'>تاریخ تهیه گزارش : " 
			. DateModules::shNow() . "<br>";
		/*if(!empty($_POST["fromReqWage"]))
		{
			echo "<br>گزارش از تاریخ : " . $_POST["fromReqWage"];
		}
		if(!empty($_POST["toReqWage"]))
		{
			echo "<br>گزارش تا تاریخ : " . $_POST["toReqWage"];
		}*/
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
AlterPersonReport_records.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

AlterPersonReport_records.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "records.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function AlterPersonReport_records()
{		
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :2
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش افراد جانشین",
		width : 700,
		defaults : {
			width : 300
		},
		items :[{
            xtype : "combo",
            hiddenName : "sex",
            fieldLabel : "جنسیت",
            /*colspan : 2,*/
            store : new Ext.data.SimpleStore({
                data : [
                    ["MALE" , "مرد" ],
                    ["WOMAN" , "زن" ]
                ],
                fields : ['id','value']
            }),
            displayField : "value",
            valueField : "id"
        }, {
            xtype: "textfield",
            fieldLabel: "آخرین مدرک تحصیلی",
            name: "educationDeg"
        },{
            xtype : "textfield",
            name : "fromWorkExp",
            fieldLabel : " سوابق کاری از"
        },{
            xtype : "textfield",
            name : "toWorkExp",
            fieldLabel : " سوابق کاری تا"
        },{
            xtype : "textfield",
            name : "fromReqWage",
            fieldLabel : "حقوق دریافتی از"
        },{
            xtype : "textfield",
            name : "toReqWage",
            fieldLabel : "تا مبلغ"
        },{
            xtype : "textfield",
            fieldLabel : "درخواست همکاری در قسمت",
            name : "assistPart"
        }

            /*,{
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
			fieldLabel : "تاریخ مراجعه از"
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
		}*/],
		buttons : [{
			text : "مشاهده گزارش",
			handler : Ext.bind(this.showReport,this),
			iconCls : "report"
		},{
			text : "خروجی excel",
			handler : Ext.bind(this.showReport,this),
			listeners : {
				click : function(){
					AlterPersonReport_recordsObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				AlterPersonReport_recordsObj.formPanel.getForm().reset();
				AlterPersonReport_recordsObj.get("mainForm").reset();
			}			
		}]
	});
	
	this.formPanel.getEl().addKeyListener(Ext.EventObject.ENTER, function(keynumber,e){
		
		AlterPersonReport_recordsObj.showReport();
		e.preventDefault();
		e.stopEvent();
		return false;
	});
}

AlterPersonReport_recordsObj = new AlterPersonReport_records();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>

