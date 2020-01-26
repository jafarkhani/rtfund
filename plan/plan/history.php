<?php
//---------------------------
// programmer:	Jafarkhani
// create Date:	94.12
//---------------------------

if(!empty($_POST["GroupID"]))
{
    require_once '../header.inc.php';
    require_once inc_component;

    $PlanID = $_POST["PlanID"];
    $GroupID = $_POST["GroupID"];
    $query = "select f.*,concat_ws(' ',fname, lname,CompanyName) fullname
					
				from PLN_PlanSurvey f
					join BSC_persons on(PersonID=ActPersonID) 
					where f.PlanID=? AND f.GroupID=?
				order by RowID";
    $param = array($PlanID, $GroupID);
    $Logs = PdoDataAccess::runquery($query, $param);

    $tbl_content = "";
    if(count($Logs) == 0)
    {
        $tbl_content = "<tr><td>فرم مورد نظر فاقد گردش می باشد</td></tr>";
    }
    else
    {
        for ($i=0; $i<count($Logs); $i++)
        {
            $tbl_content .= "<tr " . ($i%2 == 1 ? "style='background-color:#efefef'" : "") . ">
				<td width=250px>[" . ($i+1) . "]". ($i+1<10 ? "&nbsp;" : "") . "&nbsp;
					<img align='top' src='/generalUI/ext4/resources/themes/icons/user_comment.gif'>&nbsp;";

            if($Logs[$i]["ActType"] == "CONFIRM")
                $tbl_content .= "تایید اطلاعات";
            if($Logs[$i]["ActType"] == "REJECT")
                $tbl_content .= "رد اطلاعات";
            if($Logs[$i]["ActType"] == "EDIT")
                $tbl_content .= "ویرایش اطلاعات";

            $tbl_content .= "</td>
				<td  width=150px>" . $Logs[$i]["fullname"] . "</td>
				<td width=110px>" . substr($Logs[$i]["ActDate"], 11) . " " .
                DateModules::miladi_to_shamsi($Logs[$i]["ActDate"]) . "</td>
				<td><div style='cursor:pointer' class='qtip-target' data-qtip='" .
                $Logs[$i]["ActDesc"] . "'>" .
                str_ellipsis($Logs[$i]["ActDesc"], 48). "</div></td>
			</tr>";

        }
    }
    ?>
    <style>
        .infotd td{border-bottom: solid 1px #e8e8e8;padding-right:4px; height: 21px;}
    </style>
    <div style="background-color:white;width: 100%; height: 100%">
        <table class="infotd" width="100%" bgcolor="white" cellpadding="0" cellspacing="0">
            <?= $tbl_content ?>
        </table>
    </div>
    <?
    die();
}

if(!empty($_POST["PlanID"]))
{
    $_REQUEST["FlowID"] = 35;
    $_REQUEST["ObjectID"] = $_POST["PlanID"];

    require_once getenv("DOCUMENT_ROOT") . '/office/workflow/historyplan.php';
    die();
}

?>