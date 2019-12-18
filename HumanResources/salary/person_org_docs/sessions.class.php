<?php
//---------------------------
// programmer:	Mahdipour
// create Date:	91.06
//---------------------------

class manage_Session extends PdoDataAccess
{
	public $SessionID ;
	public $PersonID;
	public $salary_item_type_id;
	public $TotalHour;
	public $SessionDate ; 
        
	function __construct($bank_id = "")
	 {
		$this->DT_SessionDate = DataMember::CreateDMA(DataMember::DT_DATE);
	 	return;
	 }

	 static function GetAll($where)
	 {	 
		 
        $query = " select p.pfname , p.plname , s.PersonID , TotalHour , SessionDate , 
						  SessionID , s.salary_item_type_id,sit.full_title
					from HRM_sessions s
						 inner join HRM_persons p on s.PersonID = p.refPersonID
						 inner join HRM_salary_item_types sit on s.salary_item_type_id = sit.salary_item_type_id
				 " .$where.",s.PersonID" ; 
                
	 	return parent::runquery($query);
	 }

        
	function Add()
	{          
		//$this->SessionID = parent::GetLastID("HRM_sessions","SessionID") + 1 ; 
		
            $return = parent::insert("HRM_sessions",$this);

            if($return === false)
                    return false;

            $daObj = new DataAudit();
            $daObj->ActionType = DataAudit::Action_add;
            $daObj->MainObjectID = $this->SessionID;                
            $daObj->TableName = "HRM_sessions";
            $daObj->execute();

            return true;
	}
        
        function Edit() {
                      
	         		 
                $result = parent::update("HRM_sessions", $this,"SessionID=".$this->SessionID );
                
                if ($result === false)
			return false;	

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->SessionID; 
		$daObj->TableName = "HRM_sessions";
		$daObj->execute();

		return true;
	}	

	static function Remove($sid)
	{                                
               
                        $result = parent::delete("HRM_sessions","SessionID=:SID ",
                                                                            array(":SID" => $sid));                

                        if(!$result)
                                return false;

                        $daObj = new DataAudit();
                        $daObj->ActionType = DataAudit::Action_delete;
                        $daObj->MainObjectID = $sid;                       
                        $daObj->TableName = "HRM_sessions";
                        $daObj->execute();

                        return true;
        }

}

?>