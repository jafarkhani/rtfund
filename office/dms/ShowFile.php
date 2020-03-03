<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	94.06
//-------------------------
require_once '../../header.inc.php';
require_once inc_phpExcelReader;

if($_REQUEST["ObjectID"] == "")
	die();

$ObjectID = $_REQUEST["ObjectID"];
$DocumentID = isset($_REQUEST["DocumentID"]) ? (int)$_REQUEST["DocumentID"] : "";
$RowID = isset($_REQUEST["RowID"]) ? (int)$_REQUEST["RowID"] : "";
$DocTypes = isset($_REQUEST["DocType"]) ? $_REQUEST["DocType"] : "";

InputValidation::validate($_REQUEST["DocType"], InputValidation::Pattern_NumComma);

$query = "select RowID,PageNo,FileType,FileContent,DocumentID,ObjectID,DocDesc
	from DMS_DocFiles df join DMS_documents d using(DocumentID)
	where ObjectID=:o " 
		. (!empty($RowID) ? " AND RowID=:r" : "")
		. (!empty($DocumentID) ? " AND df.DocumentID=:d" : "")
		. (!empty($DocTypes) ? " AND d.DocType in(".$DocTypes.")" : "")."
	order by PageNo";

$params = array(":o" => $ObjectID);

if(!empty($RowID))
	$params[":r"] = (int)$RowID;
if(!empty($DocumentID))
	$params[":d"] = (int)$DocumentID;

$dt = PdoDataAccess::runquery($query, $params);
if(count($dt) == 0)
	die();

if(isset($_REQUEST["inline"]))
{
	$FileContent = $dt[0]["FileContent"] . file_get_contents(getenv("DOCUMENT_ROOT") . "/storage/documents/" . 
		$dt[0]["RowID"] . "." . $dt[0]["FileType"]);
	
		$file = "file"; 
		if(array_search ($dt[0]["FileType"], array("jpg","jpeg","png","gif")) !== false)
			$file = "image/" . $dt[0]["FileType"];
		if($dt[0]["FileType"] == "pdf" )
			$file = "application/" . $dt[0]["FileType"];
		
		header('Content-disposition:inline; filename=file.' . $dt[0]["FileType"]);
		header('Content-type: '. $file);
		header('Pragma: no-cache');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header("Content-Transfer-Encoding: binary");

		echo $FileContent;
		die();	
}

function data_uri($content, $mime) {
    $base64 = base64_encode($content);
    return ('data:' . $mime . ';base64,' . $base64);
}

echo '<script src="/generalUI/pdfobject/pdfobject.js"></script>';
echo '<meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>';
echo "<center>";
foreach($dt as $file)
{
	echo "<div style='width:100%;' align=center><hr>صفحه " . $file["PageNo"] . "<hr></div>";
	if($file["FileType"] == "pdf")
	{
		echo "<div id=pdf_DIV_" . $file["RowID"] . " style='height:100%'></div></br>";
		echo '<script>var options = {
				pdfOpenParams: {
					navpanes: 0,
					toolbar: 0,
					statusbar: 0,
					view: "FitV",
					pagemode: "thumbs",
					page: 2
				},
				forcePDFJS: true,
				PDFJS_URL: "/generalUI/pdfobject/viewer.html"
			};
			var myPDF = PDFObject.embed("/office/dms/ShowFile.php?DocumentID='.$file["DocumentID"].
				'&RowID=' . $file["RowID"] . '&ObjectID='.$file["ObjectID"].'&inline=true","#pdf_DIV_'.$file["RowID"].'", options);
			</script>';
	} 
	else if(array_search ($file["FileType"], array("jpg","jpeg","png","gif")) !== false)
	{
		$FileContent = $file["FileContent"] . 
		file_get_contents(getenv("DOCUMENT_ROOT") . "/storage/documents/" .	$file["RowID"] . "." . $file["FileType"]);
		echo "<img style='width:100%' src=" . data_uri($FileContent, 'image/jpeg') . " /></br>";
	}
	else
	{
		echo '<a href="/office/dms/ShowFile.php?DocumentID='.$file["DocumentID"].
				'&RowID=' . $file["RowID"] . '&ObjectID='.$file["ObjectID"].'&inline=true" target=_blank>' . 
				$file["DocDesc"] . "." . $file["FileType"] . "</a>";
	}
}
echo "</center>";

?>