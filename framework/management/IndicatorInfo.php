<?php
//-----------------------------
//  m.mokhtari
//	Date		: 99.05
//-----------------------------
require_once '../../header.inc.php';
require_once inc_dataGrid;
require_once 'framework.class.php';

$indexID = !empty($_POST["indexID"]) ? $_POST["indexID"] : "";
$type = !empty($_POST["type"]) ? $_POST["type"] : "";
$IndicatorTypeID = !empty($_POST["IndicatorTypeID"]) ? $_POST["IndicatorTypeID"] : "";
$IndexObj = new FRW_indicator($indexID);
$IndicatorType = $IndexObj->indexType;

//................  GET ACCESS  .....................
if(isset($_POST["MenuID"]))
	$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
else
{
	$accessObj = new FRW_access();
	$accessObj->AddFlag = false;
	$accessObj->EditFlag = false;
	$accessObj->RemoveFlag = false;
}
//...................................................
?>

<script>

IndicatorInfo.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

    indexID : '<?= $indexID ?>',
    type : '<?= $type ?>',
    IndicatorTypeID : '<?= $IndicatorTypeID ?>',
    IndicatorType : '<?= $IndicatorType ?>',
	MenuID : "<?= $_POST["MenuID"] ?>",
	
	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function IndicatorInfo(){

	this.store = new Ext.data.Store({
		proxy : {
			type: 'jsonp',
			url: this.address_prefix + "framework.data.php?task=SelectAllIndicators&indexID=" + this.indexID,
			reader: {root: 'rows',totalProperty: 'totalCount'}
		},
		fields : ["indexID","indexType","indexTypeDesc","indexName","indexDesc","measurConcept","measurPeriod",
                  "measurTimePeriod","optDirection","param1","indexSubType"],
		
		listeners : {
			load : function(){
				me = IndicatorInfoObject;
				me.BuildForms();
				//..........................................................
				record = this.getAt(0);
				console.log(record);
				me.MeetingPanel.loadRecord(record);

				IndicatorInfoObject.mask.hide();
				//..........................................................
			}
			
		}
	});
    console.log(this.indexID);
	if(this.indexID > 0)
	{
	    console.log(this.indexID);
		this.mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال بارگذاری...'});
		this.mask.show();
		this.store.load();
	}
	else
		this.BuildForms();
}

IndicatorInfo.prototype.BuildForms = function(){

	/*StatusID = this.store.totalCount == undefined ? "-1" : this.store.getAt(0).data.StatusID;
	readOnly = StatusID == "<?= MTG_STATUSID_RAW ?>" || StatusID == "-1" ? false : true;*/
    readOnly = (this.type == "show") ? true : false;
	
	this.TabPanel = new Ext.TabPanel({
		renderTo : this.get("mainForm"),
		width : 800,
		autoHeight: true,
		plain:true,
		items :[{
            xtype : "combo",
            /*readOnly : readOnly,*/
            name : "indexType",
            store: new Ext.data.Store({
                proxy:{
                    type: 'jsonp',
                    url: this.address_prefix + 'framework.data.php?task=selectIndicatorTypes',
                    reader: {root: 'rows',totalProperty: 'totalCount'}
                },
                fields :  ['InfoID','InfoDesc','param1','param2','descInfo'],
                autoLoad : true
            }),
            fieldLabel : "گروه شاخص",
            queryMode : "local",
            displayField: 'param2',
            valueField : "InfoID",
            allowBlank : false,
            itemId : "indexGroup",
            listeners :{
                change : function(){
                    IndicatorInfoObject.TabPanel.down("[name=indexSubType]").setValue("");
                },
                select : function(record){
                    el = IndicatorInfoObject.TabPanel.down("[itemId=indexSubGroup]");
                    el.getStore().proxy.extraParams["groupID"] = this.getValue();
                    el.getStore().load();
                }
            }
        },{
            xtype : "combo",
            /*readOnly : readOnly,*/
            name : "indexSubType",
            store: new Ext.data.Store({
                proxy:{
                    type: 'jsonp',
                    url: this.address_prefix + 'framework.data.php?task=selectIndicatorTypes',
                    reader: {root: 'rows',totalProperty: 'totalCount'}
                },
                fields :  ['InfoID','InfoDesc','param1','param2','descInfo'],
                autoLoad : true
            }),
            fieldLabel : "زیرگروه شاخص",
            queryMode : "local",
            displayField: 'InfoDesc',
            valueField : "InfoID",
            allowBlank : false,
            itemId : "indexSubGroup",
        },{
					xtype : "textfield",
                    readOnly : readOnly,
					fieldLabel : "نام شاخص",
					name : "indexName",
					allowBlank : false
				},{
					xtype : "textarea",
                    readOnly : readOnly,
					name : "indexDesc",
					rows : 4,
					colspan : 2,
					width : 700,
					fieldLabel : "تعریف عملیاتی متغیرهای استفاده شده در فرمول"
				},{
                    xtype : "textfield",
                    fieldLabel : "مفهوم مورد اندازه گیری ",
                    name : "measurConcept",
                    readOnly : readOnly,
                    /*hidden : (this.MeetingType == '12' || this.MeetingTypeID =='12_') ? false : true,*/
                    width : 700,
                    colspan : 2,
                    allowBlank : true
                },{
                    xtype : "textfield",
                    fieldLabel : "دوره اندازه گیری ",
                    name : "measurPeriod",
                    readOnly : readOnly,
                    colspan : 2,
                    allowBlank : true
                },{
                    xtype : "textfield",
                    fieldLabel : "مقطع زمانی اندازه گیری ",
                    name : "measurTimePeriod",
                    width : 700,
                    readOnly : readOnly,
                    colspan : 2,
                    allowBlank : true
                },{
                    xtype : "textfield",
                    fieldLabel : "جهت مطلوب ",
                    name : "optDirection",
                    readOnly : readOnly,
                    colspan : 2,
                    allowBlank : true
                }],
				buttons : [{
					text : "ذخیره",
					hidden : readOnly,
					iconCls : "save",
					handler : function(){
						IndicatorInfoObject.SaveIndicatorInfo(false);
					}
				}]
			})
		}]
	});
}

IndicatorInfoObject = new IndicatorInfo();

IndicatorInfo.prototype.SaveIndicatorInfo = function(SendFile){

	mask = new Ext.LoadMask(this.TabPanel, {msg:'در حال ذخيره سازي...'});
	mask.show();  
	
	this.MeetingPanel.getForm().submit({
		clientValidation: true,
		url: this.address_prefix + 'framework.data.php?task=SaveIndicator' ,
		isUpload : true,
		method: "POST",
		params : {
			indexID : this.indexID
		},
		
		success : function(form,action){
		    console.log('Save is successfull');
			mask.hide();
			me = IndicatorInfoObject;
			result = action.result.data;
			me.indexID = result.indexID;
		},
		failure : function(form,action){
            console.log(action.result);
			mask.hide();
			Ext.MessageBox.alert("Error", action.result.data == "" ? "شماره شاخص وارد شده تکراری است" : action.result.data);
		}
	});
}

</script>
<div style="margin: 10px" id="mainForm"></div>
