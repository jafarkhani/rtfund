<?php

/* * *****************************************************************************************
 *
 * Class Name : ReportGenerator
 *
 * *******************************************************************************************
 *
 * Script to generate report from a valid my sql connection.
 * user have to supply which fields he want to display in table.
 * All properties are changable.
 *
 */
				
class ReportGenerator {

	//public $TempFolderAddress = "/tmp/temp.xls";
	public $TempFolderAddress = DOCUMENT_ROOT . "/temp.xls";
	
	public $fontFamily = "nazanin";
	public $fontSize = "16px";
	
	public $mysql_resource;
	public $header;
	public $foolter;
	public $fields = array();
	public $arrayName = array();
	public $fieldRenders = array();
	public $columns = array();
	public $cellpad = "1";
	public $cellspace = "0";
	public $border = "1";
	public $width = "100%";
	public $modified_width = "100%";
	public $header_color = "#BDD3EF";
	public $header_textcolor = "#15428B";
	public $header_alignment = "right";
	public $body_color = "#FFFFFF";
	public $summaryRow_color = "#E2E2E2";
	public $body_textcolor = "#000000";
	public $body_alignment = "right";
	public $surrounded = true;
	public $page_size = "-1"; //(paging) تعداد رديف ها در هر صفحه
	public $paging = false; // (paging) صفحه بند
	
	private $EnableSumRow = false;
	private $pageCount = 1;
	private $pageRecordCounter = 0;
	private $AllRowCount = 0;
	private $curGroup = "";
	private $PrevRecord = null;
	
	public $excel = false;
	public $rowNumber = true;
	
	/**
	 *
	 * @var function_name($row) 
	 */
	public $rowColorRender = "";
	
	public $headerContent = "";
	public $footerContent = "";
	public $footerExplicit = false;
	
	public $groupField = "";
	public $groupLabel = false;
	public $groupLabelRender = "";
	public $groupPerPage = false;
	
	public $showPageOfPage = false;
	public $VerticalSumColumn = array();

	public $SubHeaderFunction = "";
	
	public $flushReport = false;
	
	//------------ list of columns --------------
	public $MainForm;
	public $ObjectName;
	static $FieldPrefix = "reportcolumn_fld_";
	static $OrderPrefix = "reportcolumn_ord_";
	static $Chart_X_title = "X";
	static $Chart_Y_title = "Y";
	//-------------------------------------------
	static function BeginReport() {

        echo '<html>
			<head>
				<link rel="stylesheet" type="text/css" href="/generalUI/fonts/fonts.css" />
				<META http-equiv=Content-Type content="text/html; charset=UTF-8" >
			</head><body dir="rtl">';
    	}
    
	public function ReportGenerator($MainForm = "", $ObjectName = "") {
		$this->MainForm = $MainForm;
		$this->ObjectName = $ObjectName;
	}

	static function array_sort($array, $key, $order = SORT_ASC) {
		
		$groupArrays = array();
		if (count($array) == 0) 
			return $array;
		
		foreach ($array as $row) {
			
			if(!isset($groupArrays[ $row[$key] ]))
				$groupArrays[ $row[$key] ] = array();
			$groupArrays[ $row[$key] ][] = $row;
		}

		$returnArr = array();
		foreach($groupArrays as $key => $GArr)
			foreach($GArr as $row)
				$returnArr[] = $row;

		return $returnArr;
		
		
		
		
		
		$new_array = array();
		$sortable_array = array();

		if (count($array) > 0) {
			foreach ($array as $k => $v) {
				if (is_array($v)) {
					foreach ($v as $k2 => $v2) {
						if ($k2 == $key) {
							$sortable_array[$k] = $v2;
						}
					}
				} else {
					$sortable_array[$k] = $v;
				}
			}

			switch ($order) {
				case SORT_ASC:
					asort($sortable_array);
					break;
				case SORT_DESC:
					arsort($sortable_array);
					break;
			}

			foreach ($sortable_array as $k => $v) {
				$new_array[$k] = $array[$k];
			}
		}

		return $new_array;
	}

	public function generateReport() {
		
		ini_set('max_execution_time', 30000);
		ini_set('memory_limit','1000M');

		if ($this->mysql_resource instanceof ADORecordSet)
			$this->mysql_resource = $this->mysql_resource->GetRows();

		if (!is_array($this->mysql_resource) && !$this->mysql_resource instanceof PDOStatement)
			die("User doesn't supply any valid mysql resource after executing query result");

		if (count($this->mysql_resource) == 0)
			die("<center><span style='font-size:".$this->fontSize.";font-family:".$this->fontFamily.
				";'><br>" . "گزارش مورد نظر فاقد اطلاعات می باشد." . "</span></center>");

		//..................... column setting .......................
		$this->ColumnOrder();		
		//...........................................................
		
		if ($this->excel)
		{
			$this->ExcelGeneration();
			die();
		}
		//........................ draw header .......................
		if($this->flushReport)
			ob_start();
		$this->drawHeader(true);
		if($this->flushReport)
			ob_flush();
		//.................. fill the table with data  ......................
		$index = 0;
		
		if (is_array($this->mysql_resource)) {
			if ($this->groupField != "")
				$this->mysql_resource = self::array_sort($this->mysql_resource, $this->groupField);

			$this->AllRowCount = count($this->mysql_resource);
			foreach ($this->mysql_resource as &$row) {
				$this->GroupOperation($index, $row);
				$this->DrawRow($row, $index);
				$index++;
				$this->PrevRecord = $row;
				if($this->flushReport)
					ob_flush();
			}
		} else {
			$this->AllRowCount = $this->mysql_resource->RowCount();
			while ($row = $this->mysql_resource->fetch()) {
				$this->GroupOperation($index, $row);
				$this->DrawRow($row ,$index);
				$index++;
				$this->PrevRecord = $row;
				if($this->flushReport)
					ob_flush();
			}
		}

		$this->DrawFooter();
		
		$this->RowSpaning();
	}

	function ExcelGeneration() {
		
		//ini_set("display_errors", "On");
		$worksheet = "";
		require_once 'excel.php';
		require_once "php_writeexcel-0.3.0/class.writeexcel_workbook.inc.php";
		require_once "php_writeexcel-0.3.0/class.writeexcel_worksheet.inc.php";

		$workbook = new writeexcel_workbook($this->TempFolderAddress);
		$worksheet = & $workbook->addworksheet("Sheet1");
		$heading = & $workbook->addformat(array('align' => 'center', 'bold' => 1, 'bg_color' => 'blue', 'color' => 'white'));

		if ($this->rowNumber)
			$worksheet->write(0, 0, "ردیف", $heading);

		for ($i = 0; $i < count($this->columns); $i++) {
			$worksheet->write(0, $i + ($this->rowNumber ? 1 : 0), $this->columns[$i]->header, $heading);
			if ($this->columns[$i]->HaveSum != -1)
				$this->EnableSumRow = true;
		}
		$this->PrevRecord = null;
		//-------------------------------------------------		
		if (is_array($this->mysql_resource)) {
			for ($index = 0; $index < count($this->mysql_resource); $index++) {
				$row = &$this->mysql_resource[$index];
				if ($this->rowNumber)
					$worksheet->write($index + 1, 0, ($index + 1));

				for ($i = 0; $i < count($this->columns); $i++) {
					$val = "";
					 if(!empty($this->columns[$i]->renderFunction) && $this->columns[$i]->ExcelRender)
					 {
						$functionName = $this->columns[$i]->renderFunction;
						$val = $functionName(
							$row,$row[$this->columns[$i]->field],
							$this->columns[$i]->renderParams,$this->PrevRecord);
					 }
					else 
						$val = $row[$this->columns[$i]->field];

					$worksheet->write($index + 1, $i + ($this->rowNumber ? 1 : 0), $val);
				}
				$this->PrevRecord = $row;
			}
		}
		//-------------------------------------------------
		else 
		{
			$index = 0;
			while ($row = $this->mysql_resource->fetch()) {
				if ($this->rowNumber)
					$worksheet->write($index + 1, 0, ($index + 1));

				for ($i = 0; $i < count($this->columns); $i++) {
					$val = "";
					if(!empty($this->columns[$i]->renderFunction) && $this->columns[$i]->ExcelRender)
					 {
						$functionName = $this->columns[$i]->renderFunction;
						$val = $functionName(
							$row,$row[$this->columns[$i]->field],
							$this->columns[$i]->renderParams,$this->PrevRecord);
					 }
					else 
						$val = $row[$this->columns[$i]->field];

					$worksheet->write($index + 1, $i + ($this->rowNumber ? 1 : 0), $val);
				}
				$index++;
				$this->PrevRecord = $row;
			}
		}
		$workbook->close();

		header("Content-type: application/ms-excel");
		header("Content-disposition: inline; filename=excel.xls");
		echo file_get_contents($this->TempFolderAddress);
		unlink($this->TempFolderAddress);
		die();
	}

	function drawHeader($reportTitle) {
		
		$field_count = count($this->columns);
		
		$GroupHeaderFlag = false;
		for ($i = 0; $i < $field_count; $i++) {
			if(!empty($this->columns[$i]->GroupHeader))
			{
				$GroupHeaderFlag = true;
				break;
			}
		}
		
		//..............................................
		
		echo "<table id='page_" . $this->pageCount . "' width='$this->width' 
				style='font-family:".$this->fontFamily.";border-collapse: seperate;border-spacing: 0;' 
				border='$this->border'
				cellspacing='$this->cellspace' cellpadding='$this->cellpad'>";
		echo "<caption style='background-color: #2D72AD;color: white;'>";
		if($reportTitle)
		{
			
			if ($this->headerContent != "")
			{
				//$this->pageRecordCounter++;
				//$this->pageRecordCounter++;
				echo $this->headerContent;
			}
			else if ($this->header != "") {
				echo "<center><span style='font-family:titr,b titr;'>" . $this->header . "</span><center>";
				//$this->pageRecordCounter++;
			}
			
		}
		//..............................................
		if($this->SubHeaderFunction != "")
		{
			$temp= $this->SubHeaderFunction;
			$temp($this->pageCount);

		}//..............................................
		echo "</caption>";
		echo "<thead style='position: sticky;top: 0;box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);'>".
				"<tr class='breakPage' bgcolor = '$this->header_color'>";

		// row number ---------------------------
		if ($this->rowNumber)
			echo "<td " . ($GroupHeaderFlag ? "rowspan=2" : "") . 
				" align='center' style='padding:2px' border='$this->border' height='21px'><font
				color = '$this->header_textcolor' style='font-size:".$this->fontSize."'><b>&nbsp;ردیف</b></font></td>";

		//---------------------------------------
		// Draw Header
		
		$secondRow = "";
		$currentGroup = "";
		for ($i = 0; $i < $field_count; $i++) {
			
			if($this->columns[$i]->hidden)
				continue;
			
			if($GroupHeaderFlag)
			{
				$rowspan = !empty($this->columns[$i]->GroupHeader) ? "" : "rowspan=2";
				if($rowspan == "")
				{
					if($currentGroup != $this->columns[$i]->GroupHeader)
					{
						$index = 0;
						$currentGroup = $this->columns[$i]->GroupHeader;
						while($i+$index < $field_count)
						{
							if($this->columns[$i+$index]->GroupHeader == $currentGroup)
								$index++;
							else
								break;
						}
						$colspan = "colspan=" . $index;

						echo "<td $colspan align='center' 
						style='padding:2px' border='$this->border' height='21px'><font
						color = '$this->header_textcolor' style='font-size:".$this->fontSize."'><b>&nbsp;" . 
						$this->columns[$i]->GroupHeader . "</b></font></td>";
					}
					$secondRow .= "<td align='center' 
						style='padding:2px' border='$this->border' height='21px'><font
						color = '$this->header_textcolor' style='font-size:".$this->fontSize."'><b>&nbsp;" . 
						$this->columns[$i]->header . "</b></font></td>";
				}
				else
				{
					echo "<td $rowspan align='" . ($this->columns[$i]->align == "" ? $this->header_alignment : 
						$this->columns[$i]->align). 
						"' style='padding:2px' border='$this->border' height='21px'><font
						color = '$this->header_textcolor' style='font-size:".$this->fontSize."'><b>&nbsp;" . 
						$this->columns[$i]->header . "</b></font></td>";
				}
			}
			else
			{
				echo "<td align='" . ($this->columns[$i]->align == "" ? $this->header_alignment : 
						$this->columns[$i]->align). 
						"' style='padding:2px' border='$this->border' height='21px'><font
						color = '$this->header_textcolor' style='font-size:".$this->fontSize."'><b>&nbsp;" . 
						$this->columns[$i]->header . "</b></font></td>";
			}
			
			if ($this->columns[$i]->HaveSum != -1)
				$this->EnableSumRow = true;
		}
		echo "</tr> "; 
		if($secondRow != "")
			echo "<tr bgcolor = '$this->header_color'>" . $secondRow . "</tr>";
		echo "</thead>";
		//$this->pageRecordCounter++;
		//$this->pageRecordCounter++;
	}

	/**
	 *
	 * @param text $header عنوان ستون 
	 * @param text $field فیلد مربوطه
	 * @param text $renderFunction 
	 * @param array $renderParams پارامترهای اضافی برای تابع رندر
	 * @return \ReportColumn 
	 */
	function addColumn($header, $field, $renderFunction = "", $renderParams = array()) {
				
		$obj = new ReportColumn($header, $field, $renderFunction, $renderParams);
		$this->columns[] = $obj;
		return $obj;
	}

	function GroupOperation($index, $row) {

		if($this->groupField == "")
			return;
		
		if ($index == 0)
		{
			$this->curGroup = $row[$this->groupField];
			if($this->groupLabel)
			{
				$str = $row[$this->groupField];
				if($this->groupLabelRender != "")
					eval("\$str = " . $this->groupLabelRender . "(\$row,\$index);");
				
				echo "<tr><td style=font-size:".$this->fontSize.";font-weight:bold;height:21px; colspan=" . 
					count($this->columns) . ">" . $str . "</td></tr>";
			}
		}

		if ($this->groupField != "" && $this->curGroup != $row[$this->groupField]) {
			if ($this->groupPerPage) {
				$this->DrawFooter();

				$this->pageRecordCounter = 0;
				echo "<div style='page-break-after: always;'>&nbsp;</div>";

				$this->drawHeader(true);
			} else {
				$this->pageRecordCounter++;
				$this->DrawSummaryRow();
				echo "</table><br>";
				
				$this->drawHeader(false);
			}
			
			if($this->groupLabel)
			{
				$str = $row[$this->groupField];
				if($this->groupLabelRender != "")
					eval("\$str = " . $this->groupLabelRender . "(\$row,\$index);");
				
				echo "<tr><td style=font-size:".$this->fontSize.";font-weight:bold;height:21px; colspan=" . count($this->columns) . ">" . $str . "</td></tr>";
			}

			$this->EmptySummaryRow();
			$this->curGroup = $row[$this->groupField];
		}
	}

	function DrawFooter() {
		
		$this->pageCount++;
		$this->DrawSummaryRow();
		
		if ($this->showPageOfPage)
		{
			echo "<tr><td style=font-family:".$this->fontFamily.";font-size:".$this->fontSize.
					"; colspan=" . count($this->columns) . " align=left>";
			echo "صفحه " . $this->pageCount . "</td></tr>";
			//$this->pageRecordCounter++;
		}
		if ($this->footerContent != "")
		{
			if(!$this->footerExplicit)
				echo "<tr><td colspan=" . count($this->columns) . " >";
			echo $this->footerContent;
			if(!$this->footerExplicit)
				echo "</td></tr>";
			//$this->pageRecordCounter++;
		}
		
		echo "</table>";
	}

	function DrawRow(&$row, $index) {
		
		$color = $this->body_color;
		if ($this->rowColorRender != "")
		{
			$func = $this->rowColorRender;
			$color = $func($row);
		}
		echo "<tr align = '$this->body_alignment' bgcolor = '$color'>";

		// row number ----------------------------
		if ($this->rowNumber) {
			echo "<td height=21px width=40px style='padding:2px;direction:" . $this->columns[0]->direction . "' 
						border='$this->border' align='center'>
				<font color = '$this->body_textcolor' style='font-size:".$this->fontSize."'>&nbsp;";
			echo ($index + 1) . "</font></td>";

            		$row['number']=$index+1;
		}

		//----------------------------------------
		for ($i = 0; $i < count($this->columns); $i++) {
			//Now Draw Data
            $row['number']=$index+1;
			$this->columns[$i]->direction = "rtl";
			
			//.................................
			$style = "";
			if(is_callable($this->columns[$i]->style))
				$style = call_user_func ($this->columns[$i]->style, $row, $this->columns[$i]);
			else if($this->columns[$i]->style != "")
				$style = $this->columns[$i]->style;
			//.................................
			
			echo "<td height=21px id='col_" . $this->columns[$i]->field . "_" . ($index + 1) .  "' 
				style='/*text-align:justify;*/text-indent:-4px;padding:2px;padding-right:5px;".
					"padding-left:5px;min-width: 70px;direction:" . $this->columns[$i]->direction . ";" . 
					$style .
					($this->columns[$i]->hidden ? ";display:none;" : "") . 
					(strpos($this->columns[$i]->field,"VerticalSum_") !== false ? "background-color:" . $this->summaryRow_color : "") .
					"' border='$this->border' align='" . 
						($this->columns[$i]->align == "" ? "right" : $this->columns[$i]->align) . "'>
				<font color = '$this->body_textcolor' style='font-size:".$this->fontSize."'>&nbsp;";

			$val = "";
			//------------ remove prefix of field -------------------
			$a = preg_split("/\./", $this->columns[$i]->field);
			$this->columns[$i]->field = $a[ count($a)-1 ];
			//-------------------------------------------------------
			if (!empty($this->columns[$i]->renderFunction)) {
				
				$functionName = $this->columns[$i]->renderFunction;
				$val = $functionName(
						$row,$row[$this->columns[$i]->field],
						$this->columns[$i]->renderParams,$this->PrevRecord);
				
			}
			else if(strpos($this->columns[$i]->field, "VerticalSum_") === false) {
				$val = $row[$this->columns[$i]->field];
			}

			echo $val . "</font></td>";

			//---------------- summary Row ----------------
			$col = $this->columns[$i];
			/*@ $col ReportColumn */
			if (isset($col->HaveSum) && $col->HaveSum != -1)
			{
				if($col->rowspaning && $this->PrevRecord)
				{
					if($row[$col->field] == $this->PrevRecord[$col->field])
					{
						$flag = true;
						foreach($col->rowspanByFields as $field)
						{	
							if($row[$field] != $this->PrevRecord[$field])
								$flag = false;
						}
						if($flag)
							continue;
					}
				}
			
				if($this->columns[$i]->SummaryOfRender && !empty($this->columns[$i]->renderFunction))
				{
					$functionName = $this->columns[$i]->renderFunction;
					$val = $functionName($row,$row[$this->columns[$i]->field],
							$this->columns[$i]->renderParams,$this->PrevRecord);
					
					$this->columns[$i]->HaveSum = ($this->columns[$i]->HaveSum * 1) + (str_replace(",", "", $val) * 1);
				}
				else
					$this->columns[$i]->HaveSum = ($this->columns[$i]->HaveSum * 1) + ($row[$this->columns[$i]->field] * 1);
			}
			//---------------------------------------------
		}
		echo "</tr>";
		$this->pageRecordCounter++;

		if ($this->paging == true && $this->pageRecordCounter >= $this->page_size) {
			if ($index + 1 < $this->AllRowCount) {
				$this->DrawFooter();

				$this->pageRecordCounter = 0;

				echo "<div style='page-break-after: always;'>&nbsp;</div><br>";

				$this->drawHeader(true);
			}
		}
	}

	function DrawSummaryRow() {
		
		if ($this->EnableSumRow) {
			echo "<tr align = '$this->body_alignment' bgcolor = '$this->summaryRow_color'>";
			if ($this->rowNumber)
				echo "<td style='padding:2px;color:$this->body_textcolor'></td>";

			for ($i = 0; $i < count($this->columns); $i++) {
				
				if($this->columns[$i]->hidden)
					continue;
				
				if ($this->columns[$i]->HaveSum != -1) {
					echo "<td id='sum_" . $this->columns[$i]->field . "' height=21px style='font-size:".$this->fontSize.
							";font-weight:bold;direction:ltr;
						padding:2px;color:$this->body_textcolor' border='$this->border' align='" . $this->columns[$i]->align . "'>";

					//---------- SumRender -----------
					$val = "";
					if (!empty($this->columns[$i]->SumRener)) {
						eval("\$val = " . $this->columns[$i]->SumRener . "(\$this->columns[\$i]->HaveSum, \$this->columns);");
					} else {
						$val = $this->columns[$i]->HaveSum;
					}
					if(is_int($val) || is_float($val))
					{
						if(($val*100) % 100 == 0)
							echo number_format($val, 0);
						else
							echo number_format($val, 2);
					}					 
					else
						echo $val;
					//-------------------------------
					echo "</td>";
				}
				else {
					echo "<td height=21px style='padding-right:2px' border='0'>&nbsp;</td>";
				}
			}
			echo "</tr>";
			//$this->pageRecordCounter++;
		}
	}

	function EmptySummaryRow() {
		for ($i = 0; $i < count($this->columns); $i++) {
			if ($this->columns[$i]->HaveSum != -1) {
				$this->columns[$i]->HaveSum = 0;
			}
		}
	}

	function RowSpaning(){
		
		echo "<script>";
		$field_count = count($this->columns);
		for ($i = 0; $i < $field_count; $i++) {
			if($this->columns[$i]->rowspaning)
			{
				if(strpos($this->columns[$i]->field,"VerticalSum_") === false)
				{
					echo "
						//----------------------------------------------------------
						var elems = document.getElementsByTagName('td');
						var cnt = 0;
						var value = null;
						var firstElem = null;
						var groupValues = {
						" . 
						implode(" : null,", $this->columns[$i]->rowspanByFields) . (count($this->columns[$i]->rowspanByFields)>0 ? " : null" : "") .
						"
						}

						for(i=0; i<elems.length; i++)
							if(elems[i].id.indexOf('" . $this->columns[$i]->field . "') != -1)
							{
								var RowIndex = elems[i].id.replace('col_" . $this->columns[$i]->field . "_','');
								if (RowIndex != parseInt(RowIndex))
									continue;
								if(elems[i].innerHTML == value && elems[i].parentNode.parentNode.parentNode.id == firstElem.parentNode.parentNode.parentNode.id
								";
						foreach($this->columns[$i]->rowspanByFields as $field)
							echo " && groupValues." . $field . " == " . "document.getElementById('col_" . $field . "_' + RowIndex).innerHTML";

						echo	"
								)
								{
									cnt++;
									elems[i].style.display = 'none';
								}
								else
								{
									if(firstElem)
										firstElem.rowSpan = cnt;

									firstElem = elems[i];
									cnt=1;
									value = elems[i].innerHTML;
									";
									foreach($this->columns[$i]->rowspanByFields as $field)
										echo "groupValues." . $field . " = document.getElementById('col_" . $field . "_' + RowIndex).innerHTML;";
						echo"
								}
							}
						if(firstElem)
							firstElem.rowSpan = cnt;
					";
				}
				else
				{
					$amountField = preg_split('/_/', $this->columns[$i]->field);
					$amountField = $amountField[2];
					echo "
						//----------------------------------------------------------
						var elems = document.getElementsByTagName('td');
						var cnt = 0;
						var value = 0;
						var firstElem = null;
						var groupValues = {
						" . 
						implode(" : null,", $this->columns[$i]->rowspanByFields) . (count($this->columns[$i]->rowspanByFields)>0 ? " : null" : "") .
						"
						}

						for(i=0; i<elems.length; i++)
							if(elems[i].id.indexOf('" . $this->columns[$i]->field . "') != -1)
							{
								var RowIndex = elems[i].id.replace('col_" . $this->columns[$i]->field . "_','');
								if (RowIndex != parseInt(RowIndex))
									continue;
								if(1==1 ";
								foreach($this->columns[$i]->rowspanByFields as $field)
									echo " && groupValues." . $field . " == " . "document.getElementById('col_" . $field . "_' + RowIndex).innerHTML";

						echo	"
								)
								{
									cnt++;
									value += document.getElementById('col_" . $amountField . "_' + RowIndex).childNodes[1].childNodes[0].data.replace(/,/g,'')*1;
									elems[i].style.display = 'none';
								}
								else
								{
									if(firstElem)
									{
										firstElem.rowSpan = cnt;
										formatter = Intl.NumberFormat();
										firstElem.innerHTML = formatter.format(value);
									}
									firstElem = elems[i];
									value = document.getElementById('col_" . $amountField . "_' + RowIndex).childNodes[1].childNodes[0].data.replace(/,/g,'')*1;
									cnt=1;									
									";
									foreach($this->columns[$i]->rowspanByFields as $field)
										echo "groupValues." . $field . " = document.getElementById('col_" . $field . "_' + RowIndex).innerHTML;";
						echo"
								}
							}
						if(firstElem)
						{
							firstElem.rowSpan = cnt;
							formatter = Intl.NumberFormat();
							firstElem.innerHTML = formatter.format(value);
						}
					";
				}
				
			}
		}
		echo "</script>";
	}
	
	function AddVerticalSumColumn($fieldsArray, $amountField, $header = ""){
		
		$index = 0;
		foreach($this->columns as $col)
			if(strpos($col->field, "VerticalSum_") !== false)
				$index++;
		
		$obj = new ReportColumn($header, "VerticalSum_" . $index . "_" . $amountField);
		$obj->rowspaning = true;
		$obj->rowspanByFields = $fieldsArray;
		$this->columns[] = $obj;
		return $obj;
	}
	
	//---------------------------------------------------------------

	function ColumnOrder(){
		
		$IsAnyColumnSelected = false;
		foreach($_POST as $key => $value)
			if(strpos($key, ReportGenerator::$FieldPrefix) !== false)
			{
				$IsAnyColumnSelected = true;
				break;
			}
			
		if(!$IsAnyColumnSelected)
			return;
		
		$columnObjectArr = array();
		for($i=0; $i< count($this->columns); $i++)
			$columnObjectArr[ $this->columns[$i]->field ] = $this->columns[$i];
		
		$tempColumns = array();
		
		foreach($_POST as $key => $value)
			if(strpos($key, self::$FieldPrefix) !== false)
			{
				$field = str_replace(self::$FieldPrefix, "", $key);
				$tempColumns[ $_POST[self::$OrderPrefix . $field ] ] = $columnObjectArr[ $field ];
			}
		
		ksort($tempColumns);
		$this->columns = array();
		foreach($tempColumns as $row)
			$this->columns[] = $row;
		
		if(!empty($_POST["rpcmp_userfields"]))
		{
			$arr = explode("#", $_POST["rpcmp_userfields"]);
			for($i=1; $i < count($arr); $i=$i+2)
			{
				$fieldArr = explode("_",$arr[$i]);
				$func = $fieldArr[0];
				$field = $fieldArr[1];
				$field = explode(".", $field);
				$field = count($field)>1 ? $field[1] : $field[0];
				$obj = new ReportColumn(self::FunctionName($func) . " " . 
						$columnObjectArr[$field]->header , preg_replace('/\./','',$arr[$i]), "ReportMoneyRender");
				$obj->EnableSummary();
				$this->columns[] = $obj;
			}
		}
	}
	
	static function GetSelectedColumnsStr(){
		
		$IsAnyColumnSelected = false;
		foreach($_POST as $key => $value)
			if(strpos($key, ReportGenerator::$FieldPrefix) !== false)
			{
				$IsAnyColumnSelected = true;
				break;
			}
			
		if(!$IsAnyColumnSelected)
			return "";
		
		$tempColumns = array();
		foreach($_POST as $key => $value)
			if(strpos($key, self::$FieldPrefix) !== false)
			{
				$field = str_replace(self::$FieldPrefix, "", $key);
				
				if(isset($_POST["notQueryField_" . $field]))
					continue;
				
				$tempColumns[ $_POST[self::$OrderPrefix . $field ] ] = $field;
			}
		
		ksort($tempColumns);
		return implode(",", $tempColumns);
	}
	
	function GetColumnCheckboxList($columns = 1){
		
		$returnStr = "";
		$index = 0;
		foreach($this->columns as $row)
		{
			$title = $row->header;
			$field = $row->field;
			$returnStr .= "<div style=padding-left:10px>"
					. "<input style='width:20' type=text name='".self::$OrderPrefix.$field."' "
					. "id='".self::$OrderPrefix.$field."'>"
					. "<input onclick=ReportGenerator.setOrder(this,'".$this->MainForm."',".$this->ObjectName.") "
					. "type=checkbox name='".self::$FieldPrefix.$field."' id='".self::$FieldPrefix.$field."'>"
					. $title
					. (!$row->IsQueryField ?  
						"<input type=hidden name='notQueryField_".$field."' >" : "") .
				"</div>";
			$index++;
		}
		
		return $returnStr;
	}
	
	private static function FunctionName($func){
		switch($func)
		{
			case "sum": return "مجموع";
			case "min": return "مینیمم";
			case "max": return "ماکزیمم";
			case "average": return "میانگین";
			case "count": return "تعداد";
		}
		
	}
	
	function ReportColumns(){
		
		return '{
			xtype : "container",
			autoWidth : true,
			layout : {
				type : "table",
				columns : 3
			},
			items : [{
				xtype : "container",
				height : 157,
				width : 250,	
				autoScroll : true,
				html : "' . $this->GetColumnCheckboxList(1) . '"
			},{
				xtype : "fieldset",
				title : "ایجاد ستون سفارشی",
				height : 157,
				layout : "vbox",
				items : [{
					xtype : "combo",
					width : 220,
					store : new Ext.data.SimpleStore({
						data : [
							["sum", "مجموع"],
							["min", "مینیمم"],
							["max", "ماکزیمم"],
							["average", "میانگین"],
							["count", "تعداد"]
						],
						fields : ["id","value"]
					}),
					displayField : "value",
					valueField : "id",
					itemId : "rpcmp_newfield_func"
				},'.$this->CreateColumnCombo("","rpcmp_newfield","width : 220") .',
				{
					xtype : "button",
					text : "اضافه ستون",
					iconCls : "add",
					handler : function(){
						parent = this.up("fieldset").up("fieldset");
						functionEl = parent.down("[itemId=rpcmp_newfield_func]");
						comboEl = parent.down("[itemId=rpcmp_newfield]");
						if(comboEl.getValue() == "")
							return;
						elem = parent.down("[itemId=rpcmp_newfield_mullti]");
						elem.getStore().add({
							id : functionEl.getValue() + "_" + comboEl.getValue(),
							title : functionEl.getRawValue() + " " + comboEl.getRawValue()
						});
						hiddenEl = parent.down("[name=rpcmp_userfields]");
						hiddenEl.setValue( hiddenEl.getValue() + "#" + functionEl.getValue() + "_" + comboEl.getValue() + "#");
						comboEl.setValue();
						functionEl.setValue();						
					}
				},{
					xtype : "button",
					text : "حذف ستون",
					iconCls : "cross",
					handler : function(){
						parent = this.up("fieldset").up("fieldset");
						elem = parent.down("[itemId=rpcmp_newfield_mullti]");
						hiddenEl = parent.down("[name=rpcmp_userfields]");
						str = hiddenEl.getValue().replace("#" + elem.getValue() + "#", "");
						hiddenEl.setValue(str);
						elem.getStore().removeAt(elem.getStore().find("id",elem.getValue()));
					}
				}]				
			},{
				xtype : "multiselect",
				width : 220,
				itemId : "rpcmp_newfield_mullti",
				height : 150,
				store: new Ext.data.Store({
					fields : ["id","title"]
				}),
				ddReorder: true,
				displayField : "title",
				valueField : "id"
			},{
				xtype : "hidden",
				name : "rpcmp_userfields"
			}]
		}';
	}
	
	static function UserDefinedFields(){
		
		if(empty($_POST["rpcmp_userfields"]))
			return "";
		
		$returnArr = array();
		$arr = explode("#", $_POST["rpcmp_userfields"]);
		for($i=1; $i < count($arr); $i=$i+2)
		{
			$returnArr[] = str_replace("_", "(",$arr[$i]) . ") as " . preg_replace('/\./','',$arr[$i]);
		}

		return implode(",", $returnArr);

	}
	//---------------------------------------------------------------
	
	static function PHPArray_to_JSSimpleArray($datasource){
		
		if(count($datasource) == 0)
			return "[]";
			
		$output = "[ ";
		for($i=0; $i<count($datasource); $i++)
		{
			if(is_array($datasource[$i])){
				$RowKeys = array_keys($datasource[$i]);
				$output .= "[";
				for($j=0; $j< count($RowKeys); $j++)
					$output .= "'" . $datasource[$i][$RowKeys[$j]] . "',";
				$output = substr($output,0,strlen($output)-1) . "],";	
			}
			else
				$output .= "'" . $datasource[$i] . "',";	
		}
		$output = substr($output,0,strlen($output)-1) . "]";
		return $output;
	}
	
	private function CreateColumnCombo($fieldLabel, $itemId, $extras = ""){
		
		$colStr = array();
		foreach($this->columns as $row)
			$colStr[] = '["'.$row->queryField.'", "'.$row->header.'", "'.$row->type.'"]';
			
		$str = '{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				data : [' . implode(",", $colStr) . '],
				fields : ["id","value","type"]
			}),
			fieldLabel : "'.$fieldLabel.'",
			displayField : "value",
			valueField : "id",
			itemId : "'.$itemId.'",
			hiddenName : "'.$itemId.'"
			' . ($extras != "" ? ','. $extras : "") . '
		}';
		
		return $str;
	}
			
	function GetChartItems($SourceObject, $MainFrame, $PageName, $BeforeShowFn = ""){
		
		$items = array();
		$items[] = '{
			xtype : "textfield",
			name : "rpcmp_chartName",
			fieldLabel : "عنوان نمودار",
			labelWidth : 90,
			width : 290,
		}';
		$items[] = '{
			xtype : "combo",
			labelWidth : 90,
			width : 290,
			store : new Ext.data.SimpleStore({
				data : [
					/*["gauge", "نموار گیج"],*/
					["bar", "نمودار میله ای افقی"],
					["column", "نمودار میله ای عمودی"],
					["pie", "نمودار دایره ای"]
				],
				fields : ["id","value"]
			}),
			fieldLabel : "انتخاب نمودار",
			displayField : "value",
			valueField : "id",
			itemId : "rpcmp_series",
			hiddenName : "rpcmp_series"
		}';
		
		$items[] = '{
			xtype : "fieldset",
			width : 350,
			title : "' . self::$Chart_X_title . '",
			layout : "column",
			columns : 2,
			items:[{
					xtype : "combo",
					width : 80,
					store : new Ext.data.SimpleStore({
						data : [
							["value", "مقدار اصلی"],
							["year", "سال"],
							["month", "ماه"],
							["weekday", "روز هفته"],
							["monthday", "روز ماه"]
						],
						fields : ["id","value"]
					}),
					displayField : "value",
					valueField : "id",
					disabled : true,
					itemId : "rpcmp_x1_date",
					hiddenName : "rpcmp_x1_date"
				},'. $this->CreateColumnCombo("", "rpcmp_x1", 'width : 200,listeners : {
						select : function(combo, records){ 
							if(records[0].data.type == "date")
								this.up("fieldset").down("[itemId=rpcmp_x1_date]").enable()
							else
								this.up("fieldset").down("[itemId=rpcmp_x1_date]").disable()
						}
					}') . ',
				{
					xtype : "combo",
					width : 80,
					store : new Ext.data.SimpleStore({
						data : [
							["value", "مقدار اصلی"],
							["year", "سال"],
							["month", "ماه"],
							["weekday", "روز هفته"]
						],
						fields : ["id","value"]
					}),
					displayField : "value",
					valueField : "id",
					disabled : true,
					itemId : "rpcmp_x2_date",
					hiddenName : "rpcmp_x2_date"
				},'. $this->CreateColumnCombo("", "rpcmp_x2", 'width : 200,listeners : {
						select : function(combo,records){ 
							if(records[0].data.type == "date")
								this.up("fieldset").down("[itemId=rpcmp_x2_date]").enable()
							else
								this.up("fieldset").down("[itemId=rpcmp_x2_date]").disable()
						}
					}') . ']
			}';
		
		$items[] = '{
			xtype : "fieldset",
			width : 350,
			height : 90,
			style : "margin-right:5px",
			title : "' . self::$Chart_Y_title . '",
			layout : "hbox",
			items:[{
					xtype : "combo",
					width : 80,
					store : new Ext.data.SimpleStore({
						data : [
							["value", "مقدار واقعی"],
							["sum", "مجموع"],
							["min", "مینیمم"],
							["max", "ماکزیمم"],
							["average", "میانگین"],
							["count", "تعداد"]
						],
						fields : ["id","value"]
					}),
					displayField : "value",
					valueField : "id",
					itemId : "rpcmp_y_func",
					hiddenName : "rpcmp_y_func"
				},'. $this->CreateColumnCombo("","rpcmp_y","width : 200") . ']
			}';		
		
		$items[] = "{
			xtype : 'button',
			text : 'مشاهده نمودار',
			colspan : 2,
			style : 'float:left',
			iconCls : 'diagram',
			handler : function(){ 
				
				" . ($BeforeShowFn != "" ? $BeforeShowFn . "();" : "") . "

				$SourceObject.form = $SourceObject.get('$MainFrame')
				$SourceObject.form.target = '_blank';
				$SourceObject.form.method = 'POST';
				$SourceObject.form.action =  $SourceObject.address_prefix + '$PageName';
				$SourceObject.form.submit();
				return;
			}
		}";
		
		$items[] = '{xtype : "hidden", name : "rpcmp_chart", value : "true"}';
		
		return '{
			xtype : "container",
			layout :{type : "table", columns : 2},
			items : [' . implode(",", $items) . ']}';
	}
	
	function GenerateChart($newPage = true, $reportID = ""){
		
		global $SourceObject;
		$SourceObject = $this;
		
		global $NewPage;
		$NewPage = $newPage;
		
		if($reportID != "")
		{
			global $ReportID;
			$ReportID = $reportID;
		}
		
		require_once 'ReportGeneratorChartView.php';
		die();
	}
	
	static function DashboardSetParams($ReportID){
		
		require_once getenv("DOCUMENT_ROOT") . '/framework/ReportDB/ReportDB.class.php';
		$items = FRW_ReportItems::Get(" AND ReportID=?", array($ReportID));
		$items = $items->fetchAll();
		$chart = false;
		foreach($items as $row)
		{
			$_POST[ $row["ElemName"] ] = $row["ElemValue"];
			$_REQUEST[ $row["ElemName"] ] = $row["ElemValue"];
			if($row["ElemName"] == "rpcmp_series")
				$chart = true;
		}
		return $chart;
	}
}

class ReportColumn {

	public $header;
	public $GroupHeader = "";
	public $field;
	public $queryField;
	
	public $renderFunction;
	public $renderParams;
	
	public $align;
	public $HaveSum;
	public $SumRener;
	public $direction;
	public $SummaryOfRender;
	
	public $rowspaning = false;
	public $rowspanByFields = array();
	
	public $hidden = false;
	public $style = "";
	public $ExcelRender = true;
	
	public $type = "string"; // string or date
	public $IsQueryField = true;

	public function ReportColumn($header, $field, $renderFunction = "", $renderParams = "") {
		$this->header = $header;
		$this->field = $field;
		$this->queryField = $field;
		$this->renderFunction = $renderFunction;
		$this->renderParams = $renderParams;
		$this->HaveSum = -1;
		$this->align = "";
		$this->direction = "rtl";
		$this->SumRener = "";
	}

	public function EnableSummary($SummaryOfRender = false) {
		$this->HaveSum = 0;
		$this->direction = "ltr";
		$this->SummaryOfRender = $SummaryOfRender;
	}

}

function ReportMoneyRender($row, $value){
		if(!empty($_REQUEST["excel"]))
			return $value;
		else
		{
			if((int)$value < 0)
				return "<font color=red>" . number_format($value) . "</font>";
			return number_format((int)$value);
		}
	}
	
function ReportDateRender($row, $value){
		return DateModules::miladi_to_shamsi($value);
	}
	
function ReportYesNoRender($row, $value){
	return $value == "YES" ? "بلی" : "خیر";
}

function ReportTickRender($row, $value){
	return $value == "YES" ? "٭" : "";
}

?>
