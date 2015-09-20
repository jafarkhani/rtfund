<?php
//---------------------------
// programmer:	Jafarkhani
// create Date:	89.02
//---------------------------
require_once 'header.php';
include_once '../global/public.php';
include_once '../formGenerator/form.class.php';
include_once '../userManagement/um.class.php';
require_once(inc_dataGrid);

?>
<script type="text/javascript" src="../formGenerator/Forms.js?4"></script>
<script type="text/javascript">
function AddingAction(){Adding();};

function referenceRender(v,p,r)
{
	var referenceName;
	switch(r.data.reference)
	{
		case "devotions": referenceName = "موقوفه";break;
		case "states": referenceName = "رقبه";break;
		case "rents": referenceName = "اجاره";break;
	}
	
	return referenceName + " کد " + v;
}
function operationRender(v,p,r)
{
	return "<div align='center' title='مشاهده فرم' onclick='operationMenu(event);' " +
		"style='background-repeat:no-repeat;background-position:center;"+
		"background-image:url(images/setting.gif);" +
		"cursor:pointer;width:100%;height:16'></div>";
}
function operationMenu(e)
{
	var record = dg_grid.selModel.getSelected();
	var op_menu = new Ext.menu.Menu();
	
	op_menu.add({text: 'مشاهده فرم',iconCls: 'info',handler : function(){showInfo('create');} });
	
	var readonly = (record.data.StepID == '1') ? "false" : "true";
	op_menu.add({text: 'پیوست',iconCls: 'attach',handler : function(){Attaching(readonly);} });
	
	if(record.data.StepID == '1')
	{
		op_menu.add({text: 'ارسال فرم',iconCls: 'send',handler : function(){Sending('send','create');} });
		op_menu.add({text: 'حذف',iconCls: 'remove',handler : function(){Deleting();} });
	}
	op_menu.add({text: 'سابقه',iconCls: 'history',handler : function(){showHistoryForm();} });
	
	op_menu.showAt([e.clientX-100, e.clientY+5]);
}

function unloadFn()
{
	if(win)
	{
		win.destroy();
		win = null;
	}
	if(sendWin)
	{
		sendWin.destroy();
		sendWin = null;
	}
	if(AttachWin)
	{
		AttachWin.destroy();
		AttachWin = null;
	}
	if(HistoryWin)
	{
		HistoryWin.destroy();
		HistoryWin = null;
	}
}
</script>
<?php
$dg = new sadaf_datagrid("dg","../formGenerator/wfm.data.php" ,"dg_forms");
$dg->method = "POST";
$dg->baseParams = "task: 'CreatedFormsSelect'";

$dg->addColumn('', "FormID", "string", true);
$col = $dg->addColumn('كد فرم', "LetterID", "string");
$col->width = 40;

$col = $dg->addColumn('كد پیگیری', "pursuitCode", "string");
$col->width = 40;

$col = $dg->addColumn('نوع فرم', "FormName", "string");

$col = $dg->addColumn('تاریخ ایجاد', "regDate", "string");
$col->renderer = "function(v){return MiladiToShamsi(v);}";
$col->width = 50;

$dg->addColumn('', "reference", "string", true);
$col = $dg->addColumn('آیتم وابسته', "referenceID", "string");
$col->renderer = "referenceRender";

$col = $dg->addColumn('وضعیت', "StepTitle", "string");
$dg->addColumn('', "StepID", "string", true);
//---------------------------
$col = $dg->addColumn("عملیات","","");
$col->renderer = "operationRender";
$col->sortable = false;
$col->width = 30;
//---------------------------
$dg->addButton("Add","ایجاد","add","AddingAction");

$dg->height = 400;
$dg->title = "فرم های ایجاد شده";
$dg->width = 700;
$dg->DefaultSortField = "regDate";
$dg->DefaultSortDir = "asc";
$dg->makeGrid();
//.....................................................
$drp_forms = FormGenerator::Drp_AllForms("FormsList", "---", "changeForm");
//.....................................................
?>
<script type="text/javascript">
var forms_EXTData = <?= common_component::PHPArray_to_JSArray(
	dataAccess::RUNQUERY("select * from fm_forms order by FormName"), "FormName","FormID", "reference") ?>;

//-------------------------------------------------------------------
BasisData.DevotionStore = <?= dataReader::MakeStoreObject("../devotions/dvt.data.php?task=select"
	,"'dvt01','dvt2','dvt3','dvt10'") ?>
//-----------------------------------------------------------------
BasisData.StateStore = <?= dataReader::MakeStoreObject("../states/states.data.php?task=select"
	,"'sta02','sta2','sta5'") ?>
//-------------------------------------------------------------------
function unloadFn()
{
	if(win)
	{
		win.destroy();
		win = null;
	}
	if(sendWin)
	{
		sendWin.destroy();
		sendWin = null;
	}
}
</script>
<div id="dg_forms"></div>
<!-- ----------------------------------------- -->
<div id="win_selectForm" class="x-hidden">
	<div id="pnl_selectForm" style="padding: 4px">
	<?= $drp_forms ?>
	<br>
	<div id="select_devotion" style="display:none">
		انتخاب موقوفه :	<br>
		<input type="text" id="DVT">
	</div>
	<div id="select_state" style="display:none">
		انتخاب رقبه :	<br>
		<input type="text" id="STA">
	</div>
	</div>
</div>
<!-- ----------------------------------------- -->
<div id="win_sendForm" class="x-hidden">
	<div id="pnl_sendForm" style="padding: 4px">
		<div id="sendEditor"></div>
	</div>
</div>
<!-- ----------------------------------------- -->
<div id="div_history" class="x-hidden"></div>
<!-- ----------------------------------------- -->
<div id="div_fromView" class="x-hidden"></div>
<!-- ----------------------------------------- -->
<div id="div_attach" class="x-hidden"></div>
<!-- ----------------------------------------- -->