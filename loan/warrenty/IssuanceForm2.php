<?php
//-----------------------------
//	Programmer	: Mokhtari
//	Date		: 99.05
//-----------------------------
require_once '../../header.inc.php';
require_once '../request/request.class.php';

function ListDate($BID = "") {
 
	if( $BID > 0 ) 
	{
		$qry = " select PersonID ,BailType  from LON_BailCondition where BID = ? "; 
		$res = PdoDataAccess::runquery($qry,array($BID)) ;
		$personid = $res[0]['PersonID'];
		$TypeID = $res[0]['BailType'];
	}
	else 
	{
		$personid = $_REQUEST["PersonID"];
		$TypeID = $_REQUEST["TypeID"];
	}
	
	$query0 = "select concat_ws(' ',fname,lname,CompanyName) fullname		
			from BSC_persons p
			where PersonID=" . $personid;

	$query3 = "select bf.InfoDesc TypeDesc 
			from BaseInfo bf 
			where TypeID=74 AND InfoID=" . $TypeID;

	$query1 = "select r.* , concat_ws(' ',fname,lname,CompanyName) fullname, 
				bf.InfoDesc TypeDesc ,
				BranchName
			from WAR_requests r 
				left join BSC_branches using(BranchID)
				left join BSC_persons p using(PersonID)
				left join BaseInfo bf on(bf.TypeID=74 AND InfoID=r.TypeID)
			where StatusID != 100 AND PersonID=" . $personid;

	$query2 = "select r.* 
			from LON_requests r 
			where (StatusID=70 OR StatusID=95 OR StatusID=101) AND LoanPersonID=" . $personid;
	$temp0 = PdoDataAccess::runquery($query0);
	$temp1 = PdoDataAccess::runquery($query1);
	$temp2 = PdoDataAccess::runquery($query2);
	$temp3 = PdoDataAccess::runquery($query3);

	foreach ($_POST as $key => $value) {
		$exp_key = explode('_', $key);

		if ($exp_key[0] == 'GuarType') {
			$GuarType[] = $value;
			$TypeItem[] = $exp_key[1];
		}
		if ($exp_key[0] == 'GuarAmount') {
			$GuarAmount[] = $value;
			$AmountItem[] = $exp_key[1];
		}
	}
	$GuarCount = count($TypeItem);

	$personName = (!empty($temp0)) ? $temp0[0]['fullname'] : '';
	$warType = (!empty($temp3)) ? $temp3[0]['TypeDesc'] : '';
	$tempCount1 = count($temp1);
	$tempCount2 = count($temp2);
	$rowspan1 = 20 + $tempCount1 + $tempCount2 + $GuarCount;
	$rowspan2 = 2 + $tempCount1;
	$rowspan3 = 2 + $tempCount2;
	$rowspan4 = 2 + $GuarCount;


	BeginReport();
	echo "<div style=display:none>" . PdoDataAccess::GetLatestQueryString() . "</div>";
	echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
					فرم صدور ضمانتنامه
				</td>
				
				<td width='200px' align='center' style='font-family:tahoma;font-size:11px'>کد فرم: KF-2030200F01/00
				<br>
				تاریخ تهیه گزارش : "
	. DateModules::shNow() . "<br>";

	echo "</td></tr></table>";
	
	$qry = " select bc.*,case li.KnowledgeBase when 0 then 'خیر' when 1 then 'بلی' end nb , 
					case li.EmpType when 1 then 'دولتی' when 2 then 'خصوصی'  when 3 then 'نهاد عمومی غیردولتی' when 4 then 'اجرایی'   end et 
					
			 from LON_BailCondition bc 
						 left join LON_IssuanceInfo li  on li.BID = bc.BID
			 where bc.BID = ? ";

			$ResParams = PdoDataAccess::runquery($qry, array($BID));
	?>


	<style>
		.newTable  td, th {
			border: 1px solid #dddddd;
			text-align: center;
			padding: 8px;
		}
		.newTable  th {
			color: rgb(21, 66, 139);
			background-color: rgb(189, 211, 239);
		}
		.newTable  td{
			text-align: center;
		}
		.newTable table{
			border:2px groove #9BB1CD;border-collapse:collapse;width:100%;font-family: nazanin;
			font-size: 16px;line-height: 20px;
		}
		.verticaltext {
			transform: rotate(-90deg);
			transform-origin: right, top;
			-ms-transform: rotate(-90deg);
			-ms-transform-origin:right, top;
			-webkit-transform: rotate(-90deg);
			-webkit-transform-origin:right, top;
			position: absolute;
			right: -670px;
			width: 100%;
			/*color: #ed217c;*/
		}

	</style>
	<div class="newTable">

		<table>
			<tr>
				<td style="width: 40px;" rowspan="<?= $rowspan1 ?>"><span class="verticaltext"><b>این قسمت توسط واحد ارزیابی و نظارت تکمیل می‌گردد</b></span></td>
				<td rowspan="11" ><span><b>شرح درخواست</b></span></td>
			</tr>
			<tr>
				<td><span><b>نام شخص حقیقی/ حقوقی</b></span></td>
				<td colspan="5"><span style="width: 500px;"><?= $personName ?></span></td>
			</tr>
			<tr>
				<td><span><b>نوع ضمانتنامه</b></span></td>
				<td colspan="5"><span><?= $warType ?></span></td>
			</tr>
			<tr>
				<td><span><b>موضوع قرارداد</b></span></td>
				<td colspan="5"><span><?= $_POST['contractTitle'] ?></span></td>
			</tr>
			<tr>
				<td><span><b>شرکت/ سازمان ضمانت‌گیرنده</b></span></td>
				<td colspan="5"><span><?= $_POST['organization'] ?></span></td>
			</tr>
			<tr>
				<td><span><b>مبلغ ضمانتنامه</b></span></td>
				<td colspan="5"><span><?= number_format($_POST['warAmount']) ?></span></td>
			</tr>
			<tr>
				<td><span><b>مدت ضمانتنامه</b></span></td>
				<td colspan="5"><span><?= $_POST['warDate'] ?></span></td>
			</tr>
			<tr>
				<td><span><b>وثایق پیشنهادی</b></span></td>
				<td colspan="5"><span><?= $_POST['ProposedGuarantee'] ?></span></td>
			</tr>			
			<tr>
				<td><span><b>توضیح تکمیلی</b></span></td>
				<td colspan="5"><span><?= $_POST['SupplementaryExplanation'] ?></span></td>
			</tr>
			<tr>
				<td><span><b>نوع کارفرما</b></span></td>
				<td colspan="5"><span><?= $_POST['EmpType'] /* $ResParams[0]['et']*/  ?></span></td>
			</tr>
			<tr>
				<td><span><b>آیا شرکت دانش بنیان می باشد؟</b> </span></td>
				<td colspan="5"><span><?= ( $_REQUEST['KNB'] == true ) ? "بلی" : "خیر"  /*$ResParams[0]['nb']*/  ?></span></td>
			</tr>			
			<tr>
				<td><span><b>مدارک پیوست فرم</b></span></td>
				<td colspan="5"><span>1-نامه رسمی درخواست متقاضی</span><span>2-نامه رسمی درخواست ضمانت‌گیرنده</span></td>
			</tr>

			<!--warrenty History-->
			<tr>
				<td rowspan="<?= $rowspan2 ?>"><b>سوابق ضمانت‌نامه‌های دریافتی</b></td>
				<th><span>سال دریافت</span></th>
				<th><span>عنوان ضمانتنامه</span></th>
				<th><span>ضمانت‌گیرنده</span></th>
				<th><span>مبلغ (ریال)</span></th>
				<th><span>تاریخ ابطال</span></th>
				<th><span>مبلغ فعال (ریال)</span></th>
			</tr>

			<?php
			$activeAmount = 0;
			$totActiveAmount = 0;
			foreach ($temp1 as $temp) {
				$today = date("Y-m-d");
				$activeAmount = ($temp['StatusID'] == 130 || $temp['EndDate'] < $today ) ? "0" : $temp['amount'];
				$totActiveAmount += $activeAmount;
				?>
				<tr>
					<td><span><?= DateModules::miladi_to_shamsi($temp['StartDate']) ?></span></td>
					<td><span><?= $temp['TypeDesc'] ?></span></td>
					<td><span><?= $temp['organization'] ?></span></td>
					<td><span><?= number_format($temp['amount']) ?></span></td>
					<td><span><?= DateModules::miladi_to_shamsi($temp['EndDate']) ?></span></td>
					<td><span><?= number_format($activeAmount) ?></span></td>
				</tr>
				<?php
			}
			?>
			<tr>
				<td colspan="5"><span><b>جمع ضمانتنامه‌های دريافتي</b></span></td>
				<td><span><b><?= number_format($totActiveAmount) ?></b></span></td>
			</tr>

			<!--loan History-->
			<tr>
				<td rowspan="<?= $rowspan3 ?>"><span><b>سوابق تسهیلات دریافتی</b></span></td>
				<th><span>سال دریافت</span></th>
				<th><span>نوع تسهیلات</span></th>
				<th colspan="2"><span>مبلغ (ریال)</span></th>
				<th colspan="2"><span>مبلغ مانده (ریال)</span></th>
			</tr>

			<?php
			$totPartAmount = 0;
			$totTotalRemain = 0;
			foreach ($temp2 as $temp) {
				$RequestID = $temp["RequestID"];
				$ReqObj = new LON_requests($RequestID);
				$partObj = LON_ReqParts::GetValidPartObj($RequestID);
				$ComputeArr = LON_Computes::ComputePayments($RequestID, "", null, true);
				$TotalRemain = LON_Computes::GetTotalRemainAmount($RequestID, $ComputeArr);

				$totPartAmount += $partObj->PartAmount;
				$totTotalRemain += $TotalRemain;
				?>
				<tr>
					<td><span><?= DateModules::miladi_to_shamsi($partObj->PartDate) ?></span></td>
					<td><span><?= $ReqObj->_LoanDesc ?></span></td>
					<td colspan="2"><span><?= number_format($partObj->PartAmount) ?></span></td>
					<td colspan="2"><span><?= number_format($TotalRemain) ?></span></td>
				</tr>
				<?php
			}
			?>
			<tr>
				<td colspan="2"><span><b>جمع کل تسهیلات دريافتي</b></span></td>
				<td colspan="2"><span><b><?= number_format($totPartAmount) ?></b></span></td>
				<td colspan="2"><span><b><?= number_format($totTotalRemain) ?></b></span></td>
			</tr>
			<!--required guarantee-->
			<tr>
				<td rowspan="<?= $rowspan4 ?>"><span><b>وثایق موردنیاز</b></span></td>
				<th colspan="1"><span>ردیف</span></th>
				<th colspan="3"><span>نوع وثیقه</span></th>
				<th colspan="2"><span>ارزش وثیقه (ریال)</span></th>
			</tr>
			<?php
			if (count($TypeItem) > 0 || count($AmountItem) > 0) {
				$num = count($TypeItem);
				for ($x = 0; $x < $num; $x++) {
					$i = $x + 1;
					?>

					<tr>
						<td><span><?= $i ?></span></td>
						<td colspan="3"><span><?= $GuarType[$x] ?></span></td>
						<td colspan="2"><span><?= number_format($GuarAmount[$x]) ?></span></td>
					</tr>
					<?php
				}
			} else {
				
			}

			
			?>

			<tr>
				<td colspan="4"><span><b>جمع کل وثایق</b></span></td>
				<td colspan="3"><span><b><?= number_format(array_sum($GuarAmount)) ?></b></span></td>
			</tr>

			<!--additional comments and suggestions-->
			<tr style="height: 100px;">
				<td><b>نظرات کارشناس مالی</b></td>
				<td colspan="6"><span><?= $_POST['CommentSuggest'] ?></td>
			</tr>
			<!--additional comments and suggestions-->
			<tr style="height: 100px;">
				<td><b>نظرات مسئول مالی</b></td>
				<td colspan="6"><span><?= $_POST['CommentSuggest2'] ?></td>
			</tr>
			<!--Bail Conditions-->
			<tr>
				<td colspan="7" style="text-align: right" ><span> 
						<? if($ResParams[0]['param1'] == 1 ){
						echo "<img src='/framework/icons/tick.gif' style='width:15px'>";

						}
						else 
						echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ";
						?>
						مبلغ ضمانت‌نامه‌های درخواستی کمتر از 80% سرمایه صندوق است						
						<br>
						<? if($ResParams[0]['param2'] == 1 ){
						echo "<img src='/framework/icons/tick.gif' style='width:15px'>";

						}
						else 
						echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ";
						?>

						مجموع ضمانت‌نامه‌های فعال شرکت از 50% سرمایه صندوق کمتر است
						<br>
						<? if($ResParams[0]['param3'] == 1 ){
						echo "<img src='/framework/icons/tick.gif' style='width:15px'>";

						}
						else 
						echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ";
						?>
						مجموع ضمانت‌نامه‌های صادره فعال صندوق کمتر از 12 برابر سرمایه صندوق است. 
						<br>
						<? if($ResParams[0]['param4'] == 1 ){
						echo "<img src='/framework/icons/tick.gif' style='width:15px'>";

						}
						else 
						echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ";
						?>
						مجموع ضمانت‌نامه‌های فعالی که یکی از ضامنین آن هیات‌مدیره شرکت درخواست‌کننده است، کمتر از 15 میلیاردریال است.
						<br>
						<? if($ResParams[0]['param5'] == 1 ){
						echo "<img src='/framework/icons/tick.gif' style='width:15px'>";

						}
						else 
						echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ";
						?>
						تعداد ضمانت‌نامه‌های صادره برای هر ضمانت‌خواه حداکثر 30% تعداد کل ضمانت‌نامه‌های صادره باشد. 
						<br>
						</td>
						</tr>
						<tr>
							<td><span><b>تکمیل‌کننده</b></span></td>
							<td colspan="3" style="text-align: right"><span>نام و نام خانوادگی:</span></td>
							<td colspan="3" style="text-align: right"><span>تاریخ و امضا:</span></td>
						</tr>
						<tr>
							<td colspan="2"><span><b>نظر واحد حسابداری</b></span></td>
							<td colspan="3" style="text-align: right"><span></span></td>
							<td colspan="3" style="text-align: right">تاریخ و امضا:</td>

						</tr>
						<tr>
							<td colspan="2"><span><b>نظر مدیرعامل</b></span></td>
							<td colspan="3" style="text-align: right"><span></span></td>
							<td colspan="3" style="text-align: right">تاریخ و امضا:</td>
						</tr>
		</table>
	</div>

	<?php
	die();
}

if (isset($_REQUEST["show"])) {

	ListDate($_REQUEST['BID']);
}

if(!empty($_REQUEST["ObjID"]) && $_REQUEST["ObjID"] > 0 )
{
	
	$qry = " select li.* , b.PersonID , b.BailType BailType , b.subject, b.LetterNo, 
		bi.BailType BailType2, bi.BailValue
				from LON_IssuanceInfo li
						  inner join  LON_BailCondition b on li.BID = b.BID 
						  left join   LON_BailInfo bi on bi.BID = li.BID
						  
			 where li.IID = ? " ; 
	$resInfo = PdoDataAccess::runquery($qry,array($_REQUEST["ObjID"])) ; 
	 
}
	

/* require_once getenv("DOCUMENT_ROOT") . '/framework/ReportDB/Filter_person.php'; */
?>
<script>
    WarrentyReport_total.prototype = {
        TabID: '<?= $_REQUEST["ExtTabID"] ?>',
        BID: '<?= (!empty($_POST['BID']) ? $_POST['BID'] : $resInfo[0]['BID'] ) ?>',
        LNO: "<?= (!empty($_REQUEST["LetterNo"]) ? $_REQUEST["LetterNo"] : $resInfo[0]['LetterNo'] ) ?>",
		ObjID :  "<?= $_REQUEST["ObjID"] ?>",
		PID : "<?= (!empty($_REQUEST["PID"]) ? $_REQUEST["PID"] : $resInfo[0]["PersonID"] ) ?>", 	
		PST: "<?= ( (!empty($_REQUEST["PST"]) && $_REQUEST["PST"] =='خام') ? '1' :  '0' )?>",
        address_prefix: "<?= $js_prefix_address ?>",
        pageIndex: 1,
        get: function (elementID) {
            return findChild(this.TabID, elementID);
        }
    }

    WarrentyReport_total.prototype.showReport = function (btn, e)
    {
		//alert(WarrentyReport_totalObj.formPanel.down('[itemId=KnowledgeBase]').getValue());
        this.form = this.get("mainForm")
        this.form.target = "_blank";
        this.form.method = "POST";
        this.form.action = this.address_prefix + "IssuanceForm2.php?show=true&KNB="+ WarrentyReport_totalObj.formPanel.down('[itemId=KnowledgeBase]').getValue() +"&BID=" + this.BID;
        this.form.submit();
        this.get("excel").value = "";
        return;
    }

    function WarrentyReport_total()
    {		
        this.formPanel = new Ext.form.Panel({
            renderTo: this.get("main"),
            frame: true,
            layout: {
                type: "table",
                columns: 2
            },
            defaults: {
                width: 365
            },
            bodyStyle: "text-align:right;padding:5px",
            title: "فرم صدور ضمانت نامه",
            width: 780,
            items: [{
                    xtype: "combo",
                    store: new Ext.data.SimpleStore({
                        proxy: {
                            type: 'jsonp',
                            url: this.address_prefix + '../../framework/person/persons.data.php?' +
                                    "task=selectPersons&UserType=IsCustomer",
                            reader: {root: 'rows', totalProperty: 'totalCount'}
                        },
                        fields: ['PersonID', 'fullname']
                    }),
                    fieldLabel: "نام شخص حقيقي/حقوقي",
                    displayField: "fullname",
                    pageSize: 20,
                    valueField: "PersonID",
                    name: "PersonID",	
					allowBlank: false,
                    itemId: "PersonID"
                }, {
                    xtype: "combo",
                    store: new Ext.data.Store({
                        proxy: {
                            type: 'jsonp',
                            url: this.address_prefix + 'request.data.php?task=GetWarrentyTypes',
                            reader: {root: 'rows', totalProperty: 'totalCount'}
                        },
                        fields: ["InfoID", "InfoDesc"],
                        autoLoad: true
                    }),
                    displayField: 'InfoDesc',
                    valueField: "InfoID",
                    name: "TypeID",
                    itemId: "TypeID",
                    allowBlank: false,
                    fieldLabel: "نوع ضمانتنامه"
                }, {
                    xtype: "textfield",
                    name: "contractTitle",
                    fieldLabel: "موضوع قرارداد",
                    itemId: "contractTitle",
					 allowBlank: false
                }, {
                    xtype: "textfield",
                    name: "organization",
                    fieldLabel: "سازمان مربوطه",
					allowBlank: false,
					itemId: "organization"
                }, {
                    xtype: "numberfield",
                    name: "warAmount",
					itemId: "warAmount",
					allowBlank: false,
                    hideTrigger: true,
                    fieldLabel: "مبلغ ضمانت نامه"
                }, {
                    xtype: "textfield",
                    name: "warDate",
					allowBlank: false,
					itemId: "warDate",
                    fieldLabel: "مدت ضمانتنامه"
                }, {
                    xtype: "textfield",
                    fieldLabel: "وثايق پيشنهادي",
					allowBlank: false,
                    name: "ProposedGuarantee",
					itemId: "ProposedGuarantee"
                }, {
                    xtype: "textfield",
                    fieldLabel: "توضيح تكميلي",
					allowBlank: false,
                    name: "SupplementaryExplanation",
					itemId: "SupplementaryExplanation"
                }, {
                    xtype: "numberfield",
                    fieldLabel: "شماره نامه",
					hideTrigger: true,
					allowBlank: false,
                    itemId: "LetterNo",
                    name: "LetterNo",
                }, {
                    xtype: "displayfield",
                    hideTrigger: true,
					allowBlank: false,
                    labelWidth: 50,
                    width: 80,
                    fieldCls: "blueText",
                    value: "<a href=javascript:void() onclick='WarrentyReport_totalObj.OpenLetter(" + this.LNO + ")';> <img src=/office/icons/attach.gif> </a>"

                }, {
                    xtype: 'checkbox',
                    boxLabel: 'آیا شرکت دانش بنیان می باشد؟',
					allowBlank: false,
                    style: 'margin:10px',
                    name: 'KnowledgeBase',
					itemId: 'KnowledgeBase'
                },
                {
                    xtype: "combo",
                    fieldLabel: "نوع کارفرما",
                    store: new Ext.data.SimpleStore({
                        data: [
                            ['1', "دولتی"],
                            ['2', "خصوصی"],
							['3', "نهاد عمومی غیردولتی"],
							['4', "اجرایی"]
                        ],
                        fields: ['id', 'value']
                    }),
                    displayField: "value",
					allowBlank: false,
                    valueField: "id",
                    name: "EmpType",
					itemId: "EmpType"
                },
                {
                    xtype: "fieldset",					
                    title: "وثایق موردنیاز",
                    layout: "column",
					itemId : "fs_Guars",
                    columns: 2,
                    colspan: 2,
                    width: 730,
                    items: [{
                            xtype: "displayfield",
                            hideTrigger: true,							
                            labelWidth: 50,
                            width: 80,
                            fieldCls: "blueText",
                            value: "وثیقه [ 1 ]"
                        }, {
                            xtype: "textfield",
							allowBlank: false,
                            width: 300,
                            fieldLabel: "نوع وثیقه",
                            name: "GuarType_1",
							itemId: "GuarType_1"
                        },
                        {
                            xtype: "numberfield",
                            width: 300,
							allowBlank: false,
							hideTrigger: true,	
                            fieldLabel: "ارزش وثیقه(به ریال)",
                            name: "GuarAmount_1",
							itemId: "GuarAmount_1"
                        }, {
                            xtype: "button",
                            text: "افزودن وثیقه",
                            colspan: 2,
                            iconCls: "add",
                            handler: function () {
                                me = WarrentyReport_totalObj;
                                me.pageIndex++;
                                fs = this.up("fieldset");
                                fs.insert(fs.items.length - 1, [{
                                        xtype: "displayfield",
                                        hideTrigger: true,
                                        labelWidth: 50,
                                        width: 80,
                                        fieldCls: "blueText",
                                        value: "وثیقه [ " + me.pageIndex + " ]"
                                    }, {
                                        xtype: "textfield",
                                        width: 300,
                                        fieldLabel: "نوع وثیقه",
                                        name: "GuarType_" + me.pageIndex
                                    }, {
                                        xtype: "textfield",
                                        width: 300,
                                        fieldLabel: "ارزش وثیقه(به ریال)",
                                        name: "GuarAmount_" + me.pageIndex
                                    }]);
                            }
                        }] 
                }, {
                    xtype: "textarea",
                    width: 700,
                    colspan: 2,
                    fieldLabel: "نظرات کارشناس مالی",
					allowBlank: false,
                    name: "CommentSuggest",
					itemId: "CommentSuggest"
                }
				, {
                    xtype: "textarea",
                    width: 700,
                    colspan: 2,
                    fieldLabel: "نظرات مسئول مالی",
					allowBlank: false,
                    name: "CommentSuggest2",
					itemId: "CommentSuggest2"
                }

            ],
            buttons: [{
                    text: (( this.PST ==  '1' ) ? "ذخیره / ارسال" : "ذخیره" ) ,
                    iconCls: "save",
                    handler: function () {

                        mask = new Ext.LoadMask(WarrentyReport_totalObj.formPanel, {msg: 'در حال ذخیره ...'});
                        mask.show();
                        WarrentyReport_totalObj.formPanel.getForm().submit({
                            clientValidation: true,
                            url: WarrentyReport_totalObj.address_prefix + 'ManageWarrentyReq.data.php?task=SaveIssuanceInfo',
                            method: "POST",
                            params: {
                                BID: WarrentyReport_totalObj.BID
                            },
                            success: function (form, action) {

                                mask.hide();
                                /*	if(action.result.success)
                                 {
                                 WelfareCentersObject.grid.getStore().load();
                                 WelfareCentersObject.formPanel.hide();
                                 }												
                                 else
                                 alert("عملیات مورد نظر با شکست مواجه شد.");
                                 
                                 WelfareCentersObject.formPanel.hide(); */
                            },
                            failure: function () {
                                mask.hide();
                            }
                        });
                    }
                },
                {
                    text: "چاپ ضمانتنامه",
                    handler: Ext.bind(this.showReport, this),
                    iconCls: "report"
                }]
        });

        this.formPanel.getEl().addKeyListener(Ext.EventObject.ENTER, function (keynumber, e) {

            WarrentyReport_totalObj.showReport();
            e.preventDefault();
            e.stopEvent();
            return false;
        });

		if(this.ObjID > 0 ) 
		{		
			
			this.formPanel.down('[itemId=PersonID]').setValue(this.PID);
			//this.formPanel.down('[itemId=PersonID]').getStore().load();
		
		this.formPanel.down("[name=PersonID]").getStore().load({
			params : {
				PersonID : this.PID
			},
			callback : function(){
			
				if(this.getCount() > 0)
					WarrentyReport_totalObj.formPanel.down("[name=PersonID]").setValue(this.getAt(0).data.PersonID);
			}
		});
	
			this.formPanel.down('[itemId=TypeID]').setValue("<?= $resInfo[0]["BailType"] ?>");
		//	this.formPanel.down('[itemId=TypeID]').getStore().load();

			this.formPanel.down('[itemId=contractTitle]').setValue("<?= $resInfo[0]["subject"] ?>");
			this.formPanel.down('[itemId=LetterNo]').setValue("<?= $resInfo[0]["LetterNo"] ?>");
						
			this.formPanel.down('[itemId=organization]').setValue("<?= $resInfo[0]["RelatedOrg"] ?>");
			this.formPanel.down('[itemId=warAmount]').setValue("<?= $resInfo[0]["amount"]?>");
			this.formPanel.down('[itemId=warDate]').setValue("<?= $resInfo[0]["duration"] ?>");
			this.formPanel.down('[itemId=ProposedGuarantee]').setValue("<?= $resInfo[0]["SugBail"] ?>");		
			this.formPanel.down('[itemId=SupplementaryExplanation]').setValue("<?= $resInfo[0]["comments"] ?>");		
			this.formPanel.down('[itemId=KnowledgeBase]').setValue("<?= $resInfo[0]["KnowledgeBase"] ?>");
			this.formPanel.down('[itemId=EmpType]').setValue("<?= $resInfo[0]["EmpType"] ?>");
			this.formPanel.down('[itemId=EmpType]').getStore().load();

			this.formPanel.down('[itemId=CommentSuggest]').setValue("<?= $resInfo[0]["ExtraComments"] ?>");
			this.formPanel.down('[itemId=CommentSuggest2]').setValue("<?= $resInfo[0]["ExtraComments2"] ?>");
			<?php
			for($i=0;$i<count($resInfo);$i++) 
			{ 
			?>			
				i = <?= $i ?>;
				
				if(i > 0){
					el = this.formPanel.down("[itemId=fs_Guars]");
					el.insert(el.items.length-1,[{
                            xtype: "displayfield",
                            hideTrigger: true,							
                            labelWidth: 50,
                            width: 80,
                            fieldCls: "blueText",
                            value: "وثیقه [ 1 ]"
                        }, {
                            xtype: "textfield",
							allowBlank: false,
                            width: 300,
                            fieldLabel: "نوع وثیقه",
                            name: "GuarType_" + (i+1),
							itemId: "GuarType_" + (i+1)
                        },
                        {
                            xtype: "numberfield",
                            width: 300,
							allowBlank: false,
							hideTrigger: true,	
                            fieldLabel: "ارزش وثیقه(به ریال)",
                            name: "GuarAmount_" + (i+1),
							itemId: "GuarAmount_" + (i+1)
                        }]);
				}
			
				
				this.formPanel.down('[name=GuarType_'+ (i+1) +']').setValue("<?= $resInfo[$i]["BailType2"] ?>");
				this.formPanel.down('[name=GuarAmount_'+ (i+1) +']').setValue("<?= $resInfo[$i]["BailValue"]?>");				
			<?php			
			}
			?>
		}
		else {
	
			this.formPanel.down('[itemId=PersonID]').setValue(this.PID);			
			this.formPanel.down("[name=PersonID]").getStore().load({
			params : {
				PersonID : this.PID
			},
			callback : function(){
			
				if(this.getCount() > 0)
					WarrentyReport_totalObj.formPanel.down("[name=PersonID]").setValue(this.getAt(0).data.PersonID);
			}
		});

			this.formPanel.down('[itemId=TypeID]').setValue("<?= $_REQUEST["BT"] ?>");
			this.formPanel.down('[itemId=TypeID]').getStore().load();

			this.formPanel.down('[itemId=contractTitle]').setValue("<?= $_REQUEST["ST"] ?>");
			this.formPanel.down('[itemId=LetterNo]').setValue("<?= $_REQUEST["LetterNo"] ?>");

			this.formPanel.down('[itemId=organization]').setValue("<?= $_REQUEST["RG"] ?>");
			this.formPanel.down('[itemId=warAmount]').setValue("<?= $_REQUEST["AM"] ?>");
			this.formPanel.down('[itemId=warDate]').setValue("<?= $_REQUEST["DU"] ?>");
			this.formPanel.down('[itemId=ProposedGuarantee]').setValue("<?= $_REQUEST["SB"] ?>");		
			this.formPanel.down('[itemId=SupplementaryExplanation]').setValue("<?= $_REQUEST["CO"] ?>");		
			this.formPanel.down('[itemId=KnowledgeBase]').setValue("<?= $_REQUEST["KB"] ?>");
			this.formPanel.down('[itemId=EmpType]').setValue("<?= $_REQUEST["ET"] ?>");
			this.formPanel.down('[itemId=EmpType]').getStore().load();

			this.formPanel.down('[itemId=CommentSuggest]').setValue("<?= $_REQUEST["EC"] ?>");
			this.formPanel.down('[itemId=CommentSuggest2]').setValue("<?= $_REQUEST["EC2"] ?>");
		}
		
    }

    WarrentyReport_totalObj = new WarrentyReport_total();

    WarrentyReport_total.prototype.OpenLetter = function (letterNo)
    {
        framework.OpenPage("/office/letter/LetterInfo.php", "مشخصات نامه",
                {
                    LetterID: letterNo,
                    ForView: true
                });
    }
</script>
<form id="mainForm">
    <center><br>
        <div id="main" ></div>
    </center>
    <input type="hidden" name="excel" id="excel">
</form>
