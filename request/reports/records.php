<?php

ini_set("display_errors", "On");
require_once '../header.inc.php';
/*require_once '../Request.data.php';
require_once '../Request.class.php';*/
require_once "ReportGenerator.class.php";

function statusRender($row,$value){
	switch($value)
	{
		case "1": return "ضعیف";
		case "2": return "متوسط";
		case "3": return "خوب";
		case "4": return "عالی";
	}
}
function IsRegisterRender($row,$value){
    switch($value)
    {
        case "Yes": return "مشتری";
        case "No": return "متقاضی";
    }
}
function IsPresentRender($row,$value){
    switch($value)
    {
        case "Yes": return "حضوری";
        case "No": return "غیرحضوری";
    }
}
function IsInfoORServiceRender($row,$value){
    switch($value)
    {
        case "Service": return "خدمت";
        case "Info": return "اطلاعات";
    }
}
function IsRelatedRender($row,$value){
    switch($value)
    {
        case "Yes": return "بله";
        case "No": return "خیر";
    }
}



$page_rpg = new ReportGenerator("mainForm","RequestReport_recordsObj");
$page_rpg->addColumn("نام ذینفع", "fullname");
$page_rpg->addColumn("نام متقاضی", "askerName");
/*$page_rpg->addColumn("شماره جلسه", "MeetingNo");
$page_rpg->addColumn("موضوع", "subject");
$page_rpg->addColumn("توضیحات", "details");
$page_rpg->addColumn("کلمات کلیدی", "keywords");
$page_rpg->addColumn("مسئول اجرا", "fullname");*/
$col = $page_rpg->addColumn("تاریخ مراجعه", "referalDate");
$col->type = "date";
$col = $page_rpg->addColumn("ساعت مراجعه", "referalTime");

$page_rpg->addColumn("میزان رضایت", "Poll", "statusRender");

function MakeWhere(&$where, &$whereParam){

	foreach($_POST as $key => $value)
	{
		if($key == "excel" || $key == "OrderBy" || $key == "OrderByDirection" || 
				$value === "" || strpos($key, "combobox") !== false || strpos($key, "rpcmp") !== false ||
				strpos($key, "reportcolumn_fld") !== false || strpos($key, "reportcolumn_ord") !== false)
			continue;
		
		$prefix = "mr.";

		if($key == "fromReferalDate" || $key == "toReferalDate")
			$value = DateModules::shamsi_to_miladi($value, "-");
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
		$whereParam[":$key"] = "%" . $value . "%";
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
    $query = " select mr.*,askerName,askerMob,
		concat_ws(' ',fname,lname,CompanyName) fullname" .
        ($userFields != "" ? "," . $userFields : "")."
			from request mr 
			left join BSC_persons p using(PersonID)
			left join askerperson a using(askerID)
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

	$rpg->addColumn("PID", "IDReq");
	$rpg->addColumn("نام ذینفع", "fullname");
	$rpg->addColumn("نام متقاضی", "askerName");
	$rpg->addColumn("تاریخ مراجعه", "referalDate", "ReportDateRender");
    $col = $rpg->addColumn("ساعت مراجعه", "referalTime");
    $col->ExcelRender = false;
    $rpg->addColumn("نظرسنجی", "Poll", "statusRender");
	
	if(!$rpg->excel && !$IsDashboard)
	{
	    BeginReport();
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
					گزارش درخواست ها
				</td>
				<td width='200px' align='center' style='font-family:tahoma;font-size:11px'>تاریخ تهیه گزارش : " 
			. DateModules::shNow() . "<br>";
		if(!empty($_POST["fromReferalDate"]))
		{
			echo "<br>گزارش از تاریخ : " . $_POST["fromReferalDate"];
		}
		if(!empty($_POST["toReferalDate"]))
		{
			echo "<br>گزارش تا تاریخ : " . $_POST["toReferalDate"];
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
RequestReport_records.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

RequestReport_records.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "records.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function RequestReport_records()
{		
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :2
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش درخواست ها",
		width : 700,
		defaults : {
			width : 300
		},
		items :[{
            xtype : "combo",
            hiddenName : "IsRegister",
            fieldLabel : "نوع متقاضی",
            /*colspan : 2,*/
            store : new Ext.data.SimpleStore({
                data : [
                    ['Yes' , "مشتری" ],
                    ['No' , "متقاضی" ]
                ],
                fields : ['id','value']
            }),
            displayField : "value",
            valueField : "id"
        },
            {
                xtype : "combo",
                hiddenName : "PersonID",
                store : new Ext.data.SimpleStore({
                    proxy: {
                        type: 'jsonp',
                        url: this.address_prefix + '../../framework/person/persons.data.php?' +
                            "task=selectPersons&UserType=IsCustomer",
                        reader: {root: 'rows',totalProperty: 'totalCount'}
                    },
                    fields : ['PersonID','fullname']
                }),
                fieldLabel : "نام ذینفع",
                displayField : "fullname",
                pageSize : 20,
                valueField : "PersonID"

            }/*,{
                xtype : "combo",
                name : "fullasker",
                store : new Ext.data.SimpleStore({
                    proxy: {
                        type: 'jsonp',
                        url: this.address_prefix + '../Request.data.php?task=selectAskerss',
                        reader: {root: 'rows',totalProperty: 'totalCount'}
                    },
                    fields : ['askerID','askerName']
                }),
                fieldLabel : "نام متقاضی",
                displayField : "askerName",
                pageSize : 20,
                width : 250,
                valueField : "askerID",
            }*/,{
                xtype : "combo",
                /*readOnly : readOnly,*/
                hiddenName : "askerID",
                store: new Ext.data.Store({
                    proxy:{
                        type: 'jsonp',
                        url: this.address_prefix + '../Request.data.php?task=selectAskers',
                        reader: {root: 'rows',totalProperty: 'totalCount'}
                    },
                    fields :  ['askerID','askerName','askerMob'],
                    autoLoad : true
                }),
                fieldLabel : "نام متقاضی",
                queryMode : "local",
                displayField: 'askerName',
                valueField : "askerID"
            },{
                xtype : "combo",
                hiddenName : "IsPresent",
                fieldLabel : "نوع مراجعه",
                /*colspan : 2,*/
                store : new Ext.data.SimpleStore({
                    data : [
                        ['Yes' , "حضوری" ],
                        ['No' , "غیرحضوری" ]
                    ],
                    fields : ['id','value']
                }),
                displayField : "value",
                valueField : "id"
            },{
                xtype : "shdatefield",
                name : "fromReferalDate",
                fieldLabel : "تاریخ مراجعه از"
            },{
                xtype : "shdatefield",
                name : "toReferalDate",
                fieldLabel : "تا تاریخ"
            },{
                xtype : "numberfield",
                name : "fromReferalTime",
                fieldLabel : "ساعت مراجعه از"
            },{
                xtype : "numberfield",
                name : "toReferalTime",
                fieldLabel : "تا ساعت"
            },{
                xtype : "combo",
                hiddenName : "IsInfoORService",
                fieldLabel : "نوع درخواست",
                /*colspan : 2,*/
                store : new Ext.data.SimpleStore({
                    data : [
                        ['Service' , "خدمت" ],
                        ['Info' , "اطلاعات" ]
                    ],
                    fields : ['id','value']
                }),
                displayField : "value",
                valueField : "id"
            },{
                xtype : "combo",
                store : new Ext.data.SimpleStore({
                    proxy: {
                        type: 'jsonp',
                        url: this.address_prefix +'../../framework/person/persons.data.php?task=selectPersonInfoTypes&TypeID=94&PersonID='+ this.PersonID,
                        reader: {root: 'rows',totalProperty: 'totalCount'}
                    },
                    fields : ['TypeID','InfoID','InfoDesc'],
                    autoLoad : true
                }),
                fieldLabel : "نوع خدمت",
                queryMode : 'local',
                /*allowBlank : false,
                beforeLabelTextTpl: required,*/
                displayField : "InfoDesc",
                valueField : "InfoDesc",
                name : "serviceType"
            },{
                xtype : "combo",
                hiddenName : "IsRelated",
                fieldLabel : "آیا خدمات در حیطه صندوق می باشد",
                colspan : 1,
                store : new Ext.data.SimpleStore({
                    data : [
                        ['Yes' , "بله" ],
                        ['No' , "خیر" ]
                    ],
                    fields : ['id','value']
                }),
                displayField : "value",
                valueField : "id"
            }, {
                xtype : "combo",
                hiddenName : "referPersonID",
                store : new Ext.data.SimpleStore({
                    proxy: {
                        type: 'jsonp',
                        url: this.address_prefix + '../../framework/person/persons.data.php?' +
                            "task=selectPersons&UserType=IsStaff",
                        reader: {root: 'rows',totalProperty: 'totalCount'}
                    },
                    fields : ['PersonID','fullname']
                }),
                fieldLabel : "نام کارشناس ارجاعی",
                displayField : "fullname",
                pageSize : 20,
                valueField : "PersonID"

            },{
                xtype : "combo",
                hiddenName : "Poll",
                fieldLabel : "میزان رضایت از پاسخ دهی",
                colspan : 2,
                store : new Ext.data.SimpleStore({
                    data : [
                        ['1' , "ضعیف" ],
                        ['2' , "متوسط" ],
                        ['3' , "خوب" ],
                        ['4' , "عالی" ]
                    ],
                    fields : ['id','value']
                }),
                displayField : "value",
                valueField : "id"
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
					RequestReport_recordsObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				RequestReport_recordsObj.formPanel.getForm().reset();
				RequestReport_recordsObj.get("mainForm").reset();
			}			
		}]
	});
	
	this.formPanel.getEl().addKeyListener(Ext.EventObject.ENTER, function(keynumber,e){
		
		RequestReport_recordsObj.showReport();
		e.preventDefault();
		e.stopEvent();
		return false;
	});
}

RequestReport_recordsObj = new RequestReport_records();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>

