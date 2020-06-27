<?php
//---------------------------
//	Programmer	: Sh.Jafarkhani
//	Date		: 97.05
//---------------------------

require_once "../../header.inc.php";
require_once inc_dataGrid;
$ProcessID = (int) $_POST["ProcessID"];

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg2", $js_prefix_address . "baseinfo.data.php?task=selectSharing&ProcessID=" . $ProcessID, "div_detail_dg");

$dg->addColumn(" ", "ShareID", "", true);
$dg->addColumn(" ", "ProcessID", "", true);
$dg->addColumn(" ", "CostID", "", true);
$dg->addColumn(" ", "PostID", "", true);
$dg->addColumn(" ", "BaseID", "", true);
$dg->addColumn(" ", "MethodID", "", true);
$dg->addColumn(" ", "PostID", "", true);

$dg->addColumn(" ", "IsActive", "", true);
$dg->addColumn(" ", "ChangeDate", "", true);
$dg->addColumn(" ", "changePersonName", "", true);

$col = $dg->addColumn("نوع ارتباط", "ShareType");
$col->renderer = "function(v){ return v== 'SHARE' ? 'تسهیم' : 'تخصیص'}";
$col->width = 60;

$col = $dg->addColumn(" کد حساب ", "CostCode");
$col->width = 65;

$col = $dg->addColumn(" عنوان حساب ", "CostDesc");

$col = $dg->addColumn("روش تسهیم", "MethodDesc");
$col->width = 100;
$col->ellipsis = 40; 

$col = $dg->addColumn("مبنای تسهیم", "BaseDesc");
$col->width = 120;
$col->ellipsis = 40; 

$col = $dg->addColumn("مقدار تسهیم", "BaseValue");
$col->width = 120;
$col->ellipsis = 40; 

$col = $dg->addColumn("پست سازمانی", "PostName");
$col->width = 100;

$col = $dg->addColumn("توضیحات", "ChangeDesc");
$col->width = 150;
$col->renderer = "COM_share.ChangeRender";
if ($accessObj->AddFlag)
    $dg->addButton("", "ایجاد ردیف", "add", "function(v,p,r){ return COM_shareObj.AddItem(v,p,r);}");

$col = $dg->addColumn("عملیات", "PlanID");
$col->renderer = "COM_share.OperationRender";
$col->width = 60;

$dg->addObject('this.HistoryObj');

$dg->PrintButton = true;

$dg->DefaultSortField = "ShareID";
$dg->autoExpandColumn = "CostDesc";
$dg->DefaultSortDir = "DESC";
$dg->EnableRowNumber = false;
$dg->EnableSearch = false;
$dg->height = 460;
$dg->emptyTextOfHiddenColumns = true;
$dg->EnablePaging = false;
$itemsgrid = $dg->makeGrid_returnObjects();
?>
<style type="text/css">
    .docInfo td{height:20px;}
    .blue{ color: #1E4685; font-weight:bold;}
</style>
<script type="text/javascript">

    COM_share.prototype = {
		TabID : '<?= $_REQUEST["ExtTabID"] ?>',
		address_prefix : "<?= $js_prefix_address ?>",
		ProcessID : <?= $ProcessID?>,
		
		AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
		EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
		RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,
		
        get : function(elementID){
            return findChild(this.TabID, elementID);
        }
    };
    
    function COM_share(){
		
		this.HistoryObj = Ext.button.Button({
			xtype: "button",
			text : "سابقه تغییرات", 
			enableToggle : true,
			handler : function(){
				me = COM_shareObj;
				me.itemGrid.getStore().proxy.extraParams["AllHistory"] = this.pressed ? "true" : "false";
				me.itemGrid.getStore().load();
			}
		});
		
		this.itemGrid = <?= $itemsgrid ?>;
		this.itemGrid.getView().getRowClass = function (record, index)
		{
			if (record.data.IsActive == "NO")
				return "pinkRow";
			if (record.data.ChangeDate != null)
				return "greenRow";
			return "";
		};
		this.itemGrid.render(this.get("div_detail_dg"));
		
		this.formPanel = new Ext.form.Panel({
			applyTo : this.get("DIV_formPanel"),
			title : "اطلاعات ردیف",
			frame : true,
			defaults : {
				anchor : "90%"
			},
			hidden : true,
			width : 500,
			items : [{
				xtype : "combo",
				store :  new Ext.data.Store({
					data : [{id : "SHARE", title : "تسهیم"},{id : "ALLOC", title : "تخصیص"}],
					fields : ['id','title']
				}),
				valueField : "id",
				displayField : "title",
				name : "ShareType",
				fieldLabel : "نوع ارتباط",
				width : 250
			},{
				xtype : "combo",
				width : 385,
				fieldLabel : "حساب مربوطه",
				colspan : 2,
				store: new Ext.data.Store({
					fields:["CostID","CostCode","CostDesc", "TafsiliType1","TafsiliType2",{
						name : "fullDesc",
						convert : function(value,record){
							return "[ " + record.data.CostCode + " ] " + record.data.CostDesc
						}				
					}],
					proxy: {
						type: 'jsonp',
						url: '/accounting/baseinfo/baseinfo.data.php?task=SelectCostCode',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					}
				}),
				typeAhead: false,
				itemId : "CostID",
				name : "CostID",
				valueField : "CostID",
				displayField : "fullDesc"
			},{
				xtype : "combo",
				fieldLabel : "روش تسهیم",
				allowBlank : true,
				store: new Ext.data.Store({
					fields:["InfoID","InfoDesc"],
					proxy: {
						type: 'jsonp',
						url: this.address_prefix + 'baseinfo.data.php?task=selectMethods',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					},
					autoLoad : true
				}),
				typeAhead: false,
				queryMode : "local",
				name : "MethodID",
				valueField : "InfoID",
				displayField : "InfoDesc"
			},{
				xtype : "combo",
				fieldLabel : "مبنای تسهیم",
				allowBlank : true,
				store: new Ext.data.Store({
					fields:["InfoID","InfoDesc"],
					proxy: {
						type: 'jsonp',
						url: this.address_prefix + 'baseinfo.data.php?task=selectBases',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					},
					autoLoad : true
				}),
				typeAhead: false,
				queryMode : "local",
				name : "BaseID",
				valueField : "InfoID",
				displayField : "InfoDesc"
			},{
				xtype : "textfield",
				fieldLabel : "مقدار تسهیم",
				name : "BaseValue"
			},{
				xtype : "combo",
				fieldLabel : "پست سازمانی",
				allowBlank : true,
				store: new Ext.data.Store({
					fields:["PostID","PostName"],
					proxy: {
						type: 'jsonp',
						url: '/framework/baseInfo/baseInfo.data.php?task=SelectPosts',
						reader: {root: 'rows',totalProperty: 'totalCount'} 
					},
					autoLoad : true
				}),
				typeAhead: false,
				queryMode : "local",
				name : "PostID",
				valueField : "PostID",
				displayField : "PostName"
			},{
				xtype : "textarea",
				fieldLabel : "توضیحات تغییر ردیف",
				name : "ChangeDesc",
				allowBlank : false
			},{
				xtype : "hidden",
				name : "ShareID"
			},{
				xtype : "hidden",
				name : "ProcessID",
				value : this.ProcessID
			}],
			buttons : [{
				text : "ذخیره",
				iconCls : "save",
				handler : function(){COM_shareObj.SaveItem();}
			},{
				text : "انصراف",
				iconCls : "undo",
				handler : function(){COM_shareObj.formPanel.hide();}
			}]
		});

    }
    
	COM_share.OperationRender = function(v,p,r){
	
		if(r.data.IsActive == 'NO')
			return "";
        if(COM_shareObj.EditAccess)
			var st = "<table><tr>"+
			"<td><div title='ویرایش ردیف' class='edit' onclick='COM_shareObj.EditRow();' " +
			"style='background-repeat:no-repeat;background-position:center;" +
			"cursor:pointer;width:25px;height:16'></div></td>";
        if(COM_shareObj.RemoveAccess)
			st += "<td><div title='حذف' class='remove' onclick='COM_shareObj.RemoveItem();'" +
			"style='cursor:pointer;background-repeat:no-repeat;background-position:center;" +
			"width:25px;height:16'></div></td>";
		return st + "</tr></table>";	
	}
	
	COM_share.ChangeRender = function(v,p,r){
	
		var str = 'data-qtip="';
		
		if(r.data.ChangeDate != null)
			str += '<br> توضیحات : <b>' + r.data.ChangeDesc 
			+ '</b><br> زمان عملیات : ' + r.data.ChangeDate.substr(11) + "  " + MiladiToShamsi(r.data.ChangeDate.substr(0,10)) + 
				'<br>عامل : ' + r.data.changePersonName; 
		
		str += '"';
		p.tdAttr = str;
		return v;	
	}
	
    COM_share.prototype.AddItem = function(){
		
        this.formPanel.getForm().reset();
        this.formPanel.show();
        this.formPanel.center();
    }
	
	COM_share.prototype.EditRow = function(){
		
		mask = new Ext.LoadMask(this.formPanel, {msg:'در حال حذف ...'});
		mask.show();
			
		me = COM_shareObj;
		var record = me.itemGrid.getSelectionModel().getLastSelected();

        this.formPanel.getForm().loadRecord(record);
		
		this.formPanel.getComponent("CostID").getStore().proxy.extraParams["CostID"] = record.data.CostID;
		this.formPanel.getComponent("CostID").getStore().load({
			callback : function(){ 
				mask.hide(); 
				me = COM_shareObj;
				me.formPanel.getComponent("CostID").getStore().proxy.extraParams["CostID"] = "";
			}
		});
		
        this.formPanel.show();
        this.formPanel.center();
    }

    COM_share.prototype.SaveItem = function(store,record){
		
        mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخیره سازی ...'});
        mask.show();

		this.formPanel.getForm().submit({
			clientValidation: true,
			url: this.address_prefix + 'baseinfo.data.php?task=saveSharing',
            method: 'POST',
			
			success : function(form,action){
				mask.hide();
                if(action.result.success)
                {
					me = COM_shareObj;
					me.formPanel.hide();
                    me.itemGrid.getStore().load();
					if(action.result.data != "")
						Ext.MessageBox.alert("هشدار", action.result.data);
                }
                else
                {
                    Ext.MessageBox.alert("خطا", action.result.data);
                }
			},
			failure : function(){mask.hide();}
		});
    }
       
	COM_share.prototype.RemoveItem = function(){
	
		if(!this.RemoveItemWin)
		{
			this.RemoveItemWin = new Ext.window.Window({
				title : '',
				modal : true,
				width : 400,
				closeAction : "hide",
				items : new Ext.form.Panel({
					plain: true,
					border: 0,
					bodyPadding: 5,
					items : [{
						xtype : "textarea",
						name : "ChangeDesc",
						size:35,
						fieldLabel : "توضیحات"
					}],
					buttons : [{
						text : "حذف",
						iconCls : "remove",
						handler : function(){

							var me = COM_shareObj;
							mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
							mask.show();

							var record = me.itemGrid.getSelectionModel().getLastSelected();

							Ext.Ajax.request({
								url: me.address_prefix + 'baseinfo.data.php?task=DeleteSharing',
								method: 'POST',
								params: {
									ShareID : record.data.ShareID,
									ChangeDesc : this.up('form').down("[name=ChangeDesc]").getValue()
								},

								success: function(response){
									mask.hide();
									var st = Ext.decode(response.responseText);
									if(st.success)
									{
										me = COM_shareObj;
										me.RemoveItemWin.hide();
										me.itemGrid.getStore().load();
									}
									else
									{
										Ext.MessageBox.alert("خطا", st.data);
									}
								},
								failure: function(){}
							});  
						}
					},{
						text : "انصراف",
						iconCls : "undo",
						handler : function(){
							var me = COM_shareObj;
							me.RemoveItemWin.hide();
						}
					}]//end of buttons
				})//end of items
			});//end of window       
		}
		this.RemoveItemWin.show();
    }
       
	COM_shareObj = new COM_share();
</script>
<center>
    <form id="mainForm">
        <div align="right"><div id="DIV_formPanel" align="right"></div></div>
        <div id="div_detail_dg" style="width:100%;"></div>
    </form>
</center>





