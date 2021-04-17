<?php
//---------------------------
// programmer:	Jafarkhani
// create Date: 94.06
//---------------------------

require_once getenv("DOCUMENT_ROOT") . '/office/workflow/wfm.class.php';
require_once getenv("DOCUMENT_ROOT") . '/office/dms/dms.class.php';


class request extends PdoDataAccess{
    public $IDReq;
    public $IsRegister;
    public $PersonID;
    public $askerID;
    public $IsPresent;
    public $referalDate;
    public $referalTime;
    public $LetterID;
    public $IsInfoORService;
    public $serviceType;
    public $otherService;
    public $InformationDesc;
    public $IsRelated;
    public $referPersonID;
    public $referDesc;
    public $Poll;
public $LetterPic;


	function __construct($IDReq = "") {
        $this->DT_referalDate = DataMember::CreateDMA(DataMember::DT_DATE);
		if($IDReq != "")
			PdoDataAccess::FillObject ($this, "select * from request where IDReq=?", array($IDReq));
	}
	/*static function SelectAl($where = "", $param = array(), $order= ""){

        return PdoDataAccess::runquery_fetchMode("
			select * from request
			where " . $where . " group by IDReq " . $order, $param);
	}*/
    function AddReq($pdo = null){
        /*if($this->askerMob != "")
                {
                    $dt = PdoDataAccess::runquery("select *
                        from askerperson where askerID<>? AND askerMob=?", array($this->askerID, $this->askerMob));
                    if(count($dt) > 0)
                    {
                        ExceptionHandler::PushException("تلفن وارد شده تکراری است");
                        return false;
                    }
                }*/
        if(!parent::insert("request",$this, $pdo))
            return false;
        $this->IDReq = parent::InsertID($pdo);

        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_add;
        $daObj->MainObjectID = $this->IDReq;
        $daObj->TableName = "request";
        $daObj->execute($pdo);
        return true;
    }
    function EditReq(){
        /*if($this->NationalID != "")
        {
            $dt = PdoDataAccess::runquery("select *
				from BSC_AlterPersons where AlterPersonID<>? AND NationalID=?", array($this->AlterPersonID, $this->NationalID));
            if(count($dt) > 0)
            {
                ExceptionHandler::PushException("کدملی وارد شده تکراری است");
                return false;
            }
        }*/

        if( parent::update("request",$this," IDReq=:l", array(":l" => $this->IDReq)) === false )
            return false;

        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_update;
        $daObj->MainObjectID = $this->IDReq;
        $daObj->TableName = "request";
        $daObj->execute();

        return true;
    }

    static function SelectAl($where = "", $param = array(), $order= ""){

        return PdoDataAccess::runquery_fetchMode("
        select f.*, concat_ws(' ',b1.fname, b1.lname, b1.CompanyName) fullname , mobile MobCustomer
        , concat_ws(' ',b2.fname, b2.lname, b2.CompanyName) refername        	
			from request f 
				left join BSC_persons b1 using(PersonID)
				left join BSC_persons b2 ON (f.referPersonID = b2.PersonID)
				left join askerperson a using (askerID)
				
			where " . $where . $order , $param);
    }
    static function SelectAll($where = "", $param = array(), $order= ""){

        return PdoDataAccess::runquery_fetchMode("
        select f.* , concat_ws(' ',b1.fname, b1.lname, b1.CompanyName) fullname , null LetterPic    
, concat_ws(' ',b2.fname, b2.lname, b2.CompanyName) refername ,askerName, askerMob, b1.mobile MobCustomer          	
			from request f 
				left join BSC_persons b1 using(PersonID)
                                left join BSC_persons b2 ON (f.referPersonID = b2.PersonID)
                                left join askerperson a using (askerID)
				
			where " . $where . $order , $param);
    }
    static function SelectAlll($where = "", $param = array(), $order= ""){

        return PdoDataAccess::runquery_fetchMode("
        select tTable.*, askerName, askerMob from 
			(select fTable.*, concat_ws(' ',fname, lname,CompanyName) refername
			 FROM (select f.*,
				concat_ws(' ',fname, lname,CompanyName) fullname , mobile MobCustomer
			from request f 
				left join BSC_persons b using(PersonID)) AS fTable
				left join BSC_persons b ON fTable.referPersonID = b.PersonID) AS tTable
				left join askerperson a ON tTable.askerID = a.askerID
				
			where " . $where . $order , $param);
    }

    static function DeleteRequest($IDReq){

        PdoDataAccess::runquery("delete from request where IDReq=?", array($IDReq));

        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_delete;
        $daObj->MainObjectID = $IDReq;
        $daObj->TableName = "request";
        $daObj->execute();
        return true;
    }
	
}

class askerPerson  extends PdoDataAccess{
    public $askerID;
    public $askerName;
    public $askerMob;

    function __construct($askerID = "") {

        if($askerID != "")
            PdoDataAccess::FillObject ($this, "select * from askerPerson where askerID=?", array($askerID));
    }
    function AddAsker($pdo = null){

        if(!parent::insert("askerperson",$this, $pdo))
            return false;
        $this->askerID = parent::InsertID($pdo);

        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_add;
        $daObj->MainObjectID = $this->askerID;
        $daObj->TableName = "askerperson";
        $daObj->execute($pdo);
        return true;
    }
    static function IsMobExist($param =array()){

        return PdoDataAccess::runquery_fetchMode("
			select * from askerperson
			where askerMob=? " , $param);
    }
    /*static function IsMobExist($askerMob){

        return PdoDataAccess::runquery_fetchMode("
			select * from askerperson
			 ", array($askerMob));
    }*/
}

?>
