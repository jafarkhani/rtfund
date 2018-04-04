<script type="text/javascript">
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------

WFM.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function WFM()
{
	this.PersonCombo = new Ext.form.ComboBox({
		store: new Ext.data.Store({
			proxy:{
				type: 'jsonp',
				url: this.address_prefix + '../../framework/person/persons.data.php?task=selectPersons'+
					'&UserType=IsStaff',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			},
			fields :  ['PersonID','fullname']
		}),
		displayField: 'fullname',
		valueField : "PersonID",
		allowBlank : true
	});
}

WFM.deleteRender = function(v,p,r)
{
	if(r.data.param4 == 'form')
		return "<div align='center' title='حذف ' class='remove' onclick='WFMObject.Deleting();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

WFM.StepsRender = function(v,p,r)
{
	return "<div align='center' title='حذف ' class='step' onclick='WFMObject.Steps();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

WFM.prototype.Adding = function()
{
	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		FlowID : "",
		param4 : "form",
		ObjectType : "5",
		FlowDesc : ""
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

WFM.prototype.saveData = function(store,record)
{
    mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		params: {
			task: 'SaveFlow',
			record : Ext.encode(record.data)
		},
		url: this.address_prefix +'wfm.data.php',
		method: 'POST',

		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(st.success)
			{
				WFMObject.grid.getStore().load();
			}
			else
			{
				Ext.MessageBox.alert("Error",st.data);
			}
		},
		failure: function(){}
	});
}

WFM.prototype.Deleting = function()
{
	var record = this.grid.getSelectionModel().getLastSelected();
	if(record && confirm("آيا مايل به حذف مي باشيد؟"))
	{
		Ext.Ajax.request({
		  	url : this.address_prefix + "wfm.data.php",
		  	method : "POST",
		  	params : {
		  		task : "DeleteFlow",
		  		FlowID : record.data.FlowID
		  	},
		  	success : function(response,o)
		  	{
				sd = Ext.decode(response.responseText);
				if(sd.success)
					WFMObject.grid.getStore().load();
				else{
					Ext.MessageBox.alert("ERROR", sd.data != "" ? sd.data : "عملیات مورد نظر با شکست مواجه شد" )
				}
					
		  	}
		});
	}
}

WFM.prototype.Steps = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	this.StepsGrid.getStore().proxy.extraParams = {
		FlowID : record.data.FlowID
	};
	if(!this.stepsWin)
	{
		this.stepsWin = new Ext.window.Window({
			width : 710,
			title : "مراحل گردش",
			height : 460,
			modal : true,
			closeAction : "hide",
			items : [this.StepsGrid],
			buttons :[{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		Ext.getCmp(this.TabID).add(this.stepsWin);
	}
	else
		this.StepsGrid.getStore().load();

	this.stepsWin.show();
	this.stepsWin.center();
}

//----------------------------------------------------------

WFM.deleteStepRender = function(v,p,r)
{
	return "<div align='center' title='حذف ' class='remove' onclick='WFMObject.DeleteStep();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

WFM.upRender = function(v,p,r)
{
	if(r.data.StepID == 1)
		return "";
	return "<div align='center' title='حذف ' class='up' onclick='WFMObject.moveStep(-1);' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

WFM.downRender = function(v,p,r)
{
	store = WFMObject.StepsGrid.getStore();
	record = store.getAt(store.getCount()-1);
	if(r.data.StepID == record.data.StepID)
		return "";
	return "<div align='center' title='حذف ' class='down' onclick='WFMObject.moveStep(1);' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

WFM.PersonsRender = function(v,p,r)
{
	return "<div align='center' title='افراد' class='list' onclick='WFMObject.ShowPersons();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:16px;height:16'></div>";
}

WFM.prototype.AddStep = function()
{
	var record = this.grid.getSelectionModel().getLastSelected();
	
	var modelClass = this.StepsGrid.getStore().model;
	var record = new modelClass({
		FlowID : record.data.FlowID,
		StepID : "",
		StepDesc : ""
	});

	this.StepsGrid.plugins[0].cancelEdit();
	this.StepsGrid.getStore().insert(0, record);
	this.StepsGrid.plugins[0].startEdit(0, 0);
}

WFM.prototype.saveStep = function(store,record)
{
    mask = new Ext.LoadMask(this.stepsWin, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		params: {
			task: 'SaveStep',
			record : Ext.encode(record.data)
		},
		url: this.address_prefix +'wfm.data.php',
		method: 'POST',

		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(st.success)
			{
				WFMObject.StepsGrid.getStore().load();
			}
			else
			{
				Ext.MessageBox.alert("Error",st.data);
			}
		},
		failure: function(){}
	});
}

WFM.prototype.moveStep = function(direction)
{
	var record = this.StepsGrid.getSelectionModel().getLastSelected();
	
    mask = new Ext.LoadMask(this.stepsWin, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		params: {
			task: 'MoveStep',
			FlowID : record.data.FlowID,
			StepID : record.data.StepID,
			direction : direction
		},
		url: this.address_prefix + 'wfm.data.php',
		method: 'POST',

		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(st.success)
			{
				WFMObject.StepsGrid.getStore().load();
			}
			else
			{
				Ext.MessageBox.alert("Error",st.data);
			}
		},
		failure: function(){}
	});
}

WFM.prototype.DeleteStep = function()
{
	var record = this.StepsGrid.getSelectionModel().getLastSelected();
	Ext.MessageBox.confirm("", "آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		me = WFMObject;
		
		Ext.Ajax.request({
		  	url : me.address_prefix + "wfm.data.php",
		  	method : "POST",
		  	params : {
		  		task : "DeleteStep",
		  		StepRowID : record.data.StepRowID
		  	},
		  	success : function(response)
		  	{
				result = Ext.decode(response.responseText);
				if(result.success)
					WFMObject.StepsGrid.getStore().load();
				else if(result.data == "FlowRowExists")
					Ext.MessageBox.alert("Error","آیتم هایی هستند که گردش آنها در این مرحله می باشید و قادر به حذف این مرحله نمی باشید");
				else
					Ext.MessageBox.alert("Error", "عملیات مورد نظر با شکست مواجه شد");
					
		  	}
		});
	});
}

WFM.prototype.ShowPersons = function(){
	
	var record = this.StepsGrid.getSelectionModel().getLastSelected();
	
	if(!this.PersonsWin)
	{
		this.PersonsWin = new Ext.window.Window({
			width : 765,
			title : "لیست کاربران جهت مشاهده فرم",
			bodyStyle : "background-color:white;text-align:-moz-center",
			height : 565,
			modal : true,
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "FlowStepPersons.php",
				scripts : true
			},
			buttons :[{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		Ext.getCmp(this.TabID).add(this.PersonsWin);
	}
	this.PersonsWin.show();
	this.PersonsWin.center();
	this.PersonsWin.loader.load({
		params : { 
			ExtTabID : this.PersonsWin.getEl().id,
			MenuID : <?= $_REQUEST["MenuID"] ?>,
			StepRowID : record.data.StepRowID}
	});
}

</script>
