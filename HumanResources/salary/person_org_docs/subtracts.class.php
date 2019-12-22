<?php
//-----------------------------
//	Programmer	: Mahdipour
//	Date		: 95.12
//-----------------------------

class manage_subtracts extends PdoDataAccess
{
	public $subtract_id;
	public $PersonID;
	public $subtract_type;
	public $bank_id;
	public $first_value;
	public $instalment;
	public $remainder;
	public $start_date;
	public $end_date;
	public $comments;
	public $salary_item_type_id;
	public $account_no;
	public $loan_no;
	public $reg_date;
	public $subtract_status;
	public $contract_no;

	function __construct($subtract_id = "")
	{
		$this->DT_start_date = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_end_date = DataMember::CreateDMA(DataMember::DT_DATE);
		
		if($subtract_id != "")
			parent::FillObject ($this, "select * from HRM_person_subtracts where subtract_id=?", array($subtract_id));
	}

	static function GetAll($subtract_type, $PersonID, $where = "", $whereParam = array())
	{
	    
	   
		if($subtract_type != SUBTRACT_TYPE_FIX_BENEFIT &&  $subtract_type != SUBTRACT_TYPE_FIX_FRACTION ){
		   
		   
		   self::GetRemainder($subtract_type, "", $PersonID, true);
		
			$query = "select s.*,si.full_title, b.name bankTitle, if(IsFinished,0,sr.remainder) remainder,receipt
				from HRM_person_subtracts s 
				join HRM_salary_item_types si using(salary_item_type_id)
				left join HRM_banks b using(bank_id)
				left join HRM_tmp_SubtractRemainders sr using(subtract_id)
				inner join HRM_persons p on p.PersonID = s.PersonID  
				
				";
		}
		else {		    
		    
			$query = "select s.*,si.full_title, b.name bankTitle, 0 remainder,0 receipt
						from HRM_person_subtracts s 
						join HRM_salary_item_types si using(salary_item_type_id)
						left join HRM_banks b using(bank_id)
						inner join HRM_persons p on p.PersonID = s.PersonID 
						";
		}
		
		$query .= ($where != "") ? " where " . $where : "";

		return parent::runquery($query, $whereParam);
	}

	function Add()
	{
	 	if( parent::insert("HRM_person_subtracts", $this) === false ) {
				
			return false; 
			
			}

		$this->subtract_id = parent::InsertID();

                
		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;		
		$daObj->MainObjectID = $this->subtract_id;
		$daObj->TableName = "person_subtracts";
		$daObj->execute();   

		return true;
	}

	function Edit()
	{
	 	$whereParams = array();
	 	$whereParams[":sid"] = $this->subtract_id;

	 	if( parent::update("HRM_person_subtracts",$this," subtract_id=:sid", $whereParams) === false )
	 		return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;		
		$daObj->MainObjectID = $this->subtract_id;
		$daObj->TableName = "person_subtracts";
		$daObj->execute(); 
	 	return true;
	}

	static function Remove($subtract_id)
	{
        
		$dt = PdoDataAccess::runquery("select * from HRM_payment_items where param1 in('LOAN','FIX_FRACTION','FIX_BENEFIT') AND param2 = ?", array($subtract_id));
		if(count($dt) > 0)
			return false;
				
		$result = parent::delete("HRM_person_subtracts", "subtract_id=:sid ", array(":sid" => $subtract_id));

		if($result === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;		
		$daObj->MainObjectID = $subtract_id;
		$daObj->TableName = "HRM_person_subtracts";
		$daObj->execute(); 
		return true;
	}

	static function GetRemainder($subtract_type = "", $subtract_id = "", $PersonID = "", $justCreateTempTable = false , $last_month="" , $Year=""){
								
		$where = "";
		$param = array();
		$stypes = "'LOAN','FIX_FRACTION','FIX_BENEFIT'";
		
		if($PersonID != "")
		{
			$where .= " AND per.RefPersonID=:pid";
			$param[":pid"] = $PersonID;
		}
		if($subtract_id != "")
		{
			$where .= " AND s.subtract_id=:subid";
			$param[":subid"] = $subtract_id;
		}
		if($subtract_type != "")
		{
			$where .= " AND s.subtract_type=:stype";
			$param[":stype"] = $subtract_type;
			/*switch ($subtract_type)
			{
				case SUBTRACT_TYPE_LOAN : $stypes = "'LOAN'"; break;
				case SUBTRACT_TYPE_FIX_FRACTION : $stypes = "'FIX_FRACTION'"; break;
				case SUBTRACT_TYPE_FIX_BENEFIT : $stypes = "'FIX_BENEFIT'"; break;
			}*/
		}
		
		/*if($where == "" && $justCreateTempTable == true )
		{
			$JoinClause = "  limit_staff ls inner join payment_items p 
												on ls.staff_id =  p.staff_id 
											 inner join person_subtracts s 
												on(p.param2=s.subtract_id) " ; 
			$JoinClause2 = "" ; 
		}
		else { */
			$JoinClause = "  HRM_payment_items p join HRM_person_subtracts s on(p.param2=s.subtract_id) 
											 inner join HRM_staff st
                                                   on p.staff_id = st.staff_id 
										     inner join HRM_persons per 
												   on st.personid = per.personid and st.person_type = per.person_type  ";  
			$JoinClause2 = " " ; 
		/*}*/
				
		//parent::runquery("drop table if exists tmp_SubtractReceiptSummary");				
		//parent::runquery("drop table if exists tmp_SubtractRemainders");
		parent::runquery('TRUNCATE HRM_tmp_SubtractReceiptSummary');
		parent::runquery('TRUNCATE HRM_tmp_SubtractRemainders');
	
		if($last_month > 0 ) {
		
			$LM = ((int) $last_month - 1 ) ; 				
			$dayNO = DateModules::DaysOfMonth($Year,$LM) ; 
			$edt = DateModules::shamsi_to_miladi($Year."/".$LM."/".$dayNO) ; 
			
		   $WhrMonth = " AND if(p.pay_year = ".$Year." , p.pay_month < ".$last_month." , (1=1)) AND  
								( s.end_date IS NULL  or s.end_date ='0000-00-00' or  s.end_date > '".$edt."' ) " ;  
			
		}
	    else 
		   $WhrMonth = " " ; 
		 
		parent::runquery("/*create table tmp_SubtractReceiptSummary as */
						 insert into HRM_tmp_SubtractReceiptSummary 
							select s.*,sum(get_value) receipt 
				
							from  $JoinClause
				
							where param1 in($stypes) $WhrMonth AND s.IsFinished=0 AND  st.person_type in (1,2,3,5)  $where
							group by s.subtract_id", $param);
							
		
			//	parent::runquery("ALTER TABLE tmp_SubtractReceiptSummary ADD INDEX Index_1(subtract_id)");
				
		$query = "
				select subtract_id,first_value-ifnull(sum(receipt),0)-ifnull(sum(flow),0) remainder, ifnull(sum(receipt),0) receipt
				from
					(
					select subtract_id,first_value,0 receipt,0 flow
					from HRM_person_subtracts s 
					            inner join HRM_persons per on per.PersonID = s.PersonID $JoinClause2
					where IsFinished=0  and  s.subtract_type = 1  $where

					union All

					select sf.subtract_id,0,0 receipt,sum(cast(flow_coaf as Decimal(2))*amount) flow 
					from HRM_person_subtract_flows sf 
					            join HRM_person_subtracts s on(sf.subtract_id=s.subtract_id AND IsFinished=0  AND  s.subtract_type = 1 ) 
					             inner join HRM_persons per on per.PersonID = s.PersonID $JoinClause2
					where flow_type=3 $where
					group by sf.subtract_id

					union All

					select subtract_id,0,receipt,0 
					          from HRM_tmp_SubtractReceiptSummary s  
					                  inner join HRM_persons per on per.PersonID = s.PersonID where 1=1 $where

					)t
				group by subtract_id";
		
	
		
		if($justCreateTempTable)
		{			
			parent::runquery("/*create table tmp_SubtractRemainders as*/ insert into HRM_tmp_SubtractRemainders  " . $query, $param);

			//parent::runquery("ALTER TABLE tmp_SubtractRemainders ADD INDEX Index_1(subtract_id)");
			return;
		}

	
		$dt = parent::runquery($query, $param);
		if($subtract_id != "")
			return $dt[0]["remainder"];
		
		return $dt;
	}
	//................ زمان محاسبه حقوق این تابع فراخوانی می شود جهت اینکه رکوردهایی که وام آنها تمام شده بروز رسانی شوند.
	static function UpdateExpireLoan(){
		
		$dt = PdoDataAccess::runquery(" select subtract_id , remainder
												from HRM_tmp_SubtractRemainders where remainder <= 0 ", array());
		
		for($i=0;$i<count($dt);$i++)
		{
			PdoDataAccess::runquery(" update HRM_person_subtracts set IsFinished = 1 where subtract_id = ? and subtract_type = 1  ", array($dt[$i]['subtract_id'])); 			
		}
		
		return ; 			
	}


	static function IsEditable($subtract_id){
		
		$dt = PdoDataAccess::runquery("select * from person_subtract_flows where subtract_id=?", array($subtract_id));
		if(count($dt) > 0)
			return false;
		
		$dt = PdoDataAccess::runquery("select * from payment_items where param1 in('LOAN','FIX_FRACTION','FIX_BENEFIT') AND param2 = ?", array($subtract_id));
		if(count($dt) > 0)
			return false;
		
		return true;		
	}
}

class manage_subtract_flows extends PdoDataAccess
{
	public $subtract_id;
	public $row_no;
	public $flow_type;
	public $flow_date;
	public $flow_coaf;
	public $amount;
	public $comments;
	public $alert;
	public $tempFlow;
	public $newRemainder;

	function __construct()
	{
		$this->DT_flow_date = DataMember::CreateDMA(DataMember::DT_DATE);
	}

	static function GetAll($where = "",$whereParam = array())
	{
		
		$query = "select * from person_subtract_flows s ";
		
		$query .= ($where != "") ? " where " . $where : "";
		
		return parent::runquery($query, $whereParam);
	}

	function Add()
	{	
		
		if(empty($this->tempFlow))
		$this->tempFlow = 0 ; 
		
	 	if( parent::insert("person_subtract_flows", $this) === false ) {
			return false;
		}

		$this->row_no = parent::InsertID();
                
		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;		
		$daObj->MainObjectID = $this->subtract_id;
		$daObj->SubObjectID = $this->row_no;
		$daObj->TableName = "person_subtract_flows";
		$daObj->execute();   

		return true;
	}

	function Edit()
	{
	 	$whereParams = array();
	 	$whereParams[":rno"] = $this->row_no;

	 	if( parent::update("person_subtract_flows",$this," row_no=:rno", $whereParams) === false )
	 		return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;		
		$daObj->MainObjectID = $this->subtract_id;
		$daObj->TableName = "person_subtract_flows";
		$daObj->execute(); 
	 	return true;
	}

	static function Remove($row_no)
	{
		$result = parent::delete("person_subtract_flows", "row_no=:rno ", array(":rno" => $row_no));

		if($result === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;		
		$daObj->MainObjectID = $row_no;
		$daObj->TableName = "person_subtract_flows";
		$daObj->execute(); 
		return true;
	}
	
	static function IsEditable($row_no){
		
		$dt = PdoDataAccess::runquery("select * from person_subtracts join person_subtract_flows using(subtract_id) where row_no=?", array($row_no));
		if($dt[0]["IsFinished"] == "1")
			return false;
		
		$subtract_id = $dt[0]["subtract_id"];
		$flow_date = DateModules::miladi_to_shamsi($dt[0]["flow_date"]);
		$year = DateModules::GetYear($flow_date);
		$month = DateModules::GetMonth($flow_date);
		
		$dt = PdoDataAccess::runquery("select * from payment_items join payments using(payment_type,staff_id,pay_year,pay_month)
				where param1 in('LOAN','FIX_FRACTION','FIX_BENEFIT')
					AND param2 = $subtract_id 
					AND pay_year>=$year 
					AND if(pay_year=$year, pay_month>=$month, 1=1) 
					AND	if(pay_year=$year AND pay_month=$month, calc_date > '" . $dt[0]["flow_date"] . "', 1=1)");
								
	  
		if(count($dt) > 0)
			return false;
		
		return true;		
	}

}
?>