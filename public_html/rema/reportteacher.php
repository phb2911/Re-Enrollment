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
if (isNum($cid) && isset($allCamp[intval($cid, 10)])){
    $cInfo = $allCamp[intval($cid, 10)];
    // save current campaign id into cookie
    setcookie('curCampId', $cInfo['ID'], 0, '/', COOKIE_DOMAIN);
}
elseif (!$cInfo = getCampaignInfo($db, $allCamp)){ // get current campaign info
    // current campaing not valid
    closeDbAndGoTo($db, "searchcamp.php" . (isset($_SERVER['REQUEST_URI']) ? '?redir=' . urlencode($_SERVER['REQUEST_URI']) : ''));
}

// fetch units
// only the units that contain classes in the specified unit are retrieved
$result = $db->query("SELECT schools.ID, schools.Name FROM classes JOIN schools ON classes.School = schools.ID WHERE classes.Campaign = " . $cInfo['ID'] . " GROUP BY classes.School");

// $units[ID] = Name;
$units = array();

while ($row = $result->fetch_assoc()){
    $units[$row['ID']] = $row['Name'];
}

$result->close();

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Relatório Por Unidade</title>
    
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
            
        };
            
    </script>
    
</head>
<body>
    
    <div class="top" id="divTop"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
        
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
            
            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action="reportteacher.php">
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

if (count($units)){
    displayReport($cInfo, $units);
}
else {
    echo '<br/><span style="font-style: italic; color: red;">Não há unidades desponíveis.</span>' . PHP_EOL;
}

?>
        
        
        </div>
        
        <p>&nbsp;</p>
        
    </div>
    
</body>
</html>
<?php

$db->close();

//------------------------------------------

function displayReport($cInfo, $units){
    
    global $db;
    
    $cid = $cInfo['ID'];
    $campName = $cInfo['Name'];
    
    // fetch teachers
    $result = $db->query("SELECT users.ID, users.Name FROM classes JOIN users ON classes.User = users.ID WHERE classes.Campaign = $cid GROUP BY ID ORDER BY Name");
    
    // $teachers[ID] = Name;
    $teachers = array();
    
    while ($row = $result->fetch_assoc()){
        $teachers[$row['ID']] = $row['Name'];
    }
    
    $result->close();
    
?>
            <br/>
            <div class="panel">
                
                <span style="font-weight: bold;">Relatório Por Professor</span>
                <hr/>
                
                <table style="width: 100%;">
                    <tr>
                        <td style="white-space: nowrap">Campanha:</td>
                        <td style="font-weight: bold; width: 100%;"><?php echo $campName; ?></td>
                        <td>
                            <?php if (count($teachers)) echo '<img src="' . IMAGE_DIR . 'print.png" title="Versão para impressão" style="cursor: pointer; vertical-align: bottom;" onclick="window.open(\'printrept.php?t=3&cid=' . $cid . '\', \'_blank\', \'toolbar=no,scrollbars=yes,resizable=yes,width=840,height=600\');"/>'; ?>
                        </td>
                    </tr>
                </table>
                
<?php

    if (count($teachers)){
        
        echo '<div style="font-size: 13px; font-style: italic; color: red;">* Nos calculos a baixo, apenas os alunos ativos e evadidos são considerados.</div>' . PHP_EOL;
        
        $brFlag = false;
        
        foreach ($teachers as $teacherId => $teacherName) {
            
            if ($brFlag) echo '<br/>' . PHP_EOL;
            
            $brFlag = true;
            
?>
                <table style="border-collapse: collapse; width: 100%; border: #61269e solid 1px;">
                    <tr style="background-color: #61269e; color: #ffffff;">
                        <td style="width: 40%;"><?php echo '<a style="color: white;" href="user.php?uid=' . $teacherId . '">' . htmlentities($teacherName, 0, 'ISO-8859-1') . '</a>'; ?></td>
                        <td style="width: 10%; text-align: center;">Alunos</td>
                        <td style="width: 10%; text-align: center;">Rema</td>
                        <td style="width: 10%; text-align: center;">%</td>
                        <td style="width: 10%; text-align: center;">Semestrais</td>
                        <td style="width: 10%; text-align: center;">Rema (sem.)</td>
                        <td style="width: 10%; text-align: center;">% (sem.)</td>
                    </tr>
<?php

            $totalStd = 0;
            $totalEnr = 0;
            $totalYrCont = 0;

            foreach ($units as $uid => $unitName) {
                
                $row = $db->query("CALL spGetTeacherNumbers($teacherId, $cid, $uid)")->fetch_assoc();
                
                // get numbers and conver to integers
                $total = intval($row['Total'], 10);
                $enrolled = intval($row['Enrolled'], 10);
                $yrCont = intval($row['YearlyContract'], 10);
                
                // clear $db's stored results
                clearStoredResults($db);
                
                // calculate semestral contracts
                $semCont = $total - $yrCont;
                $enrSemCont = $enrolled - $yrCont;

                // calculate percerntages
                $percentage = $total > 0 ? +number_format(($enrolled * 100) / $total, 2) : 0;
                $percYrCont = $semCont > 0 ? +number_format(($enrSemCont * 100) / $semCont, 2) : 0;

                // add to totals
                $totalStd += $total;
                $totalEnr += $enrolled;
                $totalYrCont += $yrCont;
        
?>
                    <tr style="background-color: #f1f1f1;">
                        <td style="border: #61269e solid 1px; border-left: 0;"><?php echo htmlentities($unitName, 0, 'ISO-8859-1'); ?></td>
                        <td style="border: #61269e solid 1px; border-right: 0; border-left: 0; text-align: center;"><?php echo $total; ?></td>
                        <td style="border: #61269e solid 1px; border-right: 0; border-left: 0; text-align: center;"><?php echo $enrolled; ?></td>
                        <td style="border: #61269e solid 1px; border-left: 0; text-align: center;"><?php echo $percentage; ?>%</td>
                        <td style="border: #61269e solid 1px; border-right: 0; border-left: 0; text-align: center;"><?php echo $semCont; ?></td>
                        <td style="border: #61269e solid 1px; border-right: 0; border-left: 0; text-align: center;"><?php echo $enrSemCont; ?></td>
                        <td style="border: #61269e solid 1px; border-left: 0; text-align: center;"><?php echo $percYrCont; ?>%</td>
                    </tr>
<?php
                    
            }

            // caculate the general percentage
            $percentageTotal = $totalStd > 0 ? +number_format(($totalEnr * 100) / $totalStd, 2) : 0;
            $percYrContTotal = ($totalStd - $totalYrCont) > 0 ? +number_format((($totalEnr - $totalYrCont) * 100) / ($totalStd - $totalYrCont), 2) : 0;

?>
                    <tr style="background-color: #c6c6c6;">
                        <td style="border: #61269e solid 1px; border-left: 0;">Total</td>
                        <td style="border: #61269e solid 1px; border-right: 0; border-left: 0; text-align: center;"><?php echo $totalStd; ?></td>
                        <td style="border: #61269e solid 1px; border-right: 0; border-left: 0; text-align: center;"><?php echo $totalEnr; ?></td>
                        <td style="border: #61269e solid 1px; border-left: 0; text-align: center;"><?php echo $percentageTotal; ?>%</td>
                        <td style="border: #61269e solid 1px; border-right: 0; border-left: 0; text-align: center;"><?php echo ($totalStd - $totalYrCont); ?></td>
                        <td style="border: #61269e solid 1px; border-right: 0; border-left: 0; text-align: center;"><?php echo ($totalEnr - $totalYrCont); ?></td>
                        <td style="border: #61269e solid 1px; border-left: 0; text-align: center;"><?php echo $percYrContTotal; ?>%</td>
                    </tr>
                </table>
<?php 

        }
        
        echo '<div style="text-align: right; padding: 10px 10px 0 0;"><img src="' . IMAGE_DIR . 'print.png" title="Versão para impressão" style="cursor: pointer;" onclick="window.open(\'printrept.php?t=3&cid=' . $cid . '\', \'_blank\', \'toolbar=no,scrollbars=yes,resizable=yes,width=840,height=600\');"/></div>' . PHP_EOL;

    } 
    else {
        echo '<div style="font-style: italic; color: red; padding-left: 10px;">Nenhum professor participou desta campanha.</div>';
    }
    
?>
            </div>
<?php
    
}

?>