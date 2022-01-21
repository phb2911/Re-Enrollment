<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once 'dropdown/dropdown.php';
require_once '../genreq/genreq.php';
require_once 'required/campaigninfo.php';

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

if (!$loginObj->isLoggedIn()) closeDbAndGoTo($db, LOGIN_PAGE);

$isAdmin = $loginObj->isAdmin();

if (!$isAdmin) closeDbAndGoTo($db, ".");

// fetch all campaigns and store into array
$allCamp = allCampaigns($db);

$cid = getPost('cid');

// check if current campaign id submitted by select element
if (isset($cid) && isNum($cid) && isset($allCamp[intval($cid, 10)])){
    $cInfo = $allCamp[intval($cid, 10)];
    // save current campaign id into cookie
    setcookie('curCampId', $cInfo['ID'], 0, '/', COOKIE_DOMAIN);
}
elseif (!$cInfo = getCampaignInfo($db, $allCamp)){ // get current campaign info
    // current campaing not valid
    closeDbAndGoTo($db, "searchcamp.php" . (isset($_SERVER['REQUEST_URI']) ? '?redir=' . urlencode($_SERVER['REQUEST_URI']) : ''));
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - PPY Resultados</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
            element('divTop').style.height = element('divHeadder').offsetHeight + 'px';
            
            if (element('selCamp')) styleSelectBox(element('selCamp'));
            
        };
        
        function redir(uid, cid, editFlag){
            
            // if edit flat is set to true
            // uid is submitted to the edit page
            // using the post metho, if not,
            // it is submitted to the details page
            // via get method
            
            var frm = document.createElement('form');
            frm.method = 'post';
            frm.action = (editFlag ? 'ppyedit.php' : 'ppy.php?uid=' + uid);
            
            document.body.appendChild(frm);
            
            var hid = document.createElement('input');
            hid.type = 'hidden';
            hid.name = 'cid';
            hid.value = cid;
            
            frm.appendChild(hid);
            
            if (editFlag){ // edit button clicked
                
                var hid2 = document.createElement('input');
                hid2.type = 'hidden';
                hid2.name = 'uid';
                hid2.value = uid;

                frm.appendChild(hid2);
                
            }
        
            frm.submit();
            
        }
        
    </script>
    
</head>
<body>
    
    <div class="top" id="divTop"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
        
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
            
            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action="ppyrpt.php">
                Campanha: &nbsp;
                <select name="cid" style="width: 100px; border-radius: 5px;" onchange="element('imgCampLoader').style.visibility = 'visible'; element('frmChangeCamp').submit();">
<?php

// create option
foreach ($allCamp as $cmp){
    echo '<option value="' . $cmp['ID'] . '"' . ($cmp['ID'] == $cInfo['ID'] ? ' selected="selected"' : '') . ($allCamp[intval($cmp['ID'], 10)]['Open'] ? ' style="font-weight: bold;"' : '') . '>' . $cmp['Name'] . '</option>' . PHP_EOL;
}


?>
                </select>
                <img id="imgCampLoader" src="<?php echo IMAGE_DIR; ?>rema_loader.gif" style="vertical-align: middle; visibility: hidden;"/>
                </form>
            </div>
        
<?php

renderDropDown($db, $isAdmin);

?>
        </div>    
<?php

displayReport($cInfo);

?>
        
        <p>&nbsp;</p>
    </div>
    
</body>
</html>
<?php

$db->close();

//----------------------------------------------

function displayReport($cInfo){
    
    global $db;
    
    $cid = $cInfo['ID'];
    $campName = $cInfo['Name'];
    
    $q = "SELECT users.Name AS UserName, ppy_calc_index.CalcIndex, ppy.ID, ppy.User, ppy.Axis1Only, ppy.E1_Graduation, ppy.E1_IntExp, "
            . "ppy.E1_ProficiencyTest, ppy.E2_YILTS, ppy.E2_InovPedPrac, ppy.E2_RelSug, ppy.E2_CitizenshipCamp, ppy.E2_CulturalEvents, "
            . "ppy.E2_NumCls, ppy.E2_ReferredStudent, ppy.E2_ContribToPedMeeting, ppy.E2_OtherEnv, ppy.E3_Rema, ppy.E3_AvStds, "
            . "ppy.MissedMeeting, ppy.PartialMeeting, ppy.LateRollCall, ppy.LateReportCards, ppy.MsgNotSent, ppy.MissClass FROM ppy "
            . "JOIN users ON ppy.User = users.ID JOIN ppy_calc_index ON ppy.Campaign = ppy_calc_index.Campaign WHERE ppy.Campaign = $cid "
            . "ORDER BY UserName";

    $result = $db->query($q);
    
    $numRows = $result->num_rows;
    
?>
        <br/>
        <div class="panel" style="width: 700px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Premio Performance Yázigi (PPY) - Resultados</span>
            <hr/>
            
            <table style="width: 100%;">
                <tr>
                    <td>Campanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $campName; ?></td>
                    <td><?php
                    
                    if ($numRows){
                        echo '<img src="' . IMAGE_DIR . 'print.png" title="Versão para impressão" style="cursor: pointer; vertical-align: bottom;" onclick="window.open(\'printrept.php?t=4&cid=' . $cid . '\', \'_blank\', \'toolbar=no,scrollbars=yes,resizable=yes,width=840,height=600\');"/>';
                    }
                    
                    ?></td>
                </tr>
            </table>
<?php

    if ($numRows){
        
?>
            <table style="width: 100%; border-collapse: collapse; border: #61269e solid 1px;">
                <tr style="background-color: #61269e; color: white;">
                    <td style="width: 80%;">Professor</td>
                    <td style="width: 80%; text-align: right; padding-right: 20px;"></td>
                    <td></td>
                </tr>
<?php

        $grandTotal = 0;
        $bgcolor = '';

        while ($row = $result->fetch_assoc()){
            
            $bgcolor = ($bgcolor == '#f1f1f1') ? '#ffffff' : '#f1f1f1';
            
            // calculate value
            $ax1 = 0;
            $ax2 = 0;
            $ax3 = 0;
            
            if ($row['E1_Graduation'] == 1 || $row['E1_Graduation'] == 3) $ax1 += 15;
            elseif ($row['E1_Graduation'] == 2) $ax1 += 5;
            
            $ax1 += ($row['E1_IntExp'] * 5) + ($row['E1_ProficiencyTest'] * 5);
            
            if ($ax1 > 30) $ax1 = 30;
            
            if (!$row['Axis1Only']){
                
                $ax2 = ($row['E2_YILTS'] ? 10 : 0) + ($row['E2_CitizenshipCamp'] ? 5 : 0) + ($row['E2_CulturalEvents'] ? 5 : 0) + 
                        ($row['E2_InovPedPrac'] * 2) + $row['E2_RelSug'] + ($row['E2_ReferredStudent'] * 3) + $row['E2_ContribToPedMeeting'] + 
                        $row['E2_OtherEnv'];
                
                if ($row['E2_NumCls'] == 5) $ax2 += 6;
                elseif ($row['E2_NumCls'] == 6 || $row['E2_NumCls'] == 7) $ax2 += 10;
                elseif ($row['E2_NumCls'] >= 8) $ax2 += 15;
                
                if ($ax2 > 30) $ax2 = 30;
                
                if ($row['E3_Rema'] == 1){
                    $ax3 += 5;
                }
                elseif ($row['E3_Rema'] == 2){
                    $ax3 += 10;
                }
                elseif ($row['E3_Rema'] == 3){
                    $ax3 += 20;
                }
                elseif ($row['E3_Rema'] == 4){
                    $ax3 += 30;
                }
                
                if ($row['E3_AvStds'] == 1){
                    $ax3 += 5;
                }
                elseif ($row['E3_AvStds'] == 2){
                    $ax3 += 15;
                }
                elseif ($row['E3_AvStds'] == 3){
                    $ax3 += 20;
                }
                
                if ($ax3 > 40) $ax3 = 40;
                
            }
            
            $neg = ($row['MissedMeeting'] * 2) + ($row['PartialMeeting'] / 2) + ($row['LateRollCall'] / 2) + ($row['LateReportCards'] / 2) + 
                    ($row['MsgNotSent'] / 2) + ($row['MissClass'] * 2);
            
            $gen = $ax1 + $ax2 + $ax3 - $neg;
            
            if ($gen <= 0){
                $total = 0;
            }
            else {
                $total = $gen * $row['CalcIndex'] * $row['E2_NumCls'];
            }
            
            $grandTotal += $total;
            
?>
                <tr style="background-color: <?php echo $bgcolor; ?>;">
                    <td><?php echo htmlentities($row['UserName'], 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: right; padding-right: 20px;">$<?php echo number_format($total, 2, ',', ''); ?></td>
                    <td style="white-space: nowrap;">
                        <img src="<?php echo IMAGE_DIR; ?>info.png" style="cursor: pointer;" title="Detalhes" onclick="redir(<?php echo $row['User'] . ', ' . $cid; ?>, false);"/> &nbsp;
                        <img src="<?php echo IMAGE_DIR; ?>pencil1.png"  style="cursor: pointer;" title="Editar" onclick="redir(<?php echo $row['User'] . ', ' . $cid; ?>, true);"/>
                    </td>
                </tr>
        <?php } ?>
                <tr style="background-color: #61269e; color: white;">
                    <td style="text-align: right;">Total:</td>
                    <td style="text-align: right; padding-right: 20px;">$<?php echo number_format($grandTotal, 2, ',', ''); ?></td>
                    <td></td>
                </tr>
            </table>
<?php

    }
    else {
        echo '<div style="color: red; font-style: italic; padding: 10px;">Nenhum PPY foi calculado e salvo nesta campanha.</div>';
    }

?>
        </div>
<?php

    $result->close();

}

?>