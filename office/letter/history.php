<?php
//---------------------------
// programmer:	Jafarkhani
// create Date:	91.12
//---------------------------
require_once("../header.inc.php");
require_once 'letter.class.php';
require_once inc_component;

if(empty($_REQUEST["LetterID"]))
	die();
$LetterID = $_REQUEST["LetterID"];

if(!empty($_REQUEST["task"]) && $_REQUEST["task"] == "GetTreeNodes")
	GetTreeNodes();

function GetTreeNodes(){
	
	$LetterID = $_REQUEST["LetterID"];
	
	$creator = PdoDataAccess::runquery("
		select PersonID,
			concat_ws(' ',fname, lname,CompanyName) text, 
			'true' as leaf, 'true' expanded,'user' iconCls,
			l.RegDate
		from OFC_letters l join BSC_persons p using(PersonID) where LetterID=?", array($LetterID));
	
	$index = 1;
	$returnArray = array();
    $refArray = array();

	$creator[0]["id"] = $index++;
	$creator[0]["text"] .= " [ " . substr($creator[0]["RegDate"], 10, 6) . 
			 " &nbsp;&nbsp; " . DateModules::miladi_to_shamsi($creator[0]["RegDate"]) . "	]";
	$returnArray[] = $creator[0];
	$refArray[ $creator[0]["PersonID"] ] = &$returnArray[count($returnArray) - 1];
	
	$nodes = PdoDataAccess::runquery("
		select FromPersonID ,ToPersonID, SendDate, SeenTime, IsSeen,
			concat_ws(' ',fname, lname,CompanyName,' - ',InfoDesc) text, 
			concat('<b>توضیحات ارجاع : </b>' ,replace(SendComment,'\n','<br>')) qtip,
			'true' as leaf, 'true' expanded,'user' iconCls
		from OFC_send 
			join BSC_persons p on(ToPersonID=PersonID) 
			join BaseInfo bf on(bf.TypeID=12 AND SendType=InfoID)
		where LetterID=?
		order by SendID", array($LetterID));
	
    foreach ($nodes as $row) {
       
		$row["id"] = $index++;
		$row["text"] .= " [ " . substr($row["SendDate"], 10, 6) . 
			 " &nbsp;&nbsp; " . DateModules::miladi_to_shamsi($row["SendDate"]) . " ]";
		
		if($row["SeenTime"] != "")
			$row["text"] .= " [مشاهده در " . DateModules::miladi_to_shamsi($row["SeenTime"]) .
				"&nbsp;" . substr($row["SeenTime"], 10, 6) . " ]";
		
		$parentNode = &$refArray[ $row["FromPersonID"] ];

        if (!isset($parentNode["children"])) {
            $parentNode["children"] = array();
            $parentNode["leaf"] = "false";
        }
        $lastIndex = count($parentNode["children"]);
        $parentNode["children"][$lastIndex] = $row;
		
        $refArray[ $row["ToPersonID"] ] = &$parentNode["children"][$lastIndex];
    }
	
	$str = json_encode($returnArray);

    $str = str_replace('"children"', 'children', $str);
    $str = str_replace('"leaf"', 'leaf', $str);
	$str = str_replace('"iconCls"', 'iconCls', $str);
    $str = str_replace('"text"', 'text', $str);
    $str = str_replace('"id"', 'id', $str);
    $str = str_replace('"true"', 'true', $str);
    $str = str_replace('"false"', 'false', $str);

    echo $str;
	die();
}

?>
<script>
LetterHistory.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function LetterHistory(){
	this.tree = new Ext.tree.Panel({
		renderTo : this.get('tree-div'),
		frame: true,
		autoWidth : true,
		height: 465,
		store : new Ext.data.TreeStore({
			root : {
				id : "source",
				text : "سابقه گردش نامه",
				expanded: true
			},
			proxy: {
				type: 'ajax',
				url: this.address_prefix + "history.php?task=GetTreeNodes&LetterID=<?= $LetterID ?>"
			}
		})
	});
}

LetterHistoryObject = new LetterHistory();

</script>
<div id="tree-div"></div>