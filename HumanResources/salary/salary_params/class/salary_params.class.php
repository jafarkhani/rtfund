<?php
//---------------------------
// programmer:	Jafarkhani
// create Date:	88.07
//---------------------------

class manage_salary_params extends PdoDataAccess
{
	function SearchSalaryParam($where="", $whereParam=array())
	{
		return manage_salary_params::select($where, $whereParam);
	}

	public $param_id;
	public $person_type;
	public $param_type;
	public $from_date;
	public $to_date;
	public $dim1_id;
	public $dim2_id;
	public $dim3_id;
	public $dim4_id;
	public $value;
	public $used;

	function __construct($param_id="", $person_type="", $param_type="", $param_date="", $dim_where="")
	{
		if($param_id != "")
		{
			parent::FillObject($this, "select * from HRM_salary_params where param_id=:pid", array("pid"=>$param_id));
		}
		else if($person_type != "" && $param_type != "" && $param_date != "")
		{
			$query = "select * from HRM_salary_params 
                            where person_type in ( :hr )  AND param_type =:typ AND from_date <= :date AND to_date >= :date ";
			
			$query .= ($dim_where != "") ? " AND " . $dim_where : "";

			$whereParam = array();
			$whereParam[":hr"] = $person_type;
			$whereParam[":typ"] = $param_type;
			$whereParam[":date"] = $param_date;
		
			parent::FillObject($this, $query, $whereParam);
		}
    
    
        $this->DT_from_date = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_to_date = DataMember::CreateDMA(DataMember::DT_DATE);
	}
	
	static function get_salaryParam_value($param_id="", $person_type = "", $param_type="", $param_date="",
		$dim1_id="", $dim2_id="", $dim3_id="", $dim_where = "")
	{
			     			    
	    if($dim_where == "")
		{
		   		    
			$dim_where = "1=1";
			$dim_where .= $dim1_id != "" ? " AND dim1_id=" . $dim1_id : "";
			$dim_where .= $dim2_id != "" ? " AND dim2_id=" . $dim2_id : "";
			$dim_where .= $dim3_id != "" ? " AND dim3_id=" . $dim3_id : "";
		}		
			    
		$obj = new manage_salary_params($param_id, $person_type, $param_type, $param_date, $dim_where);
		return $obj->value;
	}

     // برگرداندن ضريب ريالي در يك بازه زماني
    static  function get_rial_coefs($from_j_year , $to_j_year , $person_type ){
        $query = " SELECT *
                          FROM salary_params
							 WHERE param_type='".SPT_RIAL_COEF."' AND person_type = ".$person_type."
								ORDER BY from_date ";
        $res = parent::runquery($query);

        $rial_coefs =  array();
       for($i=0 ; $i<count($res);$i++) {
            $cur_j_date = '01/01/'.$from_j_year;
            $cur_g_date =  DateModules::Shamsi_to_Miladi($cur_j_date);
            while($res[$i]['from_date'] <= $cur_g_date
            && $res[$i]['to_date']>=$cur_g_date && $from_j_year <= $to_j_year){
                $rial_coefs[$from_j_year] = $res[$i]['value'];
                $from_j_year ++ ;
                $cur_j_date = '01/01/'.$from_j_year;
                $cur_g_date = DateModules::Shamsi_to_Miladi($cur_j_date);
            }
        }
        return $rial_coefs ;
    }
	
	function GetAll($person_type, $param_type, $where="", $whereParam=array())
	{
		
		
		$query = "select * from HRM_salary_params";

		if($param_type == SPT_FACILITY_PRIVATION_COEF)
		{
			$query = "select p.*,s.ptitle as stateName,c.ptitle as cityName 
				from HRM_salary_params as p
						left join HRM_states as s on(s.state_id=dim2_id)
						left join HRM_cities as c on(dim1_id=c.city_id)";
		}

		$query .= " where 1=1";
		if($person_type != "" && $param_type != "")
		{
			$query .= " AND param_type=:ptype AND ( person_type=:pt or person_type = 100 )";
			$whereParam[":ptype"] = $param_type;
			$whereParam[":pt"] = $person_type;
		}

        $query .= ( !empty($where) ) ? $where : '' ;
		
		return PdoDataAccess::runquery($query ,$whereParam);
	}

	function AddParam()
	{ 
		if($this->date_overlap())
			return false;

		$pdo = parent::getPdoObject();
		$pdo->beginTransaction();

		$result = parent::insert("HRM_salary_params", $this);
		
		if($result === false)
		{
			$pdo->rollBack();
			return false;
		}

		$this->param_id = parent::InsertID();

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->RelatedPersonType = 3;
		$daObj->MainObjectID = $this->param_id;
		$daObj->TableName = "HRM_salary_params";
		$daObj->execute();
        
		$pdo->commit();
        
		return true;
	}
	
	function EditParam($param_id)
	{
		        
        if($this->date_overlap())
			return false;
      
        $result = parent::update("HRM_salary_params", $this,
                                 "param_id=:pid ",
                                 array(":pid" => $this->param_id ));
		if($result === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->RelatedPersonType = 3;
		$daObj->MainObjectID = $this->param_id;
		$daObj->TableName = "HRM_salary_params";
		$daObj->execute();

		return true;
	}
	
	function RemoveParam($param_id)
	{
	 	
        $result = parent::delete("salary_params", "param_id=:pid ",
                                                   array(":pid" => $this->param_id ));
		if($result === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->RelatedPersonType = DataAudit::PersonType_staff;
		$daObj->MainObjectID = $this->param_id;
		$daObj->TableName = "salary_params";
		$daObj->execute();

		return true;
	}
	
	private static function LastID()
	{
	 	return PdoDataAccess::GetLastID("salary_params", "param_id");
	}

	 private function date_overlap()
	 {
		$query = "select count(*) from salary_params where 
			((from_date<=:fdate AND to_date>=:fdate) OR (from_date>=:fdate AND from_date <=:tdate)) 
			AND person_type=:hr AND param_type=:ptype";
	 	
		$whereParam = array();
		$whereParam[":fdate"] = $this->from_date;
		$whereParam[":tdate"] = $this->to_date;
		$whereParam[":hr"] = $this->person_type;
		$whereParam[":ptype"] = $this->param_type;
		
		if(isset($this->param_id))
		{
			$query .= " AND param_id<>:pid";
			$whereParam[":pid"] = $this->param_id;
		}
		
		if(isset($this->dim1_id))
		{
			$query .= " AND dim1_id = :d1id";
			$whereParam[":d1id"] = $this->dim1_id;
		}
		if(isset($this->dim2_id))
		{
			$query .= " AND dim2_id = :d2id";
			$whereParam[":d2id"] = $this->dim2_id;
		}
		if(isset($this->dim3_id))
		{
			$query .= " AND dim3_id = :d3id";
			$whereParam[":d3id"] = $this->dim3_id;
		}
		
		$temp = PdoDataAccess::runquery($query, $whereParam);
			
		if($temp[0][0] != 0)
		{
			parent::PushException(ER_DATE_RANGE_OVERLAP);
			return true;
		}
		return false;
	 }
}

?>