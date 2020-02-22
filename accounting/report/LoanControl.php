<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 95.08
//-----------------------------

require_once '../../header.inc.php';
require_once "ReportGenerator.class.php";
require_once inc_dataReader;

if(!empty($_REQUEST["task"]))
	$_REQUEST["task"]();

function selectLoanBlocks(){
	
	$dt = PdoDataAccess::runquery("select * from ACC_blocks join LON_loans using(BlockID)");
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}
 
function LoanReport(){
	
	$BranchID = $_POST["BranchID"];
	$blockID = $_POST["BlockID"];
	$StartDate = $_POST["StartDate"];
	$EndDate = $_POST["EndDate"];
	$TafsiliID = $_POST["TafsiliID"];
	
	$where = "";
	$param = array($_SESSION["accounting"]["CycleID"], $blockID);
	
	if($BranchID != "")
	{
		$where .= " AND BranchID =?";
		$param[] = $BranchID;
	}
	if($TafsiliID != "")
	{
		$where .= " AND (TafsiliID=? or TafsiliID2=?)";
		$param[] = $TafsiliID;
		$param[] = $TafsiliID;
	}
	if($StartDate != "")
	{
		$where .= " AND DocDate >=?";
		$param[] = DateModules::shamsi_to_miladi($StartDate, "-");
	}
	if($EndDate != "")
	{
		$where .= " AND DocDate <=?";
		$param[] = DateModules::shamsi_to_miladi($EndDate, "-");
	}
	
	$dt = PdoDataAccess::runquery("
		select cc.*,concat_ws(' - ',b1.BlockDesc,b2.BlockDesc,b3.BlockDesc,b4.BlockDesc) CostDesc,
				sum(CreditorAmount - DebtorAmount ) remain,sum(DebtorAmount) dsum, sum(CreditorAmount) csum
		from ACC_DocItems di
			join ACC_docs using(DociD)
			join ACC_CostCodes cc using(CostID)
			join ACC_blocks b1 on(cc.level1=b1.BlockID)
			join ACC_blocks b2 on(cc.level2=b2.BlockID)
			left join ACC_blocks b3 on(cc.level3=b3.BlockID)
			left join ACC_blocks b4 on(cc.level4=b4.BlockID)
			
		where CycleID=? AND DocType not in(".DOCTYPE_ENDCYCLE.",".DOCTYPE_CLOSECYCLE.") 
			AND cc.level2 = ? " . $where . "
		
		group by di.CostID 
		order by cc.CostCode" ,$param);
	
	//print_r(ExceptionHandler::PopAllExceptions());
	
	function moneyRender($row, $val) {
		
		$BranchID = $_POST["BranchID"];
		$StartDate = $_POST["StartDate"];
		$EndDate = $_POST["EndDate"];
		$TafsiliID = $_POST["TafsiliID"];
		
		$params = "IncludeRaw=true&show=true";
		$params .= $BranchID != "" ? "&BranchID=" . $BranchID : "";
		$params .= $TafsiliID != "" ? "&TafsiliID=" . $TafsiliID : "";
		$params .= $row["level1"] != "" ? "&level1=" . $row["level1"] : "";
		$params .= $row["level2"] != "" ? "&level2=" . $row["level2"] : "";
		$params .= $row["level3"] != "" ? "&level3=" . $row["level3"] : "";
		$params .= $row["level4"] != "" ? "&level4=" . $row["level4"] : "";
		$params .= $StartDate != "" ? "&fromDate=" . $StartDate : "";
		$params .= $EndDate != "" ? "&toDate=" . $EndDate : "";	
		
		return "<a target=_blank href='../accounting/report/flow.php?".$params."'>" .  number_format($val) . "</a>";
	}
	
	$rpg = new ReportGenerator();
	$rpg->mysql_resource = $dt;
	
	$col = $rpg->addColumn("کد حساب", "CostDesc");
	$col = $rpg->addColumn("جمع بدهکار", "dsum", "ReportMoneyRender");
	$col = $rpg->addColumn("جمع بستانکار", "csum", "ReportMoneyRender");
	$col = $rpg->addColumn("مانده حساب", "remain", "moneyRender");
	$rpg->generateReport();
	die();
	
}
?>
<script>
AccReport_LoanControl.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

AccReport_LoanControl.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "LoanControl.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function AccReport_LoanControl()
{
	this.FilterPanel = new Ext.form.Panel({
		width: 700,
		defaults : {
			width : 340
		},
		renderTo : this.get("div_loans"),
		frame: true,
		layout : "column",
		columns :2,
		items : [{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: "/accounting/global/domain.data.php?task=GetAccessBranches",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['BranchID','BranchName'],
				autoLoad : true					
			}),
			fieldLabel : "شعبه",
			queryMode : 'local',
			value : "<?= !isset($_SESSION["accounting"]["BranchID"]) ? "" : $_SESSION["accounting"]["BranchID"] ?>",
			displayField : "BranchName",
			valueField : "BranchID",
			name : "BranchID"
		},{
			xtype : "combo",
			fieldLabel : "انتخاب وام",
			store: new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: this.address_prefix + 'LoanControl.php?task=selectLoanBlocks',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ["BlockID","BlockDesc"],
				autoLoad : true
			}),
			queryMode : "local",
			displayField: 'BlockDesc',
			valueField : "BlockID",
			name : "BlockID"
		},{
			xtype : "shdatefield",
			fieldLabel : "تاریخ شروع",
			name : "StartDate"			
		},{
			xtype : "shdatefield",
			fieldLabel : "تاریخ پایان",
			name : "EndDate"			
		},{
			xtype : "combo",
			displayField : "TafsiliDesc",
			fieldLabel : "تفصیلی",
			valueField : "TafsiliID",
			name : "TafsiliID",
			store : new Ext.data.Store({
				fields:["TafsiliID","TafsiliDesc"],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=GetAllTafsilis&TafsiliType=1',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				}
			})
		}],
		buttons : [{
			text : "بارگذاری گزارش",
			iconCls : "report",
			handler : function(){
				
				me = AccReport_LoanControlObj;
				
				var mask = new Ext.LoadMask(me.resultPanel, {msg:'در حال بارگذاری ...'});
				mask.show();
				
				me.resultPanel.loader.load({
					params : {
						BranchID : me.FilterPanel.down("[name=BranchID]").getValue(),
						BlockID : me.FilterPanel.down("[name=BlockID]").getValue(),
						StartDate : me.FilterPanel.down("[name=StartDate]").getRawValue(),
						EndDate : me.FilterPanel.down("[name=EndDate]").getRawValue(),
						TafsiliID : me.FilterPanel.down("[name=TafsiliID]").getValue()
					},
					callback : function(){
						AccReport_LoanControlObj.resultPanel.doLayout();
						mask.hide();
					}
				});
			}
		}]
	});
	
	this.resultPanel = new Ext.panel.Panel({
		frame : true,
		renderTo : this.get("div_result"),
		autoHeight : true,
		minHeight : 350,
		loader : {
			url : this.address_prefix + "LoanControl.php?task=LoanReport"
		},
		width : 600
	});
}

AccReport_LoanControlObj = new AccReport_LoanControl();
</script>
<form id="mainForm">
	<center><br>
		<div id="div_loans" ></div>
		<br>
		<div id="div_result" ></div>		
	</center>
</form>
