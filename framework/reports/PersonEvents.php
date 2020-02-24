<?php

require_once '../../header.inc.php';
require_once "ReportGenerator.class.php";

if(isset($_REQUEST["show"]))
{
	$where = "";
	$param = array();
	$param[":p"] = $_POST["PersonID"];
	
	if(!empty($_POST["fromDate"]))
	{
		$where .= " AND EventDate >= :fd";
		$param[":fd"] = DateModules::shamsi_to_miladi($_POST["fromDate"], "-");
	}
	if(!empty($_POST["toDate"]))
	{
		$query .= " AND EventDate <= :td";
		$param[":td"] = DateModules::shamsi_to_miladi($_POST["toDate"], "-");
	}
	
	$query = "
		select 'loan' type, 'رویداد وام' title, RequestID ObjectID, 
			EventTitle description,EventDate,LetterID
		from LON_events join LON_requests using(RequestID)
		where LoanPersonID=:p $where
		
		union all 
		
		select 'package' type, 'رویداد پرونده' title, PackageID ObjectID, 
			EventTitle description,EventDate,LetterID
		from DMS_PackageEvents join DMS_packages using(PackageID)
		where PersonID=:p $where
		";
	
	$dataTable = PdoDataAccess::runquery($query, $param);
	
	$rpg = new ReportGenerator();
	$rpg->excel = !empty($_POST["excel"]);
	$rpg->mysql_resource = $dataTable;
	
	$rpg->addColumn("عنوان", "title");
	$rpg->addColumn("کد آیتم", "ObjectID");
	$rpg->addColumn("شرح", "description");
	$rpg->addColumn("شماره نامه", "LetterID");
	$rpg->addColumn("تاریخ رویداد", "EventDate", "ReportDateRender");
	
	if(!$rpg->excel)
	{
		BeginReport();
		echo "<div style=display:none>" . $query . "</div>";
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
					گزارش  رویدادهای مرتبط با ذینفع
				</td>
				<td width='200px' align='center' style='font-family:tahoma;font-size:11px'>تاریخ تهیه گزارش : " 
			. DateModules::shNow() . "<br>";
		if(!empty($_POST["fromReqDate"]))
		{
			echo "<br>گزارش از تاریخ : " . $_POST["fromDate"] . 
				($_POST["toDate"] != "" ? " - " . $_POST["toDate"] : "");
		}
		echo "</td></tr></table>";
		}
	$rpg->generateReport();
	die();
}
?>
<script>
Report_PersonEvents.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

Report_PersonEvents.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "PersonEvents.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function Report_PersonEvents()
{		
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :2
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش رویدادهای مرتبط با ذینفعان",
		width : 780,
		items :[{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../../framework/person/persons.data.php?task=selectPersons',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['PersonID','fullname']
			}),
			fieldLabel : "ذینفع",
			colspan : 2,
			width : 370,
			displayField : "fullname",
			valueField : "PersonID",
			hiddenName : "PersonID"
		},{
			xtype : "shdatefield",
			name : "fromDate",
			fieldLabel : "از تاریخ"
		},{
			xtype : "shdatefield",
			name : "toDate",
			fieldLabel : "تا تاریخ"
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
					Report_PersonEventsObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				Report_PersonEventsObj.formPanel.getForm().reset();
				Report_PersonEventsObj.get("mainForm").reset();
			}			
		}]
	});
	
	this.formPanel.getEl().addKeyListener(Ext.EventObject.ENTER, function(keynumber,e){
		
		Report_PersonEventsObj.showReport();
		e.preventDefault();
		e.stopEvent();
		return false;
	});
}

Report_PersonEventsObj = new Report_PersonEvents();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>