<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	98.02
//-------------------------
require_once('../../../header.inc.php');
require_once './dms.class.php';

$DocType = $_REQUEST["DocType"];
$temp = DMS_documents::SelectAll("DocType=?", array($DocType));

if(count($temp) == 0)
{
	echo "<center>" . "هیچ فایلی تا کنون تعریف نشده است" . "</center>";
	die();
} 
 
echo '<br><table width="700" style="margin-right:20px">';
for($i=0; $i<count($temp); $i++)
{
	echo '<tr>
		<td width="10%" style="padding-bottom:5px">
			<a target="_blank" href="/office/dms/ShowFile.php?DocumentID='.$temp[$i]["DocumentID"].'&ObjectID=0">
				<img src="/framework/icons/file.png" style="width:48px;vertical-align:middle;cursor: pointer">
				'.$temp[$i]["DocDesc"].'
			</a>
		</td>
	</tr>';
}
echo '</table>';
?> 