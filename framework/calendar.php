<?php
//---------------------------
// programmer:	Sh.Jafarkhani
// create Date:	95.09
//---------------------------
require_once('../header.inc.php');

?>
<script type="text/javascript">
		
calendar.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	PersonID : '<?= $_SESSION["USER"]["PersonID"] ?>',
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function calendar()
{
	this.colorsStore = new Ext.data.ArrayStore({
		fields :['ColorID',"title"],
		data : [
			[1,'●'],
			[2,'●'],
			[3,'●'],
			[4,'●'],
			[5,'●'],
			[6,'●']
		]
	});

	// A sample event store that loads static JSON from a local file. Obviously a real
	// implementation would likely be loading remote data via an HttpProxy, but the
	// underlying store functionality is the same.
	this.eventStore = new Ext.data.Store({
		proxy:{
			type: 'jsonp',
			url: this.address_prefix + 'management/framework.data.php?task=SelectCalendarEvents',
			reader: {root: 'rows',totalProperty: 'totalCount'}
		},
		fields :  ['EventID',"EventTitle","EventDesc",{
				name : "ColorID",
				convert : function(value){return value*1}
			},{
				name : "FromTime",
				convert : function(value){return value ? value.substr(0,5) : "";}
			},{
				name : "ToTime",
				convert : function(value){return value ? value.substr(0,5) : "";}
			},{
				name : "StartDate",
				convert : function(value,record){
					var arr = value.split(/[\-\/]/), h=1, m=0;
					if(record.data.FromTime)
					{
						var arr2 = record.data.FromTime.split(/[\:]/);
						h = arr2[0]*1;
						m = arr2[1]*1;
					}					
					return new Ext.SHDate(arr[0]*1, arr[1]*1-1, arr[2]*1, h,m);
				}
			},{
				name : "EndDate",
				convert : function(value){
					var arr = value.split(/[\-\/ \:]/);
					return new Ext.SHDate(arr[0]*1, arr[1]*1-1, arr[2]*1);
				}
			},{
				name : "IsAllDay",
				convert : function(value){return value == "YES" ? true : false}
			},{
				name : "reminder",
				convert : function(value){return value == "YES" ? true : false}
			}],
		autoLoad : true
	});

	this.calendarPanel = new Ext.calendar.CalendarPanel({
		width : 800,
		height : 600,
		renderTo : this.get("calendar_container"),
		eventStore: this.eventStore,
		calendarStore: this.calendarStore,
		activeItem: 3, // month view

		monthViewCfg: {
			showHeader: true,
			showWeekLinks: true,
			showWeekNumbers: true
		},

		listeners: {
			'eventclick': {
				fn: function(vw, record, el){
					calendarObj.showEditWindow(record);
				},
				scope: this
			},
			'dayclick': {
				fn: function(vw, dt, ad, el){
					
					if(calendarObj.InfoWin)
						calendarObj.InfoWin.down('form').form.reset();
					var modelClass = calendarObj.eventStore.model;
					var record = new modelClass({
						ColorID : 1,
						StartDate :	dt.format("Y/m/d"),
						EndDate :	dt.format("Y/m/d"),
						AllDay :	ad
						
					});
					calendarObj.showEditWindow(record);
				},
				scope: this
			},
			'eventdelete': {
				fn: function(win, rec){
					this.eventStore.remove(rec);
				},
				scope: this
			}
		}
	});
	// update the header logo date:
	//document.getElementById('logo-body').innerHTML = new Date().getDate();
}

calendar.prototype.showEditWindow = function(record){
	
	if(!this.InfoWin){
		
		this.InfoWin = new Ext.window.Window({
			width : 600,
			autoHeight : true,
			
			items : new Ext.form.Panel({
				layout :{
					type : "table",
					columns : 4
				},
				items :[{
					xtype : "textfield",
					colspan : 3,
					width : 400,
					labelWidth : 80,
					name : "EventTitle",
					allowBlank : false,
					fieldLabel : "عنوان رویداد"
				},{
					xtype : "combo",
					store : this.colorsStore,
					valueField : "ColorID",
					displayField : "title",
					name : "ColorID",	
					fieldStyle : "font-size:20px",
					tpl: new Ext.XTemplate(
						'<tpl for=".">',
							'<div class="x-boundlist-item ext-color-{ColorID}">',
								'<div class="ext-cal-picker-icon">&#160;</div>{title}',
							'</div>',
						'</tpl>'
					),
					listeners :{
						change : function(el, newValue, oldValue){
							if(this.getEl())
							{
								wrap = this.getEl().down('.x-form-field');
								var currentClass = 'ext-color-' + oldValue,
								newClass = 'ext-color-' + newValue;
								wrap.replaceCls(currentClass, newClass);
							}
						}
					}
				},{
					xtype : "textarea",
					colspan : 4,
					width : 550,
					labelWidth : 80,
					name : "EventDesc",
					allowBlank : true,
					fieldLabel : "توضیحات"
				},{
					xtype : "shdatefield",
					name : "StartDate",
					labelWidth : 80,
					width : 180,
					allowBlank : false,
					fieldLabel : "تاریخ"
				},{
					xtype : "timefield",
					name : "FromTime",
					format : "H:i",
					submitFormat : "H:i:s",
					minValue : "06:00",
					maxValue : "23:00",
					width : 100,
					listeners : {
						select : function(){
							this.up('form').down("[name=ToTime]").setValue(this.getValue());
						}
					}
				},{
					xtype : "timefield",
					name : "ToTime",
					fieldLabel : "تا",
					format : "H:i",
					submitFormat : "H:i:s",
					minValue : "06:00",
					maxValue : "23:00",
					labelWidth : 20,
					width : 120
				},{
					xtype : "shdatefield",
					name : "EndDate",
					width : 100,
					allowBlank : false
				},{
					xtype : "checkbox",
					name : "IsAllDay",
					inputValue : "YES",
					width : 85,
					labelWidth : 50,
					boxLabel : "کل روز",
					listeners : {
						change : function(){
							if(this.checked)
							{
								this.up('form').down("[name=FromTime]").disable();
								this.up('form').down("[name=ToTime]").disable();
							}
							else
							{
								this.up('form').down("[name=FromTime]").enable();
								this.up('form').down("[name=ToTime]").enable();
							}
						}
					}
				},{
					xtype : "checkbox",
					name : "reminder",
					inputValue : "YES",
					width : 85,
					labelWidth : 50,
					boxLabel : "هشدار رویداد"
				},{
					xtype : "hidden",
					name : "EventID"
				}]
			}),
			buttons :[{
				itemId : "DeleteBtn",
				iconCls : "remove",
				text : "حذف",
				handler : function(){
					calendarObj.DeleteEvent();
				}
			},'->',{
				iconCls : "save",
				text : "ذخیره",
				handler : function(){
					calendarObj.SaveEvent();
				}
			},{
				iconCls : "undo",
				text : "بازگشت",
				handler : function(){
					this.up("window").hide();
				}
			}]
		});
		
		Ext.getCmp(this.TabID).add(this.InfoWin);		
	}
	this.InfoWin.down('form').loadRecord(record);
	
	if(record.data.EventID*1 > 0)
		this.InfoWin.down("[itemId=DeleteBtn]").show();
	else
		this.InfoWin.down("[itemId=DeleteBtn]").hide();
		
	this.InfoWin.show();
	this.InfoWin.center();
};
        
calendarObj = new calendar();

calendar.prototype.SaveEvent = function(){

	if(!this.InfoWin.down('form').form.isValid())
		return;
			
	mask = new Ext.LoadMask(calendarObj.InfoWin, {msg:'در حال ذخیره ...'});
	mask.show();
	
	this.InfoWin.down('form').form.submit({
		clientValidation: true,
		url : this.address_prefix + 'management/framework.data.php?task=saveCalendarEvent',
		method : "POST",
		
		success : function(form,action){
			mask.hide();
			if(action.result.success)
				calendarObj.eventStore.load();
			else
				Ext.MessageBox.alert("Error","عملیات مورد نظر با شکست مواجه شد.");

			calendarObj.InfoWin.hide();
		},
		failure : function(){
			mask.hide();
		}
	});
}

calendar.prototype.DeleteEvent = function(){

	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return; 
		
		me = calendarObj;
		var EventID = me.InfoWin.down("[name=EventID]").getValue();
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'management/framework.data.php?task=removeCalendarEvent',
			params:{
				EventID: EventID
			},
			method: 'POST',

			success: function(){
				mask.hide();
				calendarObj.eventStore.load();
				calendarObj.InfoWin.hide();
			},
			failure: function(){}
		});
	});
}

	
</script>
<div style="margin:4px" id="calendar_container"></div>
