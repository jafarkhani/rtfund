<?php
require_once '../../header.inc.php';
require_once "ReportGenerator.class.php";

function statusRender($row, $value) {
	switch ($value) {
		case "CUR": return "جاری";
		case "END": return "مختومه";
		case "REF": return "ارجاعی";
	}
}

$page_rpg = new ReportGenerator("mainForm", "ProcessReport_recordsObj");
$page_rpg->addColumn("نوع جلسه", "MeetingTypeDesc");
$page_rpg->addColumn("شماره جلسه", "MeetingNo");
$page_rpg->addColumn("موضوع", "subject");
$page_rpg->addColumn("توضیحات", "details");
$page_rpg->addColumn("کلمات کلیدی", "keywords");
$page_rpg->addColumn("مسئول اجرا", "fullname");
$col = $page_rpg->addColumn("تاریخ پیگیری", "FollowUpDate");
$col->type = "date";
$page_rpg->addColumn("وضعیت", "RecordStatus", "statusRender");

function GetData() {

	//............................Make Where ..........................
	$where = "";
	$whereParam = array();
		
	if (!empty($_POST["SubProcessID"])  ) {
		//.................. secure section .....................
		InputValidation::validate($_REQUEST['SubProcessID'], InputValidation::Pattern_Num);

		if(!empty($_POST["ProcessStatus"]) && $_POST["ProcessStatus"] != 1)
		{
			$where .= " AND f.FlowID = :fId ";
			$param[":fId"] = $_POST['SubProcessID'];
		}
	}
	
	if (!empty($_POST["ProcessStatus"])) {
		//.................. secure section .....................
		InputValidation::validate($_REQUEST['ProcessStatus'], InputValidation::Pattern_Num);
				
		if( $_POST["ProcessStatus"] == 1 ) // خام
			$where .= " AND li.IID IS NULL ";
				
		elseif( $_POST["ProcessStatus"] == 2 )  // ارسال شده
			$where .= " AND f.IsLastRow= 'YES' AND f.StepRowID =  383 "; 
		
		elseif( $_POST["ProcessStatus"] == 3 )  // جاری
			$where .= " AND f.IsLastRow= 'YES' AND f.IsEnded = 'NO' "; 
		
		elseif( $_POST["ProcessStatus"] == 4 ) // خاتمه یافته
			$where .= " AND f.IsLastRow= 'YES' AND f.IsEnded = 'YES' ";
		
	}


	if (!empty($_POST["FStartDate"])) {

		$where .= " AND bc.ReqDate >= :FSD ";
		$param[":FSD"] = DateModules::shamsi_to_miladi($_POST['FStartDate']);
	}

	if (!empty($_POST["TStartDate"])) {

		$where .= " AND bc.ReqDate <= :TSD ";
		$param[":TSD"] = DateModules::shamsi_to_miladi($_POST['TStartDate']);
	}

	if (!empty($_POST["FEndDate"])) {

		$where .= " AND f.ActionDate >= :FED ";
		$param[":FED"] = DateModules::shamsi_to_miladi($_POST['FEndDate']);
	}

	if (!empty($_POST["TEndDate"])) {

		$where .= " AND f.ActionDate <= :TED ";
		$param[":TED"] = DateModules::shamsi_to_miladi($_POST['TEndDate']);
	}

	if (!empty($_POST["PersonID2"])) {

		$where .= " AND fs.PersonID = :NPID ";
		$param[":NPID"] = DateModules::shamsi_to_miladi($_POST['PersonID2']);
	}

	if (!empty($_POST["PersonID"])) {

		$where .= " AND f.PersonID <= :PID ";
		$param[":PID"] = DateModules::shamsi_to_miladi($_POST['PersonID']);
	}

	if (!empty($_POST["PersonID"])) {

		$where .= " AND f.PersonID <= :PID ";
		$param[":PID"] = DateModules::shamsi_to_miladi($_POST['PersonID']);
	}

	if (!empty($_POST["TotalDuration"])) {

		$where .= " AND DATEDIFF(f.ActionDate,t.ActionDate) = :DD ";
		$param[":DD"] = $_POST["TotalDuration"];
	}
		
	if (!empty($_POST["ReturnStep"]) && $_POST["ReturnStep"] > 0 ) {
		$where .= " AND f.StepRowID= :RID ";
		$param[":RID"] = $_POST["ReturnStep"] ;
	}
		
	
	$SelectClause = "";
	$JoinClause = "";
	if (!empty($_POST["StepDuration"])) {

		$SelectClause = " , tmp.* , fl.StepDesc,  concat_ws(' ',fname,lname,CompanyName) fullname ";
		$JoinClause = " 
						left join
										(
						SELECT
							t1.ObjectID,
							t1.RowID,
							t1.StepRowID,
							t1.PersonID ,
							t1.ActionDate,
							t1.ObjectID2,
							t1.ObjectID2 - COALESCE(t2.ObjectID2, t1.ObjectID2) AS diff ,
							TIMESTAMPDIFF(SECOND, t2.ActionDate, t1.ActionDate)/60 StepDuration

						FROM WFM_FlowRows t1
						LEFT JOIN WFM_FlowRows t2
							ON t1.RowID = t2.RowID + 1 and t1.ObjectID = t2.ObjectID and t1.FlowID = t2.FlowID

						where   t1.FlowID = 95

						ORDER BY t1.ObjectID ,
								 ActionDate DESC

								 ) tmp on tbl1.ObjectID = tmp.ObjectID

								 inner join WFM_FlowSteps fl on fl.StepRowID = tmp.StepRowID
								 inner join BSC_persons pr on tmp.PersonID = pr.PersonID
						 ";

		$where .= " AND DATEDIFF(f.ActionDate,t.ActionDate) = :DD ";
		$param[":DD"] = $_POST["StepDuration"];
	}

	//............................query....................................

	$query = "  select tbl1.* , 'ارزیابی و اعتبار ضمانتنامه' ProcessName, 
					   if(IsEnded = 'YES' , 'خاتمه یافته' , 'جاری' ) ProcessStatus $SelectClause
					   
				from (

				SELECT distinct f.FlowID ,f.RowID , bc.BID , f.ObjectID , bc.ReqDate Sdate, f.ActionDate Edate, 
								f.IsEnded, ( TIMESTAMPDIFF(SECOND, bc.ReqDate ,f.ActionDate) / 60 ) duration ,
								f.PersonID p1 , fs.PersonID nextConf , fs.PostID ,
								concat_ws(' ',bp.fname,bp.lname,bp.CompanyName) CustomerName ,
								
								concat_ws(' ',p.fname,p.lname,p.CompanyName,p3.fname,p3.lname,p3.CompanyName) nextConfFullname , 
								
								concat_ws(' ',p2.fname,p2.lname,p2.CompanyName) PFullname

								FROM LON_BailCondition bc  
										LEFT join LON_IssuanceInfo li on bc.BID  = li.BID
										LEFT join WFM_FlowRows f on f.ObjectID = li.IID 
				
								inner join BSC_persons bp on(bp.PersonID=bc.PersonID)
								left join WFM_FlowSteps fs on fs.FlowID = f.FlowID and fs.StepParentID = f.StepRowID
								left join WFM_FlowSteps fs2 on fs2.FlowID = f.FlowID and fs2.StepRowID = f.StepRowID
								left join WFM_FlowSteps fs3 on fs2.FlowID = f.FlowID and fs2.ReturnStep = fs3.StepRowID

								left join BSC_persons p on fs.PersonID = p.PersonID
								left join BSC_persons p2 on f.PersonID = p2.PersonID
								left join BSC_persons p3 on fs3.PersonID = p3.PersonID
								
								where (1=1) $where   /*AND f.ObjectID = 57*/

								ORDER BY f.ObjectID desc,f.ActionDate desc

				) tbl1
				$JoinClause
				/*where tbl1.FlowID = 95  AND tbl1.ObjectID = 57*/
                
                order by  tbl1.ObjectID  ";

	//..................................................................

	$dataTable = PdoDataAccess::runquery_fetchMode($query, $param);
	
	if ($_SESSION["USER"]["UserName"] == "admin") {
		//echo PdoDataAccess::GetLatestQueryString();
		//print_r(ExceptionHandler::PopAllExceptions());
	}
 
	return $dataTable;
}

function ListDate($IsDashboard = false) {

	$rpg = new ReportGenerator();
	$rpg->excel = !empty($_POST["excel"]);
	$rpg->mysql_resource = GetData();
	
	$headerTitle  = "" ; 
	
	if (!empty($_POST["ProcessID"])) { 
		$headerTitle  .= "&nbsp;"." ارزیابی و اعتبار سنجی" ; 
	}
	if (!empty($_POST["SubProcessID"])) { 
		$headerTitle  .= "&nbsp;"." زیر فرایند ارزیابی ضمانت نامه" ; 
	}
	

	/* 	if ($_SESSION["USER"]["UserName"] == "admin")
	  echo PdoDataAccess::GetLatestQueryString();

	  die(); */

	function TimeRender($row, $value) {

		if (($value / 60) >= 24) {
			$day = floor(($value / 60) / 24);
			$hr = round(($value - ($day * 24 * 60 )) / 60) ;
			$min = round(( ($value - ($day * 24 * 60 )) - $hr * 60 )) ; 
		}

		if (!empty($value))
			return $day . "&nbsp; روز" ."&nbsp;".$hr." &nbsp; ساعت"."&nbsp;". $min ."&nbsp;". "دقیقه &nbsp; ";
		else
			return "";
	}

	if (!empty($_POST["StepDuration"])) {
		$rpg->addColumn("نام فرآیند", "ProcessName");
		$rpg->addColumn("مرحله", "StepDesc");
		$rpg->addColumn("فرد اقدام کننده", "fullname");
		$rpg->addColumn("مدت زمان اقدام", "StepDuration", "TimeRender");
	} else {
		if (empty($_POST["SubProcessID"]))  
			$rpg->addColumn("نام زیرفرآیند", "ProcessName");
		
		$rpg->addColumn("کد زیر فرآیند", "BID"); 
		$rpg->addColumn(" مشتری حقیقی/حقوقی", "CustomerName"); 
		$rpg->addColumn("وضعیت فرآیند", "ProcessStatus");
		$rpg->addColumn("تاریخ شروع گردش", "Sdate", "ReportDateRender");
		$rpg->addColumn("تاریخ پایان گردش", "Edate", "ReportDateRender");
		$rpg->addColumn("مدت زمان گردش", "duration","TimeRender");
		
		if( !empty($_POST["ProcessStatus"]) && $_POST["ProcessStatus"] != 4 )
			$rpg->addColumn("منتظر تایید/بررسی", "nextConfFullname");
		
		$rpg->addColumn("آخرین  تایید کننده", "PFullname");
	}

	if (!$rpg->excel && !$IsDashboard) {
		BeginReport();
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
					گزارش فرایند   "."<br>". $headerTitle ." 
				</td>
				<td width='200px' align='center' style='font-family:tahoma;font-size:11px'>تاریخ تهیه گزارش : "
		. DateModules::shNow() . "<br>";
		if (!empty($_POST["fromFollowUpDate"])) {
			echo "<br>گزارش از تاریخ : " . $_POST["fromFollowUpDate"];
		}
		if (!empty($_POST["toFollowUpDate"])) {
			echo "<br>گزارش تا تاریخ : " . $_POST["toFollowUpDate"];
		}
		echo "</td></tr></table>";
	}
	if ($IsDashboard) {
		echo "<div style=direction:rtl;padding-right:10px>";
		$rpg->generateReport();
		echo "</div>";
	} else
		$rpg->generateReport();
	die();
}

if (isset($_REQUEST["show"])) {
	
	ListDate();
}

if (isset($_REQUEST["rpcmp_chart"])) {
	$page_rpg->mysql_resource = GetData();
	$page_rpg->GenerateChart();
	die();
}

if (isset($_REQUEST["dashboard_show"])) {
	$chart = ReportGenerator::DashboardSetParams($_REQUEST["rpcmp_ReportID"]);
	if (!$chart)
		ListDate(true);

	$page_rpg->mysql_resource = GetData();
	$page_rpg->GenerateChart(false, $_REQUEST["rpcmp_ReportID"]);
	die();
}
?>
<script>
    ProcessReport_records.prototype = {
        TabID: '<?= $_REQUEST["ExtTabID"] ?>',
        address_prefix: "<?= $js_prefix_address ?>",
        get: function (elementID) {
            return findChild(this.TabID, elementID);
        }
    }

    ProcessReport_records.prototype.showReport = function (btn, e)
    {
        this.form = this.get("mainForm")
        this.form.target = "_blank";
        this.form.method = "POST";
        this.form.action = this.address_prefix + "FlowReport.php?show=true";
        this.form.submit();
        this.get("excel").value = "";
        return;
    }

    function ProcessReport_records()
    {

        this.PersonStore = new Ext.data.Store({
            proxy: {
                type: 'jsonp',
                url: this.address_prefix + '../../framework/person/persons.data.php?task=selectPersons' +
                        '&UserType=IsStaff',
                reader: {root: 'rows', totalProperty: 'totalCount'}
            },
            fields: ['PersonID', 'fullname'],
            pageSize: 50
        });

        this.PersonStore2 = new Ext.data.Store({
            proxy: {
                type: 'jsonp',
                url: this.address_prefix + '../../framework/person/persons.data.php?task=selectPersons' +
                        '&UserType=IsStaff',
                reader: {root: 'rows', totalProperty: 'totalCount'}
            },
            fields: ['PersonID', 'fullname'],
            pageSize: 50
        });
		
		this.tree2 = Ext.create('Ext.tree.Panel', {
				title: "گردش فرآیند",
				store: new Ext.data.TreeStore({
					proxy: {
						type: 'ajax',						
						url: this.address_prefix + "ManageProcess.data.php?task=GetTreeNodes&ParentID= 95 "  ,
					},
					root: {
						text: 'گردش فرایند',
						id: 'src',
						expanded: true
					}
				}),
				width:  350,
				height: 550,
				renderTo: this.get("tree-div")
        });

        this.formPanel = new Ext.form.Panel({
            renderTo: this.get("main"),
            frame: true,
            layout: {
                type: "table",
                columns: 2
            },
            bodyStyle: "text-align:right;padding:5px",
            title: "گزارش جامع فرایند ها",
            width: 700,
			fieldDefaults: {
			labelWidth: 120
			},
            defaults: {
                width: 300
            },
            items: [{
                    xtype: "combo",
                    hiddenName: "ProcessID",
                    fieldLabel: "نام فرآیند",                   
                    store: new Ext.data.SimpleStore({
                        data: [
                            ['1', "ارزیابی و اعتبار سنجی"]
                        ],
                        fields: ['id', 'value']
                    }),
                    displayField: "value",
                    valueField: "id"
					}, 
					{
                    xtype: "combo",
                    hiddenName: "SubProcessID",
                    store: new Ext.data.Store({
                        proxy: {
                            type: 'jsonp',
                            url: this.address_prefix + 'ManageProcess.data.php?task=selectPrc',
                            reader: {root: 'rows', totalProperty: 'totalCount'}
                        },
                        fields: ['InfoID', 'SubProcessTitle'],
                        autoLoad: true
                    }),
                    fieldLabel: "نام زیر فرایند",
                    queryMode: "local",
                    displayField: 'SubProcessTitle',
                    valueField: "InfoID"
                },
                {
                    xtype: "combo",
                    hiddenName: "ProcessStatus",
                    fieldLabel: "وضعیت فرآیند",
                    colspan: 2,
                    store: new Ext.data.SimpleStore({
                        data: [
								['1', "خام"],
								['2', "ارسال شده"],
								['3', "جاری"],
								['4', "خاتمه یافته"]
                        ],
                        fields: ['id', 'value']
                    }),
                    displayField: "value",
                    valueField: "id"
                },
                {
                    xtype: "shdatefield",
                    name: "FStartDate",
                    fieldLabel: "تاریخ شروع از"
                },
                {
                    xtype: "shdatefield",
                    name: "TStartDate",
                    fieldLabel: "تاریخ شروع تا"
                },
                {
                    xtype: "shdatefield",
                    name: "FEndDate",
                    fieldLabel: "تاریخ پایان از"
                },
                {
                    xtype: "shdatefield",
                    name: "TEndDate",
                    fieldLabel: "تاریخ پایان تا"
                },
                {
                    xtype: "combo",
                    colspan: 2,
                    hiddenName: "PersonID2",
                    fieldLabel: "منتظر تایید/ بررسی",
                    itemId: "PersonID2",
                    store: this.PersonStore2,
                    width: 600,
                    valueField: 'PersonID',
                    displayField: 'fullname'
                },
                {
                    xtype: "combo",
                    colspan: 2,
                    hiddenName: "PersonID",
                    fieldLabel: "آخرین تایید کننده",
                    itemId: "PersonID",
                    store: this.PersonStore,
                    width: 600,
                    valueField: 'PersonID',
                    displayField: 'fullname'
                },
                {
                    xtype: "numberfield",                   
                    hideTrigger: true,
                    name: "TotalDuration",
                    fieldLabel: "مدت کل فرایند(روز)",
					width: 200
                },
				{
                    xtype: "numberfield",                    
                    hideTrigger: true,
                    name: "TotalDuration",
                    fieldLabel: "مدت کل فرایند(ساعت)",
					width: 200
                },{
                    xtype: "numberfield",                    
                    hideTrigger: true,
                    name: "StepDuration",
                    fieldLabel: "مدت مرحله فرآیند(روز)",
					width: 200
                },{
                    xtype: "numberfield",                    
                    hideTrigger: true,
                    name: "StepDuration",
                    fieldLabel: "مدت مرحله فرآیند(ساعت)",
					width: 200
                },
                {
                    xtype: "treecombo",
                    selectChildren: true,
                    canSelectFolders: true,
                    multiselect: false,
                    hiddenName: "ReturnStep",
                    itemId: "ReturnStep",
                    colspan: 2,
                    width: 600,
                    fieldLabel: "مرحله فرآیند",
                    store: this.tree2.store
                }],
            buttons: [{
                    text: "مشاهده گزارش",
                    handler: Ext.bind(this.showReport, this),
                    iconCls: "report"
                }, {
                    text: "خروجی excel",
                    handler: Ext.bind(this.showReport, this),
                    listeners: {
                        click: function () {
                            ProcessReport_recordsObj.get('excel').value = "true";
                        }
                    },
                    iconCls: "excel"
                }, {
                    text: "پاک کردن گزارش",
                    iconCls: "clear",
                    handler: function () {
                        ProcessReport_recordsObj.formPanel.getForm().reset();
                        ProcessReport_recordsObj.get("mainForm").reset();
                    }
                }]
        });

        this.formPanel.getEl().addKeyListener(Ext.EventObject.ENTER, function (keynumber, e) {

            ProcessReport_recordsObj.showReport();
            e.preventDefault();
            e.stopEvent();
            return false;
        });
    }

    ProcessReport_recordsObj = new ProcessReport_records();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>