<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.07
//-----------------------------
 
require_once '../../header.inc.php';

?> 
<script>
Documentation.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : "<?= $js_prefix_address ?>",
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function Documentation()
{
	new Ext.form.FieldSet({
		title : "نمودارهای BPMN",
		style : "margin:20px",
		width : 500,
		renderTo : this.get("div_bpmn"),
		contentEl : this.get("div_bpmn-diagrams")
	});
	
	new Ext.form.FieldSet({
		title : "نمودارهای کلاس",
		style : "margin:20px",
		width : 500,
		renderTo : this.get("div_cld"),
		contentEl : this.get("div_cld-diagrams")
	});
}

var DocumentationObject = new Documentation();

</script>
<div id="div_bpmn">
	<div id="div_bpmn-diagrams">
		<a target="_blank" href="/framework/documentation/bpmn-loan.png">
			<img src="/framework/documentation/bpmn.png" 
				 style="width:50px;vertical-align:middle;cursor: pointer;margin-bottom:5px"></a>
			زیر سیستم تسهیلات
		<br><a target="_blank" href="/framework/documentation/bpmn-warrenty.png">
			<img src="/framework/documentation/bpmn.png" 
				 style="width:50px;vertical-align:middle;cursor: pointer;margin-bottom:5px"></a>
			زیر سیستم ضمانت نامه
		<br><a target="_blank" href="/framework/documentation/bpmn-plan.png">
			<img src="/framework/documentation/bpmn.png" 
				 style="width:50px;vertical-align:middle;cursor: pointer;margin-bottom:5px"></a>
			زیر سیستم نظارت و ارزیابی
			<br><a target="_blank" href="/framework/documentation/bpmn-accounting.png">
			<img src="/framework/documentation/bpmn.png" 
				 style="width:50px;vertical-align:middle;cursor: pointer;margin-bottom:5px"></a>
			زیر سیستم حسابداری
			<br><a target="_blank" href="/framework/documentation/bpmn-cheque.png">
			<img src="/framework/documentation/bpmn.png" 
				 style="width:50px;vertical-align:middle;cursor: pointer;margin-bottom:5px"></a>
			زیر سیستم خزانه داری
			<br><a target="_blank" href="/framework/documentation/bpmn-vote.png">
			<img src="/framework/documentation/bpmn.png" 
				 style="width:50px;vertical-align:middle;cursor: pointer;margin-bottom:5px"></a>
			زیر سیستم نظرسنجی
			<br><a target="_blank" href="/framework/documentation/bpmn-attendance.png">
			<img src="/framework/documentation/bpmn.png" 
				 style="width:50px;vertical-align:middle;cursor: pointer;margin-bottom:5px"></a>
			زیر سیستم حضور و غیاب
			<br><a target="_blank" href="/framework/documentation/bpmn-wfm.png">
			<img src="/framework/documentation/bpmn.png" 
				 style="width:50px;vertical-align:middle;cursor: pointer;margin-bottom:5px"></a>
			زیر سیستم فرم ساز
	</div>
</div>
<div id="div_cld">
	<div id="div_cld-diagrams">
		<a target="_blank" href="/framework/documentation/cld-framework.png">
			<img src="/framework/documentation/ClassDesign.png" 
				 style="width:50px;vertical-align:middle;cursor: pointer;margin-bottom:5px"></a>
			اطلاعات پایه و مدیریت سیستم
			<br><a target="_blank" href="/framework/documentation/cld-acc.png">
			<img src="/framework/documentation/ClassDesign.png" 
				 style="width:50px;vertical-align:middle;cursor: pointer;margin-bottom:5px"></a>
			زیر سیستم حسابداری
			<br><a target="_blank" href="/framework/documentation/cld-loan.png">
			<img src="/framework/documentation/ClassDesign.png" 
				 style="width:50px;vertical-align:middle;cursor: pointer;margin-bottom:5px"></a>
			زیر سیستم تسهیلات
			<br><a target="_blank" href="/framework/documentation/cld-warrenty.png">
			<img src="/framework/documentation/ClassDesign.png" 
				 style="width:50px;vertical-align:middle;cursor: pointer;margin-bottom:5px"></a>
			زیر سیستم ضمانت نامه
			<br><a target="_blank" href="/framework/documentation/cld-plan.png">
			<img src="/framework/documentation/ClassDesign.png" 
				 style="width:50px;vertical-align:middle;cursor: pointer;margin-bottom:5px"></a>
			زیر سیستم نظارت و ارزیابی
			<br><a target="_blank" href="/framework/documentation/cld-attendance.png">
			<img src="/framework/documentation/ClassDesign.png" 
				 style="width:50px;vertical-align:middle;cursor: pointer;margin-bottom:5px"></a>
			زیر سیستم حضور و غیاب
			<br><a target="_blank" href="/framework/documentation/cld-contract.png">
			<img src="/framework/documentation/ClassDesign.png" 
				 style="width:50px;vertical-align:middle;cursor: pointer;margin-bottom:5px"></a>
			زیر سیستم قراردادها
			<br><a target="_blank" href="/framework/documentation/cld-office.png">
			<img src="/framework/documentation/ClassDesign.png" 
				 style="width:50px;vertical-align:middle;cursor: pointer;margin-bottom:5px"></a>
			زیر سیستم اتوماسیون اداری
			<br><a target="_blank" href="/framework/documentation/cld-vote.png">
			<img src="/framework/documentation/ClassDesign.png" 
				 style="width:50px;vertical-align:middle;cursor: pointer;margin-bottom:5px"></a>
			زیر سیستم نظرسنجی
			<br><a target="_blank" href="/framework/documentation/cld-flow.png">
			<img src="/framework/documentation/ClassDesign.png" 
				 style="width:50px;vertical-align:middle;cursor: pointer;margin-bottom:5px"></a>
			زیر سیستم فرمساز و گردش کار
	</div>
</div>