<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------
 
require_once '../../header.inc.php';

require_once inc_dataReader;
require_once inc_response;

if(!empty($_REQUEST["SaveUserState"]))
	SaveUserState();

function SaveUserState(){
	
	PdoDataAccess::runquery("insert into ACC_UserState values(:p,:b,:c) 
		on duplicate key update BranchID=:b , CycleID=:c", array(
			":p" => $_SESSION["USER"]["PersonID"],
			":b" => $_POST["BranchID"],
			":c" => $_POST["CycleID"]
		));
	echo Response::createObjectiveResponse(true, "");
	die();
}

?>
<script>
UserState.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function UserState()
{
	this.passform = new Ext.form.FormPanel({
		renderTo : "form_div",
		frame: true,
		title: 'تعیین شعبه و دوره',
		bodyStyle:'padding:5px 5px 0px',
		width: 400,
		items: [{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: "/accounting/global/domain.data.php?task=GetAccessBranches",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['BranchID','BranchName'],
				autoLoad : true					
			}),
			fieldLabel : "شعبه",
			anchor : "90%",
			queryMode : 'local',
			allowBlank : false,
			value : "<?= !isset($_SESSION["accounting"]["BranchID"]) ? "" : $_SESSION["accounting"]["BranchID"] ?>",
			displayField : "BranchName",
			valueField : "BranchID",
			name : "BranchID"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: "/accounting/global/domain.data.php?task=SelectCycles",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['CycleID','CycleDesc'],
				autoLoad : true					
			}),
			fieldLabel : "دوره",
			queryMode : 'local',
			anchor : "90%",
			value : "<?= !isset($_SESSION["accounting"]["CycleID"]) ? "" : $_SESSION["accounting"]["CycleID"] ?>",
			allowBlank : false,
			displayField : "CycleDesc",
			valueField : "CycleID",
			name : "CycleID"
		}],

		buttons : [{
			text : "اعمال",
			iconCls: 'save',
			handler: function() {
				
				this.up('form').submit({
					url: UserStateObject.address_prefix + 'UserState.php?SaveUserState=true' , 
					method: "POST",
					
					success : function()
					{
						window.location = window.location;
					},
					failure : function()
					{
						alert("عملیات مورد نظر با شکست مواجه شد");
						mask.hide();
					}
				});
			}

		}]
	});

	this.passform.center();
}

UserStateObject = new UserState();

UserState.prototype.UserState = function()
{
	if(this.get("new_pass").value != this.get("new_pass2").value)
	{
		return;
	}
}
</script>
<br><br>
<div id="form_div"></div>
