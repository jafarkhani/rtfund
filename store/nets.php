<?php
//-------------------------
// programmer:	Mokhtari
// Create Date:	99.04
//-------------------------
require_once('../header.inc.php');
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$AssetID = $_REQUEST["AssetID"];

$dg = new sadaf_datagrid("dg",$js_prefix_address . "store.data.php?task=GetNets&AssetID=" .$AssetID,"grid_div");

$dg->addColumn("", "netID","", true);
$dg->addColumn("", "AssetID","", true);
$dg->addColumn("", "RegFullname","", true);


$col = $dg->addColumn("دوره نت", "NetPeriod");
$col->editor = ColumnEditor::TextField();
$col->width = 130;

$col = $dg->addColumn("نحوه نت", "NetMethod");
$col->editor = ColumnEditor::TextField();
$col->width = 200;

$col = $dg->addColumn("ثبت کننده", "RegFullname");
$col->width = 100;

$col = $dg->addColumn("تاریخ ثبت", "RegDate", GridColumn::ColumnType_date);
$col->width = 100;

$accessObj->AddFlag = true;
$accessObj->RemoveFlag = true;

if($accessObj->AddFlag)
{
    $dg->enableRowEdit = true;
    $dg->rowEditOkHandler = "function(store,record){return AssetNetObject.SaveNet(record);}";

    $dg->addButton("AddBtn", "ایجاد برنامه نت", "add", "function(){AssetNetObject.AddNet();}");
}
if($accessObj->RemoveFlag)
{
    $col = $dg->addColumn("حذف", "");
    $col->sortable = false;
    $col->renderer = "function(v,p,r){return AssetNet.DeleteRender(v,p,r);}";
    $col->width = 35;
}
$dg->height = 336;
$dg->emptyTextOfHiddenColumns = true;
$dg->EnableSearch = false;
$dg->HeaderMenu = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "netID";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "netID";

$grid = $dg->makeGrid_returnObjects();

?>
<script type="text/javascript">

    AssetNet.prototype = {
        TabID : '<?= $_REQUEST["ExtTabID"]?>',
        address_prefix : "<?= $js_prefix_address?>",

        AssetID : <?= $AssetID ?>,

        get : function(elementID){
            return findChild(this.TabID, elementID);
        }
    };

    function AssetNet()
    {
        this.grid = <?= $grid ?>;
        this.grid.render(this.get("div_grid"));
    }

    AssetNet.DeleteRender = function(v,p,r){

        return "<div align='center' title='حذف' class='remove' "+
            "onclick='AssetNetObject.DeleteNet();' " +
            "style='background-repeat:no-repeat;background-position:center;" +
            "cursor:pointer;width:100%;height:16'></div>";
    }

    AssetNet.prototype.SaveNet = function(record){

        mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
        mask.show();

        Ext.Ajax.request({
            url: this.address_prefix +'store.data.php',
            method: "POST",
            params: {
                task: "SaveNets",
                record: Ext.encode(record.data)
            },
            success: function(response){
                mask.hide();
                var st = Ext.decode(response.responseText);

                if(st.success)
                {
                    AssetNetObject.grid.getStore().load();
                }
                else
                {
                    Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
                }
            },
            failure: function(){}
        });
    }

    AssetNet.prototype.AddNet = function(){


        var modelClass = this.grid.getStore().model;
        var record = new modelClass({
            netID: null,
            AssetID : this.AssetID
        });

        this.grid.plugins[0].cancelEdit();
        this.grid.getStore().insert(0, record);
        this.grid.plugins[0].startEdit(0, 0);
    }

    AssetNet.prototype.DeleteNet = function(){

        Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
            if(btn == "no")
                return;

            me = AssetNetObject;
            var record = me.grid.getSelectionModel().getLastSelected();

            mask = new Ext.LoadMask(me.grid, {msg:'در حال حذف ...'});
            mask.show();

            Ext.Ajax.request({
                url: me.address_prefix + 'store.data.php',
                params:{
                    task: "DeleteNets",
                    netID : record.data.netID
                },
                method: 'POST',

                success: function(response,option){
                    result = Ext.decode(response.responseText);
                    if(result.success)
                        AssetNetObject.grid.getStore().load();
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

    var AssetNetObject = new AssetNet();

</script>
<center>
    <div id="div_grid"></div>
</center>