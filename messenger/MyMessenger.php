<?php
//-----------------------------
//	Date		: 1398.08
//-----------------------------
require_once '../header.inc.php';
require_once inc_dataGrid;
?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		
		<script type="text/javascript" src="/generalUI/ext5/ext-all-rtl.js"></script>	
		<script type="text/javascript" src="/generalUI/ext5/packages/ext-theme-neptune-touch/build/ext-theme-neptune-touch.js"></script>

		<link rel="stylesheet" type="text/css" href="/generalUI/ext5/packages/ext-theme-neptune-touch/build/resources/ext-theme-neptune-touch-all-rtl-debug.css" />	
		<link rel="stylesheet" type="text/css" href="/generalUI/icons/icons.css" />	
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
            .SrchChatBox { 
				background: radial-gradient(circle, #faeceb, #faeceb, #faeceb); 
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
            
function div(a, b) {
    return parseInt((a / b));
}
function MiladiToShamsi(g_y, g_m, g_d) {
    var g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    var j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
    var jalali = [];
    var gy = g_y - 1600;
    var gm = g_m - 1;
    var gd = g_d - 1;
 
    var g_day_no = 365 * gy + div(gy + 3, 4) - div(gy + 99, 100) + div(gy + 399, 400);
 
    for (var i = 0; i < gm; ++i)
        g_day_no += g_days_in_month[i];
    if (gm > 1 && ((gy % 4 == 0 && gy % 100 != 0) || (gy % 400 == 0)))
        /* leap and after Feb */
        g_day_no++;
    g_day_no += gd;
 
    var j_day_no = g_day_no - 79;
 
    var j_np = div(j_day_no, 12053);
    /* 12053 = 365*33 + 32/4 */
    j_day_no = j_day_no % 12053;
 
    var jy = 979 + 33 * j_np + 4 * div(j_day_no, 1461);
    /* 1461 = 365*4 + 4/4 */
 
    j_day_no %= 1461;
 
    if (j_day_no >= 366) {
        jy += div(j_day_no - 1, 365);
        j_day_no = (j_day_no - 1) % 365;
    }
    for (var i = 0; i < 11 && j_day_no >= j_days_in_month[i]; ++i)
        j_day_no -= j_days_in_month[i];
    var jm = i + 1;
    var jd = j_day_no + 1;
    jalali[0] = jy;
    jalali[1] = jm;
    jalali[2] = jd;
    return jalali;
    //return jalali[0] + "_" + jalali[1] + "_" + jalali[2];
    //return jy + "/" + jm + "/" + jd;
}
function get_year_month_day(date) {
    var convertDate;
    var y = date.substr(0, 4);
    var m = date.substr(5, 2);
    var d = date.substr(8, 2);
    convertDate = MiladiToShamsi(y, m, d);
    return convertDate;
}
function get_hour_minute_second(time) {
    var convertTime = [];
    convertTime[0] = time.substr(0, 2);
    convertTime[1] = time.substr(3, 2);
    convertTime[2] = time.substr(6, 2);
    return convertTime;
}
function convertDate(date) {
    var convertDateTime = get_year_month_day(date.substr(0, 10));
    convertDateTime = convertDateTime[0] + "/" + convertDateTime[1] + "/" + convertDateTime[2] + " " + date.substr(10);
    return convertDateTime;
}
function get_persian_month(month) {
    switch (month) {
        case 1:
            return "فروردین";
            break;
        case 2:
            return "اردیبهشت";
            break;
        case 3:
            return "خرداد";
            break;
        case 4:
            return "تیر";
            break;
        case 5:
            return "مرداد";
            break;
        case 6:
            return "شهریور";
            break;
        case 7:
            return "مهر";
            break;
        case 8:
            return "آبان";
            break;
        case 9:
            return "آذر";
            break;
        case 10:
            return "دی";
            break;
        case 11:
            return "بهمن";
            break;
        case 12:
            return "اسفند";
            break;
    }
}



			Ext.onReady(function () {
                
                var TopPos = 0 ; 

				var ResponsiveApp = ResponsiveApp || {};
				if (!ResponsiveApp.view) {
					ResponsiveApp.view = {}
				}
				if (!ResponsiveApp.view.main) {
					ResponsiveApp.view.main = {}
				}
				(Ext.cmd.derive("ResponsiveApp.Application", Ext.app.Application, {
					name: "ResponsiveApp"
				}, 0, 0, 0, 0, 0, 0, [ResponsiveApp, "Application"], 0));

				GroupTitleRender = function (v, p, r) {

					var res = v.split("-");
					var MsgNo = " ";

					if (res[2] > 0)
						MsgNo = "<div class='blueText MsgInfoBox'  style='height:30px;width:30px' > " +
								"<div style= 'padding-top:6px' > " + res[2] + "</div></div>";

					return  "<table width=100%>" +
							"<tr><td width=100px><img src='" +
							"ShowFile.php?source=GrpPic&GID=" + r.data.GID + "' " +
							" class=GrpPic></td>" +
							"<td class=GrpInfo>" +
							"<font style=font-size:12px;font-weight:bold;color:#666>" + res[0] + "</font><br><br>" +
							"<font style=font-size:12px;color:#666>" + Ext.String.ellipsis(res[1], 150) + "</font><br><br>" +
							"</td>" +
							"<td width=45px >" + MsgNo + "</td></tr></table>";
				}

				loadNotification = function () {

					Ext.Ajax.request({
						url: 'ManageGroup.data.php',
						params: {
							task: "GetNotNumber",
							GID: formpanel.down("[itemId=GID]").getValue()
						},
						method: 'POST',
						success: function (response, option) {

							var st = Ext.decode(response.responseText);
							if (st.success)
							{

								if (st.data > 0) {                                  
                                    
                                    if(TopPos == grid2.getEl().down('.x-grid-view').getScroll().top  )
                                    {
                                        store.load({
                                                callback : function(){
                                                    grid2.getView().scrollBy(0, 999999, true);                                                     
                                                }
                                            });    
                                        TopPos = grid2.getEl().down('.x-grid-view').getScroll().top ; 
                                    }  
                                    
                                    else {
                                        Ext.getCmp('btn1').show();
                                        Ext.getCmp('btn1').setText("<div class='blueText MsgInfoBox2'  style='height:30px;width:30px' > " +
                                                   "<div> " + st.data + " </div></div>"); 
                                    }
								}
                                
                                if( grid2.getEl().down('.x-grid-view').getScroll().top > TopPos )
                                    TopPos = grid2.getEl().down('.x-grid-view').getScroll().top ; 
                                
                                                              
							}
							else
							{
								alert(st.data);
							}
						},
						failure: function () {
						}
					});


				}

				renderMsg = function (value, p, record) {

					var ShowMsg = "";
					var ShowImg = "";
					var FullName = record.data.fname + " " + record.data.lname;
					var res = record.data.SendingDate.split(" ");
					var FullTxt = record.data.MSGID + ":" + FullName + ":" + value;
					var TextTime = res[1].substr(1, 4) ;
                    
					if (record.data.FileType != "" && record.data.FileType != null) {

						var style = "";
						if (record.data.FileType == "jpg")
							style = "width='250px' height='250px'";
						else
							style = "width='50px' height='50px'";

						ShowImg = "<img src='" + "ShowFile.php?source=ShowIcn&MSGID=" + record.data.MSGID +
								"' " + style + " onclick='DownloadFile(" + record.data.MSGID + " );' > ";
					}

					var MemberID = formpanel.down("[itemId=MID]").getValue();
                    
					if (record.data.MID == MemberID) {

						if (record.data.ParentMSGID * 1 > 0)
							ShowMsg = "<div style='background-color:#f2f0f0;width:98%;'> " + Ext.String.ellipsis(record.data.ParentMsg, 200) + " </div>";

						
						return  "<div class=' MyChatBox'  style='width:90%;float:right;margin:2px;' > " +
								"<table style='width:100%'>" +
								"<tr><td style='float:right;width:70%' ><font class='blueText'>" + FullName + "</font></td> " +
								"<td title='عملیات' align='left' class='Expand' onclick='MyOperationMenu(event," + record.data.MSGID + " );' " +
								"style='float:left;width:30%;clear: left;background-repeat:no-repeat;" +
								"background-position:right;cursor:pointer;width:30px;height:30' >&nbsp;</td></tr>" +
								"</table>" +
								"<div style='width:100%'>" + ShowMsg + ShowImg + "</div>" +
								"<div style= 'padding-top:6px;align:right;width:100%' > " + value + "</div>" +
								"<div align='left' style='width:100%'><font style='font-size:10px;' color='#275a87' >" + TextTime + " " + MiladiToShamsi(res[0].substr(0, 4), res[0].substr(5, 2), res[0].substr(8, 2)) + "</font></div></div>";

					} else {

						if (record.data.ParentMSGID > 0)
							ShowMsg = "<div style='background-color:#edf8ff;width:98%;'> " + Ext.String.ellipsis(record.data.ParentMsg, 200) + " </div>";

						return   "<div class='ChatBox'  style='width:90%;float:left;margin-left:5px' >" +
								"<table style='width:100%'>" +
								"<tr><td style='float:right;width:70%' ><font class='blueText'>" + FullName + "</font></td>  " +
								"<td title='عملیات' align='left' class='Expand' onclick='OtherOperationMenu(event," + record.data.MSGID + " );' " +
								"style='float:left;width:30%;clear: left;background-repeat:no-repeat;" +
								"background-position:right;cursor:pointer;width:30px;height:30' >&nbsp;</td></tr>" +
								"</table>" +
								"<div style='width:100%'>" + ShowMsg + ShowImg + "</div>" +
								"<div style= 'padding-top:6px;' > " + value + "</div>" +
								"<div align='left'><font style='font-size:10px;' color='#275a87' >" + TextTime + " " + MiladiToShamsi(res[0].substr(0, 4), res[0].substr(5, 2), res[0].substr(8, 2)) + "</font></div></div>";

					}

				}

				renderSrchMsg = function (value, p, record) {
                
					return   "<div class='SrchChatBox'  style='width:100%;float:right;cursor:pointer;' onclick='GoToMsg(" + record.data.MSGID + ");' >   " +
							 "<div style= 'padding-top:6px;text-align:right' > " + value + "</div></div>";

				}

				OtherOperationMenu = function (e, i) {

					var op_menu = new Ext.menu.Menu();

					op_menu.add({text: 'پاسخ دادن', iconCls: 'back',
						handler: function () {
							ReplyMsg(i);
						}
					});
                                       
					op_menu.showAt(e.pageX+1500, e.pageY);
				};

				ReplyMsg = function (i) {

					var index = grid2.getStore().find('MSGID', i);
					fname = grid2.getStore().getAt(index).data.fname;
					lname = grid2.getStore().getAt(index).data.lname;
					message = grid2.getStore().getAt(index).data.message;

					Field2 = Ext.getCmp("Field2");
					Field2.show();
					Field2.down("[itemId=PersonName]").setValue(fname + ' ' + lname);
					Field2.down("[itemId=PMsg]").setValue(message);
					formpanel.down("[itemId=ParentMSGID]").setValue(i);
					formpanel.down("[itemId=MsgTxt]").setValue("");
                    
					return;
				}

				MyOperationMenu = function (e, i) {

					var op_menu = new Ext.menu.Menu();

					op_menu.add({text: 'ویرایش پیام', iconCls: 'edit',
						handler: function () {
							EditMsg(i);
						}
					});

					op_menu.add({text: 'حذف پیام', iconCls: 'remove',
						handler: function () {
							DeleteMsg(i);
						}
					});

					op_menu.showAt(e.pageX +1200 , e.pageY);
				};

				EditMsg = function (i) {

					var index = grid2.getStore().find('MSGID', i);
					fname = grid2.getStore().getAt(index).data.fname;
					lname = grid2.getStore().getAt(index).data.lname;
					message = grid2.getStore().getAt(index).data.message;

					Field2 = Ext.getCmp("Field2");
					Field2.show();
					formpanel.down("[itemId=MSGID]").setValue(i);
					Field2.down("[itemId=PersonName]").setValue(fname + lname);
					Field2.down("[itemId=PMsg]").setValue(message);
					formpanel.down("[itemId=MsgTxt]").setValue(message);

					return;
				}

				DeleteMsg = function (i) {

					var index = grid2.getStore().find('MSGID', i);

					formpanel.down("[itemId=MSGID]").setValue(i);
					Ext.MessageBox.confirm("", "آیا مایل به حذف می باشید؟", function (btn) {
						if (btn == "no")
							return;

						mask = new Ext.LoadMask(formpanel, {msg: 'در حال ذخيره سازي...'});
						mask.show();

						Ext.Ajax.request({
							url: 'ManageGroup.data.php',
							method: "POST",
							params: {
								task: "DelMsg",
								MsgId: formpanel.down("[itemId=MSGID]").getValue()
							},
							success: function (response) {
								mask.hide();
								var st = Ext.decode(response.responseText);
								if (st.data == "false")
									alert('حذف امکان پذیر نمی باشد.');
								else {
									grid2.getStore().load();
									grid2.getView().scrollBy(0, 999999, true);
								}

							},
							failure: function () {
							}
						});

					});
				}

				ClosePanel = function ()
				{
					Field2 = Ext.getCmp("Field2");
					Field2.hide();                    
					Field2.down("[itemId=MsgTxt]").setValue("");
					formpanel.down("[itemId=MSGID]").setValue("");
					return;
				}

				SaveSendingMsg = function ()
				{

					if (formpanel.down("[itemId=MsgTxt]").getValue() == "" &&
							formpanel.down("[itemId=FileType]").getValue() == "")
					{
						Ext.MessageBox.alert("", "پیغامی/فایلی برای ارسال وجود ندارد.");
						return;
					}

					mask = new Ext.LoadMask(formpanel, {msg: 'در حال ذخيره سازي...'});
					mask.show();
					formpanel.getForm().submit(
							{
								url: 'ManageGroup.data.php?task=SaveMsg',								
								method: 'POST',
								isUpload: true,								
								success: function (form, action) {
									mask.hide();
									if (action.result.success)
									{
										store.load({
                                            callback : function(){
                                                grid2.getView().scrollBy(0, 999999, true);    
                                            }
                                        });
										formpanel.down("[itemId=MsgTxt]").setValue("");
										formpanel.down("[itemId=FileType]").setValue("");
										formpanel.down("[itemId=MSGID]").setValue("");
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

				SeenMsg = function ()
				{                    
					Ext.Ajax.request({
						url: 'ManageGroup.data.php',
						params: {
							task: "SeenMsg",
							GID: formpanel.down("[itemId=GID]").getValue(),
						},
						method: 'POST',
						success: function (response, option) {

							var st = Ext.decode(response.responseText);
							if (st.success)
							{
								Ext.getCmp('btn1').hide();
							}
							else
							{
								alert(st.data);
							}
						},
						failure: function () {
						}
					});

				}

				Searching = function () {                    

					if (!SearchPanel.down("[name=SearchTxt]").getValue())
					{
						Ext.MessageBox.alert("هشدار", "ورود عبارت جستجو الزامی می باشد.");
						return false;
					}

					searchGrid.getStore().proxy.extraParams.SearchTxt = SearchPanel.down("[name=SearchTxt]").getValue();
					searchGrid.getStore().proxy.extraParams.GID = formpanel.down("[itemId=GID]").getValue();
					searchGrid.getStore().load();
				}

				GoToMsg = function (v) {

					for (var i = 0; i < grid2.getStore().data.length; i++)
					{
						var t = grid2.getStore().data.items[i].data['MSGID'];
						if (t == v) {
							break;
						}

					}

					var record = grid2.getStore().getAt(i);
					var el = grid2.getView().getNode(record);
					grid2.getSelectionModel().select(record);
				//	el.scrollIntoView();

					 rec = grid2.getStore().data.items[i+1] ;
					 grid2.getView().focusRow(rec);        
					return;
				}

				DownloadFile = function (v) {
					window.open("ShowFile.php?source=FileMsg&MSGID=" + v);
				}

				var grid = new Ext.grid.GridPanel({
					region: "center",
					title: "پیام رسان سجا",
					selType: 'rowmodel',
					columns: [
						{menuDisabled: true, header: '', dataIndex: 'GID', sortable: '1', type: 'int', hidden: true, hideMode: 'display', searchable: true, emptyText: ''},
						{menuDisabled: true, header: '', dataIndex: 'MID', sortable: '1', type: 'int', hidden: true, hideMode: 'display', searchable: true, emptyText: ''},
						{flex: 1, menuDisabled: true, header: '', dataIndex: 'TM', sortable: '1', type: 'string', type: '', hidden: false, hideMode: 'display', renderer: GroupTitleRender, searchable: true, emptyText: ''}],
					store: Ext.create('Ext.data.Store', {
						pageSize: 25,
						fields: [{name: 'GID'}, {name: 'MID'}, {name: 'TM'}], remoteSort: true, proxy: {
							type: 'jsonp',
							url: '/messenger/ManageGroup.data.php?task=SelectMyMessage',
							form: '',
							reader: {
								root: 'rows',
								totalProperty: 'totalCount',
								messageProperty: 'message'
							}
						},
						sorters: [{
								property: '',
								direction: 'desc'
							}]
					}),
					scroll: 'vertical',
					columnLines: false,
					hideHeaders: true,
					autoWidth: true,
					autoHeight: true,
					viewConfig: {
						stripeRows: true,
						loadMask: true,
						enableTextSelection: true
					},
					multiSelect: false,
					listeners: {
						afterrender: function () {
							this.getStore().load();
						}
					}
				});

				grid.on("cellclick", function () {

					var record = grid.getSelectionModel().getLastSelected();

					Ext.getCmp("MainView").removeAll();
					Ext.getCmp("MainView").add(SearchPanel);
					Ext.getCmp("MainView").add(grid2);
					Ext.getCmp("MainView").add(formpanel);

					grid2.getStore().proxy.extraParams.GID = record.data.GID;
					formpanel.down("[itemId=GID]").setValue(record.data.GID);
					formpanel.down("[itemId=MID]").setValue(record.data.MID);
            					
                    store.load({
                        callback : function(){
                            //alert(1);
                            try {
                             //   alert(2);
                                //var r = grid2.getStore().data.length - 1 ; 
                                //grid2.getView().focusRow(r);  
                                  grid2.getView().scrollBy(0, 999999, true);  
                                //  alert(3);
                            }
                            catch(err) {
                               // alert('hiiiii');
                            }
                             
                            // alert(4);
                             
                        }
                    });
                                        
                    SeenMsg();
                    setInterval(function () {loadNotification()}, 1000);
					
				});

				var store = Ext.create('Ext.data.Store', {
					remoteSort: true,
					//buffered: true,
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
					//buffered: true,
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

				grid2 = new Ext.grid.GridPanel({
					region: 'center',
					store: store,
					//verticalScrollerType: 'paginggridscroller',
					disableSelection: true,
					//invalidateScrollerOnRefresh: false,
					hideHeaders: true,
					viewConfig: {
						stripeRows: false,
						trackOver: false,
						loadMask: false
					},
					columns: [
						{dataIndex: 'MSGID', hidden: true},
						{dataIndex: 'MID', hidden: true},
						{dataIndex: 'ParentMsg', hidden: true},
						{dataIndex: 'FileType', hidden: true},
						{dataIndex: 'ParentMSGID', hidden: true},
						{dataIndex: 'fname', hidden: true},
						{dataIndex: 'lname', hidden: true},
						{dataIndex: 'SendingDate', hidden: true},
						{
							header: '',
							dataIndex: 'message',
							renderer: function (v, p, r) {
								return renderMsg(v, p, r);
							},
							flex: 1,
							type: 'string'}],
					bbar: [{
							id: "Field2",
							xtype: "container",
							fieldLabel: "Field2",
							//style: 'width:95%;background-color:#edf9fd;border-right: 4px solid #bbdbed;border-left: 4px solid #bbdbed;border-top: 4px solid #bbdbed;border-radius: 25px;padding: 5px;',
							// width: 540,
                            style: 'background-color:#f5f7f6;font-size:13px !important;color:#FF0000 !important',
							hidden: true,
							layout: {
								type: "table",
								columns: 4
							},
							items: [
								{
									xtype: "container",
                                    style:'border-top: 3px solid #e8e8e8;',
									//width: 400,
									colspan: 4,
									html: "<div><img width='18px' height='18px' style='border:2px solid #bbdbed;border-radius: 5px;' align='top' src='../messenger/MsgDocuments/close.png' onclick='ClosePanel();' >&nbsp;</div>",
								},
								{
									xtype: "displayfield",
									name: "PersonName",
									itemId: "PersonName",                                     
                                    fieldCls: "blueText",
									colspan: 4
								},
								{
									xtype: "displayfield",
									name: "PMsg",
									itemId: "PMsg",
									style: 'width:95%;',	
                                    fieldCls: "blueText",
									colspan: 4,
									renderer: function (v) {
										return Ext.String.ellipsis(v, 80);
									}
								}]
						}],
					dockedItems: [{
						id: "Field3",
						xtype: 'toolbar',
						height: 40,
						dock: 'top',
						style : "border-bottom:1px solid blue",
						//hidden : true,
						items: [
							{
								xtype: 'button',
								id: 'btn1',
                                style: "border:0px;background: white !important;",
								hidden : true,
								handler: function ()
									{			                                        
                                        store.load({
                                            callback : function(){
                                                grid2.getView().scrollBy(0, 999999, true);    
                                            }
                                        });
										SeenMsg();
									}
							},'->',
                            {
								xtype: 'button',
								iconCls: "back",
								handler:
								function ()
								{									                                  
                                   window.location.href = "https://saja.krrtf.ir/messenger/MyMessenger.php";                                   
                                }
							},
							{
								xtype: 'button',
								iconCls: "down",
								handler:
								function ()
								{									
                                    store.load({
                                            callback : function(){
                                                //grid2.getView().scrollBy(0, 999999, true);    
                                               var r = grid2.getStore().data.length - 1 ;                                             
                                                grid2.getView().focusRow(r);
                                            }
                                    });
									SeenMsg();                                
								}
							}
						]
					}]

				});

				searchGrid = new Ext.grid.GridPanel({
                    region: 'north',
					store: SearchStore,
					//verticalScrollerType: 'paginggridscroller',					
					disableSelection: true,
                    hideHeaders: true,
					//invalidateScrollerOnRefresh: false,
					viewConfig: {
						stripeRows: false,
						trackOver: false,
						loadMask: true
					},
					columns: [{menuDisabled: true,dataIndex: 'MSGID',hidden: true},
                              { menuDisabled: true,flex: 1,dataIndex: 'message',
                                renderer: function (v, p, r) {
                                    return renderSrchMsg(v, p, r);
                                },							
                                type: 'string',
                                hideMode: 'display',
                                searchable: false,
                                emptyText: ''}
                             ]

				});

				SearchPanel = new Ext.form.Panel({
					title: "جستجو",
					region: "north",
					autoScroll: true,
					collapsible: true,
					collapsed: true,
					autoHeight: true,
					maxHeight: 400,
					anchor: "100%",
					layout: {
						type: "table",
						columns: 2
					},
					//style: "padding-right:0px;align:right",
					items: [
						{
							xtype: "textfield",
							name: "SearchTxt",
							itemId: "SearchTxt",
							style: "width:90%;margin:2px;"
									//fieldCls: "rcorners2",
									// width: 300
						},
						{
							xtype: "container",
							style: "margin-left:20px;",
							html: "<div><img align='right' width='40px' height='40px' src='../messenger/MsgDocuments/search.png' " +
									" onclick='Searching();' style='cursor:pointer;'>&nbsp;</div>",
						},
						{
							xtype: "container",
							colspan: 2,
							//  width: 410,
							//style: "text-align:right",
							items: [searchGrid]
						}

					]
				});

				formpanel = new Ext.form.Panel({
					region: 'south',
					height: 85,
					layout: {
						type: "table",
						columns: 3
					},
					//style: "margin:0px 0 1px",
					border: false,
					//style: "padding-right:10px;",
					items: [{
							xtype: "textarea",
							name: "MsgTxt",
							itemId: "MsgTxt",
							rows: 1,
							style: "width:95%;margin:9px;"								
                          
                            //fieldCls: "rcorners2",
						}
						,
						{
							xtype: "filefield",
							name: "FileType",
							width: 40,
							buttonOnly: true,
							itemId: 'FileType',
							buttonConfig: {
								iconCls: 'attachFile',
								text: '',
								iconAlign: 'center',
								style: 'background: #ffffff;border:0;margin-top:10px',
								scale: 'large'
							},
                            listeners : {
                                change: function(f,new_val) {                                     
                                                                      
                                    Field2 = Ext.getCmp("Field2");
                                    Field2.show();                                   
                                    Field2.down("[itemId=PMsg]").setValue(formpanel.down("[name=FileType]").getValue());
                                                      
                               }                                                                    
                            }
						},
						{
							xtype: 'button',
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
										SaveSendingMsg();
									}
						}, {
							xtype: "hidden",
							name: "MSGID",
							itemId: "MSGID"
						},
						{
							xtype: "hidden",
							name: "GID",
							itemId: "GID"
						},
						{
							xtype: "hidden",
							name: "MID",
							itemId: "MID"
						},
						{
							xtype: "hidden",
							name: "ParentMSGID",
							itemId: "ParentMSGID"
						}

					]

				});

				(Ext.cmd.derive("ResponsiveApp.view.main.Main", Ext.container.Viewport, {
					//ui: "navigation",
					id: "MainView",
					layout: 'border',
					rtl: true,
					responsiveConfig: {
						tall: {
							headerPosition: "top"
						},
						wide: {
							headerPosition: "left"
						}
					},
					items: [grid]
				}, 0, ["app-main"], ["app-main", "box", "component", "container", "panel", "tabpanel"], {
					"app-main": true,
					box: true,
					component: true,
					container: true,
					panel: true,
					tabpanel: true
				}, ["widget.app-main"], 0, [ResponsiveApp.view.main, "Main"], 0));


				Ext.application({
					name: "ResponsiveApp",
					extend: "ResponsiveApp.Application",
					autoCreateViewport: "ResponsiveApp.view.main.Main"
				});

				/*  new Ext.tab.Panel({
				 
				 title : "alskjdaslkjdajdkasj",
				 renderTo : document.body,
				 plugins: "responsive",
				 responsiveConfig: {
				 wide: {
				 iconAlign: "left",
				 textAlign: "left"
				 },
				 tall: {
				 iconAlign: "top",
				 textAlign: "center",
				 width: 120
				 }
				 }
				 items: [
				 { title: 'Foo' , html : " سیمنبت سمینتب سمینت بکسنمیتب نسمیتب نسیتب کنمسیتب نمکسیتب منکتب " },
				 { title: 'Bar' , html : "klsdjdfaskljdo;iweurioquwieo qwo;e iqwopei qwopie opqwei "}
				 ] });*/
			});

		</script>
	</head>
	<body>
	</body>
</html>
