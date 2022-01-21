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

// retrieve units
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
            
            if (element('selCamp')) styleSelectBox(element('selCamp'));
            
        };
        
        function showReport(){
            
            if (element('selCamp').selectedIndex == 0){
                alert('Por favor selecione a campanha de rematrícula.');
            }
            else {
                window.location = 'reportunit.php?cid=' + element('selCamp').options[element('selCamp').selectedIndex].value;
            }
            
        }
            
    </script>
    
</head>
<body>
    
    <div class="top" id="divTop"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
        
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
            
            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action="reportunit.php">
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
else{
    echo '<br/><span style="font-style: italic; color: red;">Não há turmas nesta campanha.</span>' . PHP_EOL;
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
    
?>
            <br/>
            <div class="panel">
                
                <span style="font-weight: bold;">Relatório Por Unidade</span>
                <hr/>
                
                <table>
                    <tr>
                        <td style="white-space: nowrap">Campanha:</td>
                        <td style="font-weight: bold;"><?php echo $campName; ?></td>
                    </tr>
                </table>
                
                <div style="font-size: 13px; font-style: italic; color: red;">* Nos calculos abaixo, todos os alunos são considerados, inclusive os cancelados e concluintes.</div>
                
                <table style="border-collapse: collapse; width: 100%; border: #61269e solid 1px;">
                    <tr style="background-color: #61269e; color: #ffffff;">
                        <td style="width: 70%;">Unidade</td>
                        <td style="width: 10%; text-align: center;">Alunos</td>
                        <td style="width: 10%; text-align: center;">Rematriculados</td>
                        <td style="width: 10%; text-align: center;">%</td>
                    </tr>
<?php

    $genTotal = 0;
    $genEnrolled = 0;
    $bgcolor = null;

    foreach ($units as $uid => $unitName) {
        
        $row = $db->query("SELECT (SELECT COUNT(*) FROM classes JOIN students ON students.Class = classes.ID WHERE classes.Campaign = $cid AND classes.School = $uid) AS Total, (SELECT COUNT(*) FROM classes JOIN students ON students.Class = classes.ID WHERE classes.Campaign = $cid AND classes.School = $uid AND students.Status = 3) AS Enrolled")->fetch_assoc();
        
        $total = intval($row['Total'], 10);
        $enrolled = intval($row['Enrolled'], 10);
        
        $genTotal += $total;
        $genEnrolled += $enrolled;
        
        $percentage = !!$total ? +number_format(($enrolled * 100) / $total, 2) : 0;
        
        $bgcolor = ($bgcolor == '#c6c6c6') ? '#ffffff' : '#c6c6c6';
        
?>
                    <tr style="background-color: <?php echo $bgcolor; ?>;">
                        <td><?php echo htmlentities($unitName, 0, 'ISO-8859-1'); ?></td>
                        <td style="text-align: center;"><?php echo $total; ?></td>
                        <td style="text-align: center;"><?php echo $enrolled; ?></td>
                        <td style="text-align: center;"><?php echo $percentage; ?>%</td>
                    </tr>
<?php
    }

    $genPercentage = !!$genTotal ? +number_format(($genEnrolled * 100) / $genTotal, 2) : 0;
    
?>
                    <tr style="background-color: #a1a1a1; font-weight: bold;">
                        <td>Total</td>
                        <td style="text-align: center;"><?php echo $genTotal; ?></td>
                        <td style="text-align: center;"><?php echo $genEnrolled; ?></td>
                        <td style="text-align: center;"><?php echo $genPercentage; ?>%</td>
                    </tr>
                </table>
                
                <div style="text-align: right; padding: 10px 10px 0 0;"><img src="<?php echo IMAGE_DIR; ?>print.png" title="Versão para impressão" style="cursor: pointer;" onclick="window.open('printrept.php?t=2&cid=<?php echo $cid; ?>', '_blank', 'toolbar=no,scrollbars=yes,resizable=yes,width=840,height=600');"/></div>
                
            </div>
<?php
    
}

?>