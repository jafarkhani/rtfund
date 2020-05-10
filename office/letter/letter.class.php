<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.10
//-----------------------------
 
class OFC_letters extends PdoDataAccess{
	
    public $LetterID;
	public $LetterType;
	public $LetterTitle;
	public $LetterDate;
	public $RegDate;
	public $PersonID;
	public $context;
	public $organization;
	public $OrgPost;
	public $SignerPersonID;
	public $SignPostID;
	public $IsSigned;
	public $InnerLetterNo;
	public $InnerLetterDate;
	public $OuterCopies;
	public $RefLetterID;
	public $OuterSendType;
	public $AccessType;
	public $keywords;
	public $PostalAddress;
	public $ProcessID;
    public $MeetingRecordID; /*new added*/
	public $hasAttach;

    function __construct($LetterID = ""){
		$this->DT_LetterDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_RegDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_InnerLetterDate = DataMember::CreateDMA(DataMember::DT_DATE);
		
		if($LetterID != "")
			parent::FillObject($this, "select * from OFC_letters where LetterID=?", array($LetterID));
    }

    static function GetAll($where = "",$whereParam = array()){
	    $query = "select * from OFC_letters l ";
	    $query .= ($where != "") ? " where " . $where : "";
	    $dt = parent::runquery($query, $whereParam);
		
		for($i=0; $i<count($dt); $i++)
		{
			$dt[$i]["context"] = "";
			$dt[$i][6] = "";
		}
		
		return $dt;
    }
	
	static function FullSelect($where = "",$whereParam = array(), $OrderBy = "", $RefInclude= false){
		
	    $query = "select l.LetterID,l.LetterType,l.LetterDate,l.LetterTitle,
				concat(p1.fname,' ',p1.lname) RegName,
				concat(p4.fname,' ',p4.lname) signer,
				if(t.cnt > 0,'YES','NO') hasAttach" . 
				($RefInclude ? ",s.SendID,s.SendDate,s.SendComment,	
				concat(p2.fname,' ',p2.lname) sender,
				concat(p3.fname,' ',p3.lname) receiver" : "") . " 
				
			from OFC_letters l 
			join BSC_persons p1 on(l.PersonID=p1.PersonID)
			left join BSC_persons p4 on(l.SignerPersonID=p4.PersonID)
			left  join (select ObjectID,count(DocumentID) cnt 
					from DMS_documents where ObjectType='letterAttach' group by ObjectID )t
			on(t.ObjectID = l.LetterID)
			left join OFC_LetterCustomers lc on(l.LetterID=lc.LetterID)";
			
		if($RefInclude)
			$query .= " 
			join OFC_send s on(l.LetterID=s.LetterID)
			join BSC_persons p2 on(s.FromPersonID=p2.PersonID)
			join BSC_persons p3 on(s.ToPersonID=p3.PersonID)";
		
	    $query .= ($where != "") ? " where " . $where : "";
		$query .= $RefInclude ? " group by s.SendID " : " group by l.LetterID ";
		$query .= $OrderBy;
		
	    return parent::runquery_fetchMode($query, $whereParam);
    }
	
    function AddLetter($pdo = null){
	    if( parent::insert("OFC_letters", $this, $pdo) === false )
		    return false;

	    $this->LetterID = parent::InsertID($pdo);
        
		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->LetterID;
		$daObj->TableName = "OFC_letters";
		$daObj->execute($pdo);
        return true;
    }

    function EditLetter($pdo = null){
	    $whereParams = array();
	    $whereParams[":kid"] = $this->LetterID;

	    if( parent::update("OFC_letters",$this," LetterID=:kid", $whereParams, $pdo) === false )
		    return false;

	    $daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->LetterID;
		$daObj->TableName = "OFC_letters";
		$daObj->execute($pdo);
		return true;	
    }

    static function RemoveLetter($LetterID){
	    $result = parent::delete("OFC_letters", "LetterID=:kid ",
		    array(":kid" => $LetterID));

	    if($result === false)
		    return false;

	    $daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $LetterID;
		$daObj->TableName = "OFC_letters";
		$daObj->execute();
		return true;	
    }
	
	static function SelectReceivedLetters($where = "", $param = array()){
		 
		$query = "select s.*,
				l.organization,
				l.LetterType,
				l.IsSigned,
				l.LetterTitle,
				l.OrgPost,
				l.hasAttach,
				
				concat_ws(' ',fname, lname,CompanyName) FromPersonName,
				substr(s.SendDate,1,10) _SendDate,
				InfoDesc SendTypeDesc
				
			from OFC_send s
				left join BaseInfo b on(b.TypeID=12 AND s.SendType=InfoID)
				join OFC_letters l using(LetterID)
				join BSC_persons p on(s.FromPersonID=p.PersonID)				
				left join OFC_send s2 on(s2.LetterID=s.LetterID AND s2.SendID>s.SendID AND s2.FromPersonID=s.ToPersonID)
				
			where s2.SendID is null AND s.ToPersonID=:tpid " . $where . "
			group by SendID";
		$param[":tpid"] = $_SESSION["USER"]["PersonID"];
		
		$query .= dataReader::makeOrder();
		return PdoDataAccess::runquery_fetchMode($query, $param);
	}
	
	static function SelectDraftLetters($where = "", $param = array()){
		
		$query = "select LetterID,LetterTitle,LetterType,LetterDate,LetterTitle
			from OFC_letters
			left join OFC_send using(LetterID) 
			where SendID  is null AND PersonID=:pid " . $where;
		$param[':pid'] = $_SESSION["USER"]["PersonID"];

		$query .= dataReader::makeOrder();
		return PdoDataAccess::runquery($query, $param);
	}
	
	static function SelectSendedLetters($where = "", $param = array()){
		
		$query = "select s.*,l.*, InfoDesc SendTypeDesc,
				concat_ws(' ',fname, lname,CompanyName) ToPersonName,
				if(count(DocumentID) > 0,'YES','NO') hasAttach,
				substr(s.SendDate,1,10) _SendDate
			from OFC_send s
				left join BaseInfo b on(b.TypeID=12 AND s.SendType=InfoID)
				join OFC_letters l using(LetterID)
				join BSC_persons p on(s.ToPersonID=p.PersonID)
				left join DMS_documents on(ObjectType='letterAttach' AND ObjectID=s.LetterID)
			where FromPersonID=:fpid " . $where . "
			group by SendID
		";
		$param[":fpid"] = $_SESSION["USER"]["PersonID"];
		$query .= dataReader::makeOrder();

		return PdoDataAccess::runquery_fetchMode($query, $param);
	}
	
	function LetterContent($IsForPDF = false){

		$letterYear = substr(DateModules::miladi_to_shamsi($this->LetterDate),0,4);
		$content = "";
		
		if($IsForPDF)
			$content .= "<style>body {font-family: BNazanin;}</style><body dir=rtl>";
		
		$content .= "<br><div>";
		if($IsForPDF)
		{
			$content .= "<div style=margin-right:30px;float:right;width:200px>"
					. "<img  src='/framework/icons/logo.jpg' style='width:150px'></div>";
		}
		$content .= "<div style=margin-left:30px;float:left;width:150px;line-height:15px >شماره نامه: " . 
			"<span dir=ltr>" . $letterYear . "-" . $this->LetterID . "</span>". 
			"<br>تاریخ نامه: " . DateModules::miladi_to_shamsi($this->LetterDate) . 
			($this->AccessType == OFC_ACCESSTYPE_SECRET ? "<br>دسترسی : محرمانه" : "");

		if($this->LetterType == "INCOME")
		{
			$content .= "<br>شماره نامه وارده: " . $this->InnerLetterNo;
			$content .= "<br>تاریخ نامه وارده: " . DateModules::miladi_to_shamsi($this->InnerLetterDate);
		}

		if($this->RefLetterID != "")
		{
			$refObj = new OFC_letters($this->RefLetterID);
			$RefletterYear = substr(DateModules::miladi_to_shamsi($refObj->LetterDate),0,4);
			$content .= "<br>عطف به نامه: <a href=javascript:void(0) onclick=LetterInfo.OpenRefLetter(" . 
				$this->RefLetterID . ")>".
				"<span dir=ltr>" . $RefletterYear . "-" . $this->RefLetterID. "</span></a>";
		}
		$content .= "</div></div><br><br>";

		$content .= "<b><br><div align=center>به نام خداوند جان و خرد</div>";

		//------------- daily tip ---------------
		$dt = PdoDataAccess::runquery("select * from OFC_DailyTips where :ld >= FromDate and :ld <= ToDate",
			array(":ld" => $this->LetterDate));
		if(count($dt) > 0)
			$content .= "<div align=center>" . $dt[0]["description"] . "</div>";
		//---------------------------------------
		$content .= "<br>";
		$dt = PdoDataAccess::runquery("
			select  p2.sex,FromPersonID,p3.PersonSign signer, p1.PersonSign regSign,
				if(p1.IsReal='YES',concat(p1.fname, ' ', p1.lname),p1.CompanyName) RegPersonName ,
				if(p2.IsReal='YES',concat(p2.fname, ' ', p2.lname),p2.CompanyName) ToPersonName ,
				concat(p3.fname, ' ', p3.lname) SignPersonName ,
				po.PostName,
				s.IsCopy
			from OFC_send s
				join OFC_letters l using(LetterID)
				join BSC_persons p1 on(l.PersonID=p1.PersonID)
				join BSC_persons p2 on(s.ToPersonID=p2.PersonID)
				left join BSC_persons p3 on(l.SignerPersonID=p3.PersonID)
				left join BSC_posts po on(l.SignPostID=po.PostID)
			where LetterID=? 
			group by p2.PersonID
			order by SendID
			", array($this->LetterID));

		if($this->LetterType == "INNER")
		{
			foreach($dt as $row)
			{
				if($row["FromPersonID"] != $this->PersonID || $row["IsCopy"] == "YES")
					continue;	
				$content .= $row["sex"] == "MALE" ? "جناب آقای " : "سرکار خانم ";
				$content .= $row['ToPersonName'] . "<br>";
			}

			$content .= "<br> موضوع : " . $this->LetterTitle . "<br><br></b>";
			$content .= str_replace("\r\n", "", $this->context);

			$sign = $dt[0]["regSign"] != "" ? "background-image:url(\"" .
					data_uri($dt[0]["regSign"],'image/jpeg') . "\")" : "";

			$content .= "<table width=100%><tr><td><div class=signDiv style=" . $sign . "><b>" . 
					$dt[0]["RegPersonName"] . "</b><br><br>" . $dt[0]["PostName"] . "</div></td></tr></table>";
		}
		if($this->LetterType == "OUTCOME")
		{
			$content .= $this->OrgPost . " " . $this->organization . "<br>" ;
			$content .= "<br> موضوع : " . $this->LetterTitle . "<br><br></b>";
			$content .= str_replace("\r\n", "", $this->context);

			$sign = $this->IsSigned == "YES" && $dt[0]["signer"] != "" ? 
					"background-image:url(\"" . data_uri($dt[0]["signer"],'image/jpeg') . "\")" : "";

			$content .= "<table width=100%><tr><td><div class=signDiv style=" . $sign . "><b>" . 
					$dt[0]["SignPersonName"] . "</b><br><br>" . $dt[0]["PostName"] . "</div></td></tr></table>";
		}
		if($this->LetterType == "INCOME")
		{
			$content .= $this->OrgPost . " " . $this->organization . "<br>" ;
			$content .= "<br> موضوع : " . $this->LetterTitle . "<br><br></b>";
			$content .= str_replace("\r\n", "", $this->context);
		}
		foreach($dt as $row)
		{
			if($row["FromPersonID"] != $this->PersonID || $row["IsCopy"] == "NO")
				continue;	
			$content .= "<b> رونوشت : " . ($row["sex"] == "MALE" ? "جناب آقای " : "سرکار خانم ") . 
					$row['ToPersonName'] . "<br></b>";
		}

		if($this->OuterCopies != "")
		{
			$this->OuterCopies = str_replace("\r\n", " , ", $this->OuterCopies);
			$content .= "<br><b> رونوشت خارج از سازمان : <br>" . hebrevc($this->OuterCopies) . "</b><br>";
		}
		
		if($this->ProcessID*1 > 0)
		{
			require_once '../../framework/baseInfo/baseInfo.class.php';
			$pObj = new BSC_processes($this->ProcessID);
			$content .= "<br><b> فرایند: " . $pObj->ProcessTitle . "</b><br>";
		}
		
		return $content;
	}
	
	static function HasAttach($LetterID){
		
		$dt = PdoDataAccess::runquery( "select LetterID				
			from OFC_letters l 
				join DMS_documents on(ObjectType='letterAttach' AND ObjectID=l.LetterID)
			where l.LetterID=?", array($LetterID));

		return count($dt) > 0;
	}
}

class OFC_send extends PdoDataAccess{
	
	public $SendID;
    public $LetterID;
	public $FromPersonID;
	public $ToPersonID;
	public $SendDate;
	public $SendType;
	public $SendComment;
	public $IsUrgent;
	public $IsSeen;
	public $SeenTime;
	public $IsDeleted;
	public $IsCopy;
	public $ResponseTimeout;
	public $FollowUpDate;
	public $SenderDelete;

    function __construct($SendID = ""){
		
		$this->DT_SendDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_ResponseTimeout = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_FollowUpDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_SendType = DataMember::CreateDMA(DataMember::DT_INT, "1");
		
		if($SendID != "")
			parent::FillObject($this, "select * from OFC_send where SendID=?", array($SendID));
    }

    static function GetAll($where = "",$whereParam = array()){
	    $query = "select * from OFC_send ";
	    $query .= ($where != "") ? " where " . $where : "";
	    return parent::runquery($query, $whereParam);
    }
	
    function AddSend($pdo = null){
		
		$this->SendType = empty($this->SendType) ? "1" : $this->SendType;
		
	    if( parent::insert("OFC_send", $this, $pdo) === false )
		    return false;

	    $this->SendID = parent::InsertID($pdo);

	    $daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->SendID;
		$daObj->SubObjectID = $this->LetterID;
		$daObj->TableName = "OFC_send";
		$daObj->execute($pdo);
		return true;	
    }
	
	function EditSend($pdo = null){
	    if( parent::update("OFC_send", $this, "SendID=:s", array(":s" =>$this->SendID), $pdo) === false )
		    return false;

	    $daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->SendID;
		$daObj->TableName = "OFC_send";
		$daObj->execute($pdo);
		return true;	
    }
	
	function DeleteSend($pdo = null){
	    if( parent::delete("OFC_send", "SendID=?", array($this->SendID), $pdo) === false )
		    return false;

	    $daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $this->SendID;
		$daObj->TableName = "OFC_send";
		$daObj->execute($pdo);
		return true;	
    }
	
	static function UpdateIsSeen($SendID){
		$obj = new OFC_send($SendID);
		
		if($obj->ToPersonID != $_SESSION["USER"]["PersonID"])
			return false;
		
		$obj->IsSeen = "YES";		
		if($obj->SeenTime == "")
			$obj->SeenTime = PDONOW;
		return $obj->EditSend();
	}
}

class OFC_archive extends PdoDataAccess{
	
	public $FolderID;
    public $ParentID;
	public $FolderName;
	public $PersonID;
	
    function AddFolder($pdo = null){
	    if( parent::insert("OFC_archive", $this, $pdo) === false )
		    return false;

	    $this->FolderID = parent::InsertID($pdo);

	    $daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = $this->FolderID;
		$daObj->TableName = "OFC_archive";
		$daObj->execute($pdo);
		return true;	
    }
	
	function EditFolder($pdo = null){
	    if( parent::update("OFC_archive", $this, "FolderID=:s", array(":s" =>$this->FolderID), $pdo) === false )
		    return false;

	    $daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->FolderID;
		$daObj->TableName = "OFC_archive";
		$daObj->execute($pdo);
		return true;	
    }
	
	static function DeleteFolder($FolderID){
		
		PdoDataAccess::runquery("delete from OFC_ArchiveItems where FolderID=?", array($FolderID));
		
	    if( parent::delete("OFC_archive", "FolderID=?", array($FolderID)) === false )
		    return false;

	    $daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $FolderID;
		$daObj->TableName = "OFC_archive";
		$daObj->execute();
		return true;	
    }
	
	static function IsEmpty($FolderID){
		
		$dt = PdoDataAccess::runquery_fetchMode("select * from OFC_ArchiveItems where FolderID=?",
			array($FolderID));
		
		return $dt->rowCount() > 0;
	}
}

class OFC_LetterCustomers extends OperationClass{
	
	const TableName = "OFC_LetterCustomers";
	const TableKey = "RowID";
	
	public $RowID;
	public $LetterID;
	public $PersonID;
	public $IsHide;
	public $LetterTitle;
}

class OFC_templates extends OperationClass{
	
	const TableName = "OFC_templates";
	const TableKey = "TemplateID";
	
	public $TemplateID;
	public $TemplateTitle;
	public $context;
}

class OFC_LetterNotes extends OperationClass{
	
	const TableName = "OFC_LetterNotes";
	const TableKey = "NoteID";
	
	public $NoteID;
	public $LetterID;
	public $PersonID;
	public $NoteTitle;
	public $NoteDesc;
	public $ReminderDate;
	public $IsSeen;
	
	public function __construct($id = '') {
		
		$this->DT_ReminderDate = DataMember::CreateDMA(DataMember::DT_DATE);		
		parent::__construct($id);
	}
	
	public static function GetRemindNotes(){
		
		return self::Get(" AND PersonID=? AND now()>= ReminderDate AND IsSeen='NO'", 
			array($_SESSION["USER"]["PersonID"]));
	}
}

class OFC_messages extends OperationClass{
	
	const TableName = "OFC_messages";
	const TableKey = "MessageID";
	
	public $MessageID;
	public $MsgTitle;
	public $MsgDesc;
	public $MsgDate;
	public $PersonID;
	public $IsDeleted;
}

class OFC_MessageReceivers extends OperationClass{
	
	const TableName = "OFC_MessageReceivers";
	const TableKey = "SendID";
	
	public $SendID;
	public $MessageID;
	public $PersonID;
	public $IsSeen;
	public $IsDeleted;
	
	static function GetNewMessageReceiveCount(){
		
		$dt = PdoDataAccess::runquery("select SendID from OFC_MessageReceivers r
            join OFC_messages using(MessageID)
            where r.IsSeen='NO' AND r.IsDeleted='NO' AND
            r.PersonID=" . $_SESSION["USER"]["PersonID"]);
		return count($dt);
	}
	
}

class OFC_RefLetters extends OperationClass{
	
	const TableName = "OFC_RefLetters";
	const TableKey = "LetterID";
	
	public $LetterID;
	public $RefLetterID;
	
	public function RemoveRef($LetterID, $RefLetterID){
		
		if(!PdoDataAccess::delete(self::TableName, " LetterID=? AND RefLetterID=?", 
				array($LetterID, $RefLetterID)))
			return false;
		
		$daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_add;
        $daObj->MainObjectID = $LetterID;
		$daObj->SubObjectID = $RefLetterID;
        $daObj->TableName = self::TableName;
        $daObj->execute();		
		
		return true;
	}
}

class OFC_SendComments extends OperationClass{
	
	const TableName = "OFC_SendComments";
	const TableKey = "RowID";
	
	public $RowID;
	public $PersonID;
	public $SendComment;
	
}

class OFC_receivers extends OperationClass{
	
	const TableName = "OFC_receivers";
	const TableKey = "RowID";
	
	public $RowID;
	public $PersonID;
	public $ToPersonID;
	public $ToGroupID;
	
	static function Get($where = '', $whereParams = array(), $pdo = null) {
		$query = "select 
						r.*,
						if(r.ToPersonID>0, 'Person', 'Group' ) type,
						if(r.ToPersonID>0, concat_ws(' ',fname,lname,CompanyName), GroupDesc ) title 
				 from OFC_receivers r 
					left join BSC_persons p on(r.ToPersonID=p.PersonID)
					left join FRW_AccessGroups g on(r.ToGroupID=g.GroupID)
				 where 1=1 " . $where ;
		return parent::runquery($query, $whereParams, $pdo);		
	}
}

class OFC_DailyTips extends OperationClass{
	
	const TableName = "OFC_DailyTips";
	const TableKey = "RowID";
	
	public $RowID;
	public $FromDate;
	public $ToDate;
	public $description;
	
	function __construct($id = '') {
		
		$this->DT_FromDate = DataMember::CreateDMA(DataMember::DT_DATE);
		$this->DT_ToDate = DataMember::CreateDMA(DataMember::DT_DATE);
		
		parent::__construct($id);
	}
}

class OFC_roles extends OperationClass {

	const TableName = "OFC_roles";
	const TableKey = "RowID";
	
	public $RowID;
	public $PersonID;
	public $RoleID;
	
	static function GetUserRole($PersonID){
		
		$dt = PdoDataAccess::runquery("select * from OFC_roles where PersonID=? order by RoleID desc",
			array($PersonID));
		
		return count($dt) == 0 ? "" : $dt[0]["RoleID"];
	}
	
	static function UserHasRole($PersonID, $RoleID){
		
		$dt = PdoDataAccess::runquery("select * from OFC_roles "
				. " where PersonID=? AND RoleID=? order by RoleID desc",
			array($PersonID, $RoleID));
		
		return count($dt) > 0;
	}

}

class OFC_organizations extends OperationClass {

	const TableName = "OFC_organizations";
	const TableKey = "OrgID";
	
	public $OrgID;
	public $OrgTitle;
	public $OrgPost;
	
}
?>
