<script>
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.07
//-----------------------------

ManagePlan.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	MenuID : "<?= $_POST["MenuID"] ?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function ManagePlan(){

	this.AllPlansObj = Ext.button.Button({
		xtype: "button",
		text : "مشاهده همه طرح ها", 
		iconCls : "list",
		enableToggle : true,
		handler : function(){
			me = ManagePlanObject;
			me.grid.getStore().proxy.extraParams["AllPlans"] = this.pressed ? "true" : "false";
			me.grid.getStore().load();
		}
	});
}

ManagePlanObject = new ManagePlan();

ManagePlan.prototype.ManagePlan = function(){
	if(this.get("new_pass").value != this.get("new_pass2").value)
	{
		return;
	}
}

ManagePlan.PlanInfoRender = function(value, p, record){
	
	return "<div  title='اطلاعات طرح' class='info' onclick='ManagePlanObject.ShowPlanInfo();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

ManagePlan.HistoryRender = function(value, p, record){
	
	return "<div  title='سابقه' class='history' onclick='ManagePlanObject.ShowHistory();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

ManagePlan.DeleteRender = function(value, p, record){
	
	if(record.data.StepID == "<?= STEPID_RAW ?>")
		return "<div  title='حذف' class='remove' onclick='ManagePlanObject.DeletePlan();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

    ManagePlan.OpenLetter = function(LetterID){

    framework.OpenPage('/office/letter/LetterInfo.php','مشخصات نامه',
        {LetterID : LetterID});
}
    ManagePlan.ParamValueRender = function(v,p,r){

    if(r.data.LetterID != 0 && r.data.LetterID != null)
    /*return "<a href=javascript:void() onclick=Request.OpenLetter("+v+");>شماره نامه : "+v+"</a>";*/
    return "<a href=javascript:void() onclick=ManagePlan.OpenLetter("+v+");> "+v+"</a>";

}
ManagePlan.prototype.OperationMenu = function(e){

	record = this.grid.getSelectionModel().getLastSelected();
	var op_menu = new Ext.menu.Menu();
	
	op_menu.add({text: 'سابقه درخواست',iconCls: 'history', 
		handler : function(){ return ManagePlanObject.ShowHistory(); }});
	
	op_menu.showAt(e.pageX-120, e.pageY);
}

ManagePlan.prototype.ShowHistory = function(){

	if(!this.HistoryWin)
	{
		this.HistoryWin = new Ext.window.Window({
			title: 'سابقه گردش طرح',
			modal : true,
			autoScroll : true,
			width: 700,
			height : 500,
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "history.php",
				scripts : true
			},
			buttons : [{
					text : "بازگشت",
					iconCls : "undo",
					handler : function(){
						this.up('window').hide();
					}
				}]
		});
		Ext.getCmp(this.TabID).add(this.HistoryWin);
	}
	this.HistoryWin.show();
	this.HistoryWin.center();
	this.HistoryWin.loader.load({
		params : {
			PlanID : this.grid.getSelectionModel().getLastSelected().data.PlanID
		}
	});
}

ManagePlan.prototype.ShowPlanInfo = function(){

	var record = this.grid.getSelectionModel().getLastSelected();
	portal.OpenPage("../plan/plan/PlanInfo.php", {
		MenuID : ManagePlanObject.MenuID,
		PlanID : record.data.PlanID});
	return;
}

ManagePlan.prototype.DeletePlan = function(){

	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = ManagePlanObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'plan.data.php',
			params:{
				task: "DeletePlan",
				PlanID : record.data.PlanID
			},
			method: 'POST',

			success: function(response,option){
				result = Ext.decode(response.responseText);
				if(result.success)
					ManagePlanObject.grid.getStore().load();
				else if(result.data == "")
					Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
				else
					Ext.MessageBox.alert("",result.data);
				mask.hide();
				
			},
			failure: function(){}
		});
	});
}


</script>