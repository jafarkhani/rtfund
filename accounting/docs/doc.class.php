<?php

//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------

require_once DOCUMENT_ROOT . "/commitment/baseinfo/baseinfo.class.php";

class ACC_docs extends PdoDataAccess {

	public $DocID;
	public $CycleID;
	public $BranchID;
	public $LocalNo;
	public $DocDate;
	public $RegDate;
	public $DocStatus;
	public $StatusID;
	public $DocType;
	public $SubjectID;
	public $description;
	public $regPersonID;
	public $EventID;

	function __construct($DocID = "", $pdo = null) {

		$this->DT_DocDate = DataMember::CreateDMA(InputValidation::Pattern_Date);
		$this->DT_RegDate = DataMember::CreateDMA(InputValidation::Pattern_Date);

		if ($DocID != "")
			parent::FillObject($this, "select * from ACC_docs where DocID=?", array($DocID), $pdo);
	}

	static function GetAll($where = "", $whereParam = array()) {

		$query = "select sd.*, 
				bch.BranchName,
				concat_ws(' ',fname,lname,CompanyName) as regPerson, 
				b.InfoDesc SubjectDesc,b2.InfoDesc DocTypeDesc,
				fs.StepID,
				e.EventTitle,
				fr.ActionType
			
			from ACC_docs sd
			left join COM_events e using(EventID)
			left join BSC_branches bch using(BranchID)
			left join BaseInfo b on(b.TypeID=73 AND b.InfoID=SubjectID)
			left join BaseInfo b2 on(b2.TypeID=9 AND b2.InfoID=DocType)
			left join BSC_persons p on(regPersonID=PersonID)
			left join WFM_FlowSteps fs on(fs.FlowID=".FLOWID_ACCDOC." AND fs.StepID=sd.StatusID)
			left join WFM_FlowRows fr on(fr.IsLastRow='YES' AND fr.FlowID=fs.FlowID AND fr.StepRowID=fs.StepRowID AND fr.ObjectID=sd.DocID)
		";

		$query .= ($where != "") ? " where " . $where : "";

		return parent::runquery_fetchMode($query, $whereParam);
	}

	function Trigger($pdo = null) {

		if ($this->LocalNo != "") {
			$dt = PdoDataAccess::runquery("select * from ACC_docs 
			where BranchID=? AND CycleID=? AND LocalNo=?", array($this->BranchID, $this->CycleID, $this->LocalNo), $pdo);

			if (count($dt) > 0) {
				if (empty($this->DocID) || $this->DocID != $dt[0]["DocID"]) {
					ExceptionHandler::PushException("شماره سند تکراری است");
					return false;
				}
			}
			//..................................................	
			$DocDate = $this->DocDate;
			/* if($DocDate == PDONOW)
			  $DocDate = DateModules::Now();
			  else
			  $DocDate = DateModules::shamsi_to_miladi ($DocDate, "-");

			  $dt = PdoDataAccess::runquery("select * from ACC_docs
			  where LocalNo>? AND BranchID=? AND CycleID=?
			  order by LocalNo limit 1",
			  array($this->LocalNo, $this->BranchID, $this->CycleID), $pdo);

			  if(count($dt) > 0 && strcmp($DocDate,$dt[0]["DocDate"]) > 0)
			  {
			  ExceptionHandler::PushException("تاریخ سند باید از اسناد بعدی کوچکتر باشد.");
			  return false;
			  }
			  //..................................................
			  $dt = PdoDataAccess::runquery("select * from ACC_docs where LocalNo<? order by LocalNo desc limit 1",
			  array($this->LocalNo), $pdo);

			  if(count($dt) > 0 && strcmp($DocDate,$dt[0]["DocDate"]) < 0)
			  {
			  if($this->DocDate == PDONOW)
			  $this->DocDate = $dt[0]["DocDate"];
			  else
			  {
			  ExceptionHandler::PushException("تاریخ سند باید از اسناد قبلی بزرگتر باشد.");
			  return false;
			  }
			  } */
		}

		return true;
	}

	function Add($pdo = null) {

		$temp = parent::runquery("select * from ACC_cycles where CycleID=?", array($this->CycleID));
		if ($temp[0]["IsClosed"] == "YES") {
			ExceptionHandler::PushException("دوره مالی مربوطه بسته شده است");
			return false;
		}		
		
		$pdo2 = $pdo == null ? PdoDataAccess::getPdoObject() : $pdo;
		if ($pdo == null)
			$pdo2->beginTransaction();

		if ($this->LocalNo == "")
			$this->LocalNo = parent::GetLastID("ACC_docs", "LocalNo", "CycleID=?", array($this->CycleID), $pdo2) + 1;

		if (!$this->Trigger($pdo2)) {
			if ($pdo == null)
				$pdo2->rollBack();
			return false;
		}

		if (!parent::insert("ACC_docs", $this, $pdo2)) {
			if ($pdo == null)
				$pdo2->rollBack();
			return false;
		}

		$this->DocID = parent::InsertID($pdo2);

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->DocID;
		$daObj->TableName = "ACC_docs";
		$daObj->execute($pdo2);

		if ($pdo == null)
			$pdo2->commit();
		return true;
	}

	function Edit($pdo = null) {

		if (!$this->Trigger($pdo))
			return false;

		if (parent::update("ACC_docs", $this, " DocID=:did", array(":did" => $this->DocID), $pdo) === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->DocID;
		$daObj->TableName = "ACC_docs";
		$daObj->execute($pdo);

		return true;
	}

	static function Remove($DocID, $pdo = null) {

		$temp = parent::runquery("select * from ACC_docs join ACC_cycles using(CycleID)
			where DocID=?", array($DocID));
		if (count($temp) == 0)
			return false;

		if ($temp[0]["StatusID"] != ACC_STEPID_RAW) {
			ExceptionHandler::PushException("سند مربوطه تایید شده و قابل حذف نمی باشد");
			return false;
		}
		if ($temp[0]["IsClosed"] == "YES") {
			ExceptionHandler::PushException("دوره مربوطه بسته شده و سند قابل حذف نمی باشد.");
			return false;
		}
		if($temp[0]["EventID"]*1 > 0)
		{
			$eobj = new COM_events($temp[0]["EventID"]);
			if($eobj->IsSystemic == "YES")
			{
				ExceptionHandler::PushException("اسنادی که به صورت اتومات صادر می شوند باید از زیر سیستم مربوطه حذف گردند");
				return false;
			}
		}

		if ($pdo == null) {
			$pdo2 = parent::getPdoObject();
			$pdo2->beginTransaction();
		}
		else
			$pdo2 = $pdo;
		$result = parent::delete("ACC_DocCheques", "DocID=?", array($DocID), $pdo2);
		if ($result === false)
			return false;

		$result = parent::delete("ACC_DocItems", "DocID=?", array($DocID), $pdo2);
		if ($result === false)
			return false;

		$result = parent::delete("ACC_docs", "DocID=?", array($DocID), $pdo2);
		if ($result === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $DocID;
		$daObj->TableName = "ACC_docs";
		$daObj->execute($pdo2);

		if ($pdo == null)
			$pdo2->commit();
		return true;
	}

	static function GetLastLocalNo() {

		$no = parent::GetLastID("ACC_docs", "LocalNo", "CycleID=?", 
				array($_SESSION["accounting"]["CycleID"]));

		return $no + 1;
	}

	static function PrintDoc($docID) {

		require_once inc_CurrencyModule;
		
		$DocObject = new ACC_docs($docID);

		$temp = PdoDataAccess::runquery("
		select	concat_ws(' - ',b1.BlockDesc, b2.BlockDesc, b3.BlockDesc, b4.BlockDesc) CostDesc,
			cc.CostCode,
			BranchName,
			b.InfoDesc TafsiliType,	t.TafsiliDesc,
			t2.TafsiliDesc TafsiliDesc2,
			t3.TafsiliDesc TafsiliDesc3,
			di.details,
			di.param1 ParamValue1,
			di.param2 ParamValue2,
			di.param3 ParamValue3,
			sum(DebtorAmount) DSUM, 
			sum(CreditorAmount) CSUM,
			p1.paramDesc paramDesc1,
			p2.paramDesc paramDesc2,
			p3.paramDesc paramDesc3,
			p1.ParamID ParamID1,
			p2.ParamID ParamID2,
			p3.ParamID ParamID3,
			p1.SrcTable SrcTable1,
			p2.SrcTable SrcTable2,
			p3.SrcTable SrcTable3,
			p1.SrcDisplayField as SrcDisplayField1,
			p2.SrcDisplayField as SrcDisplayField2,
			p3.SrcDisplayField as SrcDisplayField3,
			p1.SrcValueField as SrcValueField1,
			p2.SrcValueField as SrcValueField2,
			p3.SrcValueField as SrcValueField3
			
		from ACC_DocItems di
			join ACC_docs d using(docID)
			join BSC_branches using(BranchID)
			join ACC_CostCodes cc using(CostID)
			left join ACC_blocks b1 on(cc.level1=b1.BlockID)
			left join ACC_blocks b2 on(cc.level2=b2.BlockID)
			left join ACC_blocks b3 on(cc.level3=b3.BlockID)
			left join ACC_blocks b4 on(cc.level4=b4.BlockID)
			left join BaseInfo b on(di.TafsiliType=InfoID AND TypeID=2)
			left join ACC_tafsilis t on(t.TafsiliID=di.TafsiliID)
			left join ACC_tafsilis t2 on(t2.TafsiliID=di.TafsiliID2)
			left join ACC_tafsilis t3 on(t3.TafsiliID=di.TafsiliID3)
			left join ACC_CostCodeParams p1 on(p1.ParamID=cc.param1)
			left join ACC_CostCodeParams p2 on(p2.ParamID=cc.param2)
			left join ACC_CostCodeParams p3 on(p3.ParamID=cc.param3)
			
		where di.DocID=?
		/*group by if(DebtorAmount<>0,0,1),di.CostID,di.TafsiliID*/group by itemID
		order by if(DebtorAmount<>0,0,1),cc.CostCode", array($docID));

		for($i=0; $i<count($temp); $i++)
		{
			for($j=1; $j<=3; $j++)
			{
				if(!empty($temp[$i]["SrcTable" . $j]))
				{
					$dt = PdoDataAccess::runquery("select ". $temp[$i]["SrcDisplayField" . $j] . " as title " .
						" from " . $temp[$i]["SrcTable" . $j] . 
						" where " . $temp[$i]["SrcValueField" . $j] . "=?", array($temp[$i]["ParamValue" . $j]));
					if(count($dt) > 0)
						$temp[$i]["ParamValue" . $j] = $dt[0]["title"];
				}
			}
		}
		
		echo '
			<style>
				.DocTbl{width:98%;border-collapse: collapse; }
				.DocTbl td {font-family:nazanin;}
				.header {background-color:#ddd}
				.SignTbl {font-family:nazanin; font-weight:bold}
			</style>
			<table class="DocTbl" border="1" cellspacing="0" cellpadding="2">
				<thead>
				<tr>
					<td colspan=11>
						<table style="width:100%">
						<tr>
						<td style="width:25%"><img src="/framework/icons/logo.jpg" style="width:100px" /></td>
						<td style="height: 60px;font-family:titr;font-size:16px" align="center">سند حسابداری
						<br>' . $temp[0]["BranchName"] . '</td>
						<td style="width:25%" align="center" >شماره سند : 
						'. $DocObject->LocalNo .'<br>تاریخ سند :
						'. DateModules::miladi_to_shamsi($DocObject->DocDate).'</td>
						</tr>
						</table>
					</td>
				</tr>
				<tr class="header">
					<td align="center" >کد حساب</td>
					<td align="center" >شرح حساب</td>
					<td align="center" >شرح ردیف</td>
					<td align="center" >تفصیلی1</td>
					<td align="center" >تفصیلی2</td>
					<td align="center" >تفصیلی3</td>
					<td align="center" >آیتم1</td>
					<td align="center" >آیتم2</td>
					<td align="center" >آیتم3</td>
					<td align="center" >بدهکار</td>
					<td align="center" >بستانکار</td>
				</tr>
				</thead>';

		$DSUM = 0;
		$CSUM = 0;
		for ($i = 0; $i < count($temp); $i++) {
			$DSUM += $temp[$i]["DSUM"];
			$CSUM += $temp[$i]["CSUM"];
			echo "<tr>
					<td >" . $temp[$i]["CostCode"] . "</td>
					<td >" . $temp[$i]["CostDesc"] . "</td>
					<td >" . $temp[$i]["details"] . "</td>	
					<td >" . $temp[$i]["TafsiliDesc"] . "</td>
					<td >" . $temp[$i]["TafsiliDesc2"] . "</td>
					<td >" . $temp[$i]["TafsiliDesc3"] . "</td>
					<td >" . ($temp[$i]["paramDesc1"] != "" ? $temp[$i]["paramDesc1"] . " : " . 
							$temp[$i]["ParamValue1"] : "") . "</td>
					<td >" . ($temp[$i]["paramDesc2"] != "" ? $temp[$i]["paramDesc2"] . " : " . 
							$temp[$i]["ParamValue2"] : "") . "</td>
					<td >" . ($temp[$i]["paramDesc3"] != "" ? $temp[$i]["paramDesc3"] . " : " . 
							$temp[$i]["ParamValue3"] : "") . "</td>
					<td >" . number_format($temp[$i]["DSUM"]) . "</td>
					<td >" . number_format($temp[$i]["CSUM"]) . "</td>
				</tr>";
		}

		echo '<tr class="header">
					<td colspan="9">جمع : 
					' . ($CSUM != $DSUM ? "<span style=color:red>سند تراز نمی باشد</span>" :
				CurrencyModulesclass::CurrencyToString($CSUM) . " ریال " ) . '</td>

					<td>' . number_format($DSUM, 0, '.', ',') . '</td>
					<td>' . number_format($CSUM, 0, '.', ',') . '</td>
				</tr>
				<tr>
					<td colspan="11">شرح سند : ' . $DocObject->description . '
					</td>
				</tr>';
		//------------------------------------------------------
		
		//------------------------------------------------------
		$dt = ACC_DocCheques::GetAll("DocID=?", array($docID));
		for ($i = 0; $i < count($dt); $i++) {
			echo "<tr style='height:60px;vertical-align:middle'>
			<td colspan=11>" . "چک شماره " . "<b>" . $dt[$i]["CheckNo"] . "</b>" . " بانک " .
			"<b>" . $dt[$i]["BankDesc"] . "</b>" . " شماره حساب " .
			"<b>" . $dt[$i]["AccountNo"] . "</b>" . " مورخ " .
			"<b>" . DateModules::miladi_to_shamsi($dt[$i]["CheckDate"]) . "</b>" . " به مبلغ " .
			"<b>" . number_format($dt[$i]["amount"]) . " ریال</b>" . " در وجه " .
			"<b>" . $dt[$i]["TafsiliDesc"] . " بابت " . $dt[$i]["description"] . "</b>" . " صادر گردید." .
			"</td>";
			//echo '<td colspan=2>امضاء تحویل گیرنده</td></tr>';
		}
		//------------------------------------------------------
		echo '	</table>
				<br>
				<div align="center" style="float:right;width:50%;" class=SignTbl>
				امضاء مسئول حسابداری
				</div>

				<div align="center" style="float:left;width:50%;" class=SignTbl>
				امضاء مدیر عامل
				</div>
				<br><br><br><br>
		';
	}
	 
	static function GetPureRemainOfSaving($PersonID, $BranchID){
		
		//------------- find Tafsili -----------------
		$dt = PdoDataAccess::runquery("select * from ACC_tafsilis where TafsiliType=" . TAFSILITYPE_PERSON .  
				" AND ObjectID=?", array($PersonID));
		if(count($dt) == 0)
		{
			ExceptionHandler::PushException("تفصیلی فرد مربوطه یافت نشد.");
			return false;
		}
		$TafsiliID = $dt[0]["TafsiliID"];
		
		//------------- find saving remain -----------------
		$dt = PdoDataAccess::runquery("
				select ifnull(sum(CreditorAmount-DebtorAmount),0) amount
				from ACC_DocItems join ACC_docs using(DocID)
				join ACC_cycles using(CycleID)
				where TafsiliType=:tt AND TafsiliID=:t AND BranchID=:b 
					AND CycleYear=:y AND CostID=:cost
				group by TafsiliID", 
			array(
				":y" => substr(DateModules::shNow(),0,4),
				":b" => $BranchID,
				":cost" => COSTID_saving,
				":tt" => TAFSILITYPE_PERSON,
				":t" => $TafsiliID
		));
		$SavingAmount = count($dt) == 0 ? 0 : $dt[0][0];
		
		//------------- minus block accounts -----------------
		$BlockedAmount = ACC_CostBlocks::GetBlockAmount(COSTID_saving, TAFSILITYPE_PERSON, $TafsiliID);

		//------------------------------------------------
		return $SavingAmount*1 - $BlockedAmount*1;
	}

	static function GetRemainOfCost($CostID, $TafsiliID, $TafsiliID2, $Date){
		
		$dt = PdoDataAccess::runquery("
				select ifnull(sum(CreditorAmount-DebtorAmount),0) amount
				from ACC_DocItems join ACC_docs using(DocID)
				where CycleID=:cycle AND CostID=:cost AND TafsiliID=:t AND TafsiliID2=:t2
					AND DocDate <= :date
				group by TafsiliID", 
			array(
				":cycle" => $_SESSION["accounting"]["CycleID"],
				":cost" => $CostID,
				":t" => $TafsiliID,
				":t" => $TafsiliID2,
				":date" => $Date
		));
		return count($dt) == 0 ? 0 : $dt[0][0];
	}
}

class ACC_DocItems extends PdoDataAccess {

	public $DocID;
	public $ItemID;
	public $CostID;
	public $TafsiliType;
	public $TafsiliID;
	public $TafsiliType2;
	public $TafsiliID2;
	public $TafsiliType3;
	public $TafsiliID3;
	public $DebtorAmount;
	public $CreditorAmount;
	public $details;
	public $locked;
	public $SourceType;
	public $SourceID1;
	public $SourceID2;
	public $SourceID3;
	public $SourceID4;
	
	public $param1;
	public $param2;
	public $param3;
	
	public $OldCostCode;
	
	public $_CostCode;

	function __construct($itemID = "") {
		
		if($itemID != "")
			PdoDataAccess::FillObject ($this, "select d.*,c.CostCode _CostCode from ACC_DocItems d join "
					. "ACC_CostCodes c using(CostID) where ItemID=?", array($itemID));
	}

	static function GetAllCount($where = "", $whereParam = array()) {

		$query = "select ifnull(count(*),0)
			
			from ACC_DocItems si
			join ACC_CostCodes cc using(CostID)
			join ACC_blocks b1 on(cc.level1=b1.blockID)
			join ACC_blocks b2 on(cc.level2=b2.blockID)
			join ACC_blocks b3 on(cc.level3=b3.blockID)
			
			/*left join BaseInfo bi on(si.TafsiliType=InfoID AND TypeID=2)
			left join BaseInfo bi2 on(si.TafsiliType2=bi2.InfoID AND bi2.TypeID=2)
			left join BaseInfo bi3 on(si.TafsiliType3=bi3.InfoID AND bi3.TypeID=2)
			left join ACC_tafsilis t on(t.TafsiliID=si.TafsiliID)
			left join ACC_tafsilis t2 on(t2.TafsiliID=si.TafsiliID2)
			left join ACC_tafsilis t3 on(t3.TafsiliID=si.TafsiliID3)
			
			left join ACC_CostCodeParams p1 on(p1.ParamID=cc.param1)
			left join ACC_CostCodeParams p2 on(p2.ParamID=cc.param2)
			left join ACC_CostCodeParams p3 on(p3.ParamID=cc.param3)*/
			
			";
		$query .= ($where != "") ? " where " . $where : "";
		$dt = parent::runquery($query, $whereParam);
		return $dt[0][0];
	}
	
	static function GetAll($where = "", $whereParam = array()) {

		$query = "select si.*,cc.CostCode,
			concat_ws('-',b1.blockDesc,b2.BlockDesc,b3.BlockDesc,b4.BlockDesc) CostDesc,
			t.TafsiliDesc,bi.InfoDesc TafsiliGroupDesc,
			t2.TafsiliDesc as Tafsili2Desc,bi2.InfoDesc Tafsili2GroupDesc,
			t3.TafsiliDesc as Tafsili3Desc,bi3.InfoDesc Tafsili3GroupDesc,
			
			p1.paramDesc paramDesc1,
			p2.paramDesc paramDesc2,
			p3.paramDesc paramDesc3,
			
			if(p1.ParamType='combo',pi1.ParamValue,si.param1) ParamValue1,
			if(p2.ParamType='combo',pi2.ParamValue,si.param2) ParamValue2,
			if(p3.ParamType='combo',pi3.ParamValue,si.param3) ParamValue3
			
		from ACC_DocItems si
			join ACC_CostCodes cc using(CostID)
			left join ACC_blocks b1 on(cc.level1=b1.blockID)
			left join ACC_blocks b2 on(cc.level2=b2.blockID)
			left join ACC_blocks b3 on(cc.level3=b3.blockID)
			left join ACC_blocks b4 on(cc.level4=b4.blockID)
			
			left join BaseInfo bi on(si.TafsiliType=InfoID AND TypeID=2)
			left join BaseInfo bi2 on(si.TafsiliType2=bi2.InfoID AND bi2.TypeID=2)
			left join BaseInfo bi3 on(si.TafsiliType3=bi3.InfoID AND bi3.TypeID=2)
			
			left join ACC_tafsilis t on(t.TafsiliID=si.TafsiliID)
			left join ACC_tafsilis t2 on(t2.TafsiliID=si.TafsiliID2)
			left join ACC_tafsilis t3 on(t3.TafsiliID=si.TafsiliID3)
			
			left join ACC_CostCodeParams p1 on(p1.ParamID=cc.param1)
			left join ACC_CostCodeParams p2 on(p2.ParamID=cc.param2)
			left join ACC_CostCodeParams p3 on(p3.ParamID=cc.param3)
			
			left join ParamItems pi1 on(pi1.ParamID=cc.param1 AND p1.ParamType='combo' AND si.param1=pi1.ItemID)
			left join ParamItems pi2 on(pi2.ParamID=cc.param2 AND p2.ParamType='combo' AND si.param2=pi2.ItemID)
			left join ParamItems pi3 on(pi3.ParamID=cc.param3 AND p3.ParamType='combo' AND si.param3=pi3.ItemID)
			
			";
		$query .= ($where != "") ? " where " . $where : "";
		return parent::runquery_fetchMode($query, $whereParam);
	}

	function BlockTrigger($pdo = null) {

		if (!isset($this->TafsiliType) || !isset($this->TafsiliID))
			return true;
		
		$BlockedAmount = ACC_CostBlocks::GetBlockAmount($this->CostID, $this->TafsiliType, $this->TafsiliID, $pdo);

		if ($BlockedAmount > 0) {
			
			if ($this->DebtorAmount*1 === 0)
				return true;
			
			$temp = PdoDataAccess::runquery("select ifnull(sum(CreditorAmount-DebtorAmount),0) remain
				from ACC_DocItems join ACC_docs using(DocID)
				where CycleID=? AND CostID=? AND TafsiliType=? AND TafsiliID=? AND ItemID<>?", array(
						$_SESSION["accounting"]["CycleID"], $this->CostID, $this->TafsiliType, $this->TafsiliID,
						$this->ItemID), $pdo);

			if ($temp[0][0] * 1 - $BlockedAmount < $this->DebtorAmount * 1) {
				ExceptionHandler::PushException("مبلغ وارد شده بیشتر از مبلغ قابل برداشت می باشد " .
						"<br>مبلغ قابل برداشت : " . ($temp[0][0] * 1 - $BlockedAmount) . "<br>");
				return false;
			}
		}

		return true;
	}

	function Add($pdo = null) {

		if (!self::BlockTrigger($pdo))
			return false;

		if ($this->CostID == COSTID_share) {
			$amount = $this->CreditorAmount > 0 ? $this->CreditorAmount : $this->DebtorAmount;
			if ($amount * 1 % ShareBaseAmount != 0) {
				ExceptionHandler::PushException("مبلغ سرفصل حساب سهام باید مضربی از " . ShareBaseAmount . " باشد");
				return false;
			}
		}

		if(empty($this->CostID))
		{
			ExceptionHandler::PushException("کد حساب مقدار دهی نشده است");
			return false;
		}
		
		if (!parent::insert("ACC_DocItems", $this, $pdo))
			return false;

		$this->ItemID = parent::InsertID($pdo);

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->DocID;
		$daObj->SubObjectID = $this->ItemID;
		$daObj->TableName = "ACC_DocItems";
		$daObj->execute($pdo);
		return true;
	}

	function Edit($pdo = null) {

		if (!self::BlockTrigger($pdo))
			return false;
		
		if(!$this->EditTrigger())
			return false;

		if (!parent::update("ACC_DocItems", $this, "ItemID=:rid", array(":rid" => $this->ItemID), $pdo))
			return false;

		if(empty($this->_CostCode))
		{
			$obj = new ACC_DocItems($this->ItemID);
			$this->_CostCode = $obj->_CostCode;
		}
		ACC_DocHistory::AddLog($this->DocID, "ویرایش ردیف سند با کدحساب [" . $this->_CostCode . "]");
		
		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->DocID;
		$daObj->SubObjectID = $this->ItemID;
		$daObj->TableName = "ACC_DocItems";
		$daObj->execute($pdo);
		return true;
	}

	function Remove() {

		if(!$this->EditTrigger())
			return false;
		
		$result = parent::delete("ACC_DocItems", "ItemID=:rid", array(":rid" => $this->ItemID));
		if ($result === false)
			return false;

		ACC_DocHistory::AddLog($this->DocID, "حذف ردیف سند با کدحساب [" . $this->_CostCode . "]");
		
		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $this->DocID;
		$daObj->SubObjectID = $this->ItemID;
		$daObj->TableName = "ACC_DocItems";
		$daObj->execute();
		return true;
	}

	function EditTrigger(){
		//------------ check for register deposite -------------
		if($this->TafsiliID > 0)
		{
			$hobj = new ACC_docs($this->DocID);
			$dt = PdoDataAccess::runquery("select LocalNo from ACC_docs join ACC_DocItems using(DocID) where 
				TafsiliID=? AND
				DocDate>=? AND CycleID=" . $_SESSION["accounting"]["CycleID"] . "
				AND BranchID=?
				AND DocType=" . DOCTYPE_DEPOSIT_PROFIT, array($this->TafsiliID, $hobj->DocDate, $hobj->BranchID));
			if(count($dt) > 0)
			{
				
				ExceptionHandler::PushException("سند سود سپرده با شماره " . 
						$dt[0][0] . " بر اساس این ردیف صادر شده و قادر به ویرایش آن نمی باشید.");
				return false;
			}
		}	
		return true;
	}
}

class ACC_DocCheques extends PdoDataAccess {

	public $DocChequeID;
	public $DocID;
	public $CheckNo;
	public $AccountID;
	public $CheckDate;
	public $amount;
	public $CheckStatus;
	public $TafsiliID;
	public $description;
	public $AccountTafsiliID;
	
	public $_AccountTafsiliID;
	public $_AccountNo;

	function __construct($id = "") {
		
		$this->DT_CheckDate = DataMember::CreateDMA(DataMember::DT_DATE);
		if($id != "")
			return PdoDataAccess::FillObject($this, "select c.*,a.AccountNo _AccountNo,t.TafsiliID _AccountTafsiliID 
				from ACC_DocCheques c
				left join ACC_accounts a using(AccountID)
				left join ACC_tafsilis t on(ObjectType='account' and ObjectID=AccountID) 
				
				where DocChequeID=?", array($id));
	}

	static function GetAll($where = "", $whereParam = array()) {
		$query = "select c.*,a.*,b.infoDesc as StatusTitle, bk.BankDesc, t.TafsiliDesc, t2.TafsiliDesc AccountTafsiliDesc
					from ACC_DocCheques c
					left join ACC_accounts a using(AccountID)
					left join ACC_banks bk on(a.BankID=bk.BankID)
					left join ACC_tafsilis t on(TafsiliType=" . TAFSILITYPE_PERSON . " AND t.TafsiliID=c.TafsiliID)
					left join ACC_tafsilis t2 on(t2.TafsiliType=".TAFSILITYPE_ACCOUNTTYPE." AND t2.TafsiliID=AccountTafsiliID) 
					join BaseInfo b on(b.typeID=4 AND b.InfoID=CheckStatus)
			";
		$query .= ($where != "") ? " where " . $where : "";
		return parent::runquery($query, $whereParam);
	}

	function Add() {
		if (parent::insert("ACC_DocCheques", $this) === false)
			return false;

		$this->DocChequeID = parent::InsertID();

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->DocChequeID;
		$daObj->TableName = "ACC_DocCheques";
		$daObj->execute();
		return true;
	}

	function Edit() {
		$whereParams = array();
		$whereParams[":kid"] = $this->DocChequeID;

		if (parent::update("ACC_DocCheques", $this, " DocChequeID=:kid", $whereParams) === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->DocChequeID;
		$daObj->TableName = "ACC_DocCheques";
		$daObj->execute();
		return true;
	}

	static function Remove($DocChequeID) {
		$result = parent::delete("ACC_DocCheques", "DocChequeID=:kid ", array(":kid" => $DocChequeID));

		if ($result === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $DocChequeID;
		$daObj->TableName = "ACC_DocCheques";
		$daObj->execute();
		return true;
	}

}

class ACC_CostBlocks extends OperationClass {

	const TableName = "ACC_CostBlocks";
	const TableKey = "CostBlockID";

	public $CostBlockID;
	public $RegDate;
	public $RegPersonID;
	public $CostID;
	public $TafsiliType;
	public $TafsiliID;
	public $BlockAmount;
	public $IsActive;
	public $IsLock;
	public $EndDate;
	public $details;
	public $SourceType;
	public $SourceID;

	function __construct($id = '') {
		
		$this->DT_EndDate = DataMember::CreateDMA(DataMember::DT_DATE);
		
		parent::__construct($id);
	}
	
	static function Get($where = "", $param = array(), $pdo = null) {

		$query = "
			SELECT cb.*,
				cc.CostCode,
				concat_ws('-', b1.blockDesc, b2.blockDesc,b3.blockDesc,b4.blockDesc) CostDesc,
				bf1.InfoDesc TafsiliTypeDesc,
				TafsiliDesc
			
			FROM ACC_CostBlocks cb

			join ACC_CostCodes cc using(CostID)
			left join ACC_blocks b1 on(cc.level1=b1.BlockID)
			left join ACC_blocks b2 on(cc.level2=b2.BlockID)
			left join ACC_blocks b3 on(cc.level3=b3.BlockID)
			left join ACC_blocks b4 on(cc.level4=b4.BlockID)

			join BaseInfo bf1 on(bf1.TypeID=2 AND bf1.InfoID=cb.TafsiliType)
			join ACC_tafsilis using(TafsiliID)
		";

		return parent::runquery_fetchMode($query, $param);
	}

	static function GetBlockAmount($CostID, $TafsiliType, $TafsiliID, $pdo = null) {
		$dt = PdoDataAccess::runquery("select ifnull(sum(BlockAmount),0) BlockAmount 
			from ACC_CostBlocks 
			where CostID=? AND TafsiliType=? AND TafsiliID=? AND IsActive='YES'", array(
					$CostID, $TafsiliType, $TafsiliID), $pdo);
		return $dt[0][0] * 1;
	}

}

class ACC_DocHistory extends OperationClass {

	const TableName = "ACC_DocHistory";
	const TableKey = "RowID";

	public $RowID;
	public $DocID;
	public $PersonID;
	public $ActionDate;
	public $description;

	static function Get($where = "", $param = array(), $pdo = null) {

		$query = "
			SELECT h.*,
				concat_ws(' ',fname, lname, CompanyName) fullname
			
			FROM ACC_DocHistory h
				join BSC_persons using(PersonID)";

		return parent::runquery_fetchMode($query, $param);
	}
	
	static function AddLog($DocID, $desc){
		
		$obj = new ACC_DocHistory();
		$obj->PersonID = $_SESSION["USER"]["PersonID"];
		$obj->ActionDate = PDONOW;
		$obj->DocID = $DocID;
		$obj->description = $desc;
		
		PdoDataAccess::insert(self::TableName, $obj);				
	}

}

class ImportDoc extends PdoDataAccess{

	static function CheckCloseCycle($CycleID = ""){

		if(ACC_cycles::IsClosed($CycleID))
		{
			echo Response::createObjectiveResponse(false, "دوره مالی جاری بسته شده است و قادر به اعمال تغییرات نمی باشید");
			die();	
		}
	}

	static function FindCostID($costCode){

		$dt = PdoDataAccess::runquery("select * from ACC_CostCodes where IsActive='YES' AND CostCode=?",
			array($costCode));

		return count($dt) == 0 ? false : $dt[0]["CostID"];
	}

	static function FindTafsiliID($TafsiliCode, $TafsiliType){

		if($TafsiliType == TAFSILITYPE_PERSON)
			$dt = PdoDataAccess::runquery("select * from ACC_tafsilis "
				. "where IsActive='YES' AND ObjectID=? AND TafsiliType=?",
			array($TafsiliCode, $TafsiliType));
		else
			$dt = PdoDataAccess::runquery("select * from ACC_tafsilis "
				. "where IsActive='YES' AND TafsiliCode=? AND TafsiliType=?",
			array($TafsiliCode, $TafsiliType));

		if(count($dt) == 0)
		{
			ExceptionHandler::PushException("تفصیلی مربوطه یافت نشد.[" . $TafsiliType . "-" .  $TafsiliCode . "]");
			return false;
		}

		return $dt[0]["TafsiliID"];
	}

	//...............................................
	static function RegisterSalaryDoc($PObj, $pdo){

		$CycleID = $PObj->pay_year;
		self::CheckCloseCycle($CycleID);

		//-------------------------------------------------
		$query = "
			select salary_item_type_id,full_title
			FROM HRM_payments p
				JOIN HRM_payment_items pi ON(p.pay_year = pi.pay_year AND p.pay_month = pi.pay_month AND	
						p.staff_id = pi.staff_id AND p.payment_type = pi.payment_type)
				JOIN HRM_salary_item_types sit using(salary_item_type_id)
				left join ACC_CostCodes cc using(CostID)

			WHERE p.pay_year = ? and p.pay_month = ? and p.payment_type = ?
				and (sit.CostID is null or cc.CostID is null)
				and (pay_value > 0 or get_value>0)
			group by pi.salary_item_type_id
			order by pi.salary_item_type_id";

		$dt = PdoDataAccess::runquery_fetchMode($query, 
				array($PObj->pay_year, $PObj->pay_month,$PObj->payment_type), $pdo);
		if($dt->rowCount() > 0)
		{
			$str = "اقلام حقوقی زیر فاقد کد حساب می باشند و امکان صدور سند وجود ندارد<br>".
					"<table border=1 width=100%>";
			while ($row = $dt->fetch())
				$str .= "<tr><td style=padding:5px align=center>" . $row["salary_item_type_id"] . 
					"</td><td style=padding:5px>" . $row["full_title"] . "</td></tr>";

			$str .= "</table>";
			ExceptionHandler::PushException($str);
			return false;
		}

		//................. check register before .................

		$query = "select * from ACC_DocItems di join ACC_docs using(DocID)
			where SourceType=".DOCTYPE_SALARY." AND SourceID1=? AND SourceID2=? AND CycleID=" . $CycleID;
		$dt = PdoDataAccess::runquery($query, array($PObj->payment_type, $PObj->pay_month));

		if(count($dt) > 0)
		{
			ExceptionHandler::PushException("سند این ماه قبلا صادر شده است");
			return false;
		};
		//---------------- check for no salary payments ---------------

		$query = "	
			select p.pay_year
			FROM HRM_payment_items pi join HRM_payments p 
				ON(p.pay_year = pi.pay_year AND p.pay_month = pi.pay_month AND
					p.staff_id = pi.staff_id AND p.payment_type = pi.payment_type)
			WHERE p.pay_year = ? and p.pay_month = ? and p.payment_type = ? ";
		$dt = PdoDataAccess::runquery_fetchMode($query, 
				array($PObj->pay_year, $PObj->pay_month,$PObj->payment_type), $pdo);
		if($dt->rowCount() == 0)
		{
			ExceptionHandler::PushException("محاسبه حقوق این ماه هنوز انجام نشده است");
			return false;
		}
		//---------------- check for non-confirm salary payments ---------------

		$query = "	
			select p.pay_year
			FROM HRM_payment_items pi join HRM_payments p 
				ON(p.pay_year = pi.pay_year AND p.pay_month = pi.pay_month AND
					p.staff_id = pi.staff_id AND p.payment_type = pi.payment_type)
			WHERE p.pay_year = ? and p.pay_month = ? and p.payment_type = ? 
				and p.state=1 ";
		$dt = PdoDataAccess::runquery_fetchMode($query, 
				array($PObj->pay_year, $PObj->pay_month,$PObj->payment_type), $pdo);
		if($dt->rowCount() > 0)
		{
			ExceptionHandler::PushException("در این ماه محاسبات حقوق غیر قطعی وجود دارد و قادر به صدور سند نمی باشید.");
			return false;
		}
		//------------------ get CostCodes -------------------
		$dt = PdoDataAccess::runquery("select * from HRM_CostCodes");
		$CostCodes = array();
		foreach($dt as $row)
			$CostCodes[ $row["RowType"] ] = $row["CostID"];
		//---------------- add doc header --------------------
		$DocObj = new ACC_docs();
		$DocObj->RegDate = PDONOW;
		$DocObj->regPersonID = $_SESSION['USER']["PersonID"];
		$DocObj->DocDate = PDONOW;
		$DocObj->CycleID = $CycleID;
		$DocObj->BranchID = Default_BranchID;
		$DocObj->DocType = DOCTYPE_SALARY;
		$DocObj->description = " حقوق " . DateModules::GetMonthName($PObj->pay_month) . " ماه";
		if(!$DocObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد سند");
			return false;
		}
		//----------------- add Doc items ------------------------
		PdoDataAccess::runquery("
			insert into ACC_DocItems(DocID,CostID,TafsiliType,TafsiliID,DebtorAmount,CreditorAmount,
				details,locked,SourceType,SourceID1,SourceID2)

			select $DocObj->DocID,
				CostID,
				if(TafsiliID<>0 , 1 , null ) TafsiliType,
				if(TafsiliID=0, null, TafsiliID) TafsiliID,

				case when effect_type=1 then 
					if(amount>0,amount,0) 
				else
					if(amount<0,-1*amount,0)
				end,
				case when effect_type=1 then 
					if(amount<0,-1*amount,0)
				else
					if(amount>0,amount,0) 
				end,
				'".$DocObj->description ."',
				'YES',
				".DOCTYPE_SALARY.",
				".$PObj->payment_type.",
				".$PObj->pay_month."

			from (
				select 
					sit.CostID,sit.effect_type,
					if(sit.effect_type=2 AND sit.TafsiliID=0,t.TafsiliID, sit.TafsiliID) TafsiliID,
					round(sum(
						case when sit.effect_type = 1 then
							pit.pay_value + pit.diff_value_coef * pit.diff_pay_value 
						else
							pit.get_value + pit.diff_value_coef * pit.diff_get_value 
						end
						+ if(sit.Param1CostID>0, param1 + diff_param1_coef * diff_param1 , 0)
						+ if(sit.Param2CostID>0, param2 + diff_param2_coef * diff_param2 , 0)
						+ if(sit.Param3CostID>0, param3 + diff_param3_coef * diff_param3 , 0)
						+ if(sit.Param4CostID>0, param4 + diff_param4_coef * diff_param4 , 0)
					)) as amount

				FROM HRM_payments p
					JOIN HRM_payment_items pit
						ON(p.pay_year = pit.pay_year AND p.pay_month = pit.pay_month AND
						p.staff_id = pit.staff_id AND p.payment_type = pit.payment_type)
					JOIN HRM_salary_item_types sit using(salary_item_type_id)				
					JOIN HRM_staff s ON s.staff_id = p.staff_id
					join HRM_persons p1 on(p1.PersonID=s.PersonID)
					join BSC_persons p2 on(p1.RefPersonID=p2.PersonID)
					JOIN ACC_tafsilis t on(t.TafsiliType=".TAFSILITYPE_PERSON." AND ObjectID=p2.PersonID)
				WHERE p.pay_year = :py and p.pay_month = :pm and p.payment_type = :pt

				group by CostID, if(sit.effect_type=2 AND sit.TafsiliID=0,t.TafsiliID, sit.TafsiliID)
			)t 
			where amount<>0", array(":py" => $PObj->pay_year, ":pm" => $PObj->pay_month, 
				":pt" => $PObj->payment_type), $pdo);

		if(ExceptionHandler::GetExceptionCount() > 0)
		{
			$pdo->rollBack();
			//print_r(ExceptionHandler::PopAllExceptions());
			ExceptionHandler::PushException("خطا در بازیابی و اضافه رکورد ها از سیستم حقوق");
			return false;
		}
		//------------------- pure pay for each person -----------------------
		if(empty($CostCodes["PurePay"]))
		{
			$pdo->rollBack();
			ExceptionHandler::PushException("کد حساب خالص پرداختی تعریف نشده است");
			return false;
		}
		PdoDataAccess::runquery("
			insert into ACC_DocItems(DocID,CostID,details,
				TafsiliType2,TafsiliID2,DebtorAmount,CreditorAmount,
				locked,SourceType,SourceID1,SourceID2)

			select $DocObj->DocID,
				".$CostCodes["PurePay"].",
				'".$DocObj->description ."',
				".TAFSILITYPE_PERSON.",
				TafsiliID,
				if(amount<0,-1*amount,0),
				if(amount>0,amount,0), 
				'YES',
				".DOCTYPE_SALARY.",
				".$PObj->payment_type.",
				".$PObj->pay_month."

			from (
				select 
					t.TafsiliID,
					sum(pit.pay_value + pit.diff_value_coef * pit.diff_pay_value
						- (pit.get_value + pit.diff_value_coef * pit.diff_get_value)
					) as amount

				FROM HRM_payments p
					JOIN HRM_payment_items pit
						ON(p.pay_year = pit.pay_year AND p.pay_month = pit.pay_month AND
						p.staff_id = pit.staff_id AND p.payment_type = pit.payment_type)
					JOIN HRM_salary_item_types sit using(salary_item_type_id)				
					JOIN HRM_staff s ON s.staff_id = p.staff_id
					join HRM_persons p1 on(p1.PersonID=s.PersonID)
					join BSC_persons p2 on(p1.RefPersonID=p2.PersonID)
					JOIN ACC_tafsilis t on(t.TafsiliType=".TAFSILITYPE_PERSON." AND ObjectID=p2.PersonID)

				WHERE p.pay_year = :py and p.pay_month = :pm and p.payment_type = :pt
				group by TafsiliID
			)t 
			where amount<>0", array(":py" => $PObj->pay_year, ":pm" => $PObj->pay_month, 
				":pt" => $PObj->payment_type), $pdo);

		if(ExceptionHandler::GetExceptionCount() > 0)
		{
			$pdo->rollBack();
			ExceptionHandler::PushException("خطا در ثبت خالص حقوق");
			return false;
		}
		//---------------------  param1 and param2 --------------------------
		for($i=1; $i<=4; $i++)
		{
			PdoDataAccess::runquery("
				insert into ACC_DocItems(DocID,CostID,details,
					DebtorAmount,CreditorAmount,
					locked,SourceType,SourceID1,SourceID2)

				select $DocObj->DocID,
					Param".$i."CostID,
					'".$DocObj->description ."',
					if(effect_type=2,amount,0), 
					if(effect_type=1,amount,0),
					'YES',
					".DOCTYPE_SALARY.",
					".$PObj->payment_type.",
					".$PObj->pay_month."

				from (
					select sit.Param".$i."CostID,effect_type,
						sum(param".$i." + diff_param".$i."_coef * diff_param".$i.") as amount

					FROM HRM_payments p
						JOIN HRM_payment_items pit
							ON(p.pay_year = pit.pay_year AND p.pay_month = pit.pay_month AND
							p.staff_id = pit.staff_id AND p.payment_type = pit.payment_type)
						JOIN HRM_salary_item_types sit using(salary_item_type_id)

					WHERE p.pay_year = :py and p.pay_month = :pm and p.payment_type = :pt
						AND sit.Param".$i."CostID>0
					group by sit.Param".$i."CostID
				)t 
				where amount<>0", array(":py" => $PObj->pay_year, ":pm" => $PObj->pay_month, 
					":pt" => $PObj->payment_type), $pdo);
		}	
		//-------------------------- pay loans ---------------------------
		//RegisterCustomerPayDoc()
		//----------------------------------------------------------------
		if(ExceptionHandler::GetExceptionCount() > 0)
		{
			$pdo->rollBack();
			ExceptionHandler::PushException("خطا در ثبت پارامترهای اقلام");
			return false;
		}
		return true;
	} 

	static function ReturnSalaryDoc($PObj, $pdo){

		$CycleID = $PObj->pay_year;
		self::CheckCloseCycle($CycleID);

		//..........................................................................
		$dt = PdoDataAccess::runquery("select DocID,LocalNo,CycleDesc 
				from ACC_docs d join ACC_DocItems using(DocID) join ACC_cycles using(CycleID)
				where d.CycleID=? AND StatusID <> ".ACC_STEPID_RAW." AND SourceType=" . DOCTYPE_SALARY . " AND SourceID1=? AND SourceID2=?",
		array($CycleID, $PObj->payment_type, $PObj->pay_month), $pdo);
		if(count($dt) > 0)
		{
			echo Response::createObjectiveResponse(false, "سند مربوطه با شماره " . $dt[0]["LocalNo"] . " در ". 
					$dt[0]["CycleDesc"] ." تایید شده و قادر به برگشت نمی باشید");
			die();
		}
		$dt = PdoDataAccess::runquery("select DocID,LocalNo,CycleDesc 
				from ACC_docs d join ACC_DocItems using(DocID) join ACC_cycles using(CycleID)
				where d.CycleID=? AND SourceType=" . DOCTYPE_SALARY_PAY . " AND SourceID1=? AND SourceID2=?",
		array($CycleID, $PObj->payment_type, $PObj->pay_month), $pdo);
		if(count($dt) > 0)
		{
			echo Response::createObjectiveResponse(false, "سند پرداخت با شماره " . 
					$dt[0]["LocalNo"] . " در ". 
					$dt[0]["CycleDesc"] . " صادر شده و قادر به برگشت نمی باشید");
			die();
		}
		//..........................................................................

		$dt = PdoDataAccess::runquery("select DocID from ACC_DocItems join ACC_docs d using(DocID)
			where d.CycleID=? AND SourceType=" . DOCTYPE_SALARY . " AND SourceID1=? AND SourceID2=?",
			array($CycleID, $PObj->payment_type, $PObj->pay_month), $pdo);
		if(count($dt) == 0)
		{
			echo Response::createObjectiveResponse(false, "سندی یافت نشد");
			die();
		}

		PdoDataAccess::runquery("delete di from ACC_DocItems join ACC_docs using(DocID)
			where CycleID=? AND SourceType=" . DOCTYPE_SALARY . " AND SourceID1=? AND SourceID2=?",
			array($CycleID, $PObj->payment_type, $PObj->pay_month), $pdo);

		return ACC_docs::Remove($dt[0][0], $pdo);
	}

	static function RegisterPaySalaryDoc($PObj, $pdo){

		ini_set("display_errors", "On");
		$CycleID = $PObj->pay_year;
		self::CheckCloseCycle($CycleID);

		$query = "select LocalNo from ACC_DocItems di join ACC_docs using(DocID)
			where SourceType=".DOCTYPE_SALARY_PAY." AND SourceID1=? AND SourceID2=? AND CycleID=" . $CycleID;
		$dt = PdoDataAccess::runquery($query, array($PObj->payment_type, $PObj->pay_month));
		if(count($dt) > 0)
		{
			ExceptionHandler::PushException("سند این ماه قبلا در سند شماره"
				.$dt[0]["LocalNo"]. "صادر شده است");
			return false;
		};

		$query = "select sum(CreditorAmount) amount from ACC_DocItems di join ACC_docs using(DocID)
			where SourceType=".DOCTYPE_SALARY." AND SourceID1=? AND SourceID2=? AND CycleID=" . $CycleID;
		$dt = PdoDataAccess::runquery($query, array($PObj->payment_type, $PObj->pay_month));

		if($dt[0]["amount"] == 0)
		{
			ExceptionHandler::PushException("سند حقوق این ماه هنوز صادر نشده است");
			return false;
		};
		//------------------ get CostCodes -------------------
		$dt = PdoDataAccess::runquery("select * from HRM_CostCodes");
		$CostCodes = array();
		foreach($dt as $row)
			$CostCodes[ $row["RowType"] ] = $row["CostID"];
		//---------------------------------------------

		$DocObj = new ACC_docs();
		$DocObj->RegDate = PDONOW;
		$DocObj->regPersonID = $_SESSION['USER']["PersonID"];
		$DocObj->DocDate = PDONOW;
		$DocObj->CycleID = $CycleID;
		$DocObj->BranchID = Default_BranchID;
		$DocObj->DocType = DOCTYPE_SALARY_PAY;
		$DocObj->description = "پرداخت حقوق " . DateModules::GetMonthName($PObj->pay_month) . " ماه";
		if(!$DocObj->Add($pdo))
		{
			ExceptionHandler::PushException("خطا در ایجاد سند");
			return false;
		}

		PdoDataAccess::runquery("
			insert into ACC_DocItems(DocID,CostID,details,TafsiliType2,TafsiliID2,DebtorAmount,CreditorAmount,
				locked,SourceType,SourceID1,SourceID2)

			select $DocObj->DocID,
				".$CostCodes["PurePay"].",
				'".$DocObj->description ."',
				".TAFSILITYPE_PERSON.",
				TafsiliID,
				if(amount>0,amount,0), 
				if(amount<0,-1*amount,0),
				'YES',
				".DOCTYPE_SALARY_PAY.",
				".$PObj->payment_type.",
				".$PObj->pay_month."

			from (
				select 
					t.TafsiliID,
					sum(pit.pay_value + pit.diff_value_coef * pit.diff_pay_value
						- (pit.get_value + pit.diff_value_coef * pit.diff_get_value)
					) as amount

				FROM HRM_payments p
					JOIN HRM_payment_items pit
						ON(p.pay_year = pit.pay_year AND p.pay_month = pit.pay_month AND
						p.staff_id = pit.staff_id AND p.payment_type = pit.payment_type)
					JOIN HRM_salary_item_types sit using(salary_item_type_id)				
					JOIN HRM_staff s ON s.staff_id = p.staff_id
					join HRM_persons p1 on(p1.PersonID=s.PersonID)
					join BSC_persons p2 on(p1.RefPersonID=p2.PersonID)
					JOIN ACC_tafsilis t on(t.TafsiliType=".TAFSILITYPE_PERSON." AND ObjectID=p2.PersonID)

				WHERE p.pay_year = :py and p.pay_month = :pm and p.payment_type = :pt
				group by TafsiliID
			)t 
			where amount<>0", array(":py" => $PObj->pay_year, ":pm" => $PObj->pay_month, ":pt" => $PObj->payment_type), $pdo);
		if(ExceptionHandler::GetExceptionCount() > 0)
		{
			$pdo->rollback();
			ExceptionHandler::PushException("خطا در ایجاد ردیف های بدهکار");
			return false;
		}
		//----------------------- add bank row -----------------------
		$CostCode_bank = self::FindCostID("1010101");
		$ParkCostID = COSTID_BRANCH_PARK;
		$FerdowsiCostID = COSTID_BRANCH_UM;

		PdoDataAccess::runquery("
			insert into ACC_DocItems(DocID,CostID,TafsiliType,TafsiliID,DebtorAmount,CreditorAmount,
				locked,SourceType,SourceID1,SourceID2)

			select $DocObj->DocID,
				CreditorCostID,
				TafsiliType,
				TafsiliID,
				if(amount<0,-1*amount,0),
				if(amount>0,amount,0), 
				locked,
				".DOCTYPE_SALARY_PAY.",
				".$PObj->payment_type.",
				".$PObj->pay_month."

			from (
				select 
					case when pc.CostID is null then null
						 else if(pc.BranchID=:b,".TAFSILITYPE_PERSON.", null) end TafsiliType,
					case when pc.CostID is null then null
						 else if(pc.BranchID=:b,t.TafsiliID, null) end TafsiliID,				
					case when pc.CostID is null then $CostCode_bank
						 else if(pc.BranchID=:b,pc.CostID,$ParkCostID) end CreditorCostID,
					if(pc.CostID is not null,'YES', 'NO') locked,
					sum(pit.pay_value + pit.diff_value_coef * pit.diff_pay_value
						- (pit.get_value + pit.diff_value_coef * pit.diff_get_value)
					) as amount

				FROM HRM_payments p
					JOIN HRM_payment_items pit
						ON(p.pay_year = pit.pay_year AND p.pay_month = pit.pay_month AND
						p.staff_id = pit.staff_id AND p.payment_type = pit.payment_type)
					JOIN HRM_salary_item_types sit using(salary_item_type_id)				
					JOIN HRM_staff s ON s.staff_id = p.staff_id
					left JOIN HRM_StaffPaidCostCode pc on(s.staff_id=pc.StaffID AND j2g(p.pay_year,p.pay_month,29) between StartDate AND EndDate)
					join HRM_persons p1 on(p1.PersonID=s.PersonID)
					join BSC_persons p2 on(p1.RefPersonID=p2.PersonID)
					JOIN ACC_tafsilis t on(t.TafsiliType=".TAFSILITYPE_PERSON." AND ObjectID=p2.PersonID)

				WHERE p.pay_year = :py and p.pay_month = :pm and p.payment_type = :pt
				group by TafsiliType,TafsiliID,CreditorCostID
			)t 
			where amount<>0", array(":py" => $PObj->pay_year, 
				":pm" => $PObj->pay_month, 
				":pt" => $PObj->payment_type,
				":b" => BRANCH_UM), $pdo);
		if(ExceptionHandler::GetExceptionCount() > 0)
		{
			$pdo->rollback();
			ExceptionHandler::PushException("خطا در ایجاد ردیف های بستانکار");
			return false;
		}
		//------------------ register park doc --------------------------
		$dt = PdoDataAccess::runquery("
				select sum(pit.pay_value + pit.diff_value_coef * pit.diff_pay_value
						- (pit.get_value + pit.diff_value_coef * pit.diff_get_value)
					) amount

				FROM HRM_payments p
					JOIN HRM_payment_items pit
						ON(p.pay_year = pit.pay_year AND p.pay_month = pit.pay_month AND
						p.staff_id = pit.staff_id AND p.payment_type = pit.payment_type)
					JOIN HRM_salary_item_types sit using(salary_item_type_id)				
					JOIN HRM_staff s ON s.staff_id = p.staff_id
					left JOIN HRM_StaffPaidCostCode pc on(s.staff_id=pc.StaffID AND j2g(p.pay_year,p.pay_month,29) between StartDate AND EndDate)

				WHERE p.pay_year = :py and p.pay_month = :pm and p.payment_type = :pt and pc.BranchID=:b
			", array(":py" => $PObj->pay_year, 
				":pm" => $PObj->pay_month, 
				":pt" => $PObj->payment_type,
				":b" => BRANCH_PARK), $pdo);
		$totalParkAmount = $dt[0][0]*1;
		if($totalParkAmount > 0)
		{
			$DocObj2 = new ACC_docs();
			$DocObj2->RegDate = PDONOW;
			$DocObj2->regPersonID = $_SESSION['USER']["PersonID"];
			$DocObj2->DocDate = PDONOW;
			$DocObj2->CycleID = $CycleID;
			$DocObj2->BranchID = BRANCH_PARK;
			$DocObj2->DocType = DOCTYPE_SALARY_PAY;
			$DocObj2->description = "پرداخت حقوق " . DateModules::GetMonthName($PObj->pay_month) . " ماه";
			if(!$DocObj2->Add($pdo))
			{
				ExceptionHandler::PushException("خطا در ایجاد سند پارک");
				return false;
			}

			PdoDataAccess::runquery("
			insert into ACC_DocItems(DocID,CostID,TafsiliType,TafsiliID,DebtorAmount,CreditorAmount,
				locked,SourceType,SourceID1,SourceID2)

			select $DocObj2->DocID,
				CreditorCostID,
				TafsiliType,
				TafsiliID,
				if(amount<0,-1*amount,0),
				if(amount>0,amount,0), 
				locked,
				".DOCTYPE_SALARY_PAY.",
				".$PObj->payment_type.",
				".$PObj->pay_month."

			from (
				select 
					".TAFSILITYPE_PERSON." TafsiliType,
					t.TafsiliID ,				
					pc.CostID CreditorCostID,
					'YES' locked,
					sum(pit.pay_value + pit.diff_value_coef * pit.diff_pay_value
						- (pit.get_value + pit.diff_value_coef * pit.diff_get_value)
					) as amount

				FROM HRM_payments p
					JOIN HRM_payment_items pit
						ON(p.pay_year = pit.pay_year AND p.pay_month = pit.pay_month AND
						p.staff_id = pit.staff_id AND p.payment_type = pit.payment_type)
					JOIN HRM_salary_item_types sit using(salary_item_type_id)				
					JOIN HRM_staff s ON s.staff_id = p.staff_id
					left JOIN HRM_StaffPaidCostCode pc on(s.staff_id=pc.StaffID AND j2g(p.pay_year,p.pay_month,29) between StartDate AND EndDate)
					join HRM_persons p1 on(p1.PersonID=s.PersonID)
					join BSC_persons p2 on(p1.RefPersonID=p2.PersonID)
					JOIN ACC_tafsilis t on(t.TafsiliType=".TAFSILITYPE_PERSON." AND ObjectID=p2.PersonID)

				WHERE p.pay_year = :py and p.pay_month = :pm and p.payment_type = :pt AND pc.BranchID=:b
				group by TafsiliType,TafsiliID,CreditorCostID
			)t 
			where amount<>0", array(":py" => $PObj->pay_year, 
				":pm" => $PObj->pay_month, 
				":pt" => $PObj->payment_type,
				":b" => BRANCH_PARK), $pdo);
			if(ExceptionHandler::GetExceptionCount() > 0)
			{
				$pdo->rollback();
				ExceptionHandler::PushException("خطا در ایجاد ردیف های بستانکار سند پارک");
				return false;
			}		

			$itemObj = new ACC_DocItems();
			$itemObj->DocID = $DocObj2->DocID;
			$itemObj->CostID = $FerdowsiCostID;
			$itemObj->DebtorAmount = $totalParkAmount;
			$itemObj->SourceType = DOCTYPE_SALARY_PAY;
			$itemObj->SourceID1 = $PObj->payment_type;
			$itemObj->SourceID2 = $PObj->pay_month;
			if(!$itemObj->Add($pdo))
			{
				ExceptionHandler::PushException("خطا در ایجاد سند ردیف بستانکار سند پارک");
				return false;
			}
		}

		//---------------------------------------------------------------

		if(ExceptionHandler::GetExceptionCount() > 0)
		{
			print_r(ExceptionHandler::PopAllExceptions());
			ExceptionHandler::PushException("خطا در صدور سند");
			return false;
		}
		return true;
	}

	static function ReturnPaySalaryDoc($PObj, $pdo){
	
	$CycleID = $PObj->pay_year;
	self::CheckCloseCycle($CycleID);
	
	//..........................................................................
	$dt = PdoDataAccess::runquery("select DocID,LocalNo,CycleDesc
			from ACC_docs d join ACC_DocItems using(DocID)  join ACC_cycles using(CycleID)
			where d.CycleID=? AND StatusID <> ".ACC_STEPID_RAW." AND SourceType=" . DOCTYPE_SALARY_PAY . 
			" AND SourceID1=? AND SourceID2=?",
	array($CycleID, $PObj->payment_type, $PObj->pay_month), $pdo);
	if(count($dt) > 0)
	{
		echo Response::createObjectiveResponse(false, "سند مربوطه با شماره " . $dt[0]["LocalNo"] . " در ". 
				$dt[0]["CycleDesc"] ." تایید شده و قادر به برگشت نمی باشید");
		die();
	}
	//..........................................................................
	
	$dt = PdoDataAccess::runquery("select DocID from ACC_DocItems  join ACC_docs using(DocID)
		where CycleID=? AND SourceType=" . DOCTYPE_SALARY_PAY . " AND SourceID1=? AND SourceID2=?",
		array($CycleID, $PObj->payment_type, $PObj->pay_month), $pdo);
	if(count($dt) == 0)
	{
		echo Response::createObjectiveResponse(false, "سندی یافت نشد");
		die();
	}
	
	PdoDataAccess::runquery("delete di from ACC_DocItems di join ACC_docs using(DocID)
		where CycleID=? AND SourceType=" . DOCTYPE_SALARY_PAY . " AND SourceID1=? AND SourceID2=?",
		array($CycleID, $PObj->payment_type, $PObj->pay_month), $pdo);

	foreach($dt as $row)
		ACC_docs::Remove($row["DocID"], $pdo);
	
	return true;
}

}
?>
