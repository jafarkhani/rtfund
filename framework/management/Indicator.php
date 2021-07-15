<?php
//-----------------------------
//  m.mokhtari
//	Date		: 1399.05
//-----------------------------

require_once '../../header.inc.php';
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);

//...................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "framework.data.php?task=SelectAllIndicators", "grid_div");

$dg->addColumn("", "indexID", "", true);

$col = $dg->addColumn("نوع شاخص", "indexTypeDesc");
$col->width = 100;

$col = $dg->addColumn("نام شاخص", "indexName");
$col->width = 300;
$col = $dg->addColumn("دوره اندازه گیری", "measurPeriod");
$col->width = 120;
$col = $dg->addColumn("جهت مطلوب", "optDirection");
$col->width = 100;

$col = $dg->addColumn('عملیات', '', 'string');
$col->renderer = "Indicators.OperationRender";
$col->width = 50;
$col->align = "center";

$dg->addButton("", "ایجاد ردیف جدید", "add", "function(){Indicators.OpenIndicator(0)}");

$dg->emptyTextOfHiddenColumns = true;
$dg->height = 500;
$dg->width = 700;
$dg->title = "مدیریت شاخص ها";
$dg->DefaultSortField = "indexID";
$dg->autoExpandColumn = "indexID";
$dg->EnableRowNumber = true;
$grid = $dg->makeGrid_returnObjects();

?>
<form id="mainForm">
    <div id="DivPanel" style="margin:8px;width:98%"></div>
</form>

<script>

    Indicators.prototype = {
        TabID : '<?= $_REQUEST["ExtTabID"]?>',
        address_prefix : "<?= $js_prefix_address?>",
        MenuID : "<?= $_POST["MenuID"] ?>",

        AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
        EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
        RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

        get : function(elementID){
            return findChild(this.TabID, elementID);
        }
    };

    function Indicators(){

        this.panel = new Ext.panel.Panel({
            renderTo : this.get("DivPanel"),
            //border : false,
            layout : "hbox",
            height : 500,
            items : [{
                xtype : "container",
                flex : 1,
                html : "<div id=div_grid width=100%></div>"
            },{
                xtype : "container",
                width : 150,
                autoScroll : true,
                height: 500,
                style : "border-left : 1px solid #99bce8;margin-left:5px",
                layout : "vbox",
                itemId : "cmp_buttons"
            }]
        });

        new Ext.data.Store({
            proxy : {
                type: 'jsonp',
                url: this.address_prefix + "framework.data.php?task=selectIndicatorTypes",
                reader: {root: 'rows',totalProperty: 'totalCount'}
            },
            fields : ["InfoID","InfoDesc","param1","param2"],
            autoLoad : true,
            listeners : {
                load : function(){
                    me = IndicatorsObject;
                    //..........................................................
                    me.panel.down("[itemId=cmp_buttons]").removeAll();
                    for(var i=0; i<this.totalCount; i++)
                    {
                        record = this.getAt(i);
                        if(record.data.param1 != "")
                        {
                            btn = me.panel.down("[itemId=g_" + record.data.param1 + "]");
                            if(!btn)
                            {
                                btn = me.panel.down("[itemId=cmp_buttons]").add({
                                    xtype : "button",
                                    width : 130,
                                    height : 50,
                                    autoScroll : true,
                                    scale : "large",
                                    style : "margin-bottom:10px",
                                    itemId : "g_" + record.data.param1,
                                    text : record.data.param2,
                                    menu : []
                                });
                            }

                            btn.menu.add({
                                itemId : record.data.InfoID,
                                text : record.data.InfoDesc,
                                handler : function(){Indicators.LoadGrid(this)}
                            });
                        }
                        else
                            me.panel.down("[itemId=cmp_buttons]").add({
                                xtype : "button",
                                width : 130,
                                height : 50,
                                autoScroll : true,
                                scale : "large",
                                style : "margin-bottom:10px",
                                itemId : record.data.InfoID + "_" + record.data.param1,
                                text : record.data.param1 != "" ? record.data.param1 : record.data.InfoDesc,
                                handler : function(){Indicators.LoadGrid(this)}
                            });
                    }
                }
            }
        });

        this.grid = <?= $grid ?>;
        this.grid.on("itemdblclick", function(view, record){
            Indicators.OpenIndicator(record.data.indexID,'show');
        });
        /*this.grid.getView().getRowClass = function(record, index)
        {
            if(record.data.StatusID == "<?= MTG_STATUSID_DONE ?>")
                return "greenRow";
            if(record.data.StatusID == "<?= MTG_STATUSID_CANCLE ?>")
                return "pinkRow";
            return "";
        }*/
        //this.grid.render(this.get("DivGrid"));

        framework.centerPanel.items.get(this.TabID).on("activate", function(){
            IndicatorsObject.grid.getStore().load();
        });
    }

    Indicators.LoadGrid = function(btn){
        var IndicatorTypeID = IndicatorsObject.grid.getStore().proxy.extraParams.indexType;
        IndicatorsObject.grid.getStore().proxy.extraParams.indexType = btn.itemId;

        IndicatorsObject.grid.setTitle(btn.text);
        if(IndicatorsObject.grid.rendered)
            IndicatorsObject.grid.getStore().loadPage(1);
        else
            IndicatorsObject.grid.render(IndicatorsObject.get("div_grid"));
    }

    Indicators.OpenIndicator = function(indexID,type){
        /*console.log(MeetingsObject.grid.getStore().proxy.extraParams.MeetingType);*/

        framework.OpenPage("/framework/management/IndicatorInfo.php", "اطلاعات شاخص",
            {
                indexID : indexID,
                type : type,
                IndicatorTypeID : IndicatorsObject.grid.getStore().proxy.extraParams.indexType,
                MenuID : IndicatorsObject.MenuID
            });
    }



    Indicators.OperationRender = function(value, p, record){


        return "<div  title='عملیات' class='setting' onclick='IndicatorsObject.OperationMenu(event);' " +
            "style='background-repeat:no-repeat;background-position:center;" +
            "cursor:pointer;width:100%;height:16'></div>";
    }

    IndicatorsObject = new Indicators();

    Indicators.prototype.OperationMenu = function(e){

        record = this.grid.getSelectionModel().getLastSelected();
        var op_menu = new Ext.menu.Menu();

        if (this.EditAccess)
            op_menu.add({text: 'ويرايش شاخص',iconCls: 'edit',
                handler : function(){
                    record = IndicatorsObject.grid.getSelectionModel().getLastSelected();
                    Indicators.OpenIndicator(record.data.indexID,'edit');
                }});

         if(this.RemoveAccess)
                op_menu.add({text: 'حذف شاخص',iconCls: 'remove',
                    handler : function(){ return IndicatorsObject.deleteIndicator(); }});

        op_menu.showAt(e.pageX-120, e.pageY);
    }

    Indicators.prototype.deleteIndicator = function(){

        Ext.MessageBox.confirm("","آیا مایل به حذف جلسه می باشید؟",function(btn){
            if(btn == "no")
                return;

            me = IndicatorsObject;
            record = me.grid.getSelectionModel().getLastSelected();

            mask = new Ext.LoadMask(me.grid, {msg:'در حال ذخيره سازي...'});
            mask.show();

            Ext.Ajax.request({
                methos : "post",
                url : me.address_prefix + "framework.data.php",
                params : {
                    task : "DeleteIndicator",
                    indexID : record.data.indexID
                },

                success : function(response){
                    result = Ext.decode(response.responseText);
                    mask.hide();
                    if(result.success)
                    {
                        IndicatorsObject.grid.getStore().load();
                    }
                    else
                        Ext.MessageBox.alert("Error",result.data);
                }
            });
        });
    }

</script>