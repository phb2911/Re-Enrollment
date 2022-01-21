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

$cid = $cInfo['ID'];
$cName = $cInfo['Name'];

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Relatório por Grupo</title>
    
    <link rel="icon" href="<?php echo getImagePath('favicon.ico'); ?>" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="<?php echo getDomain('js/general.js'); ?>"></script>
    
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
        td.numbers {
            width: 10%;
            text-align: right;
            padding-right: 20px;
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
        
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo getImagePath('banner' . ($isAdmin ? 'admin' : '') . '.jpg'); ?>"/></a>
            
            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action="reportgrp.php">
                Campanha: &nbsp;
                <select name="cid" style="width: 100px; border-radius: 5px;" onchange="element('imgCampLoader').style.visibility = 'visible'; element('frmChangeCamp').submit();">
<?php

// create option
foreach ($allCamp as $cmp){
    echo '<option value="' . $cmp['ID'] . '"' . ($cmp['ID'] == $cInfo['ID'] ? ' selected="selected"' : '') . ($allCamp[intval($cmp['ID'], 10)]['Open'] ? ' style="font-weight: bold;"' : '') . '>' . $cmp['Name'] . '</option>' . PHP_EOL;
}


?>
                </select>
                <img id="imgCampLoader" src="<?php echo getImagePath('rema_loader.gif'); ?>" style="vertical-align: middle; visibility: hidden;"/>
                </form>
            </div>
        
<?php

renderDropDown($db, $isAdmin);

?>
        </div>
        <br/>
        
        <div class="panel" style="width: 100%; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Relatório - Rematrícula por Grupo</span>
            <hr/>
            
            <div style="padding: 5px;">Campanha: &nbsp; <span style="font-weight: bold; font-style: italic;"><?php echo htmlentities($cName, 0, 'ISO-8859-1'); ?></span></div>
<?php

$groupsInfo = fetchGroupsInfo($cid);

if (!empty($groupsInfo)){
    
?>
            <div style="padding: 5px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="color: red; font-style: italic; font-size: 13px; width: 100%; vertical-align: bottom; padding: 0;">*Clique no nome do grupo para visualizar detalhes.</td>
                        <td style="padding: 0 5px 0 0;"><img src="<?php echo getImagePath('print.png'); ?>" title="Versão para impressão" style="cursor: pointer;" onclick="window.open('printrept.php?t=11&cid=<?php echo $cid; ?>', '_blank', 'toolbar=no,scrollbars=yes,resizable=yes,width=840,height=600');"/></td>
                    </tr>
                </table>
                <table style="border-collapse: collapse; width: 100%; border: solid 1px #61269e;">
                    <tr style="background-color: #61269e; color: white;">
                        <td style="width: 30%;">Grupo</td>
                        <td class="numbers">Professores</td>
                        <td class="numbers">Alunos</td>
                        <td class="numbers">Rema</td>
                        <td class="numbers">%</td>
                        <td class="numbers">Semestrais</td>
                        <td class="numbers">Rema (sem.)</td>
                        <td class="numbers">% (sem.)</td>
                    </tr>
<?php

    $bgcolor = null;
    
    foreach ($groupsInfo as $grInfo){
        
        $bgcolor = ($bgcolor == "#c6c6c6" ? '#ffffff' : '#c6c6c6');
        
        $percent = ($grInfo['Total'] ? ($grInfo['Enrolled'] * 100) / $grInfo['Total'] : 0);
        $semContPer = ($grInfo['SemesterContracts'] ? ($grInfo['SemContEnrolled'] * 100) / $grInfo['SemesterContracts'] : 0);
        
?>
                    <tr style="background-color: <?php echo $bgcolor; ?>;">
                        <td><?php echo '<a href="group.php?gid=' . $grInfo['ID'] . '">' . htmlentities($grInfo['Name'], 0, 'ISO-8859-1') . '</a>'; ?></td>
                        <td class="numbers"><?php echo $grInfo['TeacherCount']; ?></td>
                        <td class="numbers"><?php echo $grInfo['Total']; ?></td>
                        <td class="numbers"><?php echo $grInfo['Enrolled']; ?></td>
                        <td class="numbers"><?php echo +number_format($percent , 2); ?>%</td>
                        <td class="numbers"><?php echo $grInfo['SemesterContracts']; ?></td>
                        <td class="numbers"><?php echo $grInfo['SemContEnrolled']; ?></td>
                        <td class="numbers"><?php echo +number_format($semContPer , 2); ?>%</td>
                    </tr>
<?php } ?>
                </table>
            </div>
<?php
    
}
else {
    echo '<div style="padding: 5px; color: red; font-style: italic;">Não há grupos nesta campanha.</div>' . PHP_EOL;
}

?>
        </div>
        
    </div>

    <p>&nbsp;</p>
    <p>&nbsp;</p>
    <p>&nbsp;</p>
     
</body>
</html>
<?php

$db->close();

//-----------------------------------------------------

function fetchGroupsInfo($cid){
    
    global $db;
    
    $grInfo = array();
    
    $result = $db->query("CALL spFetchGroupsNumbers($cid);");
    
    while ($row = $result->fetch_assoc()){
        $grInfo[] = $row;
    }
    
    $result->close();
    
    // clear db stored results
    clearStoredResults($db);
    
    return $grInfo;
    
}

?>