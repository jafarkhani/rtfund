<?php 
//---------------------------
// programmer:	Mahdipour
// create Date:	94.12
//---------------------------
require_once("../../header.inc.php");
require_once("../data/salary_item_type.data.php");
//--------------------------------------------------------------------

if(!empty($_POST["SIT"]))
{
	$obj = new manage_salary_item_type($_POST["SIT"]);
//	print_r($obj); die();
}
else 
{
	$obj = new manage_salary_item_type();
}

//$drp_personTyp = manage_domains::DRP_PersonType("person_type",$obj->person_type,"");
$drp_EffTyp = manage_domains::DRP_EffectType("effect_type",$obj->effect_type,"");
$drp_CreditTopic = manage_domains::DRP_Credit_topic("credit_topic",$obj->credit_topic,"");
$drp_SitAvailable = manage_domains::DRP_SalaryItemAvailableFor("available_for",$obj->available_for,"");

?>

<script language="JavaScript">
	SalaryItemType.prototype.afterNewItemLoad = function()
	{
		this.SystemTitle = "1";		
		this.salary_item_type_id = '<?= !empty($_POST["SIT"]) ? $_POST["SIT"] : "" ?>';		
			
		this.Mode = this.salary_item_type_id == "" ? "new" : "edit";
			
		this.validity_start_date = new Ext.form.SHDateField({
			applyTo: this.get('validity_start_date'),
			format: 'Y/m/d'
		});

		this.validity_end_date = new Ext.form.SHDateField({
			applyTo: this.get('validity_end_date'),
			format: 'Y/m/d'
		});

        this.store1 = new Ext.data.Store({
			fields : ["InfoID","InfoDesc"],
			proxy : {
				type: 'jsonp',
				url : this.address_prefix + "../../global/domain.data.php?task=searchComputetype",
				reader: {
					root: 'rows',
					totalProperty: 'totalCount'
				}
			},
			autoLoad:true
		});


        this.store2 = new Ext.data.Store({
			fields : ["InfoID","param1","InfoDesc"],
			proxy : {
				type: 'jsonp',
				url : this.address_prefix + "../../global/domain.data.php?task=searchMultiplicand",
				reader: {
					root: 'rows',
					totalProperty: 'totalCount'
				}
			}
		});


	this.computeTypCombo = new Ext.form.field.ComboBox({
		store : this.store1,
		width : 180,
		typeAhead: false,
		queryMode : "local",
		displayField : "InfoDesc",
		valueField : "InfoID",
		hiddenName : "salary_compute_type",
		applyTo : this.get("salary_compute_type"),
		listeners : {
			select : function(combo, records){  
				SalaryItemTypeObject.MultiplicandCombo.reset();
				SalaryItemTypeObject.store2.load({
					params : { MasterID: records[0].data.InfoID}
				})
			}
		}
	});

	this.MultiplicandCombo = new Ext.form.field.ComboBox({
		store : this.store2,
		width : 150,
		typeAhead: false,
		queryMode : "local",
		displayField : "InfoDesc",
		valueField : "InfoID",
		hiddenName : "multiplicand",
		applyTo : this.get("multiplicand")
	});

    this.store1.load({
        callback:function(){
            SalaryItemTypeObject.computeTypCombo.setValue("<?= $obj->salary_compute_type ?>");
            SalaryItemTypeObject.store2.load({
                params:{MasterID:SalaryItemTypeObject.computeTypCombo.getValue()},
                callback:function(){
                    SalaryItemTypeObject.MultiplicandCombo.setValue("<?= $obj->multiplicand ?>");
                }
            });
        }
    });
	
	this.CostIDCombo = new Ext.form.field.ComboBox({
		xtype : "combo",
		width : 400,
		applyTo : this.get("cmp_costCode"),
		store: new Ext.data.Store({
			fields:["CostID","CostCode","CostDesc",{
				name : "fullDesc",
				convert : function(value,record){
					return "[ " + record.data.CostCode + " ] " + record.data.CostDesc
				}				
			}],
			proxy: {
				type: 'jsonp',
				url: '/accounting/baseinfo/baseinfo.data.php?task=SelectCostCode',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			}
		}),
		typeAhead: false,
		hiddenName : "CostID",
		valueField : "CostID",
		displayField : "fullDesc"
	});
	this.Param1CostIDCombo = new Ext.form.field.ComboBox({
		xtype : "combo",
		width : 400,
		applyTo : this.get("cmp_param1cost"),
		store: new Ext.data.Store({
			fields:["CostID","CostCode","CostDesc",{
				name : "fullDesc",
				convert : function(value,record){
					return "[ " + record.data.CostCode + " ] " + record.data.CostDesc
				}				
			}],
			proxy: {
				type: 'jsonp',
				url: '/accounting/baseinfo/baseinfo.data.php?task=SelectCostCode',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			}
		}),
		typeAhead: false,
		hiddenName : "Param1CostID",
		valueField : "CostID",
		displayField : "fullDesc"
	});
	this.Param2CostIDCombo = new Ext.form.field.ComboBox({
		xtype : "combo",
		width : 400,
		applyTo : this.get("cmp_param2cost"),
		store: new Ext.data.Store({
			fields:["CostID","CostCode","CostDesc",{
				name : "fullDesc",
				convert : function(value,record){
					return "[ " + record.data.CostCode + " ] " + record.data.CostDesc
				}				
			}],
			proxy: {
				type: 'jsonp',
				url: '/accounting/baseinfo/baseinfo.data.php?task=SelectCostCode',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			}
		}),
		typeAhead: false,
		hiddenName : "Param2CostID",
		valueField : "CostID",
		displayField : "fullDesc"
	});
	this.Param3CostIDCombo = new Ext.form.field.ComboBox({
		xtype : "combo",
		width : 400,
		applyTo : this.get("cmp_param3cost"),
		store: new Ext.data.Store({
			fields:["CostID","CostCode","CostDesc",{
				name : "fullDesc",
				convert : function(value,record){
					return "[ " + record.data.CostCode + " ] " + record.data.CostDesc
				}				
			}],
			proxy: {
				type: 'jsonp',
				url: '/accounting/baseinfo/baseinfo.data.php?task=SelectCostCode',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			}
		}),
		typeAhead: false,
		hiddenName : "Param3CostID",
		valueField : "CostID",
		displayField : "fullDesc"
	});
	this.Param4CostIDCombo = new Ext.form.field.ComboBox({
		xtype : "combo",
		width : 400,
		applyTo : this.get("cmp_param4cost"),
		store: new Ext.data.Store({
			fields:["CostID","CostCode","CostDesc",{
				name : "fullDesc",
				convert : function(value,record){
					return "[ " + record.data.CostCode + " ] " + record.data.CostDesc
				}				
			}],
			proxy: {
				type: 'jsonp',
				url: '/accounting/baseinfo/baseinfo.data.php?task=SelectCostCode',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			}
		}),
		typeAhead: false,
		hiddenName : "Param4CostID",
		valueField : "CostID",
		displayField : "fullDesc"
	});
	this.TafsiliIDCombo = new Ext.form.field.ComboBox({
		xtype : "combo",
		width : 400,
		applyTo : this.get("cmp_TafsiliID"),
		store: new Ext.data.Store({
			fields:["TafsiliID","TafsiliDesc"],
			proxy: {
				type: 'jsonp',
				url: '/accounting/baseinfo/baseinfo.data.php?task=GetAllTafsilis&TafsiliType=<?= TAFSILITYPE_PERSON ?>',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			}
		}),
		typeAhead: false,
		pageSize : 10,
		valueField : "TafsiliID",
		hiddenName : "TafsiliID",
		displayField : "TafsiliDesc"
	});
	
	if("<?= $obj->CostID ?>" != "")
		this.CostIDCombo.getStore().load({
			params : { CostID : "<?= $obj->CostID ?>"},
			callback : function(){
				SalaryItemTypeObject.CostIDCombo.setValue(this.getAt(0).data.CostID)
			}
		});
	if("<?= $obj->TafsiliID ?>" != "")
		this.TafsiliIDCombo.getStore().load({
			params : { TafsiliID : "<?= $obj->TafsiliID ?>"},
			callback : function(){
				SalaryItemTypeObject.TafsiliIDCombo.setValue(this.getAt(0).data.TafsiliID)
			}
		});
	if("<?= $obj->Param1CostID ?>" != "")
		this.Param1CostIDCombo.getStore().load({
			params : { CostID : "<?= $obj->Param1CostID ?>"},
			callback : function(){
				SalaryItemTypeObject.Param1CostIDCombo.setValue(this.getAt(0).data.CostID)
			}
		});
	if("<?= $obj->Param2CostID ?>" != "")
		this.Param2CostIDCombo.getStore().load({
			params : { CostID : "<?= $obj->Param2CostID ?>"},
			callback : function(){
				SalaryItemTypeObject.Param2CostIDCombo.setValue(this.getAt(0).data.CostID)
			}
		});
	if("<?= $obj->Param3CostID ?>" != "")
		this.Param3CostIDCombo.getStore().load({
			params : { CostID : "<?= $obj->Param3CostID ?>"},
			callback : function(){
				SalaryItemTypeObject.Param3CostIDCombo.setValue(this.getAt(0).data.CostID)
			}
		});
	if("<?= $obj->Param4CostID ?>" != "")
		this.Param4CostIDCombo.getStore().load({
			params : { CostID : "<?= $obj->Param4CostID ?>"},
			callback : function(){
				SalaryItemTypeObject.Param4CostIDCombo.setValue(this.getAt(0).data.CostID)
			}
		});
 
    if(this.disabled)
    {
        //Ext.get(this.get("InfoPanel")).disable();
        this.validity_start_date.disable();
        this.validity_end_date.disable();
        this.computeTypCombo.disable();
        this.MultiplicandCombo.disable(); 
		  
		  Ext.get(this.get("person_type")).disable();
		  Ext.get(this.get("effect_type")).disable();
		  Ext.get(this.get("available_for")).disable();
		  Ext.get(this.get("full_title")).disable();
		  Ext.get(this.get("print_title")).disable();
		  Ext.get(this.get("print_order")).disable();		  
		  Ext.get(this.get("user_data_entry")).disable();
		  Ext.get(this.get("backpay_include")).disable();
		  Ext.get(this.get("editable_value")).disable();
		  Ext.get(this.get("param1_title")).disable();
		  Ext.get(this.get("param1_input")).disable();
		  Ext.get(this.get("param2_title")).disable();
		  Ext.get(this.get("param2_input")).disable();
		  Ext.get(this.get("param3_title")).disable();
		  Ext.get(this.get("param3_input")).disable();
		  Ext.get(this.get("param4_title")).disable();
		  Ext.get(this.get("param4_input")).disable();
		  Ext.get(this.get("param5_title")).disable();
		  Ext.get(this.get("param5_input")).disable();
		  Ext.get(this.get("param6_title")).disable();
		  Ext.get(this.get("param6_input")).disable();
		  Ext.get(this.get("param7_title")).disable();
		  Ext.get(this.get("param7_input")).disable();  
		  Ext.get(this.get("remember_distance")).disable();
		  Ext.get(this.get("remember_message")).disable();
		  
        Ext.get(this.get("insure_include")).enable();
        Ext.get(this.get("tax_include")).enable();
        Ext.get(this.get("retired_include")).enable();
        Ext.get(this.get("pension_include")).enable();
        Ext.get(this.get("credit_topic")).enable();
		Ext.get(this.get("CostType1")).enable();
		Ext.get(this.get("CostType2")).enable();
	
    }
	
	
	
	}

     SalaryItemTypeObject.afterNewItemLoad();
</script>
	
        <table id="InfoPanel" width="100%">
            <tr>
                <td width="25%">نوع نيروي انساني :</td>
                <td width="25%">قراردادی</td>
                <td width="25%">تعريف شده توسط :</td>



                <td width="25%"><? if (empty($_POST["SIT"])) {
                                            echo "کاربر";
                                    ?>
                                    <input type='hidden' id='user_defined' name='user_defined'
                                            value="1">
                                    <?
                                            }
                                       elseif (!empty($_POST["SIT"])){
                                         if($obj->user_defined == 0 )
                                                echo "سیستم";
                                         else 	echo "کاربر";
                                       }

                                   ?></td>
            </tr>
            <tr>
                <td width="25%">محل محاسبه :</td>
                <td width="25%">
                    <? if(!empty($_POST["SIT"])){
                        if($obj->compute_place == 1 )
                                                echo "حکم";
                                         else 	echo "فیش";
                     }
                     if(empty($_POST["SIT"])){
                        ?>
                        <input type='hidden' id='compute_place' name='compute_place'
                    value="1">
                     <? }
                     elseif (empty($_POST["SIT"])){ ?>
                        <input type='hidden' id='compute_place' name='compute_place'
                            value="2">
                     <? } ?>
                    </td>
                <td width="15%">اثر :</td>
                <td width="25%"><?= $drp_EffTyp ?>&nbsp;*</td>
            </tr>
            <tr>
                    <td width="25%">
                    عنوان کامل:
                    </td>
                    <td width="25%">
                    <input type="text" id="full_title" name="full_title" class="x-form-text x-form-field" style="width: 180px"
                           value="<?= $obj->full_title?>">&nbsp;*

                    </td>
                    <td width="25%">
                    عنوان چاپي:
                    </td>
                    <td width="25%">
                    <input type="text" id="print_title" name="print_title" class="x-form-text x-form-field" style="width: 180px"
                           value="<?= $obj->print_title?>">&nbsp;*
                    </td>
            </tr>
            <tr>
                    <td width="25%">
                    ترتيب چاپ :
                    </td>
                    <td width="25%">
                    <input type="text" id="print_order" name="print_order" class="x-form-text x-form-field" style="width: 100px"
                           value="<?= $obj->print_order?>">
                    </td>
                    
                    <td width="25%">
                    در دسترس براي :
                    </td>
                    <td width="25%"><?= $drp_SitAvailable ?>&nbsp;*</td>
                  
            </tr>
           
            <tr>
                    <td width="25%">
                    مشمول بيمه ؟
                    </td>
                    <td width="5%" >
                      <input type="checkbox" value="1" id="insure_include"
                             name="insure_include" <?= ($obj->insure_include == 1) ? "checked" : "" ?>                            
                             class="x-form-text x-form-field" style="width: 10px" >
                    </td>
                    <td width="25%">
                    مشمول ماليات؟
                    </td>
                    <td width="5%">


                    <input type="checkbox" value="1" id="tax_include"
                             name="tax_include" <?= ($obj->tax_include == 1) ? "checked" : "" ?>                          
                             class="x-form-text x-form-field" style="width: 10px" >
                    </td>
            </tr>
           
            <tr>
                    <td width="25%">
                    ثبت داده توسط کاربر؟
                    </td>
                    <td width="5%">
                        <input type="checkbox" value="1" id="user_data_entry"
                             name="user_data_entry" <?= ($obj->user_data_entry == 1) ? "checked" : "" ?>
                             class="x-form-text x-form-field" style="width: 10px" >
                    </td>
					<td width="25%">
                    قلم تحت تاثیر ماه می باشد ؟ 
                    </td>
                    <td width="5%">
                        <input type="checkbox" value="1" id="month_length_effect"
                             name="month_length_effect" <?= ($obj->month_length_effect == 1) ? "checked" : "" ?>
                             class="x-form-text x-form-field" style="width: 10px" >
                    </td>
            </tr>
            <tr>
                    <td width="25%">
                   قلم مربوط به حق الجلسه می باشد؟
                    </td>
                    <td width="5%">
                        <input type="checkbox" value="1" id="SessionItem"
                             name="SessionItem" <?= ($obj->SessionItem == 1) ? "checked" : "" ?>
                             class="x-form-text x-form-field" style="width: 10px" >
                    </td>
            </tr>
            <tr>
            <td colspan="4">
            <font color=green>
            از گزينه ثبت داده توسط كاربر براي مواقعي كه مي خواهيم به صورت موقت نحوه محاسبه قلم را از حالت خودكار به حالت دستي تغيير دهيم .
            </font>
            </td>
            </tr>
			<tr>
				 <td width="25%">
				کد حساب :
				</td>
				<td width="25%" colspan="3"><input type="text" id="cmp_costCode"></td>
			</tr>
			<tr>
				 <td width="25%">
				حساب تفصیلی :
				</td>
				<td width="25%" colspan="3"><input type="text" id="cmp_TafsiliID"></td>
			</tr>
            <tr>
                    <td width="25%">
                    محاسبه در backpay ؟
                    </td>
                    <td width="5%">
                        <input type="checkbox" value="1" id="backpay_include"
                             name="backpay_include" <?= ($obj->backpay_include == 1) ? "checked" : "" ?>                             
                             class="x-form-text x-form-field" style="width: 10px" >
                    </td>
            </tr>
            <tr>
                    <td width="25%">
                    تاريخ شروع اعتبار :
                    </td>
                    <td width="25%">
                    <input type="text" id="validity_start_date" name="validity_start_date" class="x-form-text x-form-field" style="width: 80px"
                           value="<?= DateModules::Miladi_to_Shamsi($obj->validity_start_date)?>" >
                    </td>
                    <td width="25%">
                    تاريخ پايان اعتبار :
                    </td>
                    <td width="25%">
                    <input type="text" id="validity_end_date" name="validity_end_date" class="x-form-text x-form-field" style="width: 80px"
                           value="<?= DateModules::Miladi_to_Shamsi($obj->validity_end_date) ?>">
                    </td>
            </tr>
            <tr>
                    <td width="25%">
                    نحوه محاسبه:
                    </td>
                    <td width="25%"><input type="text" id="salary_compute_type"></td>
                    <td width="25%">
                    مضروب فيه:
                    </td>
                    <td width="25%"><input type="text" id="multiplicand"></td>
            </tr>
            <tr>
                    <td width="25%">
                    نام تابع محاسباتي:
                    </td>
                    <td width="75%" colspan="3">
                    <? if(!empty($_POST["SIT"])) { echo $obj->function_name ;} else {?>
                    <input type="text" id="function_name" name="function_name" class="x-form-text x-form-field" style="width: 200px" >
                    <? } ?>
                    </td>

            </tr>
            <tr>
                    <td width="25%">
                    قابل ويرايش پس از محاسبه
                    </td>
                    <td width="5%">

                    <input type="checkbox" value="1" id="editable_value"
                             name="editable_value" <?= ($obj->editable_value == 1) ? "checked" : "" ?> class="x-form-text x-form-field" style="width: 10px" >
                    </td>
            </tr>
            <tr>
                    <td colspan="4">
                    <font color=green>
        از گزينه ويرايش فيلدها براي اقلام محاسباتي استفاده مي شود كه محاسبه خودكار آنها براي همه افراد امكان پذير نيست .<br>
        با فعال كردن اين گزينه امكان تغيير مبلغ محاسبه شده و ثبت علت تغيير مبلغ قلم فراهم مي گردد .
                    </font>
                    </td>
            </tr>
            <tr>
                    <td width="25%">
                       عنوان پارامتر 1:
                    </td>
                    <td width="75%" colspan="3">
                    <input type="text" id="param1_title" name="param1_title" class="x-form-text x-form-field" style="width: 200px"
                           value="<?= $obj->param1_title ?>" >
                           دریافت از کاربر؟
                    <input type="checkbox" value="1" id="param1_input"
                             name="param1_input" <?= ($obj->param1_input == 1) ? "checked" : "" ?> class="x-form-text x-form-field" style="width: 10px" >
                    </td>
            </tr>
			<tr>
				<td width="25%">کد حساب پارامتر 1</td>
				<td><div id="cmp_param1cost"></div></td>
			</tr>
            <tr> 
                    <td width="25%">
                       عنوان پارامتر 2:
                    </td>
                    <td width="75%" colspan="3">
                    <input type="text" id="param2_title" name="param2_title" class="x-form-text x-form-field" style="width: 200px"
                           value="<?= $obj->param2_title ?>" >
                           دریافت از کاربر؟
                    <input type="checkbox" value="1" id="param2_input"
                             name="param2_input" <?= ($obj->param2_input == 1) ? "checked" : "" ?> class="x-form-text x-form-field" style="width: 10px" >
                    </td>
            </tr>
			<tr>
				<td width="25%">کد حساب پارامتر 2</td>
				<td><div id="cmp_param2cost"></div></td>
			</tr>
            <tr>
                    <td width="25%">
                       عنوان پارامتر 3:
                    </td>
                    <td width="75%" colspan="3">
                    <input type="text" id="param3_title" name="param3_title" class="x-form-text x-form-field" style="width: 200px"
                           value="<?= $obj->param3_title ?>" >
                           دریافت از کاربر؟
                    <input type="checkbox" value="1" id="param3_input"
                             name="param3_input" <?= ($obj->param3_input == 1) ? "checked" : "" ?> class="x-form-text x-form-field" style="width: 10px" >
                    </td>
            </tr>
			<tr>
				<td width="25%">کد حساب پارامتر 3</td>
				<td><div id="cmp_param3cost"></div></td>
			</tr>
            <tr>
                    <td width="25%">
                       عنوان پارامتر 4:
                    </td>
                    <td width="75%" colspan="3">
                    <input type="text" id="param4_title" name="param4_title" class="x-form-text x-form-field" style="width: 200px"
                           value="<?= $obj->param4_title ?>" >
                           دریافت از کاربر؟
                    <input type="checkbox" value="1" id="param4_input"
                             name="param4_input"  <?= ($obj->param4_input == 1) ? "checked" : "" ?> class="x-form-text x-form-field" style="width: 10px" >
                    </td>
            </tr>
			<tr>
				<td width="25%">کد حساب پارامتر 4</td>
				<td><div id="cmp_param4cost"></div></td>
			</tr>
            <tr>
                    <td width="25%">
                       عنوان پارامتر 5:
                    </td>
                    <td width="75%" colspan="3">
                    <input type="text" id="param5_title" name="param5_title" class="x-form-text x-form-field" style="width: 200px"
                           value="<?= $obj->param5_title ?>" >
                           دریافت از کاربر؟
                    <input type="checkbox" value="1" id="param5_input"
                             name="param5_input" <?= ($obj->param5_input == 1) ? "checked" : "" ?> class="x-form-text x-form-field" style="width: 10px" >
                    </td>
            </tr>
            <tr>
                    <td width="25%">
                       عنوان پارامتر 6:
                    </td>
                    <td width="75%" colspan="3">
                    <input type="text" id="param6_title" name="param6_title" class="x-form-text x-form-field" style="width: 200px"
                           value="<?= $obj->param6_title ?>" >
                           دریافت از کاربر؟
                    <input type="checkbox" value="1" id="param6_input"
                             name="param6_input" <?= ($obj->param6_input == 1) ? "checked" : "" ?> class="x-form-text x-form-field" style="width: 10px" >
                    </td>
            </tr>
            <tr>
                    <td width="25%">
                       عنوان پارامتر 7:
                    </td>
                    <td width="75%" colspan="3">
                    <input type="text" id="param7_title" name="param7_title" class="x-form-text x-form-field" style="width: 200px"
                           value="<?= $obj->param7_title ?>" >
                           دریافت از کاربر؟
                    <input type="checkbox" value="1" id="param7_input"
                             name="param7_input" <?= ($obj->param7_input == 1) ? "checked" : "" ?> class="x-form-text x-form-field" style="width: 10px" >
                    </td>
            </tr>
            <tr>
                    <td width="25%">
                    فاصله زماني يادآوري:
                    </td> 
                    <td width="25%">
                    <input type="text" id="remember_distance" name="remember_distance" class="x-form-text x-form-field" style="width: 80px"
                           value="<?= $obj->remember_distance ?>" > &nbsp; ماه
                    </td>
                    <td width="25%">
                    پيام يادآوري:
                    </td>
                    <td width="25%">
                    <input type="text" id="remember_message" name="remember_message" class="x-form-text x-form-field" style="width: 180px"
                           value="<?= $obj->remember_message ?>">
                    </td>
            </tr> 
            <tr>
                    <td colspan="4">
                    <font color=green>
                    در صورتي كه فاصله زمان يادآوري 0 (صفر) ماه ثبت گردد و يا اينكه ثبت نشود يادآوري انجام نخواهد شد .
                    </font>
                    </td>
            </tr><tr> <td colspan="4"><br><br></td></tr>
            
        </table>
   
