<?php
//-----------------------------
//	Programmer	: Mokhtari
//	Date		: 99.07
//-----------------------------

require_once getenv("DOCUMENT_ROOT") . '/office/dms/dms.class.php';
require_once inc_CurrencyModule;

class organDoc extends OperationClass {

    const TableName = "organDoc";
    const TableKey = "orgDocID";

    public $orgDocID;
    public $orgDocType;
    public $title;
    public $date;
    public $endDate;
	public $PersonID2;
    
    public function __construct($id = ""){
        
		$this->DT_date = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_endDate = DataMember::CreateDMA(DataMember::DT_DATE);
		
        if ($id != ''){
            /*parent::FillObject($this, "
					select c.* ,
						concat_ws(' ',fname,lname,CompanyName) _PersonName
                    from organDoc c
					left join BSC_persons using(PersonID)
					where c." . static::TableKey . " = :id", array(":id" => $id));*/
            parent::FillObject($this, "
					select c.* 
                    from organDoc c
					where c.orgDocID = :id", array(":id" => $id));
        }
    }

    
    public static function Gett($where = '', $whereParams = array(), $order = "") {
		
        return parent::runquery_fetchMode("
			select c.*,			
				concat_ws(' ',p1.fname,p1.lname,p1.CompanyName) PersonFullname
			from organDoc c  
			left join BSC_persons p1 on(c.PersonID=p1.PersonID)
			where 1=1  " . $where . " group by orgDocID " . $order, $whereParams);
    }
    public static function Get($where = '', $whereParams = array(), $order = "") {

        return parent::runquery_fetchMode("
			select c.*,	b1.InfoDesc,			
				concat_ws(' ',p1.fname,p1.lname,p1.CompanyName) PersonFullname
			from organDoc c  
			left join BSC_persons p1 on(c.PersonID2=p1.PersonID)
		    left join BaseInfo b1 on(b1.TypeID=8 AND b1.param1=17 AND c.orgDocType=b1.InfoID)
			where 1=1  " . $where . " group by orgDocID " . $order, $whereParams);
    }



	
	/*public function Remove()
	{
		if(!DMS_documents::DeleteAllDocument($this->ContractID, "contract"))
		{
			ExceptionHandler::PushException("خطا در حذف مدارک");
	 		return false;
		}
		
		return parent::Remove();
	}*/
}


?>
