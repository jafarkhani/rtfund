<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.07
//-----------------------------

require_once 'header.inc.php';
require_once inc_dataGrid;
/*var_dump($_POST);
echo '<br>';*/
/*if(!empty($_POST["ObjectID"])){echo 'valueID is not set';}*/
$ObjectType = $_POST["ObjectType"];
if(isset($_POST["ObjectID"]) && !empty($_POST["ObjectID"])) {
    /*echo 'okkkk';*/
    $ObjectID = $_POST["ObjectID"];
}else{
     /*echo 'nooooo';*/
     $ObjectID = 0;
}  

//---------------- RECOGNIZE ACCESS --------------------

$access = false;
switch($ObjectType)
{
	case "person" : 
		if($ObjectID == "" )
			$ObjectID = $_SESSION["USER"]["PersonID"];
		if($_SESSION["USER"]["PersonID"] == $ObjectID)
			$access = true;
		if($_SESSION["USER"]["IsStaff"] == "YES")
			$access = true;
		break;
	//......................................................
	case "loan":
		require_once '../../loan/request/request.class.php';
		$obj = new LON_requests($ObjectID);
		if($_SESSION["USER"]["IsCustomer"] == "YES" && in_array($obj->StatusID, array("40","60")) )
			$access = true;
		if($_SESSION["USER"]["IsStaff"] == "YES" /*&& $obj->StatusID == "50"*/)
			$access = true;
		break;
	//......................................................
	case "plan":
		require_once '../../plan/plan/plan.class.php';
		require_once '../../plan/PLNconfig.inc.php';
		$obj = new PLN_plans($ObjectID);
		if($_SESSION["USER"]["IsCustomer"] == "YES" && $_SESSION["USER"]["PersonID"] == $obj->PersonID 
				&& in_array($obj->StepID, array(STEPID_RAW,STEPID_RETURN_TO_CUSTOMER)) )
			$access = true;
		if($_SESSION["USER"]["IsExpert"] == "YES")
			$access = true;
		if($_SESSION["USER"]["IsStaff"] == "YES")
			$access = true;
		break;
	//......................................................
	case "contract":
		require_once '../../contract/contract/contract.class.php';
		$obj = new CNT_contracts($ObjectID);
		if($_SESSION["USER"]["IsStaff"] == "YES")
			$access = true;
		break;
	//......................................................
	case "warrenty":
		require_once '../../loan/warrenty/request.class.php';
		$obj = new WAR_requests($ObjectID);
		if($_SESSION["USER"]["IsStaff"] == "YES")
			$access = true;
		if($_SESSION["USER"]["IsCustomer"] == "YES" && $_SESSION["USER"]["PersonID"] == $obj->PersonID 
				&& in_array($obj->StatusID, array(STEPID_RAW,STEPID_RETURN_TO_CUSTOMER)) )
			$access = true;
		break;
	//......................................................
	case "accdoc":
		require_once '../../accounting/docs/doc.class.php';
		$obj = new ACC_docs($ObjectID);
		if($obj->StatusID == ACC_STEPID_RAW)
			$access = true;
		break;
	//......................................................
	case "traffic":
		require_once '../../attendance/traffic/traffic.class.php';
		$obj = new ATN_requests($ObjectID);
		if($obj->ReqStatus == ATN_STEPID_RAW)
			$access = true;
		break;
	//......................................................
	case "BeneficiaryDocs":
	case "orgdoc":
	case "package":
    case "writs": // new added for ahkam in modiriyat personel
    case "LegalActions": // new added for eghdamate hoghoghi
	case "asset":
    case "extInteractions":
    case "IssuanceForm":
    case "PersonBasicDoc":        //new added for basic doc in portal
    case "ReqProfessionalDoc":    //new added for professional doc in my request in portal

		$access = true;
		break;
	//......................................................
	case "form":
		require_once '../workflow/form.class.php';
		require_once '../workflow/wfm.class.php';
		$obj = new WFM_requests($ObjectID);
		$arr = WFM_FlowRows::GetFlowInfo($obj->_FlowID, $obj->RequestID);
		
		if($_SESSION["USER"]["IsCustomer"] == "YES" && 
				$_SESSION["USER"]["PersonID"] == $obj->PersonID 
				&& (!$arr["IsStarted"] || $arr["SendEnable"]) )
			$access = true;
		if($_SESSION["USER"]["IsStaff"] == "YES" && session::IsFramework())
			$access = true;
		break;
	
	//......................................................
	case "meeting":
		require_once '../../meeting/meeting.class.php';
		$obj = new MTG_meetings($ObjectID);
		$access = $obj->StatusID == MTG_STATUSID_RAW ? true : false;
		$access = true;
		break;
	case "meetingagenda":
		require_once '../../meeting/meeting.class.php';
		$aobj = new MTG_agendas($ObjectID);
		$obj = new MTG_meetings($aobj->MeetingID);
		$access = $aobj->MeetingID == "" || $obj->StatusID == MTG_STATUSID_RAW ? true : false;
		break;
	case "meetingrecord":
		require_once '../../meeting/meeting.class.php';
		$robj = new MTG_MeetingRecords($ObjectID);
		$obj = new MTG_meetings($robj->MeetingID);
		$access = $obj->StatusID == MTG_STATUSID_RAW ? true : false;
		break;
		
	case "ReqCheckList":
	    if(isset($ObjectID) && !empty($ObjectID)){$access = true;}
	    break;	
		
}
//------------------------------------------------------

if ($ObjectType == 'extInteractions' ){
    $dg = new sadaf_datagrid("dg", $js_prefix_address . "dms.data.php?" .
        "task=SelectAl&ObjectType=" . $ObjectType . "&ObjectID=" . $ObjectID, "grid_div");
}elseif ($ObjectType == 'ReqCheckList'){
    $dg = new sadaf_datagrid("dg", $js_prefix_address . "dms.data.php?" .
        "task=SelectAlll&ObjectType=" . $ObjectType . "&ObjectID=" . $ObjectID, "grid_div");
}
else{
    $dg = new sadaf_datagrid("dg", $js_prefix_address . "dms.data.php?" .
        "task=SelectAll&ObjectType=" . $ObjectType . "&ObjectID=" . $ObjectID, "grid_div");
}

//------------------------------------------------------

/*if ($ObjectType == 'ReqCheckList'){
    $dg = new sadaf_datagrid("dg", $js_prefix_address . "dms.data.php?" .
        "task=SelectAl&ObjectType=" . $ObjectType . "&ObjectID=" . $ObjectID, "grid_div");
}else{
    $dg = new sadaf_datagrid("dg", $js_prefix_address . "dms.data.php?" .
        "task=SelectAll&ObjectType=" . $ObjectType . "&ObjectID=" . $ObjectID, "grid_div");
}*/

/*$dg = new sadaf_datagrid("dg", $js_prefix_address . "dms.data.php?" .
		"task=SelectAll&ObjectType=" . $ObjectType . "&ObjectID=" . $ObjectID, "grid_div");*/

$dg->addColumn("", "DocumentID", "", true);
$dg->addColumn("", "ObjectType", "", true);
$dg->addColumn("", "ObjectID", "", true);
$dg->addColumn("", "IsConfirm", "", true);
$dg->addColumn("", "RegPersonID", "", true);
$dg->addColumn("", "param1", "", true);
$dg->addColumn("", "DocType", "", true);
$dg->addColumn("", "DocMode", "", true);
$dg->addColumn("", "place", "", true);
$dg->addColumn("", "ObjectDesc", "", true);
$dg->addColumn("", "DocTypeGroup", "", true);
$dg->addColumn("", "DocTypeGroupDesc", "", true);
$dg->addColumn("", "param1", "", true);
$dg->addColumn("", "param2", "", true);
$dg->addColumn("", "param3", "", true);

$col = $dg->addColumn("مدرک", "DocTypeDesc", "");
$col->width = 80;

$col = $dg->addColumn("اطلاعات مدرک", "paramValues", "");
$col->renderer = "ManageDocument.ParamValueRender";
$col->width = 130;

$col = $dg->addColumn("عنوان مدرک ارسالی", "DocDesc", "");

$col = $dg->addColumn("ثبت کننده", "regfullname", "");
$col->width = 100;
$col = $dg->addColumn("تاریخ ثبت", "RegDate", GridColumn::ColumnType_date);
$col->width = 80;

if($ObjectType == "package")
{
	$col = $dg->addColumn("نسخه", "DocMode", "");
	$col->renderer = "function(v,p,r){return v == 'ELEC' ? 'الکترونیک' : 'کاغذی';}";
	$col->width = 60;
	
	$col = $dg->addColumn("محل نگهداری", "PlaceDesc", "");
	$col->width = 100;
	
	$col = $dg->addColumn("آیتم وابسته", "ObjectDesc", "");
	$col->renderer = "function(v,p,r){return ManageDocument.ObjectRender(v,p,r)}";
	$col->width = 90;
}

$col = $dg->addColumn("فایل", "HaveFile", "");
$col->renderer = "function(v,p,r){return ManageDocument.FileRender(v,p,r)}";
$col->align = "center";
$col->width = 30;

$col = $dg->addColumn("توضیحات کارشناس", "RejectDesc", "");
//$col->renderer = "function(v,p,r){return ManageDocument.commentRender(v,p,r)}";
$col->align = "center";
$col->width = 120;

if($access)
{
	$col = $dg->addColumn("عملیات", "", "");
	$col->renderer = "function(v,p,r){return ManageDocument.OperationRender(v,p,r)}";
	$col->width = 60;

	$dg->addButton("", "اضافه مدرک", "add", "function(){ManageDocumentObject.AddDocument();}");
	
	$dg->enableRowEdit = true;
	$dg->rowEditOkHandler = "function(){return ManageDocumentObject.SaveDocument();}";
}

if(session::IsFramework() && $access && $ObjectType != "IssuanceForm" )
{
	$col = $dg->addColumn("تایید/رد", "", "");
	$col->renderer = "function(v,p,r){return ManageDocument.ConfirmRender(v,p,r)}";
	$col->width = 60;
	
	$dg->addButton("", "برگشت از تایید", "return", "function(){ManageDocumentObject.UnConfirm();}");
}

$dg->EnableGrouping = true;
$dg->DefaultGroupField = "DocTypeGroupDesc";

if($ObjectType == "orgdoc")
	$dg->DefaultGroupField = "DocTypeDesc";

$dg->emptyTextOfHiddenColumns = true;

if($ObjectType == "package" || $ObjectType == "orgdoc")
{
	$dg->height = 400;
}
else
{
	$dg->height = 330;
	//$dg->width = 690;
}
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "DocTypeDesc";
$dg->autoExpandColumn = "DocDesc";
$grid = $dg->makeGrid_returnObjects();

?>
<script>
	
ManageDocument.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	access : <?= $access ? "true" : "false" ?>,
	
	ObjectType : "<?= $ObjectType ?>",
	ObjectID : "<?= $ObjectID ?>",
	
	pageIndex : 1,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function ManageDocument(){
	
	this.ParamsStore = new Ext.data.Store({
		fields:["DocType","ParamID","ParamDesc","ParamType", "KeyTitle"],
		proxy: {
			type: 'jsonp',
			url: this.address_prefix + 'dms.data.php?task=selectAllParams',
			reader: {root: 'rows',totalProperty: 'totalCount'}
		},
		autoLoad : true
	});
	
	this.paramValuesStore = new Ext.data.Store({
		fields:["ParamID","ParamValue"],
		proxy: {
			type: 'jsonp',
			url: this.address_prefix + 'dms.data.php?task=selectParamValues',
			reader: {root: 'rows',totalProperty: 'totalCount'}
		}
	});
	
	this.formPanel = new Ext.form.Panel({
		renderTo: this.get("MainForm"),      
		width : 650,
		style : "margin-top:10px",
		bodyPadding: ' 10 10 12 10',
		frame: true,
		layout :{
			type : "table",
			columns :2
		},
		title: 'اطلاعات مدرک',
		hidden : true,
		items : [{
			xtype : "combo",
			fieldLabel : "گروه مدرک",
			allowBlank : false,
			colspan : 2,
			width : 500,
			name : "DocTypeGroup",
			itemId : "DocTypeGroupCombo",
			store: new Ext.data.Store({
				fields:["InfoID","InfoDesc"],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + 'dms.data.php?task=selectDocTypeGroups&ObjectType=' + this.ObjectType,
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				autoLoad : true
			}),
			typeAhead: false,
			queryMode : "local",
			valueField : "InfoID",
			displayField : "InfoDesc",
			listeners : {
				change : function(combo,records){
					me = ManageDocumentObject;
					me.formPanel.getComponent("DocTypeCombo").setValue();
					me.formPanel.getComponent("DocTypeCombo").getStore().proxy.extraParams["GroupID"] = 
						this.getValue();
					me.formPanel.getComponent("DocTypeCombo").getStore().load();
				}
			}
		},{
			xtype : "combo",
			fieldLabel : "مدرک",
			colspan : 2,
			width : 500,
			allowBlank : false,
			itemId : "DocTypeCombo",
			name : "DocType",
			store: new Ext.data.Store({
				fields:["InfoID","InfoDesc"],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + 'dms.data.php?task=selectDocTypes',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				listeners : {
					beforeload : function(store){
						if(!store.proxy.extraParams.GroupID)
						{
							me = ManageDocumentObject;							
							group = me.formPanel.getComponent("DocTypeGroupCombo").getValue();
							if(group == "")
								return false;
							this.proxy.extraParams["GroupID"] = group;
						}
					}				
				}
			}),
			listeners : {
				change : function(combo,value){
					//-------------- make params -----------------	
					DocType = value;

					me = ManageDocumentObject;
					ParamsFS = me.formPanel.getComponent("ParamsFS");
					ParamsFS.removeAll();
					
					me.ParamsStore.each(function(record){
						if(DocType == record.data.DocType)
						{
							if(record.data.KeyTitle == "LetterNo")
							{
								ParamsFS.add({
									xtype : "combo",
									colspan : 2,
									width : 500,
									store : new Ext.data.SimpleStore({
										proxy: {
											type: 'jsonp',
											url: me.address_prefix + 'dms.data.php?' +
												"task=SearchLetters",
											reader: {root: 'rows',totalProperty: 'totalCount'}
										},
										fields : ['LetterID','LetterTitle',
											{name : "fullDesc",	convert : function(value,record){
													return "[" + record.data.LetterID + "] " + record.data.LetterTitle
											} }
]
									}),
									displayField : "fullDesc",
									valueField : "LetterID",
									name : "Param" + record.data.ParamID,
									fieldLabel : record.data.ParamDesc									
								});
							}
							else if(record.data.KeyTitle == "DocumentID")
							{
								ParamsFS.add({
									xtype : "combo",
									colspan : 2,
									width : 500,
									store : new Ext.data.SimpleStore({
										proxy: {
											type: 'jsonp',
											url: me.address_prefix + 'dms.data.php?' +
												"task=SearchInternalDocuments",
											reader: {root: 'rows',totalProperty: 'totalCount'}
										},
										fields : ['DocumentID','DocDesc','groupDesc']
									}),
									tpl: new Ext.XTemplate(
										'<table class=gridCombo cellspacing="0" >',
											'<tr>',
												'<th>گروه</th>',
												'<th>عنوان مدرک</th>',
											'</tr>',
										'<tpl for=".">',
											'<tr class="x-boundlist-item">',
												'<td>{groupDesc}</td>',
												'<td>{DocDesc}</td>',
											'</tr>',
										'</tpl>',
										'</table>'),
									displayField : "DocDesc",
									valueField : "DocumentID",
									name : "Param" + record.data.ParamID,
									fieldLabel : record.data.ParamDesc,
									listeners : {
										select : function(el,records){
											this.up('form').down("[name=DocDesc]").setValue(records[0].data.DocDesc);
										}
									}
								});
							}
							else if(record.data.KeyTitle == "DocumentID")
							{
								ParamsFS.add({
									xtype : "combo",
									colspan : 2,
									width : 500,
									store : new Ext.data.SimpleStore({
										proxy: {
											type: 'jsonp',
											url: me.address_prefix + 'dms.data.php?' +
												"task=SearchLetters",
											reader: {root: 'rows',totalProperty: 'totalCount'}
										},
										fields : ['LetterID','LetterTitle',
											{name : "fullDesc",	convert : function(value,record){
													return "[" + record.data.LetterID + "] " + record.data.LetterTitle
											} }
]
									}),
									displayField : "fullDesc",
									valueField : "LetterID",
									name : "Param" + record.data.ParamID,
									fieldLabel : record.data.ParamDesc
									
								});
							}								
							else
								ParamsFS.add({
									xtype : record.data.ParamType,
									name : "Param" + record.data.ParamID,
									fieldLabel : record.data.ParamDesc,
									hideTrigger : (record.data.ParamType == "numberfield" || 
										record.data.ParamType == "currencyfield" ? true : false)
								});
						}
					});
					
					if(ParamsFS.items.length > 0)
						ParamsFS.setTitle("اطلاعات " + combo.getRawValue());
					
					//------------- fill params -------------------
					DocumentID = me.formPanel.down("[name=DocumentID]").getValue();
					if(DocumentID > 0)
					{
						me.paramValuesStore.load({
							params : {
								DocumentID : DocumentID
							},
							callback : function(){
								store = ManageDocumentObject.paramValuesStore;
								store.each(function(record){
									me.formPanel.down("[name=Param" + 
										record.data.ParamID + "]").setValue(record.data.ParamValue);
								});
							}
						});
					}					
				}
			},
			typeAhead: false,
			valueField : "InfoID",
			displayField : "InfoDesc"
		},{
			xtype : "textfield",
			width : 564,
			fieldLabel : "شرح مدرک",
			colspan : 2,
			name : "DocDesc"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				data : [
					['ELEC' , "الکترونیکی" ],
					['PAPER' , "کاغذی" ]
				],
				fields : ['id','value']
			}),
			displayField : "value",
			fieldLabel : "وضعیت مدرک",
			valueField : "id",
			name : "DocMode",
			hidden : <?= session::IsFramework() ? "false" : "true" ?>,
			value : "ELEC",
			listeners : {
				select : function(combo,records){
					if(this.getValue() == "PAPER")
						this.up("form").down("[name=place]").enable();
					else
						this.up("form").down("[name=place]").disable();
				}
			}
		},{
			xtype : "combo",
			name : "place",
			hidden : <?= session::IsFramework() ? "false" : "true" ?>,
			disabled : true,
			fieldLabel : "محل نگهداری",
			store: new Ext.data.Store({
				fields:["InfoID","InfoDesc"],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + 'dms.data.php?task=selectPlaces',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				autoLoad : true
			}),
			queryMode : "local",
			displayField : "InfoDesc",
			valueField : "InfoID"
		},{
			xtype : "fieldset",
			colspan : 2,
			title : "اطلاعات مدرک",
			itemId : "ParamsFS",
			layout : "column",
			columns : 2
		},{
			xtype : "fieldset",
			title : "فایل های مدرک",
			layout : "column",
			columns : 2,
			colspan : 2,
			items:[{
				xtype : "displayfield",
				hideTrigger : true,
				labelWidth : 50,
				width : 80,
				fieldCls : "blueText",
				value : "صفحه [ 1 ]"
			},{
				xtype : "filefield",
				width : 450,
				fieldLabel : "فایل مدرک",
				name : "FileType_1"
			},{
				xtype : "button",
				text : "اضافه صفحه",
				colspan : 2,
				iconCls : "add",
				handler : function(){
					me = ManageDocumentObject;
					me.pageIndex++;
					fs = this.up("fieldset");
					fs.insert(fs.items.length-1, [{
						xtype : "displayfield",
						hideTrigger : true,
						labelWidth : 50,
						width : 80,
						fieldCls : "blueText",
						value : "صفحه [ " + me.pageIndex + " ]"
					},{
						xtype : "filefield",
						width : 450,
						fieldLabel : "فایل مدرک",
						name : "FileType_" + me.pageIndex
					}]);
					
				}
			}]
		},{
			xtype : "hidden",
			name : "DocumentID"
		}],
		buttons : [{
			text : "ذخیره",
			iconCls : "save",
			handler : function(){ ManageDocumentObject.SaveDocument(); }
		},{
			text : "انصراف",
			iconCls : "undo",
			handler : function(){
				ManageDocumentObject.formPanel.hide();
				ManageDocumentObject.grid.show();
			}
		}]
	});

	this.grid = <?= $grid ?>;
	this.grid.addDocked({
		xtype: 'toolbar',
		dock: 'bottom',
		items: [{ 
			xtype: 'container', 
			width : 680,
			html : "ردیف های سبز رنگ ردیف های تایید شده و برابر اصل شده توسط صندوق بوده و قابل تغییر نمی باشند"+
				"<br>ردیف های قرمز ردیف های رد شده توسط صندوق می باشند"
		}
    ]});
	this.grid.getView().getRowClass = function(record, index)
	{
		if(record.data.IsConfirm == "YES")
			return "greenRow";
		if(record.data.IsConfirm == "NO")
			return "pinkRow";
		return "";
	}
	if(this.grid.plugins[0])
		this.grid.plugins[0].on("beforeedit", function(editor,e){
			if(e.record.data.IsConfirm == "YES")
				return false;
			if(e.record.data.DocumentID > 0 &&
				e.record.data.RegPersonID != "<?= $_SESSION["USER"]["PersonID"] ?>")
				return false;	
				
			return true;
		});
		
	this.grid.render(this.get("div_grid"));
}

ManageDocument.FileRender = function(v,p,r){
	
	if(v == "false")
		return "";
	return "<div align='center' title='مشاهده فایل' class='attach' "+
		"onclick='ManageDocument.ShowFile(" + r.data.DocumentID + "," + r.data.ObjectID + ");' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16;float:right'></div>";
}

ManageDocument.OpenLetter = function(LetterID){
	
	framework.OpenPage('/office/letter/LetterInfo.php','مشخصات نامه',
		{LetterID : LetterID});
}

ManageDocument.ParamValueRender = function(v,p,r){
	
	if(r.data.DocType == "<?= DMS_DOCTYPE_LETTER ?>")
		return "<a href=javascript:void() onclick=ManageDocument.OpenLetter("+v+");>شماره نامه : "+v+"</a>";
		
	if(r.data.DocType == "<?= DMS_DOCTYPE_Documents ?>")
		return "<a href=/office/dms/ShowFile.php?DocumentID="+v+"&ObjectID=0 target=_blank>"+v+"</a>";
	return v;
	
}

ManageDocument.ShowFile = function(DocumentID, ObjectID){
	
	window.open("/office/dms/ShowFile.php?DocumentID=" + DocumentID + "&ObjectID=" + ObjectID);
}

ManageDocument.OperationRender = function(v,p,r){
	
	if(r.data.IsConfirm == "YES")
		return "";

	if(ManageDocumentObject.access)
		return "<div align='center' title='ویرایش' class='edit' "+
			"onclick='ManageDocumentObject.EditDocument();' " +
			"style='background-repeat:no-repeat;background-position:center;" +
			"cursor:pointer;width:20px;height:16;float:right'></div>" +

		"<div align='center' title='حذف' class='remove' "+
			"onclick='ManageDocumentObject.DeleteDocument();' " +
			"style='background-repeat:no-repeat;background-position:center;" +
			"cursor:pointer;width:20px;height:16;float:right'></div>" ;		
}

ManageDocument.commentRender = function(v,p,r){
		
	if(v == "" || v == null)
		return "";
	return "<div align='center' title='توضیحات کارشناس' class='comment' " +
		" onclick='ManageDocumentObject.ShowComment()' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16;float:right'></div>";
}

ManageDocument.prototype.ShowComment = function(){
	
	if(!this.commentWin)
	{
		this.commentWin = new Ext.window.Window({
			width : 400,
			height : 200,
			bodyStyle : "background-color:white;padding:10px",
			html : "",
			closeAction : "hide",
			buttons : [{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		
		Ext.getCmp(this.TabID).add(this.commentWin);
	}
	
	var record = this.grid.getSelectionModel().getLastSelected();
	this.commentWin.update(record.data.RejectDesc);
	this.commentWin.show();
	this.commentWin.center();
}

ManageDocument.prototype.AddDocument = function(){
	
	this.formPanel.show();
	this.formPanel.getForm().reset();
	this.formPanel.getComponent("ParamsFS").removeAll();
	this.grid.hide();
}

ManageDocument.prototype.EditDocument = function(){
	
	this.formPanel.show();
	this.grid.hide();
	this.formPanel.getComponent("ParamsFS").removeAll();
	
	var record = this.grid.getSelectionModel().getLastSelected();
	this.formPanel.getForm().reset();
	this.formPanel.down("[name=DocumentID]").setValue(record.data.DocumentID);
	this.formPanel.loadRecord(record);	
	
	this.formPanel.getComponent("DocTypeCombo").getStore().proxy.extraParams["GroupID"] = record.data.DocTypeGroup;
	this.formPanel.getComponent("DocTypeCombo").getStore().load({
		callback : function(){ ManageDocumentObject.formPanel.getComponent("DocTypeCombo").setValue(record.data.DocType) }
	});
	
	if(record.data.DocMode == "PAPER")
		this.formPanel.down("[name=place]").enable();
	else
		this.formPanel.down("[name=place]").disable();
}

ManageDocument.prototype.SaveDocument = function(){
	
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID),{msg:'در حال ذخیره سازی ...'});
	mask.show();

	this.formPanel.getForm().submit({
		clientValidation: true,
		url: this.address_prefix +'dms.data.php',
		method: "POST",
		isUpload : true,
		params: {
			task: "SaveDocument",
			ObjectID : this.ObjectID,
			ObjectType : this.ObjectType,
		},
		success: function(form,action){
			mask.hide();

			if(action.result.success)
			{   
				ManageDocumentObject.grid.getStore().load();
				ManageDocumentObject.formPanel.hide();
				ManageDocumentObject.grid.show();
			}
			else
			{
				if(action.result.data == "")
					alert("خطا در اجرای عملیات");
				else
					alert(action.result.data);
			}
		},
		failure: function(form,action){
			mask.hide();
			Ext.MessageBox.alert("Error",action.result.data);			
		}
	});
}

ManageDocument.prototype.DeleteDocument = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = ManageDocumentObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'dms.data.php',
			params:{
				task: "DeleteDocument",
				DocumentID : record.data.DocumentID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				ManageDocumentObject.grid.getStore().load();
			},
			failure: function(){}
		});
	});
}

ManageDocument.ObjectRender = function(v,p,r){
	
	if(r.data.param1 == "0" || r.data.param1 == "")
		return v;
	return "<a href=javascript:void(0) onclick=ManageDocumentObject.ObjectInfo("+
			r.data.DocumentID+")" + " >" + v + "[" + r.data.ObjectID + "]" + "</a>";		
}

ManageDocument.prototype.ObjectInfo = function(DocumentID){
	
	var index = this.grid.getStore().find("DocumentID", DocumentID);
	var record = this.grid.getStore().getAt(index);
	
	//e.stopImmediatePropagation();
	
	if(record.data.param3 == "1")
		window.open(record.data.param1 + "?" + record.data.param2 + "=" + record.data.ObjectID);
	else
	{
		eval("param={" + record.data.param2 + ": " + record.data.ObjectID + "}");
		framework.OpenPage(record.data.param1, "اطلاعات " + record.data.ObjectDesc, param);
	}
}

<?if(session::IsFramework()){?>

ManageDocument.ConfirmRender = function(v,p,r){
	
	if(r.data.IsConfirm == "YES")
		return "";
	
	st = "<div align='center' title='تایید' class='tick' "+
		"onclick='ManageDocumentObject.beforeConfirmDocument(\"YES\");' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:24px;height:16;float:right'></div>";
	if(r.data.IsConfirm == "NOTSET")
		st += "<div align='center' title='رد' class='cross' "+
		"onclick='ManageDocumentObject.beforeConfirmDocument(\"NO\");' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:18px;height:16;float:right'></div>";
	
	return st;
}

ManageDocument.prototype.beforeConfirmDocument = function(mode){
	if(mode == "YES")
	{
		Ext.MessageBox.confirm("","فایل با اصل مدرک مطابق می باشد؟", function(btn){
			if(btn == "no")
				return;
			
			ManageDocumentObject.ConfirmDocument("YES");
		});
		return;
	}
	if(!this.confirmWin)
	{
		this.confirmWin = new Ext.window.Window({
			width : 412,
			height : 198,
			modal : true,
			title : "دلیل رد مدرک برای مشتری",
			bodyStyle : "background-color:white",
			items : [{
				xtype : "textarea",
				width : 400,
				rows : 8,
				name : "RejectDesc"
			}],
			closeAction : "hide",
			buttons : [{
				text : "رد مدرک",
				iconCls : "cross",
				handler : function(){ManageDocumentObject.ConfirmDocument('NO');}
			},{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		
		Ext.getCmp(this.TabID).add(this.confirmWin);
	}
	this.confirmWin.show();
	this.confirmWin.center();
}

ManageDocument.prototype.ConfirmDocument = function(mode){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	mask = new Ext.LoadMask(this.grid,{msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'dms.data.php',
		method: "POST",
		params: {
			task: "ConfirmDocument",
			DocumentID : record.data.DocumentID,
			mode : mode,
			RejectDesc : mode == "NO" ? this.confirmWin.down("[name=RejectDesc]").getValue() : ""
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				ManageDocumentObject.grid.getStore().load();
				if(ManageDocumentObject.confirmWin)
					ManageDocumentObject.confirmWin.hide();
			}
			else
			{
				if(st.data == "")
					alert("خطا در اجرای عملیات");
				else
					alert(st.data);
			}
		},
		failure: function(){}
	});
}

ManageDocument.prototype.UnConfirm = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	if(!record)
		return;
	mask = new Ext.LoadMask(this.grid,{msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'dms.data.php',
		method: "POST",
		params: {
			task: "UnConfirmDocument",
			DocumentID : record.data.DocumentID
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
				ManageDocumentObject.grid.getStore().load();
			else
			{
				if(st.data == "")
					alert("خطا در اجرای عملیات");
				else
					alert(st.data);
			}
		},
		failure: function(){}
	});
}

<?}?>
ManageDocumentObject = new ManageDocument();


</script>
<center>
	<div id="MainForm"></div>
</center>
<div>
	<div style="width:100%" id="div_grid"></div>
</div>
