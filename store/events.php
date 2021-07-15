<?php
//-------------------------
// programmer:	Mokhtari
// Create Date:	99.04
//-------------------------
require_once('header.inc.php');
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$AssetID = $_REQUEST["AssetID"];

$dg = new sadaf_datagrid("dg",$js_prefix_address . "store.data.php?task=GetEvents&AssetID=" .$AssetID,"grid_div");

$dg->addColumn("", "eventID","", true);
$dg->addColumn("", "AssetID","", true);
$dg->addColumn("", "actionFullname","", true);
$dg->addColumn("", "FollowUpFullname","", true);

/*$col->renderer = "function(v,p,r){return v == 1 ? 'نت پیشگیرانه' : v == 2 ? 'نت موردی' : ''}";*/
$col = $dg->addColumn("نوع اقدام", "actionType");
$col->editor = ColumnEditor::ComboBox(array(
    array("id"=>'1',"title"=>'نت پیشگیرانه'),
    array("id"=>"2",'title'=>"نت موردی")),
    "id", "title");
$col->width = 80;

/*$dg->addColumn("", "actionDesc","", true);
$dg->addColumn("", "referDate","", true);
$dg->addColumn("", "actionDate","", true);
$dg->addColumn("", "actionPID","", true);
$dg->addColumn("", "FollowUpDate","", true);
$dg->addColumn("", "FollowUpPID","", true);
$dg->addColumn("", "FollowUpDesc","", true);*/
$col = $dg->addColumn("ثبت کننده", "RegFullname");
$col->width = 100;

$col = $dg->addColumn("شرح اقدام", "actionDesc");
$col->editor = ColumnEditor::TextField();

$col = $dg->addColumn("تاریخ ارجاع", "referDate", GridColumn::ColumnType_date);
$col->editor = ColumnEditor::SHDateField();
$col->width = 100;

$col = $dg->addColumn("تاریخ اقدام", "actionDate", GridColumn::ColumnType_date);
$col->editor = ColumnEditor::SHDateField();
$col->width = 100;

$col = $dg->addColumn("اقدام کننده", "actionPID");
$col->editor = "this.PersonCombo";
$col->renderer = "function(v,p,r){return r.data.actionFullname }";
$col->width = 120;

$col = $dg->addColumn("تاریخ پیگیری آینده", "FollowUpDate", GridColumn::ColumnType_date);
$col->editor = ColumnEditor::SHDateField(true);
$col->width = 100;

/*$col = $dg->addColumn("شماره نامه", "LetterID");
$col->renderer = "LoanEvent.LetterRender";
$col->editor = ColumnEditor::NumberField(true);
$col->width = 70;*/

$col = $dg->addColumn("پیگیری کننده آینده", "FollowUpPID");
$col->editor = "this.PersonComboo";
$col->renderer = "function(v,p,r){return r.data.FollowUpFullname }";
$col->width = 120;

$col = $dg->addColumn("شرح پیگیری آینده", "FollowUpDesc");
$col->editor = ColumnEditor::TextField(true);
$col->width = 200;

$accessObj->AddFlag = true;
$accessObj->RemoveFlag = true;

if($accessObj->AddFlag)
{
    $dg->enableRowEdit = true;
    $dg->rowEditOkHandler = "function(store,record){return AssetEventObject.SaveEvent(record);}";

    $dg->addButton("AddBtn", "ایجاد رویداد", "add", "function(){AssetEventObject.AddEvent();}");
}
if($accessObj->RemoveFlag)
{
    $col = $dg->addColumn("حذف", "");
    $col->sortable = false;
    $col->renderer = "function(v,p,r){return AssetEvent.DeleteRender(v,p,r);}";
    $col->width = 35;
}
$dg->height = 336;
$dg->emptyTextOfHiddenColumns = true;
$dg->EnableSearch = false;
$dg->HeaderMenu = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "eventID";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "eventID";

$grid = $dg->makeGrid_returnObjects();

?>
<script type="text/javascript">

    AssetEvent.prototype = {
        TabID : '<?= $_REQUEST["ExtTabID"]?>',
        address_prefix : "<?= $js_prefix_address?>",

        AssetID : <?= $AssetID ?>,

        get : function(elementID){
            return findChild(this.TabID, elementID);
        }
    };

    function AssetEvent()
    {
        this.PersonCombo = new Ext.form.ComboBox({
            store: new Ext.data.Store({
                proxy:{
                    type: 'jsonp',
                    url: '/framework/person/persons.data.php?task=selectPersons&IsStaff=YES&EmptyRow=true',
                    reader: {root: 'rows',totalProperty: 'totalCount'}
                },
                fields :  ['PersonID','fullname'],
                autoLoad : true
            }),
            displayField: 'fullname',
            valueField : "PersonID"
        });

        this.PersonComboo = new Ext.form.ComboBox({
            store: new Ext.data.Store({
                proxy:{
                    type: 'jsonp',
                    url: '/framework/person/persons.data.php?task=selectPersons&IsStaff=YES',
                    reader: {root: 'rows',totalProperty: 'totalCount'}
                },
                fields :  ['PersonID','fullname']
            }),
            displayField: 'fullname',
            valueField : "PersonID"
        });

        this.grid = <?= $grid ?>;
        this.grid.render(this.get("div_grid"));
    }

    AssetEvent.DeleteRender = function(v,p,r){

        /*if(r.data.EventRefNo != null &&  r.data.EventRefNo != "")
            return "";

        if(r.data.EventType == "9" && r.data.ChequeStatus != "1")
            return "";*/

        return "<div align='center' title='حذف' class='remove' "+
            "onclick='AssetEventObject.DeleteEvent();' " +
            "style='background-repeat:no-repeat;background-position:center;" +
            "cursor:pointer;width:100%;height:16'></div>";
    }

    AssetEvent.LetterRender = function(v,p,r){

        if(v == null)
            return "";
        return "<a onclick='AssetEventObject.OpenLetter(" + v + ")' href=javascript:void(1) >" + v + "</a>";
    }

    AssetEvent.prototype.SaveEvent = function(record){

        mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
        mask.show();

        Ext.Ajax.request({
            url: this.address_prefix +'store.data.php',
            method: "POST",
            params: {
                task: "SaveEvents",
                record: Ext.encode(record.data)
            },
            success: function(response){
                mask.hide();
                var st = Ext.decode(response.responseText);

                if(st.success)
                {
                    AssetEventObject.grid.getStore().load();
                }
                else
                {
                    Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
                }
            },
            failure: function(){}
        });
    }

    AssetEvent.prototype.AddEvent = function(){


        var modelClass = this.grid.getStore().model;
        var record = new modelClass({
            eventID: null,
            AssetID : this.AssetID
        });

        this.grid.plugins[0].cancelEdit();
        this.grid.getStore().insert(0, record);
        this.grid.plugins[0].startEdit(0, 0);
    }

    AssetEvent.prototype.DeleteEvent = function(){

        Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
            if(btn == "no")
                return;

            me = AssetEventObject;
            var record = me.grid.getSelectionModel().getLastSelected();

            mask = new Ext.LoadMask(me.grid, {msg:'در حال حذف ...'});
            mask.show();

            Ext.Ajax.request({
                url: me.address_prefix + 'store.data.php',
                params:{
                    task: "DeleteEvents",
                    eventID : record.data.eventID
                },
                method: 'POST',

                success: function(response,option){
                    result = Ext.decode(response.responseText);
                    if(result.success)
                        AssetEventObject.grid.getStore().load();
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

    AssetEvent.prototype.OpenLetter = function(LetterID){

        framework.OpenPage("/office/letter/LetterInfo.php", "مشخصات نامه",
            {
                LetterID : LetterID
            });
    }

    var AssetEventObject = new AssetEvent();

</script>
<center>
    <div id="div_grid"></div>
</center>