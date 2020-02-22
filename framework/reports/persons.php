<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	96.05
//-------------------------
require_once '../../header.inc.php';
require_once "ReportGenerator.class.php";

function RealRender($row, $value){
	return $value == "YES" ? "حقیقی" : "حقوقی";
}

$page_rpg = new ReportGenerator("mainForm","BSC_PersonReportObj");
$page_rpg->addColumn("نام و نام خانوادگی/شرکت", "fullname");
$page_rpg->addColumn("نوع", "IsReal", "RealRender");
$page_rpg->addColumn("کدملی/شناسه ملی", "NationalID");
$page_rpg->addColumn("نام پدر", "FatherName");
$page_rpg->addColumn("شماره شناسنامه", "ShNo");
$page_rpg->addColumn("تلفن", "PhoneNo");
$page_rpg->addColumn("همراه", "mobile");
$page_rpg->addColumn("شماره دورنگار", "fax");
$page_rpg->addColumn("آدرس", "address");
$page_rpg->addColumn("ایمیل", "email");
$page_rpg->addColumn("وب سایت", "WebSite");
$page_rpg->addColumn("کد اقتصادی", "NationalID");

$page_rpg->addColumn("شماره ثبت", "RegNo");
$col = $page_rpg->addColumn("تاریخ ثبت", "RegDate");
$col->type = "date";
$page_rpg->addColumn("محل ثبت", "RegPlace");
$page_rpg->addColumn("نوع شرکت", "CompanyTypeDesc");
$page_rpg->addColumn("شماره شبا", "AccountNo");
$page_rpg->addColumn("حوزه فعالیت", "DomainDesc");

$page_rpg->addColumn("مشتری", "IsCustomer", 'ReportTickRender');
$page_rpg->addColumn("سهامدار", "IsShareholder", 'ReportTickRender');
$page_rpg->addColumn("سرمایه گذار", "IsAgent", 'ReportTickRender');
$page_rpg->addColumn("حامی", "IsSupporter", 'ReportTickRender');
$page_rpg->addColumn("همکاران صندوق", "IsStaff", 'ReportTickRender');
$page_rpg->addColumn("کارشناس خارج از صندوق", "IsExpert", 'ReportTickRender');

function MakeWhere(&$where, &$whereParam){
		
		foreach($_POST as $key => $value)
		{
			if($key == "excel" || $key == "OrderBy" || $key == "OrderByDirection" || 
					$value === "" || strpos($key, "combobox") !== false || strpos($key, "rpcmp") !== false ||
					strpos($key, "reportcolumn_fld") !== false || strpos($key, "reportcolumn_ord") !== false)
				continue;
			$prefix = "";
			$where .= " AND " . $prefix . $key . " = :$key";
			$whereParam[":$key"] = $value;
		}
	}	

function GetData(){
	$where = "";
	$whereParam = array();
	$userFields = ReportGenerator::UserDefinedFields();
	$userFields = str_replace("(fullname)", "(concat_ws(' ',fname,lname,CompanyName))", $userFields);
	MakeWhere($where, $whereParam);
	
	$query = "select p.*, concat_ws(' ',fname,lname,CompanyName) fullname,
				b1.InfoDesc CompanyTypeDesc,
				d.DomainDesc				
				".
				($userFields != "" ? "," . $userFields : "")."
				
			from BSC_persons p
			left join BaseInfo b1 on(b1.typeID=14 and b1.InfoID=CompanyType)
			left join BSC_ActDomain d using(DomainID)
			where 1=1 " . $where ;
	
	$group = ReportGenerator::GetSelectedColumnsStr();
	$query .= $group == "" ? " " : " group by " . $group;
	$query .= $group == "" ? " order by fullname" : " order by " . $group;
	
	$dataTable = PdoDataAccess::runquery_fetchMode($query, $whereParam);
	$query = PdoDataAccess::GetLatestQueryString();
	print_r(ExceptionHandler::PopAllExceptions());
	
	return $dataTable;
}
	
function ListDate($IsDashboard = false){
	
	$rpg = new ReportGenerator();
	$rpg->excel = !empty($_POST["excel"]);
	$rpg->mysql_resource = GetData();
	
	$rpg->addColumn("نام و نام خانوادگی/شرکت", "fullname");
	$rpg->addColumn("نوع", "IsReal", "RealRender");
	$rpg->addColumn("کدملی/شناسه ملی", "NationalID");
	$rpg->addColumn("نام پدر", "FatherName");
	$rpg->addColumn("شماره شناسنامه", "ShNo");
	$rpg->addColumn("تلفن", "PhoneNo");
	$rpg->addColumn("همراه", "mobile");
	$rpg->addColumn("شماره دورنگار", "fax");
	$rpg->addColumn("آدرس", "address");
	$rpg->addColumn("ایمیل", "email");
	$rpg->addColumn("وب سایت", "WebSite");
	$rpg->addColumn("کد اقتصادی", "NationalID");
	$rpg->addColumn("شماره ثبت", "RegNo");
	$rpg->addColumn("تاریخ ثبت", "RegDate", "ReportDateRender");
	$rpg->addColumn("محل ثبت", "RegPlace");
	$rpg->addColumn("نوع شرکت", "CompanyTypeDesc");
	$rpg->addColumn("شماره شبا", "AccountNo");
	$rpg->addColumn("حوزه فعالیت", "DomainDesc");
	$rpg->addColumn("مشتری", "IsCustomer", 'ReportTickRender');
	$rpg->addColumn("سهامدار", "IsShareholder", 'ReportTickRender');
	$rpg->addColumn("سرمایه گذار", "IsAgent", 'ReportTickRender');
	$rpg->addColumn("حامی", "IsSupporter", 'ReportTickRender');
	$rpg->addColumn("همکاران صندوق", "IsStaff", 'ReportTickRender');
	$rpg->addColumn("کارشناس خارج از صندوق", "IsExpert", 'ReportTickRender');
	
	if(!$rpg->excel && !$IsDashboard)
	{
		BeginReport();
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
					گزارش ذینفعان
				</td>
				<td width='200px' align='center' style='font-family:tahoma;font-size:11px'>تاریخ تهیه گزارش : " 
			. DateModules::shNow() . "<br>";
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
BSC_PersonReport.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

BSC_PersonReport.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "persons.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function BSC_PersonReport()
{		
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :2
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش ذینفعان ",
		width : 800,
		items :[{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../person/persons.data.php?' +
						"task=selectPersons",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['PersonID','fullname']
			}),
			fieldLabel : "ذینفع",
			pageSize : 25,
			width : 370,
			displayField : "fullname",
			valueField : "PersonID",
			hiddenName : "ReqPersonID",
			listeners :{
				select : function(record){
					el = BSC_PersonReportObj.formPanel.down("[itemId=cmp_subAgent]");
					el.getStore().proxy.extraParams["PersonID"] = this.getValue();
					el.getStore().load();
				}
			}
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				data : [
					['YES' , "حقیقی" ],
					['NO' , "حقوقی" ]
				],
				fields : ['id','value']
			}),
			fieldLabel : "نوع ذینفع",
			displayField : "value",
			valueField : "id",
			hiddenName : "IsReal"
		},{
			xtype : "fieldset",
			colspan : 2,
			title : " ذینفع",
			items :[{
				xtype : "container",
				html : "<input type=checkbox name=IsStaff value='YES'>همکاران صندوق" + "&nbsp;&nbsp;" +
					"<input type=checkbox name=IsCustomer value='YES'>مشتری " + "&nbsp;&nbsp;" +
					"<input type=checkbox name=IsShareholder value='YES'>سهامدار " + "&nbsp;&nbsp;" +
					"<input type=checkbox name=IsAgent value='YES'>سرمایه گذار " + "&nbsp;&nbsp;" +
					"<input type=checkbox name=IsSupporter value='YES'>حامی " + "&nbsp;&nbsp;" +
					"<input type=checkbox name=IsExpert value='YES'>کارشناس خارج از صندوق " 
			}]
		},{
			xtype : "trigger",
			fieldLabel: 'حوزه فعالیت',
			name: 'DomainDesc',	
			triggerCls:'x-form-search-trigger',
			onTriggerClick : function(){
				BSC_PersonReportObj.ActDomainLOV();
			}
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../person/persons.data.php?task=selectCompanyTypes',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['InfoID','InfoDesc'],
				autoLoad : true					
			}),
			displayField : "InfoDesc",
			valueField : "InfoID",
			queryMode : "local",
			fieldLabel: 'نوع شرکت',
			hiddenName: 'CompanyType'
		},{
			xtype : "fieldset",
			colspan :2,
			title : "ستونهای گزارش",
			items :[<?= $page_rpg->ReportColumns() ?>]
		},{
			xtype : "fieldset",
			colspan :2,
			title : "رسم نمودار",
			items : [<?= $page_rpg->GetChartItems("BSC_PersonReportObj","mainForm","persons.php") ?>]
		},{
			xtype : "hidden",
			name : "DomainID",
			colspan : 2
		}],
		buttons : [{
			text : "گزارش ساز",
			iconCls : "db",
			handler : function(){ReportGenerator.ShowReportDB(
						BSC_PersonReportObj, 
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
					BSC_PersonReportObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				BSC_PersonReportObj.formPanel.getForm().reset();
				BSC_PersonReportObj.get("mainForm").reset();
			}			
		}]
	});
	
	this.formPanel.getEl().addKeyListener(Ext.EventObject.ENTER, function(keynumber,e){
		
		BSC_PersonReportObj.showReport();
		e.preventDefault();
		e.stopEvent();
		return false;
	});
}

BSC_PersonReport.prototype.ActDomainLOV = function(){
		
	if(!this.DomainWin)
	{
		this.DomainWin = new Ext.window.Window({
			autoScroll : true,
			width : 420,
			height : 420,
			title : "حوزه فعالیت",
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "../baseInfo/ActDomain.php?mode=adding",
				scripts : true
			}
		});
		
		Ext.getCmp(this.TabID).add(this.DomainWin);
	}
	
	this.DomainWin.show();
	
	this.DomainWin.loader.load({
		params : {
			ExtTabID : this.DomainWin.getEl().dom.id,
			parent : "BSC_PersonReportObj.DomainWin",
			selectHandler : function(id, name){
				BSC_PersonReportObj.formPanel.down("[name=DomainDesc]").setValue(name);
				BSC_PersonReportObj.formPanel.down("[name=DomainID]").setValue(id);
			}
		}
	});
}

BSC_PersonReportObj = new BSC_PersonReport();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>