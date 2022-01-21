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
$sids = getPost('sids');

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
$result = $db->query("SELECT schools.ID, schools.Name FROM classes JOIN schools ON classes.School = schools.ID WHERE classes.Campaign = " . $cInfo['ID'] . " GROUP BY classes.School ORDER BY Name");

$units = array();

while ($row = $result->fetch_assoc()){

    if (isset($sids) && is_array($sids)){
        // check if current can be found in unit array
        $isIncluded = in_array($row['ID'], $sids);
    }
    else {
        // if sids parameter not set, all units are included, 
        // therefore the flag is set to true to all units.
        $isIncluded = true;
    }

    $units[] = array('ID' => $row['ID'], 'Name' => $row['Name'], 'Included' => $isIncluded);

}

$result->close();

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Relatório Por Turma</title>
    
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
            
            // set up arrow visibility
            element('imgUpArrow').style.visibility = (window.pageYOffset == 0 ? 'hidden' : 'visible');
            
        };
        
        window.onscroll = function(){
            // hide up arrow if page is on top
            element('imgUpArrow').style.visibility = (window.pageYOffset == 0 ? 'hidden' : 'visible');
        };
        
        function validateInput(){
            
            var chkBoxes = document.getElementsByClassName('chkFilter');
            var flag = false;
            var i;
            
            for (i = 0; i < chkBoxes.length; i++){
                if (chkBoxes[i].checked){
                    flag = true;
                    break;
                }
            }
            
            if (!flag){
                alert('Por favor selecione pelo menos uma unidade.');
            }
            
            return flag;
            
        }
        
        function showBox(){
            element('overlay').style.visibility = 'visible';
            element('filterBox').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('filterBox').style.opacity = '1';
        }
        
        function hideBox(){
            element('filterBox').style.opacity = '0';
            element('overlay').style.opacity = '0';
            element('overlay').style.visibility = 'hidden';
            element('filterBox').style.visibility = 'hidden';
        }
        
    </script>
    
</head>
<body>
    
    <div class="top" id="divTop"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
            
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
            
            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action="reportclass.php">
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
    
    <img id="imgUpArrow" src="<?php echo IMAGE_DIR; ?>arrow_up.png" style="position: fixed; right: 20px; bottom: 20px; cursor: pointer;" onclick="document.body.scrollTop = document.documentElement.scrollTop = 0;" title="Topo da página"/>
    
</body>
</html>
<?php

$db->close();

//------------------------------------------

function displayReport($cInfo, $units){
    
    global $db;
    
    $cid = $cInfo['ID'];
    $campName = $cInfo['Name'];
 
    // build query string
    $q = "SELECT classes.ID, classes.Name, users.ID AS TeacherID, users.Name AS Teacher, schools.Name AS School, StudentCount(classes.ID, 0) AS NumStds, "
            . "StudentCount(classes.ID, 1) AS NumStdsEnr FROM classes JOIN users ON classes.User = users.ID JOIN schools "
            . "ON classes.School = schools.ID WHERE classes.Campaign = $cid";
 
    // check which units will be included
    // take advantage and build the report query string
    $qFlag = false;
    $rptQ = '';
    
    foreach ($units as $unit){

        if ($unit['Included']){

            if (!$qFlag) {
                $q .= " AND (";
            }
            else {
                $q .= " OR ";
            }

            $q .= "schools.ID = " . $unit['ID'];
            $rptQ .= '&sid[]=' . $unit['ID'];

            $qFlag = true;
        }

    }

    if ($qFlag) $q .= ")";

    $q .= " ORDER BY School, Name, Teacher";
    
    $result = $db->query($q);
    
    $numRows = $result->num_rows;
    
?>
            <br/>
            <div class="panel">
                
                <span style="font-weight: bold;">Relatório - Rematrícula Por Turma</span>
                <hr/>
                
                <table style="width: 100%;" >
                    <tr>
                        <td style="white-space: nowrap; text-align: right;">Campanha:</td>
                        <td style="width: 100%; font-weight: bold;"><?php echo $campName; ?></td>
                        <td style="white-space: nowrap">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="white-space: nowrap; text-align: right;">Unidade(s):</td>
                        <td style="width: 100%; font-weight: bold;"><?php 
                        
                        $flag = false;
                        
                        foreach ($units as $unit){
                            
                            if ($unit['Included']){
                                
                                if ($flag) echo ' / ';
                                
                                echo $unit['Name'];
                                
                                $flag = true;
                                
                            }
                            
                        }
                        
                        ?></td>
                        <td style="white-space: nowrap">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="white-space: nowrap">Número de turmas:</td>
                        <td style="width: 100%; font-weight: bold;"><?php echo $numRows; ?></td>
                        <td style="white-space: nowrap; text-align: right;">
<?php if (count($units) > 1){ ?>
                            <img src="<?php echo IMAGE_DIR; ?>filter.png" title="Filtrar Unidade" style="cursor: pointer; vertical-align: bottom;" onclick="showBox();"/> &nbsp;
<?php } ?>
                            <img src="<?php echo IMAGE_DIR; ?>print.png" title="Versão para impressão" style="cursor: pointer; vertical-align: bottom;" onclick="window.open('printrept.php?t=7&cid=<?php echo $cid . $rptQ; ?>', '_blank', 'toolbar=no,scrollbars=yes,resizable=yes,width=840,height=600');"/>
                        </td>
                    </tr>
                </table>
                
            </div>
            
<?php

    if ($numRows){
        
?>
            <br/>
            <table style="width: 100%; border: #61269e solid 1px;">
                <tr style="background-color: #61269e; color: #ffffff;">
                    <td style="width: 28%;">Turma</td>
                    <td style="width: 14%;">Unidade</td>
                    <td style="width: 28%;">Professor</td>
                    <td style="width: 10%; text-align: center;">Alunos</td>
                    <td style="width: 10%; text-align: center;">Rematriculados</td>
                    <td style="width: 10%; text-align: center;">%</td>
                </tr>
<?php
        $bgcolor = null;
        
        while ($row = $result->fetch_assoc()){
            
            $numStd = intval($row['NumStds'], 10);
            $numStdsEnr = intval($row['NumStdsEnr'], 10);
            
            // prevent division by zero exception
            $percent = ($numStd ? ($numStdsEnr * 100) / $numStd : 0);
            
            $bgcolor = ($bgcolor == '#c6c6c6') ? '#ffffff' : '#c6c6c6';
                
?>
                <tr style="background-color: <?php echo $bgcolor; ?>;">
                    <td><?php echo '<a href="class.php?clsid=' . $row['ID'] . '">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</a>'; ?></td>
                    <td><?php echo htmlentities($row['School'], 0, 'ISO-8859-1'); ?></td>
                    <td><?php echo '<a href="campbyuser.php?cid=' . $cid . '&uid=' . $row['TeacherID'] . '">' . htmlentities($row['Teacher'], 0, 'ISO-8859-1') . '</a>'; ?></td>
                    <td style="text-align: center;"><?php echo $numStd; ?></td>
                    <td style="text-align: center;"><?php echo $numStdsEnr; ?></td>
                    <td style="text-align: center;"><?php echo round($percent, 2); ?>%</td>
                </tr>
<?php } ?>
            </table>
            
            <div class="overlay" id="overlay"></div>
            <div class="helpBox" id="filterBox" style="width: 450px; height: 160px;">
                <div class="closeImg" onclick="hideBox();"></div>
                <span style="font-weight: bold;">Filtrar Unidades</span>
                <hr>
                Exibir as turmas das seguintes unidades:
                <form method="post" action="reportclass.php">
                <div style="border: silver solid 1px; padding: 5px; height: 60px; overflow-y: scroll;">
<?php

        $idFlag = 1;

        foreach ($units as $unit){

            if ($idFlag > 1) echo '<br/>';

            echo PHP_EOL . '<input type="checkbox" class="chkFilter" id="chkFilter' . $idFlag . '" name="sids[]" value="' . $unit['ID'] . 
                    '"' . ($unit['Included'] ? ' checked="checked"' : '') . '/><label for="chkFilter' . $idFlag . '"> ' . htmlentities($unit['Name'], 0, 'ISO-8859-1') . '</label>';

            $idFlag++;

        }

?>
                </div>
                <div style="padding-top: 5px;">
                    <input type="submit" value="Filtrar" onclick="return validateInput();"/>
                </div>
                </form>
            </div>
            
<?php
        
    }
    else {
        echo '<div style="color: red; font-style: italic; padding: 10px 0 0 10px;">Não foi encontrado nenhuma turma nesta campanha.</div>' . PHP_EOL;
    }
    
    $result->close();
    
}

?>