<?php
//---------------------------
// programmer:	Mahdipour
// create Date:	94.11
//---------------------------

class manage_person extends PdoDataAccess
{
	public $PersonID;
	public $pfname;
	public $plname;
	public $efname;
	public $elname;
	public $father_name;
	public $idcard_no;
	public $idcard_serial;
	public $birth_date;
	public $birth_state_id;
	public $birth_city_id;
	public $birth_place;
	public $issue_date;
	public $issue_state_id;
	public $issue_city_id;
	public $issue_place;
	public $country_id;
	public $national_code;
	public $sex;
	public $marital_status;
	public $family_protector;
	public $locality_type;
	public $address1;
	public $postal_code1;
	public $home_phone1;
	public $address2;
	public $postal_code2;
	public $home_phone2;
	public $work_phone;
	public $work_int_phone;
	public $mobile_phone;
	public $email;
	public $nationality;
	public $religion;
	public $subreligion;	 
	public $insure_no;
	public $military_status;
	public $military_from_date;
	public $military_to_date;
	public $military_duration; 
	public $military_comment;	 
	public $military_type;	
	public $role_student;
	public $role_staff;
	public $role_part_time_teacher;
	public $InsurePost;
	public $role_other;
	public $person_type;
	public $comment;
	public $military_duration_day ; 
	public $OtherPerson ; 
	public $picture ;
public $RefPersonID ;
public $detectiveID ;
public $JobCode ;
public $childCount; //new added
public $employ_date; //new added


	function __construct($PersonID = "", $staff_id = "")
	{
	 	if($PersonID != "")
	 	{
									
	 		$query = "select * from HRM_persons where PersonID=:pid" ;
	 		parent::FillObject($this, $query, array(":pid"=> $PersonID));

			if(empty($this->PersonID))
			{
				$this->PushException("کد وارد شده معتبر نمی باشد.");
				return null;
			}
	 	}
		else if($staff_id != "")
		{
			$query = " SELECT p.*
			           FROM HRM_persons p JOIN HRM_staff s ON(p.PersonID=s.PersonID)

			           WHERE s.staff_id = :stid" ;

			parent::FillObject($this, $query, array(":stid"=> $staff_id));
			if(empty($this->PersonID))
			{
				$this->PushException("کد وارد شده معتبر نمی باشد.");
				return null;
			}
		}
	 	else
	 	{
	 		$this->sex = "1";
	 		$this->marital_status = "1";
	 		$this->family_protector = "0";
	 		$this->locality_type = "0";
	 		$this->birth_state_id = "19";
	 		$this->birth_city_id = "11";
	 		$this->issue_state_id = "19";
	 		$this->issue_city_id = "11";
            
	 		
	 	}
	 	return;	 	
	 }
	 
	static public function SelectPerson($where, $whereParam, $include_new_persons = false , $show_All_history = false , $costID = false )
	{            
           
       $query = "SELECT bp.PersonID,
		                 bp.fname pfname,
		                 bp.lname plname,
		                 s.staff_id,
		                 p.person_type,
		                 o.ptitle as unit_name,
		                                 'کارمند' as personTypeName

					FROM BSC_persons bp
						LEFT JOIN HRM_persons p ON bp.PersonID = p.RefPersonID
						LEFT JOIN HRM_staff s ON s.PersonID = p.PersonID
						LEFT JOIN HRM_org_new_units o ON o.ouid = s.ouid
					WHERE bp.IsStaff='YES' " ; 	
		             
        $query .= ($where != "") ? " AND " . $where : "";
            
	
	return parent::runquery($query,$whereParam);
	}
	 
	static function GetAllPersons($where, $whereParam, $fetchMode = false)
	{
		
		
	 	$query = " select   p.* ,
                                    s.staff_id ,s.FacCode , s.EduGrpCode , s.ProCode , s.ResearchGrpCode , 
                                    s.last_person_type , 
                                    case s.person_type 
                                            when  1 then 'هیئت علمی' 
                                            when  2 then 'کارمند' 
                                            when  3 then 'روزمزدبیمه ای' 
                                            when  5 then 'قراردادی'
                                            when 10 then 'بازنشسته'
                                    end person_type_title , 
                                    s.personel_no ,
                                    w.cur_group , 
                                    w.ouid ,                                    
                                    po.title , 
                                    po.post_no ,
                                    c1.ptitle birthTitle,
                                    c2.ptitle  issueTitle ,
                                    cty.ptitle ctyTitle,
                                    bi2.InfoDesc maritalTitle ,
                                    bi1.InfoDesc militaryTitle,
                                    'قراردادی' person_type_title ,
                                    u.ptitle as org_unit_title
 
                   from HRM_persons p
                             left join HRM_countries cty on(cty.country_id = p.country_id)
                             left join HRM_cities c1 on(p.birth_city_id  = c1.city_id)
                             left join HRM_cities c2 on(p.issue_city_id  = c2.city_id)
                             left join BaseInfo bi1 on(bi1.InfoID = p.military_type and bi1.TypeID = 53)
                             left join BaseInfo bi2 on(bi2.InfoID = p.marital_status and bi2.TypeID = 71)                                    
                             join HRM_staff as s on(s.person_type= p.person_type and s.PersonID=p.PersonID)  
                             left join HRM_writs w 
                                                on (s.last_writ_id = w.writ_id and 
                                                    s.last_writ_ver = w.writ_ver and 
                                                    s.staff_id = w.staff_id and 
                                                    s.person_type = w.person_type)
                                                    
                             left join HRM_units as u on(u.ouid=s.unitCode)
                             left join HRM_position po on po.post_id = w.post_id
                       where (1=1)       
					";
				
                $query .= ($where != "") ? " AND " . $where : "";
				
		if(!$fetchMode)
			return parent::runquery($query,$whereParam);
		else
			return parent::runquery_fetchMode($query,$whereParam);
	}
		 
	static function Count($where, $whereParam)
	{
	 	$query = "select count(*) from persons p join staff s on(s.person_type=p.person_type and s.PersonID=p.PersonID)
			whhere (s.last_cost_center_id in(" . manage_access::getValidCostCenters() . ") OR s.last_cost_center_id is null)
						AND s.person_type in(" . manage_access::getValidPersonTypes() . ")";
	 	
	 	$query .= ($where != "") ? " AND " . $where : "";
	 	
	 	$temp = parent::runquery($query,$whereParam);
	 	
	 	return $temp[0][0];
	}

	static function  Last_StaffID_Before_Retired($Personid)
	{

		$query = " select staff_id , person_type
						from staff where person_type in (select min(person_type)
												from staff
													where personid =".$Personid." and person_type != 10)
															and personid =".$Personid ;
		$temp = parent::runquery($query);

        if(count($temp) > 0 )
            return $temp[0]["staff_id"];

        else return 0 ;
		
	}
	
	public static function IsValidNID($NID=null){
		  	
			if($NID=='' || !preg_match("/^[0-9]{10}$/",$NID)){
				return  false;
			}
			$NID_DIG = str_split($NID);
			$NID_SUM = 0;
			for($i=10;$i>1;$i--){
				$NID_SUM +=$i*$NID_DIG[10-$i];
			}
			$CH_SUM = ($NID_SUM%11);
			$CH_SUM = $CH_SUM<2 ? $CH_SUM : 11-$CH_SUM ; 
			if($CH_SUM != $NID_DIG[9]) return false;
			return true; 
  }

	function AddPerson($staffObject,$pic="")
	{
			
		$pdo = PdoDataAccess::getPdoObject();
	 	
	 	if($pdo->beginTransaction())
	 	{   	
	 					
			if($this->military_from_date != NULL || $this->military_from_date != '0000-00-00' )
			{ 
				$Vyear = 0 ; 
				$Vmonth = $Vday = 0 ; 
				$resDay = DateModules::GDateMinusGDate($this->military_to_date , $this->military_from_date) ; 	
				DateModules::day_to_ymd($resDay, $Vyear, $Vmonth, $Vday) ; 
				$mm = ( $Vyear * 12  ) +  $Vmonth ; 
				$dd =  $Vday ; 
				
				$this->military_duration = $mm ; 
				$this->military_duration_day = $dd ; 			
				
			}
			
			if(parent::insert("HRM_persons",$this,$pdo) ===  false)
			{                          
                                parent::PushException(ER_PERSON_DONT_SAVE);
				$pdo->rollBack();
				return false;
			}
                                             
                         
			$this->PersonID = parent::InsertID();

			$staffObject->PersonID = $this->PersonID;
			$staffObject->person_type = $this->person_type;
                        
                       
			$return = $staffObject->AddStaff($pdo);
			if(!$return)
			{
				parent::PushException(ER_PERSON_DONT_SAVE);
				$pdo->rollBack();
				return false;
			}
			
			if($return)
			   $pdo->commit();
			
			$daObj = new DataAudit();
			$daObj->ActionType = DataAudit::Action_add;
			$daObj->MainObjectID = $this->PersonID;
			$daObj->TableName = "HRM_persons";
			$daObj->execute();
				
			return true;
	 	}
	 	else 
	     	return false;
	 }
	 
	function EditPerson()
	{   
		
		
	 	$whereParams = array();
	 	$whereParams[":pid"] = $this->PersonID;

     	
		//........بررسی اعتبار کد ملی فرد .........
			if(!self::IsValidNID($this->national_code) )
			{	
				
				parent::PushException("کد ملی فرد معتبر نمی باشد.");
				
				return false;
			}			
					
					
	 	if(PdoDataAccess::update("HRM_persons",$this," PersonID=:pid", $whereParams) === false) {
		
			echo PdoDataAccess::GetLatestQueryString() ;
			die();
			return false;
		
		}

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->PersonID;
		$daObj->TableName = "HRM_persons";
		$daObj->execute();
		
	 	return true;
	 }
	 
	static  function RemovePerson ($PersonID)
	{
		$pdo = PdoDataAccess::getPdoObject();
		if($pdo->beginTransaction())
		{
			$whereParam = array(":PID"=> $PersonID);

			require_once 'dependent.class.php';
			if(count(manage_person_dependency::GetAllDependency("PersonID=:PID", $whereParam)) != 0)
				return "فرد مورد نظر دارای وابستگی می باشد و قابل حذف نیست";

			$return = manage_staff::remove($PersonID, "", $pdo);
			if(!$return)
			{
				$pdo->rollBack();
				return false;
			}

			$return = PdoDataAccess::delete("HRM_persons","PersonID=:PID", $whereParam, $pdo);
			if(!$return)
			{
				$pdo->rollBack();
				return false;
			}

			$pdo->commit();

			$daObj = new DataAudit();
			$daObj->ActionType = DataAudit::Action_delete;
			$daObj->MainObjectID = $PersonID;
			$daObj->TableName = "persons";
			$daObj->execute();

			return true;

		}
		else
	     	return false;
	 	
	 	

	 		
	 		
	 	
			
	 	return true;
	 }
	 
	 public static  function LastID($pdo = "")
	 {
	 	return PdoDataAccess::GetLastID("persons","PersonID", "", array(), $pdo);
	 }
	 
	 static function GetPersonPicture($PersonID)
	 {		
	 	$query = " SELECT picture FROM HRM_persons WHERE PersonID = :PID";
	 	$whereParam = array(":PID"=> $PersonID);
	 	$temp = parent::runquery($query,$whereParam);
	 				
	 	return $temp[0][0];
	 }
			
	/**
	 * 
	 * @return manage_person object 
	 */
	public static function SearchPerson($PersonID)
	{
		return new manage_person($PersonID);
	}
	
		
}

?>