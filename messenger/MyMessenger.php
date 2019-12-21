<?php
//-----------------------------
//	Date		: 1398.08
//-----------------------------
require_once '../header.inc.php';
require_once inc_dataGrid;
       
echo '<head><meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>			
        
        <link rel="stylesheet" type="text/css" href="/generalUI/ext4/resources/css/ext-rtl-mobile.css?v=1" />
        <link rel="stylesheet" type="text/css" href="/generalUI/ext4/resources/css/ext-all-mobile.css?v=1" />
        <link rel="stylesheet" type="text/css" href="/generalUI/icons/icons.css?v=1" />
        <script type="text/javascript" src="/generalUI/ext4/resources/ext-all.js?v=1"></script>
        <script type="text/javascript" src="/generalUI/ext4/resources/ext-extend.js?v=1"></script>
        <script type="text/javascript" src="/generalUI/ext4/ux/component.js?v=1"></script>
        <script type="text/javascript" src="/generalUI/ext4/ux/message.js?v=1"></script>
        <script type="text/javascript" src="/generalUI/ext4/ux/grid/SearchField.js?v=1"></script>
        <script type="text/javascript" src="/generalUI/ext4/ux/TreeSearch.js?v=1"></script>
        <script type="text/javascript" src="/generalUI/ext4/ux/CurrencyField.js?v=1"></script>
        <script type="text/javascript" src="/generalUI/ext4/ux/grid/ExtraBar.js?v=1"></script>
        <script type="text/javascript" src="/generalUI/ext4/ux/grid/gridprinter/Printer.js?v=1"></script>
        <script type="text/javascript" src="/generalUI/ext4/ux/grid/excel.js"></script>
        <script type="text/javascript" src="/generalUI/jquery-3.4.1.min.js"></script>
      </head>';



$dg = new sadaf_datagrid("dg", $js_prefix_address . "ManageGroup.data.php?task=SelectMyMessage", "DivGrid");

$dg->addColumn("", "GID", "int", true);
$dg->addColumn("", "MID", "int", true);

$col = $dg->addColumn("", "TM");
$col->renderer = "MyMessenger.GroupTitleRender";


$dg->emptyTextOfHiddenColumns = true;
$dg->autoExpandColumn = "TM";
$dg->DefaultSortField = "TM";
$dg->DefaultSortDir = "DESC";
//$dg->width = 600;
$dg->EnablePaging = false;
$dg->HeaderMenu = false;
$dg->EnableSearch = false;
$dg->disableFooter = true;

$grid = $dg->makeGrid_returnObjects();
?>
<body dir="rtl">    
    <!--<div class="sticky" ><img  src="../messenger/MsgDocuments/GoBottom.png" alt="Avatar"></div>-->
    <div id="SearchPanel" style="width: 100%;float:left" ></div>
    <div id="mainpanel" style="width: 100%;float:left" ></div>            
    <div id="DivGrid" style="width:100%"></div>	

</body>
<style>.Expand {background-image:url('../messenger/MsgDocuments/expand.png') !important;}</style>
<style>
    .attachFile {
        background-repeat:no-repeat;
        background-position:left;
        background: #ffffff;
        background-image:url('../messenger/MsgDocuments/attach.png') !important;
        width:30px!important;
        height:30px!important;
    }
</style>
<style>	
    .GrpPic {
        border:1px inset #9990;
        width:70px;
        height:70px;
        cursor:pointer;
        vertical-align: middle;
        border-radius: 50%;}
    .GrpInfo {padding-right:5px; line-height: 21px}
    .MsgInfoBox {
        background: linear-gradient(to top, #72dbfc, #159fcd);
        border-radius: 50%; 
        color : white;
        cursor: pointer;
        padding-right:4px;
        line-height: 22px; 
        margin: 2px; 
        text-align: center;
        vertical-align: middle;
    }

    .MsgInfoBox2 {
        background: linear-gradient(to top, #72dbfc, #159fcd);
        border-radius: 50%; 
        color : white;
        cursor: pointer;
        border:false;
        text-align: top;
        vertical-align: top;
    }


    .ChatBox { 
        background: radial-gradient(circle, #f7fffb, #e3faee, #e1faed); 
        border-radius: 10px;
        color : black;        
        padding-right:4px;
        line-height: 22px; 
        margin: 2px; 
        text-align: right;
        vertical-align: middle;        
    }

    .MyChatBox {
        background: radial-gradient(circle, #f2f9ff, #f5faff, #f0f7ff);
        border-radius: 10px; 
        color : black;
        cursor: pointer;
        padding-right:4px;
        line-height: 22px; 
        margin: 2px; 
        text-align: right;
        vertical-align: middle;        
    }

    .rcorners2 {
        border-radius: 25px;
        border: 2px solid #bbdbed;
        padding: 20px; 
        width: 200px;
        height: 150px;  
    }

    .button {
        background-color: #4CAF50;  
        border: none;
        color: white;
        padding: 20px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 16px;
        margin: 4px 2px;
        cursor: pointer;
        border-radius: 50%;
    }

    .bottomright {
        position: absolute;
        bottom: 8px;
        right: 16px;
        font-size: 18px;
    }

</style>
<script>
    MyMessenger.prototype = {
        WinID: document.body,
        address_prefix: "<?= $js_prefix_address ?>",
        IsFirstLoad: false,
        ScrollPosition : 0, 
        FullMsg : " ", 
        get: function (elementID) {
            return findChild(this.WinID, elementID);
        }
    };

    function MyMessenger() {

//...................................................................
        var store = Ext.create('Ext.data.Store', {
            remoteSort: true,
            buffered: true,
            fields: [{name: 'MSGID'}, {name: 'GID'}, {name: 'MID'}, {name: 'fname'}, {name: 'lname'}, {name: 'message'},
                {name: 'FileType'}, {name: 'ParentMsg'}, {name: 'ParentMSGID'}, {name: 'SendingDate'}],
            proxy: {
                type: 'jsonp',
                url: '/messenger/ManageGroup.data.php?task=SelectMessageGrp',
                reader: {
                    root: 'rows',
                    totalProperty: 'totalCount',
                    messageProperty: 'MSGID'
                },
                simpleSortMode: true
            },
            sorters: [{
                    property: 'MSGID',
                    direction: 'DESC'
                }]
        });

        var SearchStore = Ext.create('Ext.data.Store', {
            remoteSort: true,
            buffered: true,
            fields: [{name: 'MSGID'}, {name: 'MID'}, {name: 'message'}],
            proxy: {
                type: 'jsonp',
                url: '/messenger/ManageGroup.data.php?task=SearchMsg',
                reader: {
                    root: 'rows',
                    totalProperty: 'totalCount',
                    messageProperty: 'MSGID'
                },
                simpleSortMode: true
            },
            sorters: [{
                    property: 'MSGID',
                    direction: 'DESC'
                }]
        });


        this.grid2 = new Ext.grid.GridPanel({
            // width: 600,
            height: 430,
            store: store,
            verticalScrollerType: 'paginggridscroller',          
            disableSelection: true,
            invalidateScrollerOnRefresh: false,
            viewConfig: {
                trackOver: false,
                loadMask: false
            },
            columns: [{menuDisabled: true,
                    align: 'center',
                    header: '',
                    dataIndex: 'MSGID',
                    emptyText: '',
                    hidden: true},
                {menuDisabled: true,
                    align: 'center',
                    header: '',
                    dataIndex: 'MID',
                    emptyText: '',
                    hidden: true},
                {menuDisabled: true,
                    align: 'center',
                    header: '',
                    dataIndex: 'ParentMsg',
                    emptyText: '',
                    hidden: true},
                {menuDisabled: true,
                    align: 'center',
                    header: '',
                    dataIndex: 'FileType',
                    emptyText: '',
                    hidden: true},
                {menuDisabled: true,
                    align: 'center',
                    header: '',
                    dataIndex: 'ParentMSGID',
                    emptyText: '',
                    hidden: true},
                {menuDisabled: true,
                    align: 'center',
                    header: '',
                    dataIndex: 'fname',
                    emptyText: '',
                    hidden: true},
                {menuDisabled: true,
                    align: 'center',
                    header: '',
                    dataIndex: 'lname',
                    emptyText: '',
                    hidden: true},
                {menuDisabled: true,
                    align: 'center',
                    header: '',
                    dataIndex: 'SendingDate',
                    emptyText: '',
                    hidden: true},
                {menuDisabled: true,
                    header: '',
                    dataIndex: 'message',
                    renderer: function (v, p, r) {
                        return MyMessenger.renderMsg(v, p, r);
                    },
                    flex: 1,
                    type: 'string',
                    type: '',
                    hidden: false,
                    hideMode: 'display',
                    searchable: true,
                    emptyText: ''}]

        });

        this.searchGrid = new Ext.grid.GridPanel({
            //width: 420,
            height: 370,
            store: SearchStore,
            verticalScrollerType: 'paginggridscroller',
            loadMask: true,
            disableSelection: true,
            invalidateScrollerOnRefresh: false,
            viewConfig: {
                trackOver: false
            },
            columns: [{menuDisabled: true,
                    align: 'center',
                    header: '',
                    dataIndex: 'MSGID',
                    emptyText: '',
                    hidden: true},
                {menuDisabled: true,
                    header: '',
                    flex: 1,
                    dataIndex: 'message',
                    renderer: function (v, p, r) {
                        return MyMessenger.renderSrchMsg(v, p, r);
                    },
                    //width: 580,
                    type: 'string',
                    type: '',
                    hidden: false,
                    hideMode: 'display',
                    searchable: true,
                    emptyText: ''}]

        });

        this.newItemPanel = new Ext.Panel({
            renderTo: this.get("mainpanel"),
            title: "لیست پیغام",
            autoHeight: true,
            // width: 620,
            height: 720,
            dockedItems: [{
                    id: "Field3",
                    xtype: 'toolbar',
                    height: 30,
                    dock: 'top',
                    items: [{
                            xtype: 'button',
                            text: '&nbsp;',
                            iconCls: "search",
                            handler:
                                    function ()
                                    {
                                        MyMessengerObject.SearchPanel.show();
                                    }
                        },
                        '->',
                        {
                            xtype: 'button',
                            id: 'btn1',
                            handler:
                            function ()
                            {
                                MyMessengerObject.grid2.getStore().load();
                              //  MyMessengerObject.GoToMsg(279);
                               
                                MyMessengerObject.grid2.getView().scrollBy(0, 999999, true);
                                MyMessengerObject.SeenMsg();
                            }
                        },
                        {
                            xtype: 'button',
                            text: '&nbsp;',
                            iconCls: "down",
                            handler:
                            function ()
                            {
                                MyMessengerObject.grid2.getStore().load();
                              //  MyMessengerObject.GoToMsg(279);
                                
                                MyMessengerObject.grid2.getView().scrollBy(0, 999999, true);
                                MyMessengerObject.SeenMsg();                                
                            }
                        }
                    ]
                }],
            frame: true,
            style: "padding-right:10px;",
            items: [this.grid2,
                {
                    id: "Field2",
                    xtype: "container",
                    fieldLabel: "Field2",
                    style: 'width:95%;background-color:#edf9fd;border-right: 4px solid #bbdbed;border-left: 4px solid #bbdbed;border-top: 4px solid #bbdbed;border-radius: 25px;padding: 5px;',
                    // width: 540,
                    hidden: true,
                    layout: {
                        type: "table",
                        columns: 4
                    },
                    items: [
                        {
                            xtype: "container",
                            //width: 400,
                            colspan: 4,
                            html: "<div><img width='18px' height='18px' style='border:2px solid #bbdbed;border-radius: 5px;' align='top' src='../messenger/MsgDocuments/close.png' onclick='MyMessengerObject.ClosePanel();' >&nbsp;</div>",
                        },
                        {
                            xtype: "displayfield",
                            name: "PersonName",
                            itemId: "PersonName",
                            style: "border-radius: 25px;",
                            colspan: 4/*,
                             renderer: function (v) {
                             return Ext.util.Format.Money(v) + " ریال"
                             }*/
                        },
                        {
                            xtype: "displayfield",
                            name: "PMsg",
                            itemId: "PMsg",
                            style: "border-radius: 25px;",
                            colspan: 4,
                            renderer: function (v) {
                                return Ext.String.ellipsis(v, 80);
                            }
                        }]
                },

                this.formpanel = new Ext.form.Panel({
                    layout: {
                        type: "table",
                        columns: 4
                    },
                    style: "margin:0px 0 1px",
                    border: false,
                    //width: 650,
                    frame: false,
                    items: [
                        {
                            xtype: "numberfield",
                            name: "MSGID",
                            itemId: "MSGID",
                            colspan: 3,
                            hidden: true
                        },
                        {
                            xtype: "numberfield",
                            name: "GID",
                            itemId: "GID",
                            colspan: 3,
                            hidden: true
                        },
                        {
                            xtype: "numberfield",
                            name: "MID",
                            itemId: "MID",
                            colspan: 3,
                            hidden: true
                        },
                        {
                            xtype: "numberfield",
                            name: "ParentMSGID",
                            itemId: "ParentMSGID",
                            colspan: 3,
                            hidden: true
                        },
                        {
                            xtype: "textarea",
                            name: "MsgTxt",
                            itemId: "MsgTxt",
                            style: "width:99%;margin:0px;",
                            fieldCls: "rcorners2",
                            colspan: 2,
                            height: 80
                        },
                        {
                            xtype: "container",
                            layout: "hbox",
                            style: "vertical-align:top;height:102;margin-top:60px",
                            colspan: 2,
                            height: 110,
                            width: 320,
                            items: [
                                /* {
                                 xtype: 'button',
                                 colspan: 1,
                                 height: 40,
                                 width: 40,
                                 fieldCls: "button",
                                 border:false,
                                 style: "border-radius: 1%;background: #f0f0f0 url(../messenger/MsgDocuments/attach.png);",                            
                                 name: 'button1',
                                 autoEl: {tag: 'center'},
                                 text: '',
                                 handler:
                                 function ()
                                 {
                                 MyMessengerObject.SaveSendingMsg();
                                 }
                                 },*/
                                {
                                    xtype: "filefield",
                                    name: "FileType",
                                    colspan: 1,
                                    buttonOnly: true,
                                    itemId: 'FileType',
                                    buttonConfig: {
                                        iconCls: 'attachFile',
                                        text: '',
                                        iconAlign: 'center',
                                        style: 'background: #ffffff;border:0;margin-top:10px',
                                        scale: 'large'
                                    }
                                },
                                {
                                    xtype: 'button',
                                    colspan: 1,
                                    height: 40,
                                    width: 40,
                                    fieldCls: "button",
                                    border: false,
                                    style: "border-radius: 1%;background: #f0f0f0 url(../messenger/MsgDocuments/send.png);",
                                    name: 'button1',
                                    autoEl: {tag: 'center'},
                                    text: '',
                                    handler:
                                            function ()
                                            {
                                                MyMessengerObject.SaveSendingMsg();
                                            }
                                }

                            ]
                        }




                    ]

                })
            ]
            ,
            /*loader:{
             url: this.address_prefix + "ManageGroup.php",
             scripts: true
             },*/


        });

        Ext.getCmp('btn1').hide();
        this.newItemPanel.hide();
        this.SearchPanel = new Ext.form.Panel({
            renderTo: this.get("SearchPanel"),
            title: "جستجو",
            autoHeight: true,
            closable: true,
            //width: 440,
            height: 450,
            frame: true,
            layout: {
                type: "table",
                columns: 3
            },
            style: "padding-right:0px;align:right",
            items: [

                {
                    xtype: "textfield",
                    name: "SearchTxt",
                    itemId: "SearchTxt",
                    style: "width:90%;margin:2px;",
                    fieldCls: "rcorners2",
                    height: 40,
                    colspan: 2
                            // width: 300
                },
                /*{
                 xtype: 'button',
                 height: 45,
                 width: 45,
                 colspan:2,
                 fieldCls: "button",
                 style: "border-radius: 50%;align:'right';background: #f0f0f0 url(../messenger/MsgDocuments/search.png);",                        
                 name: 'button1',                        
                 autoEl: {tag: 'center'},
                 text: '',
                 handler:
                 function ()
                 {
                 MyMessengerObject.SearchGrid.load();
                 //MyMessengerObject.SaveSendingMsg();
                 }
                 },*/
                {
                    xtype: "container",
                    width: 100,
                    //style: "width:10%;margin:2px;",
                    colspan: 1,
                    html: "<div><img align='right' width='40px' height='40px' src='../messenger/MsgDocuments/search.png' " +
                            " onclick='MyMessengerObject.Searching();' style='cursor:pointer;'>&nbsp;</div>",
                },
                {
                    xtype: "container",
                    colspan: 3,
                    //  width: 410,
                    style: "text-align:right",
                    items: [this.searchGrid]
                }

            ]
        });
        this.SearchPanel.hide();

        //.........................
        this.grid = <?= $grid ?>;
        this.grid.render(this.get("DivGrid"));

        this.grid.on("cellclick", function () {
            MyMessengerObject.newItemPanel.show();
            var record = MyMessengerObject.grid.getSelectionModel().getLastSelected();

            MyMessengerObject.grid2.getStore().proxy.extraParams.GID = record.data.GID;
            MyMessengerObject.formpanel.down("[itemId=GID]").setValue(record.data.GID);
            MyMessengerObject.formpanel.down("[itemId=MID]").setValue(record.data.MID);
            store.prefetch({
                start: 0,
                limit: 10,
                callback: function () {                    
                    store.guaranteeRange(0,5); 
                    //store.load();
                   // MyMessengerObject.grid2.getView().scrollBy(0, 999999, true);
                    //if (MyMessengerObject.IsFirstLoad === false) {                        
                        /*
                         * var records = Ext.getCmp('prGrid').getStore().data.length + 1;
                         * var scrollPosition = 100;   
                         YourGrid.getEl().down('.x-grid-view').scroll('bottom', scrollPosition, true);
                         */
                      
                      // MyMessengerObject.ScrollPosition = MyMessengerObject.grid2.getEl().down('.x-grid-view').getScroll().top ; 
                      // MyMessengerObject.IsFirstLoad = true;
                   // }

                }
            });
            
            MyMessengerObject.grid.hide();
            MyMessengerObject.SeenMsg();
         //   setInterval(function () {MyMessengerObject.loadNotification()}, 1000);
           // MyMessengerObject.grid2.getView().scrollBy(0, 999999, true);
        });

    }

    MyMessenger.prototype.loadNotification = function () {
        
        Ext.Ajax.request({
                url: MyMessengerObject.address_prefix + 'ManageGroup.data.php',
                params:{
                    task: "GetNotNumber",
                    GID : MyMessengerObject.formpanel.down("[itemId=GID]").getValue()
                },
                method: 'POST',

                success: function(response,option){
                  
                    var st = Ext.decode(response.responseText);
                    if(st.success)
                    {
                        
                        if (st.data > 0) {
                            Ext.getCmp('btn1').show();
                            Ext.getCmp('btn1').setText("<div class='blueText MsgInfoBox2'  style='height:40px;width:40px' > " +
                                        "<div> " + st.data + " </div></div>");
                        }   
                        
                        diffPo = MyMessengerObject.grid2.getEl().down('.x-grid-view').getScroll().top  - MyMessengerObject.ScrollPosition ; 
                        
                        if(diffPo < 190 )
                        {
                            MyMessengerObject.grid2.getStore().load();                              
                        }                       
                    }
                    else
                    {
                        alert(st.data);
                    }
                },
                failure: function(){}
        });


    }

    MyMessenger.GroupTitleRender = function (v, p, r) {

        var res = v.split("-");
        var MsgNo = " ";

        if (res[2] > 0)
            MsgNo = "<div class='blueText MsgInfoBox'  style='height:30px;width:30px' > " +
                    "<div style= 'padding-top:6px' > " + res[2] + "</div></div>";

        return  "<table width=100%>" +
                "<tr><td width=100px><img src='" +
                MyMessengerObject.address_prefix + "ShowFile.php?source=GrpPic&GID=" + r.data.GID + "' " +
                " class=GrpPic></td>" +
                "<td class=GrpInfo>" +
                "<font style=font-size:12px;font-weight:bold;color:#666>" + res[0] + "</font><br><br>" +
                "<font style=font-size:12px;color:#666>" + Ext.String.ellipsis(res[1], 150) + "</font><br><br>" +
                "</td>" +
                "<td width=45px >" + MsgNo + "</td></tr></table>";
    }
    MyMessenger.renderMsg = function (value, p, record) {

        var ShowMsg = "";
        var ShowImg = "";
        var FullName = record.data.fname + " " + record.data.lname;
        var res = record.data.SendingDate.split(" ");
        var FullTxt = record.data.MSGID + ":" + FullName + ":" + value;
        var TextTime = res[1].substr(1, 4);

        if (record.data.FileType != "" && record.data.FileType != null) {

            var style = "";
            if (record.data.FileType == "jpg")
                style = "width='250px' height='250px'";
            else
                style = "width='50px' height='50px'";

            ShowImg = "<img src='" + MyMessengerObject.address_prefix + "ShowFile.php?source=ShowIcn&MSGID=" + record.data.MSGID +
                    "' " + style + " onclick='MyMessengerObject.DownloadFile(" + record.data.MSGID + " );' > ";
        }

        var MemberID = MyMessengerObject.formpanel.down("[itemId=MID]").getValue();
        
        if (record.data.MID == MemberID) {
            
            if (record.data.ParentMSGID > 0)
                ShowMsg = "<div style='background-color:#f2f0f0;width:98%;'> " + Ext.String.ellipsis(record.data.ParentMsg, 200) + " </div>";

            return  "<div class=' MyChatBox'  style='width:90%;float:right;margin:2px;' > " +
                    "<table style='width:100%'>" +
                    "<tr><td style='float:right;width:70%' ><font class='blueText'>" + FullName + "</font></td> " +
                    "<td title='عملیات' align='left' class='Expand' onclick='MyMessengerObject.MyOperationMenu(event," + record.data.MSGID + " );' " +
                    "style='float:left;width:30%;clear: left;background-repeat:no-repeat;" +
                    "background-position:right;cursor:pointer;width:30px;height:30' >&nbsp;</td></tr>" +
                    "</table>" +
                    "<div style='width:100%'>" + ShowMsg + ShowImg + "</div>" +
                    "<div style= 'padding-top:6px;align:right;width:100%' > " + value + "</div>" +
                    "<div align='left' style='width:100%'><font style='font-size:10px;' color='#275a87' >" + TextTime + " " + MiladiToShamsi(record.data.SendingDate) + "</font></div></div>";

        } else {

            if (record.data.ParentMSGID > 0)
                ShowMsg = "<div style='background-color:#edf8ff;width:98%;'> " + Ext.String.ellipsis(record.data.ParentMsg, 200) + " </div>";

            return   "<div class='ChatBox'  style='width:90%;float:left;margin-left:5px' >" +
                    "<table style='width:100%'>" +
                    "<tr><td style='float:right;width:70%' ><font class='blueText'>" + FullName + "</font></td>  " +
                    "<td title='عملیات' align='left' class='Expand' onclick='MyMessengerObject.OtherOperationMenu(event," + record.data.MSGID + " );' " +
                    "style='float:left;width:30%;clear: left;background-repeat:no-repeat;" +
                    "background-position:right;cursor:pointer;width:30px;height:30' >&nbsp;</td></tr>" +
                    "</table>" +
                    "<div style='width:100%'>" + ShowMsg + ShowImg + "</div>" +
                    "<div style= 'padding-top:6px;' > " + value + "</div>" +
                    "<div align='left'><font style='font-size:10px;' color='#275a87' >" + TextTime + " " + MiladiToShamsi(record.data.SendingDate) + "</font></div></div>";

        }

    }

    MyMessenger.renderSrchMsg = function (value, p, record) {

        return   "<div class='ChatBox'  style='width:70%;float:right;cursor:pointer;' onclick='MyMessengerObject.GoToMsg(" + record.data.MSGID + ");' >   " +
                "<div style= 'padding-top:6px;text-align:right' > " + value + "</div></div>";

    }

    MyMessenger.prototype.OtherOperationMenu = function (e, i) {

        var op_menu = new Ext.menu.Menu();

        op_menu.add({text: 'پاسخ دادن', iconCls: 'back',
            handler: function () {
                MyMessengerObject.ReplyMsg(i);
            }
        });

        op_menu.showAt(e.pageX - 120, e.pageY);
    };

    MyMessenger.prototype.ReplyMsg = function (i) {

        var index = MyMessengerObject.grid2.getStore().find('MSGID', i);
        fname = MyMessengerObject.grid2.getStore().getAt(index).data.fname;
        lname = MyMessengerObject.grid2.getStore().getAt(index).data.lname;
        message = MyMessengerObject.grid2.getStore().getAt(index).data.message; 
                 
        Ext.getCmp("Field2").show();
        MyMessengerObject.newItemPanel.down("[itemId=PersonName]").setValue(fname +' '+ lname);
        MyMessengerObject.newItemPanel.down("[itemId=PMsg]").setValue(message);
        MyMessengerObject.formpanel.down("[itemId=ParentMSGID]").setValue(i);
        MyMessengerObject.formpanel.down("[itemId=MsgTxt]").setValue("");

        return;
    }

    MyMessenger.prototype.MyOperationMenu = function (e, i) {

        var op_menu = new Ext.menu.Menu();

        op_menu.add({text: 'ویرایش پیام', iconCls: 'edit',
            handler: function () {
                MyMessengerObject.EditMsg(i);
            }
        });

        op_menu.add({text: 'حذف پیام', iconCls: 'remove',
            handler: function () {
                MyMessengerObject.DeleteMsg(i);
            }
        });

        op_menu.showAt(e.pageX - 120, e.pageY);
    };

    MyMessenger.prototype.EditMsg = function (i) {
       
        var index = MyMessengerObject.grid2.getStore().find('MSGID', i);
        fname = MyMessengerObject.grid2.getStore().getAt(index).data.fname;
        lname = MyMessengerObject.grid2.getStore().getAt(index).data.lname;
        message = MyMessengerObject.grid2.getStore().getAt(index).data.message; 
         
        Ext.getCmp("Field2").show();
        MyMessengerObject.formpanel.down("[itemId=MSGID]").setValue(i);
        MyMessengerObject.newItemPanel.down("[itemId=PersonName]").setValue(fname + lname);
        MyMessengerObject.newItemPanel.down("[itemId=PMsg]").setValue(message);
        MyMessengerObject.formpanel.down("[itemId=MsgTxt]").setValue(message);
        
        return;
    }

    MyMessenger.prototype.DeleteMsg = function (i) {

        var index = MyMessengerObject.grid2.getStore().find('MSGID', i);
        
        MyMessengerObject.formpanel.down("[itemId=MSGID]").setValue(i);
        Ext.MessageBox.confirm("", "آیا مایل به حذف می باشید؟", function (btn) {
            if (btn == "no")
                return;

            mask = new Ext.LoadMask(MyMessengerObject.newItemPanel, {msg: 'در حال ذخيره سازي...'});
            mask.show();

            Ext.Ajax.request({
                url: MyMessengerObject.address_prefix + 'ManageGroup.data.php',
                method: "POST",
                params: {
                    task: "DelMsg",
                    MsgId: MyMessengerObject.formpanel.down("[itemId=MSGID]").getValue()
                },
                success: function (response) {
                    mask.hide();
                    var st = Ext.decode(response.responseText);
                    if (st.data == "false")
                        alert('حذف امکان پذیر نمی باشد.');
                    else {
                        MyMessengerObject.grid2.getStore().load();
                        MyMessengerObject.grid2.getView().scrollBy(0, 999999, true);
                    }

                },
                failure: function () {}
            });

        });
    }

    MyMessenger.prototype.ClosePanel = function ()
    {
        Ext.getCmp("Field2").hide();
        MyMessengerObject.newItemPanel.down("[itemId=MsgTxt]").setValue("");
        MyMessengerObject.formpanel.down("[itemId=MSGID]").setValue("");
        return;
    }

    MyMessenger.prototype.SaveSendingMsg = function ()
    {

        if (MyMessengerObject.formpanel.down("[itemId=MsgTxt]").getValue() == "" &&
                MyMessengerObject.formpanel.down("[itemId=FileType]").getValue() == "")
        {
            Ext.MessageBox.alert("", "پیغامی/فایلی برای ارسال وجود ندارد.");
            return;
        }

        mask = new Ext.LoadMask(MyMessengerObject.newItemPanel, {msg: 'در حال ذخيره سازي...'});
        mask.show();
        MyMessengerObject.formpanel.getForm().submit(
                {
                    url: this.address_prefix + 'ManageGroup.data.php?task=SaveMsg',
                    /*params:{
                     MsgTxt: MyMessengerObject.newItemPanel.down("[itemId=MsgTxt]").getValue(),
                     FileType:MyMessengerObject.newItemPanel.down("[itemId=FileType]").getValue()
                     },*/
                    method: 'POST',
                    isUpload: true,
                    /* success: function (response, option) {
                     mask.hide();
                     if (response.responseText.indexOf("InsertError") != -1 ||
                     response.responseText.indexOf("UpdateError") != -1)
                     {
                     alert("عملیات مورد نظر با شکست مواجه شد");
                     return;
                     }
                     var st = Ext.decode(response.responseText);
                     if (st.success)
                     {
                     MyMessengerObject.newItemPanel.hide();
                     //MyMessengerObject.grid2.getStore().load();
                     MyMessengerObject.grid2.getView().scrollBy(0, 999999, true);
                     } else
                     {
                     alert(response.responseText);
                     }
                     },
                     failure: function () {} */
                    success: function (form, action) {
                        mask.hide();
                        if (action.result.success)
                        {                                                  
                            MyMessengerObject.grid2.getStore().load();
                            MyMessengerObject.grid2.getView().scrollBy(0, 999999, true);
                            MyMessengerObject.formpanel.down("[itemId=MsgTxt]").setValue("");
                            MyMessengerObject.formpanel.down("[itemId=FileType]").setValue("");
                            MyMessengerObject.formpanel.down("[itemId=MSGID]").setValue("");
                            Ext.getCmp("Field2").hide(); 
                        } else
                            Ext.MessageBox.alert("", "عملیات مورد نظر با شکست مواجه شد.");

                    },
                    failure: function (form, action) {
                        mask.hide();
                        Ext.MessageBox.alert("", action.result.data);
                    }

                });

    }
    
    MyMessenger.prototype.SeenMsg = function ()
    {               
        Ext.Ajax.request({
                url: MyMessengerObject.address_prefix + 'ManageGroup.data.php',
                params:{
                    task: "SeenMsg",
                    GID : MyMessengerObject.formpanel.down("[itemId=GID]").getValue(),                   
                },
                method: 'POST',
                success: function(response,option){
                  
                    var st = Ext.decode(response.responseText);
                    if(st.success)
                    {
                        Ext.getCmp('btn1').hide();                 
                    }
                    else
                    {
                        alert(st.data);
                    }
                },
                failure: function(){}
        });

    }

    MyMessenger.prototype.Searching = function () {

        if (!this.SearchPanel.down("[name=SearchTxt]").getValue())
        {
            Ext.MessageBox.alert("هشدار", "ورود عبارت جستجو الزامی می باشد.");
            return false;
        }

        this.searchGrid.getStore().proxy.extraParams.SearchTxt = this.SearchPanel.down("[name=SearchTxt]").getValue();
        this.searchGrid.getStore().proxy.extraParams.GID = MyMessengerObject.formpanel.down("[itemId=GID]").getValue();

        if (!this.searchGrid.rendered)
            this.searchGrid.render(this.get("SearchPanel"));
        else
            this.searchGrid.getStore().load();
    }

    MyMessenger.prototype.GoToMsg = function (v) {

        for (var i = 0; i < MyMessengerObject.grid2.getStore().data.length; i++)
        {
            var t = MyMessengerObject.grid2.getStore().data.items[i].data['MSGID'];
            if (t == v) {
                break;
            }

        }

        var record = MyMessengerObject.grid2.getStore().getAt(i);
        var el = MyMessengerObject.grid2.getView().getNode(record);
        MyMessengerObject.grid2.getSelectionModel().select(record);
        el.scrollIntoView();

        // rec = MyMessengerObject.grid2.getStore().data.items[i+1] ;
        // MyMessengerObject.grid2.getView().focusRow(rec);        
        return;
    }

    MyMessenger.prototype.DownloadFile = function (v) {
        window.open(MyMessengerObject.address_prefix + "ShowFile.php?source=FileMsg&MSGID=" + v);
    }


    var MyMessengerObject;

    Ext.onReady(function () {
        MyMessengerObject = new MyMessenger();
    });

</script>
