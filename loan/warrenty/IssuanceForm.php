<?php
//-----------------------------
//	Programmer	: Mokhtari
//	Date		: 99.05
//-----------------------------
require_once '../../header.inc.php';
require_once '../request/request.class.php';

function ListDate(){
    $personid = $_REQUEST["PersonID"];
    $TypeID = $_REQUEST["TypeID"];
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

    foreach($_POST as $key => $value){
        $exp_key = explode('_', $key);

        if($exp_key[0] == 'GuarType'){
            $GuarType[] = $value;
            $TypeItem[] = $exp_key[1];
        }
        if($exp_key[0] == 'GuarAmount'){
            $GuarAmount[] = $value;
            $AmountItem[] = $exp_key[1];
        }

    }
    $GuarCount=count($TypeItem);

    $personName = (!empty($temp0)) ? $temp0[0]['fullname'] : '';
    $warType = (!empty($temp3)) ? $temp3[0]['TypeDesc'] : '';
    $tempCount1=count($temp1);
    $tempCount2=count($temp2);
    $rowspan1 = 18+$tempCount1+$tempCount2+$GuarCount;
    $rowspan2 = 2+$tempCount1;
    $rowspan3 = 2+$tempCount2;
    $rowspan4 = 2+$GuarCount;


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
                    <td rowspan="10" ><span><b>شرح درخواست</b></span></td>
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
                </tr><tr>
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
                foreach ($temp1 as $temp){
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
                foreach ($temp2 as $temp){
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
                if (count($TypeItem) > 0 || count($AmountItem) > 0){
                    $num=count($TypeItem);
                    for ($x = 0; $x < $num; $x++) {
                        $i = $x+1;
                        ?>

                        <tr>
                            <td><span><?= $i ?></span></td>
                            <td colspan="3"><span><?= $GuarType[$x] ?></span></td>
                            <td colspan="2"><span><?= number_format($GuarAmount[$x]) ?></span></td>
                        </tr>
                        <?php
                    }
                }else{ }
                ?>

                <tr>
                    <td colspan="4"><span><b>جمع کل وثایق</b></span></td>
                    <td colspan="3"><span><b><?= number_format(array_sum($GuarAmount)) ?></b></span></td>
                </tr>

                <!--additional comments and suggestions-->
                <tr style="height: 100px;">
                    <td><b>نظرات و پیشنهادات تکمیلی</b></td>
                    <td colspan="6"><span><?= $_POST['CommentSuggest'] ?></td>
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

if(isset($_REQUEST["show"]))
{
    ListDate();
}


/*require_once getenv("DOCUMENT_ROOT") . '/framework/ReportDB/Filter_person.php';*/

?>
<script>
    WarrentyReport_total.prototype = {
        TabID : '<?= $_REQUEST["ExtTabID"]?>',
        address_prefix : "<?= $js_prefix_address?>",
        pageIndex : 1,

        get : function(elementID){
            return findChild(this.TabID, elementID);
        }
    }

    WarrentyReport_total.prototype.showReport = function(btn, e)
    {
        this.form = this.get("mainForm")
        this.form.target = "_blank";
        this.form.method = "POST";
        this.form.action =  this.address_prefix + "IssuanceForm.php?show=true";
        this.form.submit();
        this.get("excel").value = "";
        return;
    }

    function WarrentyReport_total()
    {
        this.formPanel = new Ext.form.Panel({
            renderTo : this.get("main"),
            frame : true,
            layout :{
                type : "table",
                columns :2
            },
            defaults : {
                width : 365
            },
            bodyStyle : "text-align:right;padding:5px",
            title : "فرم صدور ضمانت نامه",
            width : 780,
            items :[{
                xtype : "combo",
                store : new Ext.data.SimpleStore({
                    proxy: {
                        type: 'jsonp',
                        url: this.address_prefix + '../../framework/person/persons.data.php?' +
                            "task=selectPersons&UserType=IsCustomer",
                        reader: {root: 'rows',totalProperty: 'totalCount'}
                    },
                    fields : ['PersonID','fullname']
                }),
                fieldLabel : "نام شخص حقيقي/حقوقي",
                displayField : "fullname",
                pageSize : 20,
                valueField : "PersonID",
                hiddenName : "PersonID"
            },{
                xtype : "combo",
                store : new Ext.data.Store({
                    proxy:{
                        type: 'jsonp',
                        url: this.address_prefix + 'request.data.php?task=GetWarrentyTypes',
                        reader: {root: 'rows',totalProperty: 'totalCount'}
                    },
                    fields :  ["InfoID", "InfoDesc"],
                    autoLoad : true
                }),
                displayField: 'InfoDesc',
                valueField : "InfoID",
                hiddenName : "TypeID",
                allowBlank : false,
                fieldLabel : "نوع ضمانتنامه"
            },{
                xtype : "textfield",
                name : "contractTitle",
                fieldLabel : "موضوع قرارداد"
            },{
                xtype : "textfield",
                name : "organization",
                fieldLabel : "سازمان مربوطه"
            },{
                xtype : "textfield",
                name : "warAmount",
                hideTrigger : true,
                fieldLabel : "مبلغ ضمانت نامه"
            },{
                xtype : "textfield",
                name : "warDate",
                fieldLabel : "مدت ضمانتنامه"
            },{
                xtype : "textfield",
                fieldLabel : "وثايق پيشنهادي",
                name : "ProposedGuarantee"
            },{
                xtype : "textfield",
                fieldLabel : "توضيح تكميلي",
                name : "SupplementaryExplanation"
            },{
                xtype : "fieldset",
                title : "وثایق موردنیاز",
                layout : "column",
                columns : 2,
                colspan : 2,
                width : 730,
                items:[{
                    xtype : "displayfield",
                    hideTrigger : true,
                    labelWidth : 50,
                    width : 80,
                    fieldCls : "blueText",
                    value : "وثیقه [ 1 ]"
                },{
                    xtype : "textfield",
                    width : 300,
                    fieldLabel : "نوع وثیقه",
                    name : "GuarType_1"
                },
                    {
                        xtype : "textfield",
                        width : 300,
                        fieldLabel : "ارزش وثیقه(به ریال)",
                        name : "GuarAmount_1"
                    },{
                    xtype : "button",
                    text : "افزودن وثیقه",
                    colspan : 2,
                    iconCls : "add",
                    handler : function(){
                        me = WarrentyReport_totalObj;
                        me.pageIndex++;
                        fs = this.up("fieldset");
                        fs.insert(fs.items.length-1, [{
                            xtype : "displayfield",
                            hideTrigger : true,
                            labelWidth : 50,
                            width : 80,
                            fieldCls : "blueText",
                            value : "وثیقه [ " + me.pageIndex + " ]"
                        },{
                            xtype : "textfield",
                            width : 300,
                            fieldLabel : "نوع وثیقه",
                            name : "GuarType_" + me.pageIndex
                        },{
                            xtype : "textfield",
                            width : 300,
                            fieldLabel : "ارزش وثیقه(به ریال)",
                            name : "GuarAmount_" + me.pageIndex
                        }]);
                    }
                }]
            },{
                xtype : "textarea",
                width : 700,
                colspan :2,
                fieldLabel : "نظرات و پیشنهادات تکمیلی",
                name : "CommentSuggest"
            }

],
            buttons : [{
                text : "مشاهده گزارش",
                handler : Ext.bind(this.showReport,this),
                iconCls : "report"
            },{
                text : "پاک کردن گزارش",
                iconCls : "clear",
                handler : function(){
                    WarrentyReport_totalObj.formPanel.getForm().reset();
                    WarrentyReport_totalObj.get("mainForm").reset();
                }
            }]
        });

        this.formPanel.getEl().addKeyListener(Ext.EventObject.ENTER, function(keynumber,e){

            WarrentyReport_totalObj.showReport();
            e.preventDefault();
            e.stopEvent();
            return false;
        });
    }

    WarrentyReport_totalObj = new WarrentyReport_total();
</script>
<form id="mainForm">
    <center><br>
        <div id="main" ></div>
    </center>
    <input type="hidden" name="excel" id="excel">
</form>
