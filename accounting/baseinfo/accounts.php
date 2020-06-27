<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------
require_once '../../header.inc.php';
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................
require_once 'accounts.js.php';

//_____________________________BANK___________________________________

$Bank_Grid = new sadaf_datagrid('BankGrid',
		$js_prefix_address . 'baseinfo.data.php?task=GetBankData','BankGrid_Box');

$col = $Bank_Grid->addColumn('',"BankID", GridColumn::ColumnType_int, true);

$col = $Bank_Grid->addColumn("نام بانک","BankDesc");
$col->editor = ColumnEditor::TextField();

if($accessObj->AddFlag)
{
	$Bank_Grid->addButton = true;
	$Bank_Grid->addHandler = "function(){return AccountObj.NewRowBank();}";
}
$Bank_Grid->enableRowEdit = true;
$Bank_Grid->rowEditOkHandler = "function(){return AccountObj.SaveBankData();}";

if($accessObj->RemoveFlag)
{
	$col=$Bank_Grid->addColumn('حذف', '', 'string');
	$col->renderer = "Account.RemoveBank";
	$col->width = 50;
}
$col = $Bank_Grid->addColumn('حسابها', '', 'string');
$col->renderer = "Account.listRender";
$col->width = 50;

$Bank_Grid->EnableRowNumber = true;
$Bank_Grid->width = 500;
$Bank_Grid->height=450;
$Bank_Grid->autoExpandColumn = "BankDesc" ;
$Bank_Grid->title = "بانک ها";
$Bank_Grid->pageSize = 19;
$Bank_Grid->disableChangePageSize = true;
$Bank_Grid->EnableSearch = false;

$GBank=$Bank_Grid->makeGrid_returnObjects();

//_________________________________ACCOUNT_________________________________________
           
$dg_accounts = new sadaf_datagrid('BranchAccGrid',
	$js_prefix_address.'baseinfo.data.php?task=SelectAccounts','BranchAccGrid_Box');

$dg_accounts->addColumn('','AccountID',"",true);
$dg_accounts->addColumn('','BranchID',"",true);
$dg_accounts->addColumn('','IsActive',"",true);

$col = $dg_accounts->addColumn("عنوان حساب",'AccountDesc','string');
$col->editor = ColumnEditor::TextField();

$col = $dg_accounts->addColumn("شماره حساب",'AccountNo','string');
$col->editor = ColumnEditor::TextField();
$col->width = 120;

$col = $dg_accounts->addColumn("شماره شبا",'shaba','string');
$col->editor = ColumnEditor::TextField(true);
$col->width = 180;

$col = $dg_accounts->addColumn("نوع حساب",'AccountType');
$col->width = 80;
$col->renderer = 'function(v,p,r){return AccountObj.TypeHesab(v,p,r);}';
$col->editor = ColumnEditor::ComboBox(
		PdoDataAccess::runquery("select * from BaseInfo where TypeID=3 AND IsActive='YES'"),'InfoID','InfoDesc');

if($accessObj->AddFlag)
{
	$dg_accounts->addButton = true;
	$dg_accounts->addHandler ="function(){return AccountObj.NewRowAcc();}";
}
$dg_accounts->enableRowEdit = true;
$dg_accounts->rowEditOkHandler = "function(){return AccountObj.SaveAccData();}";

$col=$dg_accounts->addColumn('عملیات', '', 'string');
$col->renderer = "Account.sendIdAcc";
$col->width = 70;

$dg_accounts->EnableRowNumber = true;
$dg_accounts->title = "تعریف حساب بانکی";
$dg_accounts->width = 780;
$dg_accounts->height = 450;
$dg_accounts->autoExpandColumn='AccountDesc';
$dg_accounts->pageSize = 19;
$dg_accounts->disableChangePageSize = true;
$dg_accounts->EnableSearch = false;
$dg_accounts->emptyTextOfHiddenColumns = true;
$AccountsDG = $dg_accounts->makeGrid_returnObjects();

//____________________________CHEQUE___________________________________________

$ChequeBook_Grid = new sadaf_datagrid('ChequeBookGrid',
		$js_prefix_address.'baseinfo.data.php?task=SelectCheques','ChequeBookGrid_Box');

$ChequeBook_Grid->addColumn('','ChequeBookID',GridColumn::ColumnType_int,true);
$ChequeBook_Grid->addColumn('','AccountID',GridColumn::ColumnType_int,true);
$ChequeBook_Grid->addColumn('','IsActive',"",true);

$col = $ChequeBook_Grid->addColumn("شماره سریال چک",'SerialNo','string');
$col->editor = ColumnEditor::TextField();

$col = $ChequeBook_Grid->addColumn("از شماره چک",'MinNo',GridColumn::ColumnType_int);
$col->width = 80;
$col->editor = ColumnEditor::TextField();

$col = $ChequeBook_Grid->addColumn("تا شماره چک",'MaxNo',GridColumn::ColumnType_int);
$col->width = 80;
$col->editor = ColumnEditor::TextField();

$CHK_status=array(array('val'=>'YES','name'=>' فعال'),
                  array('val'=>'NO','name'=>'غیر فعال'));
$col = $ChequeBook_Grid->addColumn('وضعیت','IsActive',GridColumn::ColumnType_string);
$col->editor = ColumnEditor::ComboBox($CHK_status,'val','name');
$col->width = 80;
 
if($accessObj->AddFlag)
{
	$ChequeBook_Grid->addButton = true;
	$ChequeBook_Grid->addHandler ="function(){return AccountObj.NewRowCheque();}";
}
$ChequeBook_Grid->enableRowEdit = true;
$ChequeBook_Grid->rowEditOkHandler = "function(){return AccountObj.SaveChequeData();}";

$col=$ChequeBook_Grid->addColumn('کپی', '', 'string');
$col->renderer = "Account.CopyChequeRender";
$col->width = 30;

$col=$ChequeBook_Grid->addColumn('حذف', '', 'string');
$col->renderer = "Account.RemoveCheque";
$col->width = 50;

$ChequeBook_Grid->addButton("", "تنظیم چاپ چک", "print", "function(){return AccountObj.SetChequePrint();}");

$ChequeBook_Grid->title = "تعریف دسته چک";
$ChequeBook_Grid->EnableRowNumber = true;
$ChequeBook_Grid->width =600;
$ChequeBook_Grid->height=450;
$ChequeBook_Grid->autoExpandColumn='SerialNo';
$ChequeBook_Grid->pageSize = 20;
$ChequeBook_Grid->disableChangePageSize = true;
$ChequeBook_Grid->EnableSearch = false;

$GCheque=$ChequeBook_Grid->makeGrid_returnObjects();

?>
<script type="text/javascript">

    AccountObj.grid=<?=$GBank ?>; 
	AccountObj.grid.plugins[0].on("beforeedit", function(editor,e){
		if(!e.record.data.BankID)
			return AccountObj.AddAccess;
		return AccountObj.EditAccess;
	});
	AccountObj.AccGrid= <?= $AccountsDG?>;
	AccountObj.AccGrid.plugins[0].on("beforeedit", function(editor,e){
		if(!e.record.data.AccountID)
			return AccountObj.AddAccess;
		return AccountObj.EditAccess;
	});
	AccountObj.CheqGrid= <?= $GCheque?>;
	AccountObj.CheqGrid.plugins[0].on("beforeedit", function(editor,e){
		if(!e.record.data.ChequeBookID)
			return AccountObj.AddAccess;
		return AccountObj.EditAccess;
	});

	AccountObj.mainTab.add({
			itemId:'banks',
			title:'بانک',
			style:'padding:5px',
			width: 1150,
			items:[AccountObj.grid]
		},{
			itemId:'accounts',
			title:'شماره حساب',
			style:'padding:5px',
			width: 1150,
			disabled: true,
			items: [AccountObj.AccGrid]
		},{
			itemId:'cheque',
			title:'دسته چک',
			style:'padding:5px',
			width: 1150,
			disabled: true,
			items: [AccountObj.CheqGrid]
		});
</script>
  <br><br>
  <div id="mainTab"></div>
