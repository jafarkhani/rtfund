<script>
    //-----------------------------
    //	Programmer	: SH.Jafarkhani
    //	Date		: 1394.11
    //-----------------------------

    PlanInfo.prototype = {
    TabID : '<?= $_REQUEST["ExtTabID"]?>',
    address_prefix : "<?= $js_prefix_address?>",
    MenuID : "<?= $_POST["MenuID"] ?>",

    FormType : <?= $PlanObj->FormType ?>,
    PlanID : <?= $PlanID ?>,
    PlanRecord : null,
    User : '<?= $User ?>',
    portal : <?= session::IsPortal() ? "true" : "false" ?>,
    readOnly : <?= $readOnly ? "true" : "false" ?>,

    AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
    EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
    RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

    GroupForms : {},
    Scopes : <?= common_component::PHPArray_to_JSArray($scopes, "InfoDesac", "InfoID") ?>,

    get : function(elementID){
    return findChild(this.TabID, elementID);
}
};

    function PlanInfo(){

    this.PlanRecord = {
        PlanID : <?= $PlanID ?>,
        StepID : <?= $PlanObj->StepID ?>,
        StepIDName : '<?php
            if ($PlanObj->StepID==100){
                echo 'ارسال درخواست';
            }elseif ($PlanObj->StepID==101){
                echo ' ایجاد درخواست';
            }elseif ($PlanObj->StepID==102){
                echo 'درخواست مدارک طرح از مشتری';
            }elseif ($PlanObj->StepID==103){
                echo 'تکمیل مدارک توسط مشتری';
            }elseif ($PlanObj->StepID==104){
                echo 'راکد';
            }elseif ($PlanObj->StepID==105){
                echo 'تدوین گزارش ارزیابی';
            }elseif ($PlanObj->StepID==106){
                echo 'ارجاع به مدیرعامل';
            }elseif ($PlanObj->StepID==107){
                echo 'اخذ مصوبه';
            }elseif ($PlanObj->StepID==108){
                echo 'ارجاع به پذیرش جهت تکمیل پرونده';
            }else{
                echo 'نامشخص';
            }
            ?>',


        FacilityAmount : '<?= $PlanObj->FacilityAmount ?>',
        evaluationType : '<?= $PlanObj->evaluationType ?>',
        CustomerPersonID : '<?= $PlanObj->PersonID ?>',
        evaluationAskerID : '<?= $PlanObj->evaluationAskerID ?>',
        PlanDesc : '<?= $PlanObj->PlanDesc ?>',
        LoanID : '<?= $PlanObj->LoanID ?>',
        SupportPersonID : '<?= $PlanObj->SupportPersonID ?>',
        ExpertStatus : '<?= $ExpertStatusDesc ?>'
    };
    //--------------------------------------------------------------------------
    if(!this.portal)
{
    this.planFS = new Ext.form.FieldSet({
    title : "اطلاعات طرح",
    width : 758,
    layout : {
    type : "table",
    columns : 2
},
    renderTo : this.get("div_plan"),
    items : [{
    xtype : "textfield",
    fieldLabel : "وضعیت",
    name : "StepPID",
    width : 400,
    readOnly: true,
    value : this.PlanRecord.StepIDName
},{
    xtype : "combo",
    store : new Ext.data.SimpleStore({
    proxy: {
    type: 'jsonp',

    url: this.address_prefix + '../../framework/person/persons.data.php?task=SearchPersons&UserTypes=IsCustomer',
    reader: {root: 'rows',totalProperty: 'totalCount'}
},
    fields : ['PersonID','name'],
    autoLoad : true
}),
    fieldLabel : "مشتری",
    displayField : "name",
    /*queryMode : 'local',*/
    pageSize : 20,
    width : 300,
    allowBlank: false,
    valueField : "PersonID",
    name : "CustomerPersonID",
    value : this.PlanRecord.CustomerPersonID
},{
    xtype : "textfield",
    fieldLabel : "عنوان طرح",
    name : "PlanDesc",
    width : 300,
    value : this.PlanRecord.PlanDesc
},{
    xtype : "currencyfield",
    allowBlank: false,
    fieldLabel : "مبلغ تسهیلات درخواستی",
    name : "FacilityAmount",
    width : 300,
    hideTrigger: true,
    value : this.PlanRecord.FacilityAmount
},{
    xtype : "combo",
    store : new Ext.data.SimpleStore({
    proxy: {
    type: 'jsonp',
    url: this.address_prefix +'../../framework/person/persons.data.php?task=selectPersonInfoTypes&TypeID=97',
    reader: {root: 'rows',totalProperty: 'totalCount'}
},
    fields : ['TypeID','InfoID','InfoDesc'],
    autoLoad : true
}),
    width : 300,
    fieldLabel : "نوع ارزیابی",
    queryMode : 'local',
    displayField : "InfoDesc",
    valueField : "InfoID",
    allowBlank: false,
    /*hidden : true,*/
    name : "evaluationType",
    value : this.PlanRecord.evaluationType
},{
    xtype : "combo",
    store : new Ext.data.SimpleStore({
    proxy: {
    type: 'jsonp',
    url: this.address_prefix + '../../framework/person/persons.data.php?' +
    "task=selectPersons&UserTypes=IsAgent,IsSupporter&EmptyRow=true",
    reader: {root: 'rows',totalProperty: 'totalCount'}
},
    fields : ['PersonID','fullname'],
    autoLoad : true
}),
    fieldLabel : "متقاضی ارزیابی",
    displayField : "fullname",
    queryMode : 'local',
    /*pageSize : 20,*/
    width : 300,
    allowBlank: false,
    /*hidden : true,*/
    valueField : "PersonID",
    name : "evaluationAskerID",
    value : this.PlanRecord.evaluationAskerID
}/*,{
				xtype : "combo",
				store : new Ext.data.SimpleStore({
					proxy: {
						type: 'jsonp',
						url: this.address_prefix + '../../loan/loan/loan.data.php?task=GetAllLoans',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					},
					fields : ['LoanID','LoanDesc'],
					autoLoad : true
				}),
				fieldLabel : "وام درخواستی",
				queryMode : 'local',
				displayField : "LoanDesc",
				valueField : "LoanID",
				name : "LoanID",
				value : this.PlanRecord.LoanID
			},{
				xtype : "combo",
				store : new Ext.data.SimpleStore({
					proxy: {
						type: 'jsonp',
						url: '/framework/person/persons.data.php?task=selectPersons&UserTypes=IsAgent,IsSupporter',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					},
					fields : ['PersonID','fullname'],
					autoLoad : true
				}),
				fieldLabel : "حامی طرح",
				displayField : "fullname",
				valueField : "PersonID",
				name : "SupportPersonID",
				value : this.PlanRecord.SupportPersonID,
				width : 400
			}*/,{
    xtype : "button",
    text : "ذخیره",
    disabled : this.AddAccess ? false : true,
    itemId : "btn_save",
    style : "float:left",
    width : 80,
    iconCls : "save",
    handler : function(){ PlanInfoObject.SavePlan(); }
},{
    xtype : "hidden",
    name : "PlanID",
    value : this.PlanID
}]
});

    if(this.readOnly || this.User != "Admin")
{
    var R1 = this.planFS.down("[name=SupportPersonID]").getStore();
    var t = setInterval(function(){
    if(!R1 || !R1.isLoading())
{
    clearInterval(t);
    PlanInfoObject.planFS.getEl().readonly();
    PlanInfoObject.planFS.down("[itemId=btn_save]").disable();
}
}, 1000);
}
}

    this.TabPanel = new Ext.TabPanel();
    for(i=0; i < this.Scopes.length; i++)
{
    this.TabPanel.add({
    title : "حوزه " + this.Scopes[i].InfoDesc,
    items : new Ext.tree.Panel({
    store: new Ext.data.TreeStore({
    proxy: {
    type: 'ajax',
    url: this.address_prefix + 'plan.data.php?task=selectGroups&FormType=' + this.FormType + '&PlanID=' + this.PlanID +
    "&ScopeID=" + this.Scopes[i].id
}
}),
    root: {id: 'src'},
    rootVisible: false,
    autoScroll : true,
    width : 758,
    height : 120,
    listeners : {
    itemclick : function(v,record){
    if(!record.data.leaf) return;
    PlanInfoObject.LoadElements(record);
},
    itemcontextmenu : function(view, record, item, index, e){
    PlanInfoObject.ShowMenu(view, record, item, index, e);
}
}
})
});
}
    this.TabPanel.setActiveTab(0);

    this.itemsPanel = new Ext.panel.Panel({
    bodyStyle : 'padding:4px;',
    height : this.portal ? 355 : 470,
    width: 758,
    autoScroll : true
});

    if(this.portal)
    buttons = {
    text : "مدارک",
    iconCls: 'attach',
    menu: {
    itemId : "Operation",
    xtype: 'menu',
    plain: true,
    showSeparator : true,
    items : [{
    text : 'مدارک درخواست دهنده',
    iconCls : "attach",
    handler : function(){ PlanInfoObject.PlanDocuments('person'); }
},{
    text : 'مدارک ضمیمه طرح',
    iconCls : "attach",
    itemId : "cmp_PlanDocuments",
    handler : function(){ PlanInfoObject.PlanDocuments('plan'); }
}]
}
};
    else
    buttons = {
    text : "عملیات",
    iconCls: 'setting',
    menu: {
    itemId : "Operation",
    xtype: 'menu',
    plain: true,
    showSeparator : true,
    items : [{
    text : 'مدارک درخواست دهنده',
    iconCls : "attach",
    handler : function(){ PlanInfoObject.PlanDocuments('person'); }
},{
    text : 'مدارک ضمیمه طرح',
    iconCls : "attach",
    itemId : "cmp_PlanDocuments",
    handler : function(){ PlanInfoObject.PlanDocuments('plan'); }
}]
}
};

    this.MainPanel =  new Ext.panel.Panel({
    applyTo : this.get("mainForm"),
    width: 760,
    height : this.portal ? 530 : 618,
    items : [this.TabPanel,this.itemsPanel],
    tbar : [{
    text : "ردیف های دارای اطلاعات",
    iconCls : "list",
    itemId : "btn_filled",
    enableToggle : true,
    handler : function(){
    PlanInfoObject.itemsPanel.items.each(function(item){item.hide();});
    PlanInfoObject.TabPanel.getActiveTab().down('treepanel').getStore().load({
    params : {
    filled : this.pressed ? "true" : "false"
}
});
}
},'-',buttons,'-',{
    text : "چاپ طرح",
    iconCls : "print",
    handler : function(){
    window.open(PlanInfoObject.address_prefix + "printPlan.php?PlanID=" + PlanInfoObject.PlanID);
}
}]
});

    this.menu = this.MainPanel.getDockedItems()[0].down("[itemId=Operation]");

    if(this.User == "Admin")
{
    if(this.EditAccess)
    this.menu.add({
    text : "تغییر وضعیت",
    iconCls : "refresh",
    handler : function(){
    PlanInfoObject.SetStatus();
}
},
{
    text : "رویدادهای مرتبط با طرح",
    iconCls : "task",
    handler : function(){
    PlanInfoObject.EventsShow();
}
});
}

    if(this.User == "Customer" && !this.readOnly)
{
    this.MainPanel.getDockedItems()[0].add('-',{
    text : "ارسال طرح جهت ارزیابی",
    iconCls : "send",
    handler : function(){
    PlanInfoObject.BeforeSendPlan(<?= STEPID_CUSTOMER_SEND ?>);
}
});
}
    if(this.User == "Admin" && !this.readOnly)
{
    if( new Array(<?= STEPID_RAW ?>,<?= STEPID_REJECT ?>,<?= STEPID_RETURN_TO_CUSTOMER ?>).indexOf(this.PlanRecord.StepID*1) != -1)
    return;

    if(this.PlanRecord.StepID == "<?= STEPID_CUSTOMER_SEND ?>" && this.EditAccess)
{
    this.menu.add({
    text : "تایید اولیه طرح",
    iconCls : "send",
    handler : function(){
    PlanInfoObject.BeforeSendPlan(<?= STEPID_CONFIRM ?>);
}
});
}
    this.menu.add([{
    text : "برگشت به مشتری جهت انجام اصلاحات",
    iconCls : "undo",
    handler : function(){
    PlanInfoObject.BeforeSendPlan(<?= STEPID_RETURN_TO_CUSTOMER ?>);
}
},{
    text : "رد طرح",
    iconCls : "cross",
    handler : function(){
    PlanInfoObject.BeforeSendPlan(<?= STEPID_REJECT ?>);
}
}]);
}

    if(this.User == "Admin")
{
    if(this.EditAccess)
{
    this.menu.add({
    text : "کارشناسی طرح",
    iconCls : "user_comment",
    handler : function(){
    PlanInfoObject.experts();
}
});

    this.menu.add({
    text : "ارسال به حامی طرح",
    iconCls : "tick",
    handler : function(){
    PlanInfoObject.BeforeSendPlan(<?= STEPID_SEND_SUPPORTER ?>);
}
});
}
    /*this.menu.add({
        text : "رویدادهای مرتبط با طرح",
        iconCls : "task",
        handler : function(){
            PlanInfoObject.EventsShow();
        }
    });*/

}
    if(this.User == "Expert" && this.PlanRecord.ExpertStatus != "SEND")
{
    this.MainPanel.getDockedItems("[dock=top]")[0].add('-',{
    text : "تایید کارشناسی طرح",
    iconCls : "send",
    handler : function(){
    PlanInfoObject.SendExpert();
}
});
}

    /*console.log(this.PlanRecord.CustomerPersonID);*/
}

    PlanInfoObject = new PlanInfo();

    PlanInfo.prototype.SavePlan = function(){

    mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخيره سازي...'});
    mask.show();

    Ext.Ajax.request({
    methos : "post",
    url : this.address_prefix + "plan.data.php",
    params : {
    task : "SaveNewPlan",
    PlanID : this.PlanRecord.PlanID,
    /*PersonID : this.planFS.down("[name=PersonID]").getValue(),*/
    CustomerPersonID : this.planFS.down("[name=CustomerPersonID]").getValue(),
    PlanDesc : this.planFS.down("[name=PlanDesc]").getValue(),
    FacilityAmount : this.planFS.down("[name=FacilityAmount]").getValue(),
    evaluationType : this.planFS.down("[name=evaluationType]").getValue(),
    evaluationAskerID : this.planFS.down("[name=evaluationAskerID]").getValue()
    /*LoanID : this.planFS.down("[name=LoanID]").getValue(),
    SupportPersonID : this.planFS.down("[name=SupportPersonID]").getValue()*/
},

    success : function(response){
    mask.hide();
    result = Ext.decode(response.responseText);
    if(!result.success)
    Ext.MessageBox.alert("Error", "عملیات مورد نظر با شکست مواجه شد");
}
});

}

    PlanInfo.prototype.LoadElements = function(record, season){

    var parentEl = this.itemsPanel;
    parentEl.items.each(function(item){item.hide();});

    var mask2 = new Ext.LoadMask(parentEl, {msg:'در حال بارگذاري...'});
    mask2.show();

    var frm = null;
    eval("frm = this.GroupForms.elem_" + record.data.id);
    if(frm == null)
{
    eval("this.GroupForms.elem_" + record.data.id + " = new Array();");
    this.store = new Ext.data.Store({
    proxy:{
    type: 'jsonp',
    extraParams : {
    GroupID : record.data.id,
    PlanID : this.PlanID
},
    url: this.address_prefix + "plan.data.php?task=SelectElements",
    reader: {root: 'rows',totalProperty: 'totalCount'}
},
    fields : ["ElementID", "ParentID", "GroupID", "ElementTitle", "ElementType",
    "properties", "EditorProperties", "ElementValue", "ElementValues"],
    autoLoad : true,
    listeners :{
    load : function(){
    PlanInfoObject.MakeElemForms(this , season);
    mask2.hide();
}
}
});
}
    else
{
    for(i=0; i<frm.length; i++)
    parentEl.down("[itemId=" + frm[i] + "]").show();
    mask2.hide();
}

}

    function merge(obj1,obj2){
    var obj3 = {};
    for (var attrname in obj1) { obj3[attrname] = obj1[attrname]; }
    for (var attrname in obj2) { obj3[attrname] = obj2[attrname]; }
    return obj3;
}

    PlanInfo.prototype.MakeElemForms = function(store, season){

    var parentEl = this.itemsPanel;

    for(var i=0; i < store.getCount(); i++)
{
    record = store.getAt(i);
    switch(record.data.ElementType)
{
    case "panel" :
    btn = {
    text : "ذخیره",
    iconCls : "save",
    handler : function(){
    mask = new Ext.LoadMask(parentEl, {msg:'در حال ذخيره سازي...'});
    mask.show();
    this.up('form').getForm().submit({
    url:  PlanInfoObject.address_prefix + 'plan.data.php?task=SavePlanItems',
    method: 'POST',
    params : {
    PlanID : PlanInfoObject.PlanID,
    ElementID : this.up('form').itemId.split("_")[1]
},
    success: function(form,result){
    record = PlanInfoObject.TabPanel.getActiveTab().down('treepanel').getSelectionModel().getSelection()[0];
    record.set("cls","filled");
    mask.hide();
},
    failure: function(){}
});
}
};
    NewElement = {
    xtype : "form",
    frame : true,
    itemId : "element_" + record.data.ElementID,
    style : "margin-bottom:4px",
    buttons : [!this.readOnly ? btn : ""]
};
    break;
    //..................................................................
    case "grid" :

    var fields = new Array();
    var columns = [ {dataIndex : "RowID",hidden : true},
{dataIndex : "PlanID",hidden : true},
{dataIndex : "ElementID",hidden : true}];
    while(true)
{
    i++;
    var sub_record = store.getAt(i);
    if(sub_record == null || sub_record.data.ParentID != record.data.ElementID)
{
    i--;
    break;
}
    var editor = {xtype : sub_record.data.ElementType};
    if(sub_record.data.ElementType == "datefield")
    editor.format = "Y/m/d";
    if(sub_record.data.ElementType == "combo")
{
    arr = sub_record.data.ElementValues.split("#");
    data = [];
    for(j=0;j<arr.length;j++)
    data.push([ arr[j] ]);
    editor.store = new Ext.data.SimpleStore({
    fields : ['value'],
    data : data
});
    editor.displayField = "value";
    editor.valueField = "value";
}
    eval("editor = merge(editor,{" + sub_record.data.EditorProperties + "});");

    NewColumn = {
    menuDisabled : true,
    sortable : false,
    text : sub_record.data.ElementTitle,
    dataIndex : "element_" + sub_record.data.ElementID,
    editor : editor
};
    if(sub_record.data.ElementType == "currencyfield")
{
    NewColumn.type = "numbercolumn";
    NewColumn.renderer = Ext.util.Format.Money;
    NewColumn.summaryType = "sum";
    NewColumn.summaryRenderer = Ext.util.Format.Money;

}
    if(sub_record.data.ElementType == "currencyfield" ||
    sub_record.data.ElementType == "numberfield")
    NewColumn.editor.hideTrigger = "true";


    eval("NewColumn = merge(NewColumn,{" + sub_record.data.properties + "});");
    columns.push(NewColumn);
    if(sub_record.data.ElementType == "currencyfield")
    fields.push({name : "element_" + sub_record.data.ElementID, type : "int"});
    else
    fields.push("element_" + sub_record.data.ElementID);
}
    NewElement = {
    xtype : "grid",
    features: [{ftype: 'summary'}],
    viewConfig: {
    stripeRows: true,
    enableTextSelection: true
},
    selType : 'rowmodel',
    scroll: 'vertical',
    itemId : "element_" + record.data.ElementID,
    store : new Ext.data.Store({
    proxy:{
    type: 'jsonp',
    url: this.address_prefix + "plan.data.php?task=SelectPlanItems",
    reader: {root: 'rows',totalProperty: 'totalCount'},
    extraParams : {
    PlanID : this.PlanID,
    ElementID : record.data.ElementID
}
},
    fields : ["RowID", "PlanID", "ElementID"].concat(fields),
    autoLoad : true,
    listeners : {
    update : function(store,record){
    mask = new Ext.LoadMask(parentEl, {msg:'در حال ذخيره سازي...'});
    mask.show();
    Ext.Ajax.request({
    url:  PlanInfoObject.address_prefix + 'plan.data.php?task=SavePlanItems',
    params:{
    record : Ext.encode(record.data)
},
    method: 'POST',
    success: function(response,option){
    mask.hide();
    store.load();
},
    failure: function(){}
});
    return true;
}
}
}),
    columns: columns
};

    if(!this.readOnly)
{
    NewElement.plugins = [new Ext.grid.plugin.RowEditing()];
    NewElement.tbar = [{
    text : "ایجاد ردیف",
    iconCls : "add",
    handler : function(){
    var grid = this.up('grid');
    var modelClass = grid.getStore().model;
    var record = new modelClass({
    RowID : null,
    PlanID : PlanInfoObject.PlanID,
    ElementID : grid.getStore().proxy.extraParams.ElementID
});
    grid.plugins[0].cancelEdit();
    grid.getStore().insert(0, record);
    grid.plugins[0].startEdit(0, 0);
}
},'-',{
    text : "ویرایش ردیف",
    iconCls : "edit",
    handler : function(){
    var grid = this.up('grid');
    var record = grid.getSelectionModel().getLastSelected();
    if(record == null)
{
    Ext.MessageBox.alert("","ابتدا ردیف مورد نظر را انتخاب کنید");
    return;
}
    grid.plugins[0].startEdit(grid.getStore().indexOf(record),0);
}
},'-',{
    text : "حذف ردیف",
    iconCls : "remove",
    handler : function(){
    var grid = this.up('grid');
    var record = grid.getSelectionModel().getLastSelected();
    if(record == null)
{
    Ext.MessageBox.alert("","ابتدا ردیف مورد نظر را انتخاب کنید");
    return;
}

    Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟",function(btn){
    if(btn == "no")
    return;
    var mask = new Ext.LoadMask(parentEl, {msg:'در حال ذخيره سازي...'});
    mask.show();
    Ext.Ajax.request({
    url:  PlanInfoObject.address_prefix + 'plan.data.php?task=DeletePlanItem',
    params:{
    RowID : record.data.RowID
},
    method: 'POST',
    success: function(response,option){
    mask.hide();
    grid.getStore().load();
},
    failure: function(){}
});
});
}
}];
}

    break;
    //..................................................................
    case "radio" :
    record.data.ElementValue = record.data.ElementValue== "" ? -1 : record.data.ElementValue;
    var items = new Array();
    ElementValues = record.data.ElementValues.split('#');
    for(j=0;j<ElementValues.length;j++)
    items.push({
    boxLabel : ElementValues[j],
    name : "element_" + record.data.ElementID,
    inputValue : j,
    readOnly : this.readOnly,
    checked : record.data.ElementValue == j ? true : false
});
    NewElement = {
    xtype : "radiogroup",
    fieldLabel : record.data.ElementTitle,
    itemId : "element_" + record.data.ElementID,
    items : items,
    columns: ElementValues.length
};
    break;
    case "combo":
    arr = record.data.ElementValues.split("#");
    data = [];
    for(j=0;j<arr.length;j++)
    data.push([ arr[j] ]);
    NewElement = {
    xtype : record.data.ElementType,
    store : new Ext.data.SimpleStore({
    fields : ['value'],
    data : data
}),
    readOnly : this.readOnly,
    valueField : "value",
    displayField : "value",
    fieldLabel : record.data.ElementTitle,
    itemId : "element_" + record.data.ElementID,
    name : "element_" + record.data.ElementID,
    value : record.data.ElementValue
};
    break;
    case "displayfield":
    NewElement = {
    xtype : record.data.ElementType,
    fieldLabel : record.data.ElementTitle,
    itemId : "element_" + record.data.ElementID,
    value : record.data.ElementValues.replace(/\n/g,"<br>"),
    fieldCls : "desc"
};
    break;
    case "currencyfield":
    case "numberfield":
    NewElement = {
    xtype : record.data.ElementType,
    readOnly : this.readOnly,
    fieldLabel : record.data.ElementTitle,
    itemId : "element_" + record.data.ElementID,
    name : "element_" + record.data.ElementID,
    value : record.data.ElementValue,
    hideTrigger : true
};
    break;
    default :
    NewElement = {
    xtype : record.data.ElementType,
    readOnly : this.readOnly,
    fieldLabel : record.data.ElementTitle,
    itemId : "element_" + record.data.ElementID,
    name : "element_" + record.data.ElementID,
    value : record.data.ElementValue
};
}

    eval("NewElement = merge(NewElement,{" + record.data.properties + "});");

    if(record.data.ParentID == 0)
{
    eval("PlanInfoObject.GroupForms.elem_" + record.data.GroupID + ".push('element_" + record.data.ElementID + "');");
    parentEl.add(NewElement);
}
    else
{
    var parent = this.itemsPanel.down("[itemId=element_" + record.data.ParentID + "]");
    parent.add(NewElement);
}
}
}

    PlanInfo.prototype.ShowMenu = function(view, record, item, index, e)
    {
        if(this.User == "Customer")
        return;
        e.stopEvent();
        e.preventDefault();
        view.select(index);

        this.Menu = new Ext.menu.Menu();

        if(record.data.id == "src" || !record.isLeaf())
        return;

        this.Menu.add({
        text: 'تایید اطلاعات',
        iconCls: 'tick',
        handler : function(){PlanInfoObject.BeforeSurveyGroup('CONFIRM', record);}
    },{
        text: 'رد اطلاعات',
        iconCls: 'cross',
        handler : function(){PlanInfoObject.BeforeSurveyGroup('REJECT', record);}
    },{
        text: 'سابقه',
        iconCls: 'history',
        handler : function(){PlanInfoObject.ShowHistory(record);}
    });

        var coords = e.getXY();
        this.Menu.showAt([coords[0]-120, coords[1]]);
    }

    PlanInfo.prototype.BeforeSurveyGroup = function(mode, record){

    if(mode == "CONFIRM")
{
    Ext.MessageBox.confirm("","آیا مایل به تایید می باشید؟", function(btn){
    if(btn == "no")
    return;

    PlanInfoObject.SurveyGroup(mode, "", record);
});
    return;
}
    if(!this.commentWin)
{
    this.commentWin = new Ext.window.Window({
    width : 412,
    height : 198,
    modal : true,
    title : "توضیحات رد اطلاعات",
    bodyStyle : "background-color:white",
    items : [{
    xtype : "textarea",
    width : 400,
    rows : 8,
    name : "ActDesc"
}],
    closeAction : "hide",
    buttons : [{
    text : "رد اطلاعات",
    iconCls : "cross",
    itemId : "btn_reject"
},{
    text : "بازگشت",
    iconCls : "undo",
    handler : function(){this.up('window').hide();}
}]
});

    Ext.getCmp(this.TabID).add(this.commentWin);
}
    this.commentWin.down("[itemId=btn_reject]").setHandler(function(){
    PlanInfoObject.SurveyGroup('REJECT', this.up('window').down("[name=ActDesc]").getValue(), record);
});
    this.commentWin.show();
    this.commentWin.center();
}

    PlanInfo.prototype.SurveyGroup = function(mode, ActDesc, record){

    mask = new Ext.LoadMask(this.itemsPanel, {msg:'در حال بارگذاري...'});
    mask.show();

    Ext.Ajax.request({
    methos : "post",
    url : this.address_prefix + "plan.data.php",
    params : {
    task : "SurveyGroup",
    PlanID : this.PlanID,
    GroupID : record.data.id,
    mode : mode,
    ActDesc : ActDesc
},

    success : function(){

    if(PlanInfoObject.commentWin)
    PlanInfoObject.commentWin.hide();
    PlanInfoObject.itemsPanel.items.each(function(item){item.hide();});
    var btn = PlanInfoObject.MainPanel.down("[itemId=btn_filled]");
    PlanInfoObject.TabPanel.getActiveTab().down('treepanel').getStore().load({
    params : {
    filled : btn.pressed ? "true" : "false"
}
});
    mask.hide();
}
});
}

    PlanInfo.prototype.BeforeSendPlan = function(StepID){

    if(StepID == <?= STEPID_CUSTOMER_SEND ?> || StepID == <?= STEPID_SEND_SUPPORTER ?>)
{
    Ext.MessageBox.confirm("", "پس از ارسال طرح دیگر قادر به ویرایش طرح نمی باشید.<br>آیا مایل به ارسال می باشید؟",
    function(btn){
    if(btn == "no")
    return;
    PlanInfoObject.SendPlan(StepID, "");
});
    return;
}
    if(StepID == "<?= STEPID_CONFIRM ?>")
{
    Ext.MessageBox.confirm("", "آیا مایل به تایید می باشید؟",
    function(btn){
    if(btn == "no")
    return;
    PlanInfoObject.SendPlan(StepID, "");
});
    return;
}

    if(!this.commentWin2)
{
    this.commentWin2 = new Ext.window.Window({
    width : 412,
    height : 198,
    modal : true,
    title : "توضیحات رد اطلاعات",
    bodyStyle : "background-color:white",
    items : [{
    xtype : "textarea",
    width : 400,
    rows : 8,
    name : "ActDesc"
}],
    closeAction : "hide",
    buttons : [{
    text : "رد طرح",
    iconCls : "cross",
    hidden : true,
    itemId : "btn_reject"
},{
    text : "برگشت طرح به مشتری",
    hidden : true,
    iconCls : "undo",
    itemId : "btn_return"
},{
    text : "بازگشت",
    iconCls : "undo",
    handler : function(){this.up('window').hide();}
}]
});

    Ext.getCmp(this.TabID).add(this.commentWin2);
}
    if(StepID == "<?= STEPID_REJECT ?>")
{
    this.commentWin2.down("[itemId=btn_reject]").show();
    this.commentWin2.down("[itemId=btn_reject]").setHandler(function(){
    PlanInfoObject.SendPlan(StepID, this.up('window').down("[name=ActDesc]").getValue());
});
}
    if(StepID == "<?= STEPID_RETURN_TO_CUSTOMER ?>")
{
    this.commentWin2.down("[itemId=btn_return]").show();
    this.commentWin2.down("[itemId=btn_return]").setHandler(function(){
    PlanInfoObject.SendPlan(StepID, this.up('window').down("[name=ActDesc]").getValue());
});
}
    this.commentWin2.show();
    this.commentWin2.center();
}

    PlanInfo.prototype.SendPlan = function(StepID, ActDesc){

    mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال بارگذاري...'});
    mask.show();

    Ext.Ajax.request({
    methos : "post",
    url : this.address_prefix + "plan.data.php",
    params : {
    task : "ChangeStatus",
    PlanID : this.PlanID,
    StepID : StepID,
    ActDesc : ActDesc
},

    success : function(response){
    mask.hide();
    if(PlanInfoObject.commentWin2)
    PlanInfoObject.commentWin2.hide();

    result = Ext.decode(response.responseText);
    if(result.success)
{
    if(PlanInfoObject.portal)
    portal.OpenPage("/plan/plan/NewPlan.php");
    else
{
    framework.CloseTab(PlanInfoObject.TabID);
    if(typeof ManagePlanObject == "object")
    ManagePlanObject.grid.getStore().load();
}
}
    else
{
    if(result.data != "")
    Ext.MessageBox.alert("Error", result.data);
    else
    Ext.MessageBox.alert("Error", "عملیات مورد نظر با شکست مواجه شد");
}
}
});
}

    PlanInfo.prototype.ShowHistory = function(record){

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
    this.HistoryWin.show();
    this.HistoryWin.center();
    this.HistoryWin.loader.load({
    params : {
    PlanID : this.PlanID,
    GroupID : record.data.id
}
});
    return;
}
    this.HistoryWin.show();
    this.HistoryWin.center();
}

    PlanInfo.prototype.PlanDocuments = function(ObjectType){

    if(!this.documentWin)
{
    this.documentWin = new Ext.window.Window({
    width : 920, 
    height : 440,
    modal : true,
    autoScroll:true,
    bodyStyle : "background-color:white;padding: 4px 4px 4px 4px",
    closeAction : "hide",
    loader : {
    url : "/office/dms/documents.php",
    scripts : true
},
    buttons :[{
    text : "بازگشت",
    iconCls : "undo",
    handler : function(){this.up('window').hide();}
}]
});
    Ext.getCmp(this.TabID).add(this.documentWin);
}

    this.documentWin.show();
    this.documentWin.center();

    this.documentWin.loader.load({
    scripts : true,
    params : {
    ExtTabID : this.documentWin.getEl().id,
    ObjectType : ObjectType,
    /*ObjectID : ObjectType == "plan" ? this.PlanID : this.PlanRecord.PersonID*/
    ObjectID : ObjectType == "plan" ? this.PlanID : this.PlanRecord.CustomerPersonID
}
});
}

    PlanInfo.prototype.experts = function(){

    if(!this.ExpertsWin)
{
    this.ExpertsWin = new Ext.window.Window({
    title: 'کارشناسی طرح',
    modal : true,
    autoScroll : true,
    width: 900,
    height : 600,
    bodyStyle : "background-color:white",
    closeAction : "hide",
    loader : {
    url : this.address_prefix + "PlanExperts.php",
    params :{
    PlanID : this.PlanID
},
    scripts : true
},
    buttons : [/*{
				text : "reload",
				handler : function(){
						this.up('window').loader.load({
							params : {
								ExtTabID : PlanInfoObject.ExpertsWin.getEl().id
							}
						});
					}
			},*/{
    text : "بازگشت",
    iconCls : "undo",
    handler : function(){
    this.up('window').hide();
}
}]
});
    Ext.getCmp(this.TabID).add(this.ExpertsWin);
}
    this.ExpertsWin.show();
    this.ExpertsWin.center();
    this.ExpertsWin.loader.load({
    params : {
    ExtTabID : this.ExpertsWin.getEl().id
}
});
}

    PlanInfo.prototype.SendExpert = function(){

    if(!this.commentWin3)
{
    this.commentWin3 = new Ext.window.Window({
    width : 412,
    height : 198,
    modal : true,
    title : "توضیحات",
    bodyStyle : "background-color:white",
    items : [{
    xtype : "textarea",
    width : 400,
    rows : 8,
    name : "DoneDesc"
}],
    closeAction : "hide",
    buttons : [{
    text : "ارسال",
    iconCls : "send",
    handler : function(){
    me = PlanInfoObject;
    mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال بارگذاري...'});
    mask.show();

    Ext.Ajax.request({
    methos : "post",
    url : "/plan/plan/plan.data.php",
    params : {
    task : "SendExpert",
    PlanID : me.PlanID,
    DoneDesc : me.commentWin3.down("[name=DoneDesc]").getValue()
},

    success : function(response){
    mask.hide();
    result = Ext.decode(response.responseText);
    if(result.success)
{
    PlanInfoObject.commentWin3.hide();
    if(PlanInfoObject.portal)
    portal.OpenPage("/plan/plan/ManagePlans.php");
    else
{
    framework.CloseTab(PlanInfoObject.TabID);
    if(ManagePlanObject.grid.isVisible())
    ManagePlanObject.grid.getStore().load();
}

}
    else
{
    if(result.data != "")
    Ext.MessageBox.alert("Error", result.data);
    else
    Ext.MessageBox.alert("Error", "عملیات مورد نظر با شکست مواجه شد");
}
}
});
}
},{
    text : "بازگشت",
    iconCls : "undo",
    handler : function(){this.up('window').hide();}
}]
});

    Ext.getCmp(this.TabID).add(this.commentWin3);
}
    Ext.MessageBox.confirm("","بعد از تایید کارشناسی دیگر قادر به انجام عملیات روی طرح نمی باشید. آیا مایل به تایید می باشید؟", function(btn){
    if(btn == "no")
    return;

    PlanInfoObject.commentWin3.show();
})
}

    PlanInfo.prototype.SetStatus = function(){

    if(!this.setStatusWin)
{
    this.setStatusWin = new Ext.window.Window({
    width : 412,
    height : 198,
    modal : true,
    title : "تغییر وضعیت",
    defaults : {width : 380},
    bodyStyle : "background-color:white",
    items : [{
    xtype : "combo",
    store : new Ext.data.SimpleStore({
    proxy: {
    type: 'jsonp',
    url: this.address_prefix + "plan.data.php?task=selectRequestStatuses",
    reader: {root: 'rows',totalProperty: 'totalCount'}
},
    fields : ['StepID','StepDesc'],
    autoLoad : true
}),
    fieldLabel : "وضعیت جدید",
    queryMode : 'local',
    allowBlank : false,
    displayField : "StepDesc",
    valueField : "StepID",
    itemId : "StepID"
},{
    xtype : "textarea",
    itemId : "comment",
    fieldLabel : "توضیحات"
}],
    closeAction : "hide",
    buttons : [{
    text : "تغییر وضعیت",
    iconCls : "save",
    itemId : "btn_save",
    handler : function(){
    status = this.up('window').getComponent("StepID").getValue();
    comment = this.up('window').getComponent("comment").getValue();
    PlanInfoObject.SendPlan(status, "[تغییر وضعیت]" + comment);
    this.up('window').hide();
}
},{
    text : "بازگشت",
    iconCls : "undo",
    handler : function(){this.up('window').hide();}
}]
});

    Ext.getCmp(this.TabID).add(this.setStatusWin);
}
    this.setStatusWin.show();
    this.setStatusWin.center();
}

    PlanInfo.prototype.EventsShow = function(){

    if(!this.EventsWin)
{
    this.EventsWin = new Ext.window.Window({
    title: 'رویدادهای مرتبط با طرح',
    modal : true,
    autoScroll : true,
    width: 600,
    height : 400,
    bodyStyle : "background-color:white",
    closeAction : "hide",
    loader : {
    url : this.address_prefix + "events.php",
    params :{
    PlanID : this.PlanID
},
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
    Ext.getCmp(this.TabID).add(this.EventsWin);

    this.EventsWin.show();
    this.EventsWin.center();
    this.EventsWin.loader.load({
    params : {
    MenuID : this.MenuID,
    ExtTabID : this.EventsWin.getEl().id
}
});
    return;
}
    this.EventsWin.show();
    this.EventsWin.center();
}

</script>
