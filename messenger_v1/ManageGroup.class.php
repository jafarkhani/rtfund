<?php
//---------------------------
// programmer:	bMahdipour
// create Date:	90.12.16
//---------------------------

class manage_MSG_group extends PdoDataAccess
{
	 
	public $GID;
	public $GroupTitle;
    public $FileType;

	
	function __construct($GID="")
    {
       $this->DT_GID = DataMember::CreateDMA(DataMember::Pattern_Num);
       $this->DT_GroupTitle = DataMember::CreateDMA(DataMember::Pattern_FaEnAlphaNum);
       $this->DT_FileType = DataMember::CreateDMA(DataMember::Pattern_FaEnAlphaNum);                        
      
       if (!empty($GID)) {           
			$params = array($GID);
			$query = "select * from MSG_group where GID =?";
			parent::FillObject($this, $query, $params);
		}
        
		return;
    }
	static function GetAll($where = "",$whereParam = array())
	{
       
		$query = "select * from MSG_group ";
		$query .= ($where != "") ? " where " . $where : "";
        
		return parent::runquery($query, $whereParam);
	}
	 
	function Add()
	{
	 	if( parent::insert("MSG_group", $this) === false )
			return false;

		$this->GID = parent::InsertID();
		
		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = PdoDataAccess::InsertID();
		$daObj->TableName = "MSG_group";
		$daObj->execute();

		return true;
	}
	function Edit()
	{
	 	$whereParams = array();
	 	$whereParams[":GID"] = $this->GID;

	 	if( parent::update("MSG_group",$this," GID=:GID", $whereParams) === false )
	 		return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->GID;
		$daObj->TableName = "MSG_group";
		$daObj->execute();

	 	return true;
    }	
	 	 

	static function Remove($GID)
	{

        $res = parent::runquery(" select count(*) cn from MSG_members where GID = ? " , array($GID) );

        if($res[0]['cn'] > 0 )
        {
            parent::PushException("این گروه دارای عضو می باشد و حذف آن امکان پذیر نمی باشد .");
            return false ;
        }

        //.................
        $obj = new manage_MSG_group($GID) ;
        $filetype = $obj->FileType ;             
        $filename = $obj->GID;       
        
        if (file_exists(GRPPIC_DIRECTORY . $filename . "." . $filetype)) {
            unlink(GRPPIC_DIRECTORY . $filename . "." . $filetype);
        }
        //................
        $result = parent::delete("MSG_group", "GID=:GID ", array(":GID" => $GID));
		
		if($result === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $GID;
		$daObj->TableName = "MSG_group";
		$daObj->execute();

		return true;
	}
}

class manage_MSG_members extends PdoDataAccess
{
	 
	public $MID;
	public $GID;
    public $PersonID;
    
	function __construct()
    {
        $this->DT_MID = DataMember::CreateDMA(DataMember::Pattern_Num);
        $this->DT_GID = DataMember::CreateDMA(DataMember::Pattern_Num);
        $this->DT_PersonID = DataMember::CreateDMA(DataMember::Pattern_Num);        
    }
	static function GetAll($where = "",$whereParam = array())
	{
		$query = "select m.*,p.fname,p.lname    
                  from MSG_members m 
                    inner join BSC_persons p on m.PersonID = p.PersonID   ";
		$query .= ($where != "") ? " where " . $where : "";
		return parent::runquery($query, $whereParam);
	}
	 

	function Add()
	{
        
        if( parent::insert("MSG_members", $this) === false )
			return false;
		
		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = PdoDataAccess::InsertID();
		$daObj->TableName = "MSG_members";
		$daObj->execute();

		return true;
	}
	function Edit()
	{
	 	$whereParams = array();
	 	$whereParams[":GID"] = $this->GID;
        $whereParams[":MID"] = $this->MID;

	 	if( parent::update("MSG_members",$this," GID=:GID and MID=:MID ", $whereParams) === false )
	 		return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_update;
		$daObj->MainObjectID = $this->GID;
        $daObj->SubObjectID = $this->MID;
		$daObj->TableName = "MSG_members";
		$daObj->execute();

	 	return true;
    }
	
	static function Remove($GID,$MID)
	{
        
        $result = parent::delete("MSG_members", "GID=:GID and MID=:MID", array(
                                                ":GID" => $GID , ":MID" => $MID ));
		
		if($result === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $GID;
        $daObj->SubObjectID = $MID;
		$daObj->TableName = "MSG_members";
		$daObj->execute();

		return true;
	}
    
    
    
}

class manage_MSG_messages extends PdoDataAccess
{
	 
	public $MSGID;
	public $GID;
    public $MID;
    public $message;
    public $FileType;
    public $ParentMSGID;
    public $SendingDate;
    
	function __construct($MSGID="")
    {
        $this->DT_MSGID = DataMember::CreateDMA(DataMember::Pattern_Num);
        $this->DT_GID = DataMember::CreateDMA(DataMember::Pattern_Num);
        $this->DT_MID = DataMember::CreateDMA(DataMember::Pattern_Num);        
        $this->DT_message = DataMember::CreateDMA(DataMember::Pattern_Html);        
        $this->DT_FileType = DataMember::CreateDMA(DataMember::Pattern_FaEnAlphaNum);       
        $this->DT_ParentMSGID = DataMember::CreateDMA(DataMember::Pattern_Num);   
        $this->DT_SendingDate = DataMember::CreateDMA(DataMember::Pattern_DateTime);   
        
        if (!empty($MSGID)) {           
			$params = array($MSGID);
			$query = "select * from MSG_messages where MSGID =?";
			parent::FillObject($this, $query, $params);
		}
        
    }
    
    static function GetAllMyMessage($where = "",$whereParam = array())
	{
		$query = "  select m.* ,
                           concat(GroupTitle,'-',if(message is null , ' There is NO Messages Yet ' , message),'-',if(MsgNo is null , 0 , MsgNo)) TM , 
                           if(ms.message is null , 'there is no message yet' , ms.message ) LastMsg , 
                           if(t2.MsgNo is null , 0 ,t2.MsgNo ) MsgNo
                    from MSG_members m

                    inner join MSG_group g on m.GID = g.GID

                    left join MSG_messages ms on ms.GID = m.GID

                    left join (
                        select max(MSGID) maxID
                        from MSG_messages
                        group by GID 
                    ) t on t.maxID = ms.MSGID
                    left join (
                        select me.GID , count(*) MsgNo
                        from MSG_messages me
                        inner join MSG_members mb on me.GID = mb.GID
                                    left join MSG_messagestatus st
                                         on me.MSGID = st.MSGID and mb.MID = st.MID
                        where mb.PersonID = :PID and mb.MID <> me.MID and  st.MSID is null
                        group by me.GID
                    ) t2 on t2.GID = ms.GID
                    
                    where m.PersonID = :PID
                    group by ms.GID
                ";
		$query .= ($where != "") ? " where " . $where : "";
		return parent::runquery($query, $whereParam);
	}
    
    static function GetAllGroupMessage($where = "",$whereParam = array())
	{
        //Add Transaction        
        manage_MSG_messages::InsertSeenMsg($where, $whereParam);
                
		//........................
        $query = "  SELECT ms.MSGID , ms.GID , ms.MID , ms.message message , fname , lname, ms.SendingDate,
                           ms.FileType, ms.ParentMSGID , pms.message ParentMsg
                    FROM MSG_messages ms
                            inner join MSG_members m
                                    on ms.GID = m.GID and ms.MID = m.MID
                            inner join BSC_persons pr using(PersonID)
                            left join MSG_messages pms 
                                    on ms.ParentMSGID = pms.MSGID                                    
                    where ms.GID = :GID
                    order by MSGID  ASC
                    
                 ";
		$query .= ($where != "") ? " where " . $where : "";
		return parent::runquery($query, $whereParam);
	}
    
    static function InsertSeenMsg($where = "",$whereParam = array())
    {
        //......................ثبت پیامهای مشاهده شده.......
        $whereParam2 = array();
        $whereParam2[":PID"] = $_SESSION["USER"]["PersonID"] ; 
        $whereParam2[":GID"] = $whereParam[":GID"]; 
                
        $qry = " select me.MSGID , mb.MID
                 from MSG_messages me
                             inner join MSG_members mb on me.GID = mb.GID
                             left join MSG_messagestatus st on me.MSGID = st.MSGID and mb.MID = st.MID
                 where mb.PersonID = :PID and me.GID = :GID and  st.MSID is null
                 "; 
        $res = parent::runquery($qry, $whereParam2);
     
        for($i=0;$i<count($res);$i++)
        {
            $InsQry = "insert into MSG_messagestatus (MSGID,MID,status) values (:P1,:P2,1) " ; 
            PdoDataAccess::runquery($InsQry,array(":P1" => $res[$i]['MSGID'], ":P2" => $res[$i]['MID'])) ;
        }
       
        return true ;
    }    
    
    static function GetSearchMessage($where = "",$whereParam = array())
	{
		$query = "  select MSGID,ms.message
                    from MSG_members m
                         inner join MSG_group g on m.GID = g.GID
                         inner join MSG_messages ms on ms.GID = m.GID

                    where m.GID = :GID and m.PersonID = :PID and ms.message LIKE :STxt
                 ";
		$query .= ($where != "") ? " where " . $where : "";
        
		return parent::runquery($query, $whereParam);
	}
    
    function Add() {
		if (parent::insert("MSG_messages", $this) === false)
			return false;

		$this->MSGID = parent::InsertID();

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = PdoDataAccess::InsertID();
		$daObj->TableName = "MSG_messages";
		$daObj->execute();

		return true;
	}
    
    function EDIT() {
        
		$whereParams = array();
		$whereParams[":MSGID"] = $this->MSGID;

		if (parent::update("MSG_messages", $this, " MSGID=:MSGID", $whereParams) === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_add;
		$daObj->MainObjectID = PdoDataAccess::InsertID();
		$daObj->TableName = "MSG_messages";
		$daObj->execute();

		return true;
	}
    
    static function Remove($MsgId) {
		$result = parent::delete("MSG_messages", "MSGID=:MSGID ", array(":MSGID" => $MsgId));

		if ($result === false)
			return false;

		$daObj = new DataAudit();
		$daObj->ActionType = DataAudit::Action_delete;
		$daObj->MainObjectID = $MsgId;
		$daObj->TableName = "MSG_messages";
		$daObj->execute();
		return true;
	}

}

?>