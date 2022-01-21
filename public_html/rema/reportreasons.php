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
    <title>YCG Rema - Relatório - Motivos</title>
    
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
            
            element('imgUpArrow').style.visibility = (window.pageYOffset == 0 ? 'hidden' : 'visible');
            
        };
        
        window.onscroll = function(){
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
                <form id="frmChangeCamp" method="post" action="reportreasons.php">
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
    
    $rptQ = '&cid=' . $cInfo['ID'];
    
    foreach ($units as $unit){

        if ($unit['Included']){

            $rptQ .= '&sid[]=' . $unit['ID'];

        }

    }
    
    if (count($units)){
    
?>
            <br/>
            <div class="panel" style="width: 700px; left: 0; right: 0; margin: auto;">
                
                <span style="font-weight: bold;">Relatório - Motivos de Não Rematrícula</span>
                <hr/>
                
                <table style="width: 100%;">
                    <tr>
                        <td style="white-space: nowrap; text-align: right;">Campanha:</td>
                        <td style="width: 100%; font-weight: bold;"><?php echo $cInfo['Name']; ?></td>
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
                        <td style="white-space: nowrap">
<?php if (count($units) > 1){ ?>
                            <img src="<?php echo IMAGE_DIR; ?>filter.png" title="Filtrar Unidade" style="cursor: pointer; vertical-align: bottom;" onclick="showBox();"/> &nbsp;
<?php } ?>
                            <img src="<?php echo IMAGE_DIR; ?>print.png" title="Versão para impressão" style="cursor: pointer; vertical-align: bottom;" onclick="window.open('printrept.php?t=9<?php echo $rptQ; ?>', '_blank', 'toolbar=no,scrollbars=yes,resizable=yes,width=840,height=600');"/>
                        </td>
                    </tr>
                </table>
<?php

        $flag = false;

        foreach ($units as $unit){
            
            if ($unit['Included']){
                
                if ($flag) echo '<br/>';
            
                $flag = true;
                
?>
                <table style="width: 100%; border: #61269e solid 1px;">
                    <tr style="background-color: #61269e; color: #ffffff;">
                        <td colspan="3"><?php echo htmlentities($unit['Name'], 0, 'ISO-8859-1'); ?></td>
                    </tr>
                    <tr style="background-color: #61269e; color: #ffffff;">
                        <td style="width: 60%;">Descrição</td>
                        <td style="width: 20%; text-align: center;">Quantidade</td>
                        <td style="width: 20%; text-align: center;">%</td>
                    </tr>
<?php

                // fech data
                // it is necessary to store data in array before displaying
                // in order to be able to fetch total and calculate percentage
                $q = "SELECT reasons.Description, COUNT(*) AS Count FROM students JOIN classes ON students.Class = classes.ID " .
                     "JOIN reasons ON students.Reason = reasons.ID WHERE classes.Campaign = " . $cInfo['ID'] . " AND students.Status = 2 " .
                     "AND classes.School = " . $unit['ID'] . " GROUP BY students.Reason ORDER BY Count DESC, Description";

                $result = $db->query($q);
                
                $rows = array();
                $totalReasons = 0;
                
                while ($row = $result->fetch_assoc()){
                    
                    $rows[] = $row;
                    $totalReasons += intval($row['Count'], 10);
                    
                }
                
                $result->close();
                
                $bgcolor = '';
                
                foreach ($rows as $row){

                    $bgcolor = ($bgcolor == '#c6c6c6') ? '#f1f1f1' : '#c6c6c6';
                    
?>
                    <tr style="background-color: <?php echo $bgcolor; ?>;">
                        <td><?php echo htmlentities($row['Description'], 0, 'ISO-8859-1'); ?></td>
                        <td style="text-align: center;"><?php echo $row['Count']; ?></td>
                        <td style="text-align: center;"><?php echo round(($row['Count'] * 100) / $totalReasons, 2) . '%'; ?></td>
                    </tr>
<?php
                    
                }
                
                if ($totalReasons){
?>
                    <tr>
                        <td style="text-align: right;">Total</td>
                        <td style="text-align: center; background-color: #61269e; color: #ffffff;"><?php echo $totalReasons; ?></td>
                        <td style="text-align: center; background-color: #61269e; color: #ffffff;">100%</td>
                    </tr>
<?php
                }
                else {
                    // this unit has empty classes only
                    echo '<tr><td style="color: red; font-style: italic;" colspan="4">Não há alunos matriculados nesta unidade.</td></tr>';
                }

?>
                </table>
<?php
                
            }
            
        }
        
        // no included units found
        if (!$flag){
            echo '<div style="color: red; font-style: italic; padding: 10px 0 0 10px;">Não foi encontrada nenhuma turma nesta campanha.</div>' . PHP_EOL;
        }

?>
            </div>
            <div class="overlay" id="overlay"></div>
            <div class="helpBox" id="filterBox" style="width: 450px; height: 160px;">
                <div class="closeImg" onclick="hideBox();"></div>
                <span style="font-weight: bold;">Filtrar Unidades</span>
                <hr>
                Exibir as turmas das seguintes unidades:
                <form method="post" action="reportreasons.php">
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
        echo '<div style="color: red; font-style: italic; padding: 10px 0 0 10px;">Não foi encontrada nenhuma turma nesta campanha.</div>' . PHP_EOL;
    }
 
}

?>