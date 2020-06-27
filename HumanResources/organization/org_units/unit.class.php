<?php
//---------------------------
// programmer:	Mahdipour
// create Date:	94.11
//---------------------------

include_once $address_prefix . "/HumanResources/global/manageTree.class.php" ; 

class manage_units extends PdoDataAccess
{
	public static function DRP_Units($dropdownName, $selectedID = "", $extraRow = "", $width = "", $where = "")
	{
		$query = "select ouid,ptitle from org_new_units";
		$query .= ($where != "") ? " where " . $where : "";
		
		$obj = new AutoComplete_DROPDOWN();

		$temp = parent::runquery($query);
				
		$obj->datasource = $temp;
		$obj->valuefield = "%ouid%";
		$obj->textfield = "%ptitle%";
		$obj->width = $width;
		$obj->Style = 'class="x-form-text x-form-field" style="width:'.$width.'" ';
		$obj->id = $dropdownName;

		if(!empty($extraRow))
			$obj->datasource = array_merge(array(array("ouid" => "-1", "ptitle" => $extraRow)),$obj->datasource);
		$return = $obj->bind_dropdown($selectedID);
		
		return $return;		
	}
	
	/**
	 * نوع واحد سازمانی (دانشکده،پژوهشکده،گروه،...)
	 *
	 * @param string $dropName
	 * @param int $selectedId
	 * @param string $extraRow
	 * @param int/percent $width
	 * @return comboBox
	 */
	static function DRP_org_unit_type($dropName, $selectedId = "", $extraRow = "", $width = "90%")
	{
		require_once inc_component;
		
		$obj = new DROPDOWN();
		
		$obj->datasource = parent::runquery("select * from Basic_Info where TypeID=24");
		$obj->valuefield = "%InfoID%";
		$obj->textfield = "%Title%";
		$obj->Style = 'class="x-form-text x-form-field" style="width:' . $width . '"';
		$obj->id = $dropName;
	
		if(!empty($extraRow))
			$obj->datasource = array_merge(array(array("InfoID" => "-1", "Title" => $extraRow))	,$obj->datasource);
			
		return $obj->bind_dropdown($selectedId);
	}

	/**
	 * نوع واحد دستگاه(اصلی،پشتیبانی،ستادی،عملیاتی)
	 *
	 * @param string $dropName
	 * @param int $selectedId
	 * @param string $extraRow
	 * @param int/percent $width
	 * @return comboBox
	 */
	static function DRP_unitType($dropName, $selectedId = "", $extraRow = "", $width = "90%")
	{
		require_once inc_component;
		
		$obj = new DROPDOWN();
		
		$obj->datasource = parent::runquery("select * from Basic_Info where TypeID=25");
		$obj->valuefield = "%InfoID%";
		$obj->textfield = "%Title%";
		$obj->Style = 'class="x-form-text x-form-field" style="width:' . $width . '"';
		$obj->id = $dropName;
	
		if(!empty($extraRow))
			$obj->datasource = array_merge(array(array("InfoID" => "-1", "Title" => $extraRow))	,$obj->datasource);
			
		return $obj->bind_dropdown($selectedId);
	}
	
	static function DRP_LevelType($dropName, $selectedId = "", $extraRow = "", $width = "90%")
	{
		require_once inc_component;
		
		$obj = new DROPDOWN();
		
		$obj->datasource = parent::runquery("select * from Basic_Info where TypeID=26");
		$obj->valuefield = "%InfoID%";
		$obj->textfield = "%Title%";
		$obj->Style = 'class="x-form-text x-form-field" style="width:' . $width . '"';
		$obj->id = $dropName;
	
		if(!empty($extraRow))
			$obj->datasource = array_merge(array(array("InfoID" => "-1", "Title" => $extraRow))	,$obj->datasource);
			
		return $obj->bind_dropdown($selectedId);
	}

	//----------------------------------------------------------------------

	public $ouid;
	public $org_unit_type;
	public $ptitle;
	public $etitle;
	public $daily_work_place_no;
	public $contract_work_place_no;
	public $state_id;
	public $ctid;
	public $ccid;
	public $detective_name;
	public $employer_name;
	public $detective_address;
	public $collective_security_branch;
	public $parent_path;
	public $parent_ouid;
	
	public $UnitType;
	public $LevelType;
	public $RegDate;

	function  __construct($ouid = "")
	{
		if($ouid == "")
			return;
		
		parent::FillObject($this,"select * from org_new_units where ouid=?", array($ouid));

		if(parent::AffectedRows() == 0)
		{
			$this->PushException("کد وارد شده معتبر نمی باشد.");
			return;
		}
	}
	
	static function getAllUnits($where= "", $whereParam = array())
	{
		$query = "select * from org_new_units";
		$query .= ($where != "") ? " where " . $where : "";
		
		$temp = parent::runquery($query, $whereParam);
		return $temp;
	}
	
	static function get_full_title($ouid, $wit_own = true, $separator = " - ")
	{
		if($ouid != '')
		{
			
			$obj = new manage_tree(); 
			$obj->node_id_field = "ouid";
			$obj->node_title_field = "ptitle";
			$obj->parent_node_id_field = "parent_ouid";
			$obj->table_name = "org_new_units";
						
			return $obj->get_node_path_title($ouid, $wit_own, $separator);
		}
		return "";
	}
	
	function AddUnit()
	{
		$this->RegDate = date("Y-m-d");
	 	$return = parent::insert("org_new_units", $this);
	 	
	 	if($return === false)
			return false;
		
		$this->ouid = parent::InsertID();

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->ouid;
		$daObj->TableName = "org_new_units";
		$daObj->execute();

		return true;
	}
	 
	function EditUnit()
	{
	 	$db = parent::getPdoObject();
	 	$db->beginTransaction();
	 	
	 	parent::runquery("insert into org_units_history select *,'EDIT',now() from org_new_units where ouid=:ouid", array(":ouid" => $this->ouid));
	 	$return =  parent::update("org_new_units", $this, " ouid=:ouid", array(":ouid" => $this->ouid));
	 	
	 	if($return === false)
	 	{
	 		$db->rollBack();
			return false;
	 	}

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->ouid;
		$daObj->TableName = "org_new_units";
		$daObj->execute();
		
	 	$db->commit();	 	
	 	return true;
	}
	 
	static function CountUnits($where = "", $whereParam = array())
	{
		$query = " select count(*) from org_new_units";
		$query .= ($where != "") ? " where " . $where : "";		
		
		$temp = parent::runquery($query, $whereParam);
		return $temp[0][0];
	}
	
	static function RemoveUnit($ouid)
	{
	 	$whereParams = array();
	 	$whereParams[":ouid"] = $ouid;
	 	
	 	$db = PdoDataAccess::getPdoObject();
	 	/*@var $db PDO*/
	 	$db->beginTransaction();
	 	
	 	parent::runquery("insert into org_units_history select *,'DELETE',now() from org_new_units where ouid=:ouid", $whereParams);
	 	$return =  PdoDataAccess::delete("org_new_units", " ouid=:ouid", $whereParams);
	 	
	 	if($return === false)
	 	{
	 		$db->rollBack();
			return false;
	 	}

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $ouid;
		$daObj->TableName = "org_new_units";
		$daObj->execute();
	 	
	 	$db->commit();	 	
	 	return true;
	}
}
	
?>