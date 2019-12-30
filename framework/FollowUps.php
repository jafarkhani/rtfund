<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1395.07
//-----------------------------

require_once 'header.inc.php';
require_once inc_dataGrid;

$dg = new sadaf_datagrid("dg", $js_prefix_address . "management/framework.data.php?task=SelectFollowUps", "grid_div");

$dg->addColumn("", "type", "", true);

$col = $dg->addColumn("موضوع", "title", "");
$col->width = 120;

$col = $dg->addColumn("کد آیتم", "ObjectID", "");
$col->renderer = "FolowUps.ObjectRender";
$col->width = 100;

$col = $dg->addColumn("شرح ردیف", "description", "");

$dg->EnablePaging = false;
$dg->disableFooter = true;
$dg->height = 150;
$dg->width = 790;
$dg->EnablePaging = false;
$dg->EnableSearch = false;
$dg->autoExpandColumn = "description";
$grid = $dg->makeGrid_returnObjects();

?>
<script>

FolowUps.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

FolowUps.ObjectRender = function(v,p,r){
	
	if(v == null)
		return "";
	return "<a onclick='FolowUpsObject.OpenObject(" + v + ",\""+r.data.type+"\")' "+
			"href=javascript:void(1) >" + v + "</a>";
}

FolowUps.prototype.OpenObject = function(ObjectID, type){
	
	switch(type)
	{
		case "letter":
			framework.OpenPage("/office/letter/LetterInfo.php", "مشخصات نامه", {LetterID : ObjectID});
			break;
		case "loan":
			framework.OpenPage("/loan/request/RequestInfo.php", "اطلاعات درخواست", {RequestID : ObjectID});
			break;
		case "package":
			framework.OpenPage("/office/dms/packages.php", "مدیریت پرونده ها",{MenuID : 116});
			break;
        case "meetingRecord":
            framework.OpenPage("/meeting/MeetingInfo.php", "اطلاعات جلسه",
                {
                    MeetingID : ObjectID,
                    MenuID : 281
                });
            break;
	}
	
}

function FolowUps(){
	
	this.grid = <?= $grid ?>;
	this.grid.render(this.get("grid_div"));
}

FolowUpsObject = new FolowUps();

</script>
<center>
	<div id="grid_div"></div>
	
</center>