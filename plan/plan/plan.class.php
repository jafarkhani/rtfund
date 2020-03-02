<?php
//---------------------------
// programmer:	Jafarkhani
// create Date: 94.06
//---------------------------

require_once getenv("DOCUMENT_ROOT") . '/office/workflow/wfm.class.php';
require_once getenv("DOCUMENT_ROOT") . '/office/dms/dms.class.php';

class PLN_plans extends PdoDataAccess
{
    public $PlanID;
    public $FormType;
    public $PlanDesc;
    public $LoanID;
    public $PersonID;
    public $RegDate;
    public $StepID;
    public $SupportPersonID;
    public $LetterID;
    public $FacilityAmount;
    public $evaluationType;
    public $evaluationAskerID;
    public $AddDate;
    public $AdderPersonID;
    public $EditDate;
    public $EditorPersonID;
    public $technologyArea;
    public $IsGetCost;
    public $evaluationCost;
    public $ApprovedAmount;
    public $FinancialBroker;
    public $EvaluationBroker;
    public $RecordMeetingID;
    public $RecordMeetingDate;
    public $wage;
    public $LoanType;


    function __construct($PlanID = "") {

        if($PlanID != "")
            PdoDataAccess::FillObject ($this, "select * from PLN_plans where PlanID=?", array($PlanID));
    }

    static function SelectAll($where = "", $param = array(), $order= ""){

        return PdoDataAccess::runquery_fetchMode("
			select p.* ,if(p1.IsReal='YES',concat(p1.fname, ' ', p1.lname),p1.CompanyName) ReqFullname, StepDesc, ol.LetterDate,bi.InfoDesc,
			concat_ws(' ',p2.fname, p2.lname,p2.CompanyName) askername
			from PLN_plans p
			join BSC_persons p1 using(PersonID)
			left join BSC_persons p2 on(p.evaluationAskerID=p2.PersonID)
			left join BaseInfo bi on(bi.TypeID=97 AND p.evaluationType=bi.InfoID)
			left join WFM_FlowSteps fs on(FlowID=" . FLOWID . " AND p.StepID=fs.StepID)
			left join PLN_experts e on(p.PlanID=e.PlanID)
			left join OFC_letters ol on(p.LetterID=ol.LetterID)
			where " . $where . " group by p.PlanID " . $order, $param);
    }
    static function IsPlanExist($Type,$Val){
        $param = $Val;
        if($Type == 'Loan')
            $where = "p.LoanID=?";
        if($Type == 'Letter')
            $where = "p.LetterID=?";

        /*echo 'param:'.$param;
        echo '<br>';
        echo 'where:'.$where;*/


        $dt = PLN_plans::SelectAll($where, $param, dataReader::makeOrder());
        $count = $dt->rowCount();
        if ($count > 0){
            return true;
        }else{
            return false;
        }
        /*echo dataReader::getJsonData($dt, $count, $_GET["callback"]);
        die();*/
    }

    function AddPlan($pdo = null){

        $this->RegDate = PDONOW;

        if(!parent::insert("PLN_plans",$this, $pdo))
            return false;
        $this->PlanID = parent::InsertID($pdo);

        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_add;
        $daObj->MainObjectID = $this->PlanID;
        $daObj->TableName = "PLN_plans";
        $daObj->execute($pdo);
        return true;
    }

    function EditPlan($pdo = null){

        if( parent::update("PLN_plans",$this," PlanID=:l", array(":l" => $this->PlanID), $pdo) === false )
            return false;

        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_update;
        $daObj->MainObjectID = $this->PlanID;
        $daObj->TableName = "PLN_plans";
        $daObj->execute($pdo);
        return true;
    }

    static function DeletePlan($PlanID){

        $obj = new PLN_plans($PlanID);
        if($obj->StepID != STEPID_RAW)
            return false;

        if(!DMS_documents::DeleteAllDocument($obj->PlanID, "plan"))
        {
            ExceptionHandler::PushException("خطا در حذف مدارک");
            return false;
        }

        if( parent::delete("PLN_PlanItems"," PlanID=?", array($PlanID)) === false )
            return false;

        if( parent::delete("PLN_plans"," PlanID=?", array($PlanID)) === false )
            return false;

        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_delete;
        $daObj->MainObjectID = $PlanID;
        $daObj->TableName = "PLN_plans";
        $daObj->execute();
        return true;
    }

    static function ChangeStatus($PlanID, $StepID, $ActDesc = "", $LogOnly = false, $pdo = null){

        if(!$LogOnly)
        {
            $obj = new PLN_plans();
            $obj->PlanID = $PlanID;
            $obj->StepID = $StepID;
            if(!$obj->EditPlan($pdo))
                return false;
        }

        return WFM_FlowRows::AddOuterFlow(FLOWID, $PlanID, $StepID, $ActDesc, $pdo);
    }

    static function ChangeStatuses($PlanID, $StepID, $ActDesc = "", $LogOnly = false, $pdo = null){

        if(!$LogOnly)
        {
            $obj = new PLN_plans();
            $obj->PlanID = $PlanID;
            $obj->StepID = $StepID;
            if(!$obj->EditPlan($pdo))
                return false;
        }

        return WFM_FlowRows::AddOuterFlows(FLOWID, $PlanID, $StepID, $ActDesc, $pdo);
    }

}

class PLN_PlanItems extends PdoDataAccess
{
    public $RowID;
    public $PlanID;
    public $ElementID;
    public $ElementValue;

    function __construct($RowID = "") {

        if($RowID != "")
            PdoDataAccess::FillObject ($this, "select * from PLN_PlanItems where RowID=?", array($RowID));
    }

    static function SelectAll($where = "", $param = array()){

        return PdoDataAccess::runquery("select * from PLN_PlanItems	where " . $where, $param);
    }

    function AddItem($pdo = null){

        if (!parent::insert("PLN_PlanItems", $this, $pdo)) {
            return false;
        }
        $this->RowID = parent::InsertID($pdo);

        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_add;
        $daObj->MainObjectID = $this->RowID;
        $daObj->TableName = "PLN_PlanItems";
        $daObj->execute($pdo);
        return true;
    }

    function EditItem($pdo = null){

        if( parent::update("PLN_PlanItems",$this," RowID=:l", array(":l" => $this->RowID), $pdo) === false )
            return false;

        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_update;
        $daObj->MainObjectID = $this->RowID;
        $daObj->TableName = "PLN_PlanItems";
        $daObj->execute($pdo);
        return true;
    }

    static function DeleteItem($RowID){

        if( parent::delete("PLN_PlanItems"," RowID=?", array($RowID)) === false )
            return false;

        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_delete;
        $daObj->MainObjectID = $RowID;
        $daObj->TableName = "PLN_PlanItems";
        $daObj->execute();
        return true;
    }
}

class PLN_PlanSurvey extends PdoDataAccess
{
    public $RowID;
    public $PlanID;
    public $GroupID;
    public $ActDate;
    public $ActType;
    public $ActDesc;
    public $ActPersonID;

    function __construct($RowID = "") {

        if($RowID != "")
            PdoDataAccess::FillObject ($this, "select * from PLN_PlanSurvey where RowID=?", array($RowID));
    }

    static function SelectAll($where = "", $param = array()){

        return PdoDataAccess::runquery("select * from PLN_PlanSurvey where " . $where, $param);
    }

    function AddRow($pdo = null){

        if (!parent::insert("PLN_PlanSurvey", $this, $pdo)) {
            return false;
        }
        $this->RowID = parent::InsertID($pdo);

        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_add;
        $daObj->MainObjectID = $this->RowID;
        $daObj->TableName = "PLN_PlanSurvey";
        $daObj->execute($pdo);
        return true;
    }

    function EditRow($pdo = null){

        if( parent::update("PLN_PlanSurvey",$this," RowID=:l", array(":l" => $this->RowID), $pdo) === false )
            return false;

        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_update;
        $daObj->MainObjectID = $this->RowID;
        $daObj->TableName = "PLN_PlanSurvey";
        $daObj->execute($pdo);
        return true;
    }

    static function DeleteRow($RowID){

        if( parent::delete("PLN_PlanSurvey"," RowID=?", array($RowID)) === false )
            return false;

        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_delete;
        $daObj->MainObjectID = $RowID;
        $daObj->TableName = "PLN_PlanSurvey";
        $daObj->execute();
        return true;
    }
}

class PLN_experts extends OperationClass {

    const TableName = "PLN_experts";
    const TableKey = "RowID";

    public $RowID;
    public $PlanID;
    public $PersonID;
    public $RegDate;
    public $SendDesc;
    public $DoneDesc;
    public $StatusDesc;
    public $EndDate;
    public $DoneDate;
    public $ScopeID;
    public $IsSeen;

    function __construct($id = ""){

        $this->DT_RegDate = DataMember::CreateDMA(DataMember::DT_DATE);
        $this->DT_EndDate = DataMember::CreateDMA(DataMember::DT_DATE);
        $this->DT_DoneDate = DataMember::CreateDMA(DataMember::DT_DATE);

        parent::__construct($id);
    }

    public static function Get($where = '', $whereParams = array()) {

        return parent::runquery_fetchMode("
			select e.*, concat_ws(' ',fname, lname,CompanyName) fullname,
				InfoDesc ScopeDesc
			from PLN_experts e
			join BaseInfo bf on(TypeID=21 AND InfoID=ScopeID)
			left join BSC_persons p1 using(PersonID) where 1=1 " . $where, $whereParams);
    }
}

class PLN_PlanEvents extends OperationClass {

    const TableName = "PLN_PlanEvents";
    const TableKey = "EventID";

    public $EventID;
    public $PlanID;
    public $EventTitle;
    public $EventDate;

    function __construct($id = ""){

        $this->DT_EventDate = DataMember::CreateDMA(DataMember::DT_DATE);

        parent::__construct($id);
    }

}
?>
