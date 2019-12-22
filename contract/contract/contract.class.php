<?php
//-----------------------------
//	Programmer	: Fatemipour
//	Date		: 94.08
//-----------------------------

require_once getenv("DOCUMENT_ROOT") . '/office/dms/dms.class.php';
require_once inc_CurrencyModule;

class CNT_contracts extends OperationClass {

    const TableName = "CNT_contracts";
    const TableKey = "ContractID";

    public $ContractID;
    public $TemplateID;
    public $RegPersonID;
    public $RegDate;
    public $description;
    public $PersonID;
	public $PersonID2;
    public $StartDate;
    public $EndDate;
	public $content;
	public $ContractType;
	public $LoanRequestID;
	public $WarrentyRequestID;
	public $ContractAmount;
	public $StatusID;

	public $_TemplateTitle;
	public $_PersonName;
    
    public function __construct($id = ""){
        
		$this->DT_StartDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_EndDate = DataMember::CreateDMA(DataMember::DT_DATE);
		
        if ($id != ''){
            parent::FillObject($this, "
					select c.* ,  t.TemplateTitle as _TemplateTitle,
						concat_ws(' ',fname,lname,CompanyName) _PersonName
                    from CNT_contracts c
                    left join CNT_templates t using(TemplateID) 
					left join BSC_persons using(PersonID)
					where c." . static::TableKey . " = :id", array(":id" => $id));
        }
    }

    public static function Get($content = false, $where = '', $whereParams = array(), $order = "") {
		
        return parent::runquery_fetchMode("
			select c.ContractID,
				c.TemplateID,
				c.RegPersonID,
				c.RegDate,
				c.description,
				c.PersonID,
				c.PersonID2,
				c.StartDate,
				c.EndDate,
				c.StatusID,
				sp.StepID,
				c.ContractAmount,
				concat(if(fr.ActionType='REJECT','رد ',''),sp.StepDesc) StepDesc,
				fr.ActionType," .
				($content ? "c.content," : "") .
				"c.ContractType,
				c.LoanRequestID,
				c.WarrentyRequestID,
				c.ContractAmount,
				t.TemplateTitle,
				b1.InfoDesc,
				concat_ws(' ',p1.fname,p1.lname,p1.CompanyName) PersonFullname
			  
			from CNT_contracts c  
			join CNT_templates t using(TemplateID) 
			join WFM_FlowSteps sp on(sp.FlowID=" . FLOWID_CONTRACT . " AND sp.StepID=c.StatusID)
			left join BSC_persons p1 on(c.PersonID=p1.PersonID)
			left join WFM_FlowRows fr on(fr.IsLastRow='YES' AND fr.ObjectID=c.ContractID 
				AND fr.StepRowID=sp.StepRowID AND fr.FlowID=sp.FlowID)
			left join BaseInfo b1 on(b1.TypeID=18 AND c.ContractType=b1.InfoID)
				
			where 1=1 " . $where . " group by ContractID " . $order, $whereParams);
    }

	public function GetContractContext(){

		if($this->StatusID != CNT_STEPID_RAW)
			return $this->content;
		
		$temp = parent::runquery("
			select c.ContractID,
				c.RegDate,
				c.description,
				c.StartDate,
				c.EndDate,
				c.ContractType,
				c.LoanRequestID,
				c.ContractAmount,

				concat_ws(' ',p1.fname,p1.lname,p1.CompanyName) PersonFullname,
				p1.NationalID,
				p1.address,
				p1.mobile,
				p1.WebSite,
				p1.email,
				p1.PostalCode,
				p1.RegNo,
				p1.RegDate,
				p1.EconomicID,
				bfp1.InfoDesc CompanyTypeDesc,
				
				concat_ws(' ',p2.fname,p2.lname,p2.CompanyName) PersonFullname2,
				p2.NationalID NationalID2,
				p2.address address2,
				p2.mobile mobile2,
				p2.WebSite WebSite2,
				p2.email email2,
				bfp2.InfoDesc CompanyTypeDesc2,

				r.RequestID,
				r.ReqAmount,
				rp.InstallmentCount,
				rp.CustomerWage,
				rp.DelayMonths,
				concat(rp.PayInterval,' ',if(IntervalType='MONTH','ماه','روز')) PayInterval,
				
				wr.organization WAR_organization,
				wr.amount WAR_amount,
				wr.StartDate WAR_StartDate,
				wr.EndDate WAR_EndDate,
				wr.wage WAR_wage,
				wr.LetterNo WAR_LetterNo,
				wr.LetterDate WAR_LetterDate
	
			from CNT_contracts c 
			left join LON_requests r on(LoanRequestID=RequestID)
			left join LON_ReqParts rp on(rp.IsHistory='NO' AND rp.RequestID=r.RequestID)
			left join WAR_requests wr on(c.WarrentyRequestID=wr.RequestID)
			
			left join BSC_persons p1 on(c.PersonID=p1.PersonID)
			left join BaseInfo bfp1 on(bfp1.TypeID=14 AND p1.CompanyType=bfp1.InfoID)
			left join BSC_persons p2 on(c.PersonID2=p2.PersonID)
			left join BaseInfo bfp2 on(bfp2.TypeID=14 AND p2.CompanyType=bfp2.InfoID)
			
			where ContractID=? group by ContractID ", array($this->ContractID));
		
		//print_r(ExceptionHandler::PopAllExceptions());
		$ContractRecord = $temp[0];
		//---------------------- installment Info --------------------------
		if($this->LoanRequestID > 0)
		{
			$dt = PdoDataAccess::runquery("select * from LON_installments "
					. " where history='NO' AND IsDelayed='NO' AND RequestID=?",
				array($this->LoanRequestID));
			if(count($dt) > 0)
			{
				$ContractRecord["InstallmentStartDate"] = $dt[0]["InstallmentDate"];
				$ContractRecord["InstallmentEndDate"] = $dt[count($dt)-1]["InstallmentDate"];
				$ContractRecord["InstallmentAmount"] = $dt[0]["InstallmentAmount"];
				$ContractRecord["LastInstallmentAmount"] = $dt[count($dt)-1]["InstallmentAmount"];
				unset($dt);
			}
		}
		//---------------------- Warrenty Info --------------------------
		if($this->WarrentyRequestID > 0)
		{
			require_once getenv("DOCUMENT_ROOT") . '/loan/warrenty/request.class.php';
			$warObj = new WAR_requests($this->WarrentyRequestID);
			$days = DateModules::GDateMinusGDate($warObj->EndDate,$warObj->StartDate);
			$days -= 1;
			$TotalWage = round($days*$warObj->amount*(1-$warObj->SavePercent/100)*$warObj->wage/36500);	
			
			$ContractRecord["WAR_WageAmount"] = $TotalWage;
			$ContractRecord["WAR_SepordeAmount"] = $warObj->amount*$warObj->SavePercent/100;
		}
		//------------------------------------------------------------------
		$temp = CNT_TemplateItems::Get(" AND TemplateID in(0,?)", array($this->TemplateID));
		$TplItems = $temp->fetchAll();

		$TplItemsStore = array();
		foreach ($TplItems as $it) {
			$TplItemsStore[$it['TemplateItemID']] = $it;
		}
		$res = explode(CNTconfig::TplItemSeperator, $this->content);
		$CntItems = CNT_ContractItems::GetContractItems($this->ContractID);

		$ValuesStore = array();
		foreach ($CntItems as $it) {
			$ValuesStore[$it['TemplateItemID']] = $it['ItemValue'];
		}

		if (substr($this->content, 0, 3) == CNTconfig::TplItemSeperator) {
			$res = array_merge(array(''), $res);
		}

		$st = '';
		for ($i = 0; $i < count($res); $i++) {
			if ($i % 2 != 0) {
				$tempValue = "";
				$TempType = $TplItemsStore[ $res[$i] ]["ItemType"];
				
				if($TempType == "block")
				{
					$st .= $this->BlockInfo( $TplItemsStore[ $res[$i] ]["FieldName"] );
				}
				else if(isset($ValuesStore[$res[$i]]))
				{
					$tempValue = $ValuesStore[$res[$i]];
				}
				else if(isset($TplItemsStore[ $res[$i] ]["FieldName"]))
				{
					$tempValue = $ContractRecord [ $TplItemsStore[ $res[$i] ]["FieldName"] ];
				}

				switch ($TempType) {
					case 'shdatefield':
						$st .= DateModules::miladi_to_shamsi($tempValue);
						break;
					case 'currencyfield':
						$st .= number_format($tempValue) . " ( " . 
							CurrencyModulesclass::CurrencyToString($tempValue) . " ) ریال";
						break;
					case "charCurrencyfield":
						$st .= CurrencyModulesclass::CurrencyToString($tempValue);
						break;
					default : 
						$st .= nl2br($tempValue);
				}

			} else {
				$st .= $res[$i];
			}
		}
		
		return $st;
	}
	
	private function BlockInfo($FieldName){
		switch($FieldName)
		{
			case "OrgSigners":
				$dt = PdoDataAccess::runquery("select * from BSC_OrgSigners "
					. "where PersonID=?", array($this->PersonID));
				if(count($dt) == 0)
					return "";
				$returnStr = "<table width=100% border=1 style=border-collapse:collapse>
					<caption>صاحبین امضاء</caption>
					<tr>
						<th>نام و نام خانوادگی</th>
						<th>پست</th>
						<th>نام پدر</th>
						<th>شماره شناسنامه</th>
						<th>محل صدور</th>
						<th>تاریخ تولد</th>
						<th>کد ملی</th>
						<th>تلفن ثابت</th>
						<th>تلفن همراه</th>
						<th>آدرس</th>
						<th>کد پستی</th>
					</tr>
				";
				foreach($dt as $row){
					$returnStr .= "<tr>
						<td>" . ($row["sex"] == "MALE" ? "آقای " : "خانم ") . $row["fullname"] . "</td>
						<td>" . $row["PostDesc"] . "</td>
						<td>" . $row["FatherName"] . "</td>
						<td>" . $row["ShNo"] . "</td>
						<td>" . $row["ShPlace"] . "</td>
						<td>" . DateModules::miladi_to_shamsi($row["BirthDate"]) . "</td>
						<td>" . $row["NationalID"] . "</td>
						<td>" . $row["telephone"] . "</td>
						<td>" . $row["mobile"] . "</td>
						<td>" . $row["address"] . "</td>
						<td>" . $row["PostalCode"] . "</td>
					</tr>";
				}				
				return $returnStr . "</table>";
			//..................................................................
			case "documents":
				$dt = PdoDataAccess::runquery("
					SELECT d.DocType,d.DocumentID,ParamType,ParamDesc,ParamValue,b.InfoDesc
						FROM DMS_documents d
						join BaseInfo b on(InfoID=DocType AND TypeID=8 AND param1=1)
						join DMS_DocParams using(DocType)
						left join DMS_DocParamValues  using(ParamID,DocumentID)

						where  ObjectType='warrenty' AND ObjectID=?
					order by d.DocType,DocumentID,ParamID",array(
						$this->ContractType == "1" ? $this->LoanRequestID : $this->WarrentyRequestID));
				if(count($dt) == 0)
					return "";
				
				$returnStr = "";
				$currentDocType = 0;
				$currenctDocumentID = 0;
				for($i=0; $i<count($dt); $i++)
				{
					if($dt[$i]["DocType"] != $currentDocType)
					{
						$currenctDocumentID = $dt[$i]["DocumentID"];
						$currentDocType = $dt[$i]["DocType"];
						$returnStr .= "<table width=100% border=1 style=border-collapse:collapse>
							<caption>" . $dt[$i]["InfoDesc"] . "</caption><tr>";
						for($j=$i; $j<count($dt); $j++)
						{
							if($dt[$j]["DocumentID"] == $currenctDocumentID)
								$returnStr .= "<th>" . $dt[$j]["ParamDesc"] . "</th>";
						}
						$returnStr .= "</tr>";			
						$currenctDocumentID = 0;
					}
					
					if($dt[$i]["DocumentID"] != $currenctDocumentID)
						$returnStr .= "<tr>";
					//..............................................
					$value = $dt[$i]["ParamValue"];
					switch($dt[$i]["ParamType"])
					{
						case "currencyfield" : $value = number_format($value); break;
						case "shdatefield" : $value = DateModules::miladi_to_shamsi($value); break;
						case "textarea" : $value = hebrevc($value); break;
					}
					$returnStr .= "<td>" . $value . "</td>";
					//..............................................
					$currenctDocumentID = $dt[$i]["DocumentID"];	
					if($i+1 >= count($dt) || $dt[$i+1]["DocumentID"] != $currenctDocumentID)
						$returnStr .= "</tr>";
					if($i+1 >= count($dt) || $dt[$i+1]["DocType"] != $currentDocType)
						$returnStr .= "</table><br>";
				}
				return $returnStr;
			//..................................................................
			case "guarantors":
				if($this->ContractType == "1")
					$dt = PdoDataAccess::runquery("select * from LON_guarantors "
					. "where RequestID=? AND PersonType='GUARANTOR'", array($this->LoanRequestID));
				else
					$dt = PdoDataAccess::runquery("select * from WAR_guarantors "
					. "where RequestID=? AND PersonType='GUARANTOR'", array($this->WarrentyRequestID));
				break;		
			//..................................................................
			case "sponsors":
				if($this->ContractType == "1")
					$dt = PdoDataAccess::runquery("select * from LON_guarantors "
					. "where RequestID=? AND PersonType='SPONSOR'", array($this->LoanRequestID));
				else
					$dt = PdoDataAccess::runquery("select * from WAR_guarantors "
					. "where RequestID=? AND PersonType='SPONSOR'", array($this->WarrentyRequestID));
				break;
			//..................................................................
		}
		
		if(count($dt) == 0)
			return "";
		$returnStr = "<table width=100% border=1 style=border-collapse:collapse>
			<tr>
				<td>نام و نام خانوادگی</td>
				<td>نام پدر</td>
				<td>کد ملی</td>
				<td>شماره شناسنامه</td>
				<td>تاریخ تولد</td>
				<td>محل تولد</td>
				<td>تلفن ثابت</td>
				<td>تلفن همراه</td>
				<td>آدرس</td>
			</tr>
		";
		foreach($dt as $row){
			$returnStr .= "<tr>
				<td>" . ($row["sex"] == "MALE" ? "آقای " : "خانم ") . $row["fullname"] . "</td>
				<td>" . $row["father"] . "</td>
				<td>" . $row["NationalCode"] . "</td>
				<td>" . $row["ShNo"] . "</td>
				<td>" . DateModules::miladi_to_shamsi($row["BirthDate"]) . "</td>
				<td>" . $row["ShCity"] . "</td>
				<td>" . $row["phone"] . "</td>
				<td>" . $row["mobile"] . "</td>
				<td>" . $row["address"] . "</td>
			</tr>";
		}				
		return $returnStr . "</table>";
	}
	
	public function Remove()
	{
		if(!DMS_documents::DeleteAllDocument($this->ContractID, "contract"))
		{
			ExceptionHandler::PushException("خطا در حذف مدارک");
	 		return false;
		}
		
		return parent::Remove();
	}
}

class CNT_ContractItems extends OperationClass {

    const TableName = "CNT_ContractItems";
    const TableKey = "ContractItemID";

    public $ContractItemID;
    public $ContractID;
    public $TemplateItemID;
    public $ItemValue;

    public function __construct($id = "") {
        parent::__construct($id);
    }

    public static function RemoveAll($ContractID, $pdo = null) {
		
        return parent::delete(static::TableName, "ContractID=:ContractID", array(":ContractID" => $ContractID), $pdo);
    }

    public static function GetContractItems($ContractID) {
        
        return parent::runquery("select * from " . static::TableName . " where ContractID=:ContractID", 
				array(":ContractID" => $ContractID));        
    }

}

class CNT_ContractSigns extends OperationClass
{
	const TableName = "CNT_ContractSigns";
	const TableKey = "SignID";
	
	public $SignID; 
	public $ContractID;
	public $PersonID;
	public $SignerPost;
	public $SignerName;
	public $description;

	public static function Get($where = '', $whereParams = array()) {
		
		$query = "select s.*,concat(fname,' ',lname) fullname
		from CNT_ContractSigns s
			left join BSC_persons using(PersonID)
		where 1=1 " . $where;
		
		return PdoDataAccess::runquery($query, $whereParams);
	}
}
?>
