<?php
//---------------------------
// programmer:	Jafarkhani
// create Date:	88.03.24
//---------------------------

class sadaf_datagrid
{
	private $id;
	private $columns;
	private $divName;
	private $Url;
	private $Method;
	private $buttons = array();
	
	public $baseParams;
	
	public $remoteSort;
	public $DefaultSortField;
	/**
	 * "desc" is the default value
	 *
	 * @var string
	 */
	public $DefaultSortDir;
	/**
	 * Name of the column within the datasource that contains a record identifier value.
	 *
	 * @var string
	 */
	public $primaryKey;
	/**
	 * The title text to display in the panel header (defaults to '').
	 *
	 * @var string
	 */
	public $title;
	/**
	 * The width of this grid in pixels (defaults to auto).
	 *
	 * @var int
	 */
	public $width;
	/**
	 * The height of this grid in pixels (defaults to auto).
	 *
	 * @var int
	 */
    public $height;
	public $autoExpandColumn;
	/**
	 * If 'true',you can select several rows by 'ctrl' key.The default value is false.
	 *
	 * @var string
	 */
	public $multipleSelectRow;
	//---------------------------------
	/**
	 * set true for adding delete button
	 *
	 * @var boolean
	 */
	public $deleteButton;
	/**
	 * the name of javascript function for deleting
	 *
	 * @var string
	 */
	public $deleteHandler;
 	/** set true for adding add button
	 *
	 * @var boolean
	 */
	public $addButton;
	
	/**
	 * the name of javascript function for adding
	 *
	 * @var string
	 */
	public $addHandler;
	
	/** 
	* set true for adding option button
	*@var boolean
	*/
	public $optionButton;
	public $optionText;
	public $optionTooltip;
	public $optionHandler;
	
	/**
	 * If true,the rows of grid become unselectable,default value is false.
	 *
	 * @var bool
	 */
	public $disableSelection;
	public $rowSelectHandler;
	/**
	 * you can use the search panel in topbar of grid by setting this field "true",the default value is true
	 *
	 * @var bool
	 */
	public $EnableSearch;
	/**
	 * If you want your grid that  just maked and not loaded first,set this field true.
	 * Then you can render and load it by init function."Ext.onReady(init);".
	 * The default value is "false"
	 * 
	 *
	 * @var bool
	 */
	public $notRender = false;
	
	/**
	 * if set true the grid being a EditorGridPanel instead of GridPanel.default is false
	 *
	 * @var boolean
	 */
	public $editorGrid;
	
	public $remoteGroup;
	public $EnableGrouping;
	public $DefaultGroupField;
	public $GroupingNumberAppendix = " رکورد";
	public $startCollapsed = false; 
	/**
	 this function render the group header The return Text must be a template 
	 * for example return "{values.text} : دارای {values.rs.length} رکورد"  
	 * Notice : if you want to write command in { } you must do it like {[]} 
	 * for example: return '{values.rs.length} {[ values.rs.length > 1 ? "Items" : "Item" ]}' 
	 * values =[group,groupId,startRow,rs,cls,style]
	 *
	 * @var unknown_type
	 */
	public $groupHeaderTpl;
	
	
	public $EnableSummaryRow = false;
	/**
	 * Default value is true
	 *
	 * @var unknown_type
	 */
	public $EnablePaging = true;
	
	public $PrintButton = false;
		
	public $EnableRowNumber = false;
	
	public $collapsible;
	public $collapsed;
	
	public $pageSize = 25;
	public $disableFooter = false;
	
	public $enableRowEdit = false;
	// params : store,record
	public $rowEditOkHandler;

	public $HeaderMenu = true;
	public $hideHeaders = false;
	
	private $formID;
	private $CurrencyStringRow;
	private $CurrencyStringRowDataIndex;
	private $plugins = array();
	private $dateColumns = array();
	
	public $emptyTextOfHiddenColumns = false;
	public $ScrollPaging = false;
	
	public $scroll = "vertical"; //values : vertical, horizental, both
	
	public $StoreLoadFirst = true;
	public $EnableCellEditing = false;

	public $PageSizeChange = true;
	public $selType = "rowmodel";
	
	public $previewField = "";
	
	/**
	 * vertical lines for grid
	 * @var boolean 
	 */
	public $columnLines = false;

	public $loadMask = true;
	public $ExcelButton = true;
	//-------------------------------------------------------------------------
	/**
	 * get instance of sadaf_datagrid class
	 *
	 * @param string $gridName the name of grid
	 * @return sadaf_datagrid
	 */
	function sadaf_datagrid($ObjectName, $Url, $divID = "", $formID="")
	{
		$this->id = $ObjectName;
		$this->divName = $divID;
		$this->formID = $formID;
		
		$this->Url = $Url;
		$this->Method = "POST";
		$this->baseParams = "";		

		$this->DefaultSortDir = "desc";		
		
		$this->title = '';
		$this->width = "";
		$this->height = "";
		
		$this->deleteButton = false;
		$this->addButton = false;
		$this->optionButton = false;
		$this->optionTooltip = "";
		
		$this->EnableSearch = true;
		$this->multipleSelectRow = false;
		
		$this->disableSelection = false;
		
		$this->remoteGroup = true;
		$this->EnableGrouping = false;
		$this->DefaultGroupField = "";
		$this->groupHeaderTpl = "";
		
		$this->collapsed = true;
		$this->collapsible = false;
		
		$this->CurrencyStringRow = "";
	}
	//-------------------------------------------------------------------------
	/**
	 * add column to your grid
	 *
	 * @param String $header
	 * @param String $dataIndex
	 * @param String $type  {'string','int','float','boolean','date'}
	 * @return GridColumn
	 */
	function addColumn($header,$dataIndex,$type="",$hidden=false)
	{
		$obj = new GridColumn($header,$dataIndex,$type,$hidden);
		$no = isset($this->columns) ? count($this->columns) : 0;
		$this->columns[$no] = $obj;

		return $obj;
	}
	
	function openHeaderGroup($text)
	{
		$this->columns[count($this->columns)] = array("mode" => "open", "text" => $text);
	}
	function closeHeaderGroup()
	{
		$this->columns[count($this->columns)] = array("mode" => "close");
	}
	//-------------------------------------------------------------------------
	function addButton($id,$text,$iconCls,$handler, $tooltip = "")
	{
		$this->buttons[] = "{itemId: '$id',text: '$text',iconCls: '$iconCls',handler: $handler, tooltip : '$tooltip'}";
	}
	//-------------------------------------------------------------------------
	function addObject($object)
	{
		$this->buttons[] = $object;
	}
	//-------------------------------------------------------------------------
	function addPlugin($object)
	{
		$this->plugins[] = $object;
	}
	//-------------------------------------------------------------------------
	function makeGrid_returnObjects()
	{
		if($this->ScrollPaging)
			return $this->CreateBufferGrid();
		
		return $this->getJsClasses_returnObject();
		
	}
	//-------------------------------------------------------------------------
	private function getJsClasses_returnObject()
	{
		$fields = "";
		for ($i=0; $i < count($this->columns); $i++)
			if(is_object($this->columns[$i]))
				$fields .= "{name: '" . $this->columns[$i]->dataIndex . "'" . 
					($this->columns[$i]->type == GridColumn::ColumnType_int ||
					 $this->columns[$i]->type == GridColumn::ColumnType_money ? ",type : 'int'" : "") . "},";
		$fields = substr($fields, 0, strlen($fields) - 1);
		//**********************************************************************
		$store = "Ext.create('Ext.data.Store', {
				pageSize: " . $this->pageSize . ",
				fields:[" . $fields . "]," .
				(($this->EnableGrouping && $this->DefaultGroupField != "") ?
				"groupField:'" . $this->DefaultGroupField . "',remoteGroup: true," : "") .
				"remoteSort: true," .
				($this->enableRowEdit || $this->EnableCellEditing ? "listeners : {update : " . $this->rowEditOkHandler . "}," : "") .
				"proxy: {
					type: 'jsonp',
					url: '" . $this->Url . "',
					form : '" . $this->formID . "',
					reader: {
						root: 'rows',
						totalProperty: 'totalCount',
						messageProperty : 'message'
					}
				},
				sorters: [{
					property: '" . $this->DefaultSortField . "',
					direction: '" . $this->DefaultSortDir . "'
				}]
			})";
		
		//************************************************
		$grid = "new Ext.grid.GridPanel({
			selType : '" . $this->selType . "',
			 columns: [" .
				($this->EnableRowNumber ? "new Ext.grid.RowNumberer()," : "") .
				$this->setExtColumnModel() . "],
						
			store: " . $store . ",
			scroll: '" . $this->scroll . "', 
			columnLines: " . ($this->columnLines ? "true" : "false") . " ,
			title:'" . $this->title . "'," .
			"hideHeaders: " . ($this->hideHeaders ? "true," : "false,") .
			($this->width == "" ? "autoWidth:true," : "width: " . $this->width . ",") .
			($this->height == "" ? "autoHeight:true," : "height: " . $this->height . ",") .
			" viewConfig: {
				stripeRows: true,
				loadMask : ".($this->loadMask ? "true" : "false").",
				enableTextSelection: true
				" .
				($this->previewField != "" ? "
				,plugins: [{
					ptype: 'preview',
					bodyField: '" . $this->previewField . "',
					expanded: true					
				}]" : "") . "
			},listeners :{" .
				($this->rowSelectHandler ? "select: " . $this->rowSelectHandler : "") .
			"}
			,plugins : [";
		
		$grid .= $this->EnableCellEditing ? "new Ext.grid.plugin.CellEditing({clicksToEdit: 2})," : "";
		
		if($this->enableRowEdit)
		{
			$grid .= "new Ext.grid.plugin.RowEditing(";
			if(count($this->dateColumns) != 0)
			{
				$grid .= "{listeners : {beforeedit: function(editor,e){";
				for($k=0; $k < count($this->dateColumns); $k++)
					$grid .= "
					if(e.grid.columns.findObject('dataIndex','" . $this->dateColumns[$k] . "').getEditor().xtype != 'displayfield' &&
						e.record.data." . $this->dateColumns[$k] . " != null)
					{
						e.record.data." . $this->dateColumns[$k] . " =
						MiladiToShamsi(e.record.data." . $this->dateColumns[$k] . ".substr(0,10) );
					}		
					";
				$grid .= "}}}";
			}
			$grid .=  "),";
		}
				$grid .= ($this->EnableSearch) ?	 "new Ext.ux.grid.Search({mode:'remote'})," : "";
				$grid .= (count($this->plugins) != 0) ? implode(",", $this->plugins) . "," : "";

				$grid = $grid[strlen($grid)-1] != "[" ? substr($grid,0,strlen($grid)-1) : $grid;
			$grid .= "]";

		$grid .= $this->EnableSummaryRow ? ",features: [{ftype: 'summary'}]" : "";
		
		if($this->collapsible)
			$grid .= ",collapsible : true,titleCollapse:true,collapsed:" . ($this->collapsed ? "true" : "false");
		if($this->disableSelection)
			$grid .= ",disableSelection: true";
			
		$grid .= $this->multipleSelectRow ? ",multiSelect: true" : ",multiSelect: false";
		
		
		if($this->EnablePaging)
		{
			$grid .= ",bbar: 
				new Ext.PagingToolbar({
					ExcelButton : " . ($this->ExcelButton ? "true" : "false") . ",
					store:Ext.getCmp(Ext.id)," . 
					($this->PageSizeChange ? " 
					plugins: [new Ext.ux.PageSizePlugin()]," : "") . " 
					pageSize: $this->pageSize,
			        displayInfo: true
			    })";
		}
		else if(!$this->disableFooter)
		{	
			$grid .=  ",bbar: new Ext.ExtraBar({
			        	store: Ext.getCmp(Ext.id),
						ExcelButton : " . ($this->ExcelButton ? "true" : "false") . ",
			            displayInfo: true			            
			       		})";
		}	
		$grid .= ",tbar:[";
		$grid .= ($this->optionButton) ?
					"'-',{
						text: '" . $this->optionText . "',
						tooltip:'" . $this->optionTooltip . "',
						iconCls: 'option',
						handler: " . $this->optionHandler . "}," : "";

		$grid .= ($this->addButton) ?
					"'-',{
						text: 'ايجاد',
						tooltip:'ايجاد ركورد جديد',
						iconCls: 'add',
						handler: " . $this->addHandler . "}," : "";
		
		$grid .= ($this->PrintButton) ?
					"'-',{
						text: 'چاپ',
						tooltip:'چاپ رکوردهای گرید',
						iconCls: 'print',
						handler: function(){
							var grid = this.up('grid');
							Ext.ux.grid.Printer.printAutomatically = false;
							Ext.ux.grid.Printer.print(grid);
						}
					}," : "";

		$grid .= ($this->deleteButton) ?
					"'-',{
						text: 'حذف',
						tooltip: 'حذف رديفهاي انتخابي',
						iconCls:'remove',
						handler: " . $this->deleteHandler . "}," : "" ;

		for($i=0; $i < count($this->buttons); $i++)
			$grid .= "'-'," . $this->buttons[$i] . ",";
		$grid = ($grid[strlen($grid)-1] == ",") ? substr($grid,0,strlen($grid)-1) : $grid;
		$grid .= "]";

		if ($this->EnableGrouping)
		{
			if($this->EnableSummaryRow)
			{
				$grid .= ",features : [{
						ftype: 'groupingsummary',
						groupHeaderTpl: " . ($this->groupHeaderTpl != "" ? "'" . $this->groupHeaderTpl . "'" : 
							"'{[values.rows[0].data.".$this->DefaultGroupField."]} ({[values.rows.length]}" . $this->GroupingNumberAppendix . ")'") .
                        ",startCollapsed: " . ($this->startCollapsed ? "true" : "false") .
                        "}]";
            } else {
                $grid .= ",features : [
					Ext.create('Ext.grid.feature.Grouping',{
						groupHeaderTpl: " . ($this->groupHeaderTpl != "" ? "'" . $this->groupHeaderTpl . "'" : 
							"'{[values.rows[0].data.".$this->DefaultGroupField."]} ({[values.rows.length]}" . $this->GroupingNumberAppendix . ")'") .
						",startCollapsed: " . ($this->startCollapsed ? "true" : "false") .
				"})" .
				($this->EnableSummaryRow ? ",{ftype: 'summary'}" : "")
				. "]";
			}
		}
		if($this->CurrencyStringRowDataIndex != "")
		{
			$grid .= "
				,dockedItems : [{
					xtype : 'toolbar',
					dock : 'bottom',
					style : 'padding-right : 5px; text-align: right;'
				}]";
		}

		$grid .= ",listeners : {
			afterrender : function(){";
				
		if($this->CurrencyStringRowDataIndex != "")
			$grid .= "this.getStore().on('load',Ext.bind(function(){
					var value = this.getStore().sum('".$this->CurrencyStringRowDataIndex."');
					this.getDockedItems('toolbar[dock=\"bottom\"]')[0].update(
						'<b> جمع :&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
						+ Ext.util.Format.Money(value) + '</b>&nbsp;<br><br>' + CurrencyToString(value) + '<br>&nbsp;');
				},this));";
		
		$grid .= $this->StoreLoadFirst ? "this.getStore().load();" : "";
		$grid .= "},
			beforerender : function(){
				var pagingToolbar = this.getDockedItems('pagingtoolbar');
				var ExtraToolbar = this.getDockedItems('extrabar');
				if(pagingToolbar.length > 0)
					pagingToolbar[0].bind(this.getStore());
				if(ExtraToolbar.length > 0)
					ExtraToolbar[0].bind(this.getStore());
				}

			}
		})";
        //***************************************************
		return $grid;
	}
	//-------------------------------------------------------------------------
	private function setExtColumnModel()
	{
		$str = "";
		$cur_ColSpan = "";
		$flex = false;
		for($i = 0; $i < count($this->columns); $i++)
		{
			if($cur_ColSpan != $this->columns[$i]->ColSpanText)
			{
				if($cur_ColSpan != "" || $this->columns[$i]->ColSpanText == "")
				{
					$str = substr($str, 0, strlen($str) - 1);
					$str .= "]},";
				}

				$cur_ColSpan = $this->columns[$i]->ColSpanText;
				if($cur_ColSpan != "")
					$str .= "{header : '" . $cur_ColSpan . "', columns :[";
			}
			
			if(is_array($this->columns[$i]))
			{
				$str = $this->columns[$i]["mode"] == "open" ? 
						$str . "{text : '" . $this->columns[$i]["text"] . "',columns:[" : 
						substr($str, 0, strlen($str) - 1) . "]},";
				continue;
			}
			
			$str .= "{";
			if($this->autoExpandColumn == "" && !$flex)
			{
				if(!$this->columns[$i]->hidden)
				{
					$str .= "flex : 1,";
					$flex = true;
				}
			}
			else if($this->autoExpandColumn == $this->columns[$i]->dataIndex)
				$str .= "flex : 1,";
			if(!$this->HeaderMenu)
				$str .= "menuDisabled : true,";
			$tempArr = get_object_vars($this->columns[$i]);
			$tempKeyArr = array_keys($tempArr);

			for($j = 0; $j <count($tempArr); $j++)
			{
				if(isset($tempArr[$tempKeyArr[$j]]))
				{
					switch($tempKeyArr[$j])
					{
						case "ColSpanText":
							continue;
						case "renderer":
						case "summaryRenderer":
						case "width":
						case "dblclick":
							$str .= $tempKeyArr[$j] . ": " . $tempArr[$tempKeyArr[$j]] . ", ";
							break;
						case "type":
							if($tempArr[$tempKeyArr[$j]] == "")
								$str .= "type:'string', ";
							if($tempArr[$tempKeyArr[$j]] != GridColumn::ColumnType_money)
								$str .= "type: '" . $tempArr[$tempKeyArr[$j]] . "',";
							
							if($tempArr[$tempKeyArr[$j]] == GridColumn::ColumnType_date || 
								$tempArr[$tempKeyArr[$j]] == GridColumn::ColumnType_datetime)
							{
								$this->dateColumns[] = $this->columns[$i]->dataIndex;
								$str .= "renderer: function(v){if(v == '' || v == null)return ''; ".
										"return MiladiToShamsi(v.toString().substring(0,10),'Y/m/d') " .
										($tempArr[$tempKeyArr[$j]] == GridColumn::ColumnType_datetime ? 
										" + (v.toString().length>11 ? ' ' + v.toString().substring(11) : '')" : "").
										" ;}, ";
							}
							if($tempArr[$tempKeyArr[$j]] == GridColumn::ColumnType_money)
							{
								$str .= "type: 'numbercolumn', tdCls : 'ltrDir',";
								$str .= "renderer: Ext.util.Format.Money, ";
							}
							break;

						case "editor":
							$st = $tempArr[$tempKeyArr[$j]];
							$st = str_replace("@@",  $tempArr["dataIndex"], $st);
							$str .= "editor: " . $st . ", ";
							if(strpos($st, "ComboBox") != false)
								$str .= "renderer: function(v,p,r,rowIndx,colIndex){
											var combo = this.getGridColumns()[colIndex].getEditor();
											var store = combo.getStore();
											store.clearFilter();
											var index = store.findExact(combo.valueField, v);
											var record = store.getAt(index);
											return (record) ? record.get(combo.displayField) : '';
										}, ";
							break;
							
						case "hidden":
							$flag = $tempArr[$tempKeyArr[$j]] ? "true" : "false";
							$str .= $tempKeyArr[$j] . ": " . $flag . ",hideMode : 'display',";
							break;
						case "searchable":
							$flag = $tempArr[$tempKeyArr[$j]] ? "true" : "false";
							$str .= $tempKeyArr[$j] . ": " . $flag . ", ";
							break;
						case "ellipsis":
							if($tempArr[$tempKeyArr[$j]] > 0){
								$str .= "renderer: function(v,p){
									p.tdAttr = 'data-qtip=\"' +
									(v != null ? v.replace(/\\n/g, \"<br>\") : v ) + '\"';
									return Ext.util.Format.ellipsis(v," . $tempArr[$tempKeyArr[$j]] . ",false);}, ";
							}
							break;
						default:
							$str .= $tempKeyArr[$j] . ": '" . $tempArr[$tempKeyArr[$j]] . "', ";
					}
				}
			}
			
			if(($this->emptyTextOfHiddenColumns && $this->columns[$i]->hidden) || $this->columns[$i]->emptyText)
				$str .= "renderer : function(){return \"\";}, ";
			
			$str = substr($str, 0, strlen($str) - 2) . "},";
		}
		$str = substr($str, 0, strlen($str) - 1);
		if($cur_ColSpan != "")
		{
			$str .= "}]";
		}
		return $str;
	}
	//-------------------------------------------------------------------------
	function AddCurrencyStringRow($summaryColumnIndex)
	{
		$this->CurrencyStringRow = "" .
			$this->id."_grid.on('viewready',function(){
				
				var value = 
				Ext.DomHelper.insertAfter(".$this->id."_grid.plugins[0].getSummaryNode(),
					'<div id=\"".$this->id."_fa_summary\" style=\"font-weight:bold\" class=\"x-grid3-summary-row\">'+
					'&nbsp;<br>' + CurrencyToString(value) + '<br>&nbsp;</div>',true);
			});

			".$this->id."_store.on('load',function(){
				var sums = ".$this->id."_grid.plugins[0].getSummaryValuesArray();
				var value = sums[".$summaryColumnIndex."];
				document.getElementById('".$this->id."_fa_summary').innerHTML = '&nbsp;<br>' + CurrencyToString(value) + '<br>&nbsp;';
			});
		});";
		$this->CurrencyStringRowDataIndex = $summaryColumnIndex;
	}

	static function booleanRender($trueValue = "YES", $trueIcon = "tick.gif", $falseIcon = "cross.gif"){
		return "function(v,p,r){ ".
					"if(v == '".$trueValue."') return '<img src=/generalUI/icons/".$trueIcon.">';".
				($falseIcon != "" ? " else return '<img src=/generalUI/icons/".$falseIcon.">';" : "").
				" }";
	}
	
	static function checkRender($checkValue = "1", $crossShow = false){
		return "function(v,p,r){ ".
					"if(v == '".$checkValue."') return '<img src=/generalUI/icons/tick.gif>';".
				($crossShow ? " else return '<img src=/generalUI/icons/cross.gif>';" : "").
				" }";
	}
	
	static function buttonRender($text, $icon, $handler){
		
		return '<div class="x-btn x-btn-default-small x-icon-text-right x-btn-icon-text-right x-btn-default-small-icon-text-right">'.
  		'<button type="button" onclick="'.$handler.'" class="x-btn-center">'.
		'<span class="x-btn-inner"">'.$text.'</span>'.
		'<span class="x-btn-icon '.$icon.'"></span></button></div>';
	}
}

class GridColumn
{
	public $id;
	public $align;
	
	public $header;
	public $dataIndex;
	
	public $width ;    //Number
	public $sortable ; //Boolean
	
	const ColumnType_date = "datecolumn";
	const ColumnType_datetime = "datetimecolumn";
	const ColumnType_boolean = "booleancolumn";
	const ColumnType_string = "string";
	const ColumnType_int = "numbercolumn";
	const ColumnType_money = "money";
	
	public $type;
	
	public $hidden = false;    //Boolean
	
	/**
	   * The function to use to process the cell's raw data to
	   *      return HTML markup for the grid view.
	   * <script type="text/javascript">
	   * function renderTopic(value, p, record){
	   *     return String.format(
	   *             '<b><a href="showthread.php?t={2}" target="_blank">{0}</a></b><a href="forumdisplay.php?f={3}" target="_blank">{1} Forum</a>',
	   *             value, record.data.forumtitle, record.id, record.data.forumid);
	   * }
	   * </script>
	   * $Col = new GridColumn();
	   * @var unknown_type
	   */
	public $renderer;  //Function
	   
	/**
	   * for example "new Ext.form.NumberField({allowBlank: false,allowNegative: false,maxValue: 100})" 
	   *
	   * @var string
	   */
	public $editor;
	
	const SummeryType_sum = "sum";
	const SummeryType_count = "count";
	const SummeryType_min = "min";
	const SummeryType_max = "max";
	const SummeryType_average = "average";
	public $summaryType;
	
	/**
	 * parameters : value, summaryData, dataIndex
	 * @var type 
	 */
	public $summaryRenderer;
	
	public $dblclick;
	public $searchable = true;
	
	public $emptyText = false;
	
	public $ellipsis = 0;
	
	public $ColSpanText = "";
	/**
   * Enter description here...
   *
   * @param String $header
   * @param String $dataIndex
   * @param String $type  {'string','int','float','boolean','date'}
   * @return GridColumn
   */
	public function  __construct($header, $dataIndex, $type = GridColumn::ColumnType_string, $hidden = false)
	{
		$this->header= $header;
		$this->dataIndex = $dataIndex;
		$this->type = $type;
		$this->hidden = $hidden;
		$this->sortable = true;
	}
}

class ColumnEditor
{
	/*static function ComboBox($comboBoxID, $extComboID = "", $listeners = "")
	{
		$str = "new Ext.form.ComboBox({";
		$str .= ($extComboID != "") ? "id: '$extComboID'," : "";
        $str .= "typeAhead: true,
                triggerAction: 'all',
				transform: '$comboBoxID',
				lazyRender: true,
				listClass: 'x-combo-list-small',
				listeners: {" . $listeners . "}
            })";
		return $str;
	}*/

	static function ComboBox($data, $valueField, $displayField, $comboID = "", $itemId = "" , $allowBlank = false)
	{  
		$Blank = !$allowBlank ? "false" : "true" ; 
		 
		$str = "
			new Ext.form.ComboBox({ 
				" . ($comboID != "" ? "id : '$comboID'," : "") . "
				store : Ext.data.Store({
					fields : ['" . implode("','",array_keys($data[0])) . "'],
					data : " . json_encode($data) . "
				            }),				
				typeAhead: true, allowBlank: $Blank , 
				displayField : '" . $displayField . "',
				valueField : '" . $valueField . "',
                triggerAction: 'all',
				lazyRender: true,
                listClass: 'x-combo-list-small'";
		
		$str .= ($itemId != "") ? ",itemId : '$itemId'" : "";
		$str .= "})";
		return $str;
	}

	static function SlaveComboBox($data, $valueField, $displayField, $masterfield, $masterComboID)
	{
		$str = "
			new Ext.form.ComboBox({
				store : Ext.data.Store({
					fields : ['" . implode("','",array_keys($data[0])) . "'],
					data : " . json_encode($data) . "
				}),
				typeAhead: true,
				displayField : '" . $displayField . "',
				valueField : '" . $valueField . "',
                triggerAction: 'all',
				lazyRender: true,
                listClass: 'x-combo-list-small',
				listeners:{
					'beforeSetValue': function(combo,record){
						Ext_BindDropDown('$masterComboID',this.id,'$masterfield');
					},
					'afterSetValue': function(){
						Ext_BindDropDown('$masterComboID',this.id,'$masterfield');
					},
					'expand': function(){
						Ext_BindDropDown('$masterComboID',this.id,'$masterfield');
					}
				}
            })";		
		return $str;
	}

	static function DateField($allowBlank = false, $itemId = "")
	{
		$st = "new Ext.form.DateField({allowBlank:";
		$st .= $allowBlank ? "true" : "false";
		$st .= ($itemId != "") ? ",itemId : '$itemId'" : "";
		$st .= ",format: 'Y/m/d'})";
		return $st;
	}

	static function SHDateField($allowBlank = false, $itemId = "")
	{
		$st = "new Ext.form.SHDateField({allowBlank:";
		$st .= $allowBlank ? "true" : "false";
		$st .= ($itemId != "") ? ",itemId : '$itemId'" : "";
		$st .= ",format: 'Y/m/d'})";
		return $st;
	}

	static function NumberField($allowBlank = false, $itemId = "", $maxValue = "")
	{
		$st = "new Ext.form.NumberField({decimalPrecision:4,hideTrigger:true,allowBlank: ";
		$st .= ($allowBlank) ? "true" : "false";
		$st .= ($itemId != "") ? ",itemId : '$itemId'" : "";
		$st .= ($maxValue != "") ? ",maxValue: " . $maxValue  : "";
		$st .= "})";
		return $st;
	}
	
	static function TimeField($allowBlank = false, $itemId = "")
	{
		$st = "new Ext.form.TimeField({format : 'H:i:s',allowBlank: ";
		$st .= ($allowBlank) ? "true" : "false";
		$st .= ($itemId != "") ? ",itemId : '$itemId'" : "";
		$st .= "})";
		return $st;
	}
	
	static function CurrencyField($allowBlank = false, $itemId = "", $maxValue = "")
	{
		$st = "new Ext.form.CurrencyField({hideTrigger:true,allowBlank: ";
		$st .= ($allowBlank) ? "true" : "false";
		$st .= ($itemId != "") ? ",itemId : '$itemId'" : "";
		$st .= ($maxValue != "") ? ",maxValue: " . $maxValue  : "";
		$st .= "})";
		return $st;
	}

    static function CheckField($itemId = "", $inputValue = "1")
	{
		$st = " new Ext.form.Checkbox({
                    inputValue: '$inputValue'";
		$st .= ($itemId != "") ? ",itemId : '$itemId'" : "";
        $st .= "})";
		return $st;
	}
	
	static function TextField($allowBlank = false, $itemId = "", $extraProperties = "")
	{
		$st = "new Ext.form.TextField({allowBlank: " ;
		$st .= ($allowBlank) ? "true" : "false";
		$st .= ($itemId != "") ? ",itemId : '$itemId'" : "";
		$st .= ($extraProperties != "") ? "," . $extraProperties : "";
		$st .= "})";
		return $st;
	}
		static function TextArea($allowBlank = false, $itemId = "")
	{
		$st = "new Ext.form.TextArea({rows : 3,allowBlank: " ;
		$st .= ($allowBlank) ? "true" : "false";
		$st .= ($itemId != "") ? ",itemId : '$itemId'" : "";
		$st .= "})";
		return $st;
	}

}
?>
