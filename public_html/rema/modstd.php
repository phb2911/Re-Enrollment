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

$cid = getPost('cid');

// fetch all campaigns and store into array
$allCamp = allCampaigns($db);

// check if current campaign id submitted by select element
if (isNum($cid) && isset($allCamp[intval($cid, 10)])){
    $cInfo = $allCamp[intval($cid, 10)];
    // save current campaign id into cookie
    setcookie('curCampId', $cInfo['ID'], 0, '/', COOKIE_DOMAIN);
}
elseif (!$cInfo = getCampaignInfo($db, $allCamp)){ // get current campaign info
    // invalid campaign
    closeDbAndGoTo($db, "searchcamp.php" . (isset($_SERVER['REQUEST_URI']) ? '?redir=' . urlencode($_SERVER['REQUEST_URI']) : ''));
}

// select user id
$uid = ($isAdmin ? getGet('uid') : $loginObj->userId);

// validate input
$isValid = (isNum($uid) && $usrName = $db->query("SELECT Name FROM users WHERE ID = $uid")->fetch_row()[0]);

if (getPost('flag') === '1' && $isValid && ($isAdmin || $cInfo['Open'])){
    
    $stdStArr = getPost('st_status');
    
    if (isset($stdStArr) && is_array($stdStArr)){
        
        $queryCount = 0;
        $q = "";
        
        foreach ($stdStArr as $stdId => $stdStatus) {
            
            // validate input
            if (isNum($stdId) && ($stdStatus === '0' || $stdStatus === '1' || $stdStatus === '2' || $stdStatus === '3')){
                
                $tempNotes = isset($_POST['st_notes']) && isset($_POST['st_notes'][$stdId]) ? trim($_POST['st_notes'][$stdId]) : null;
                $tempReason = isset($_POST['st_reason']) && isset($_POST['st_reason'][$stdId]) ? $_POST['st_reason'][$stdId] : null;
                
                $notes = (isset($tempNotes) && strlen($tempNotes)) ? "'" . $db->real_escape_string($tempNotes) . "'" : 'null';
                $reason = (isset($tempReason) && isNum($tempReason) && $tempReason > 0 ? $tempReason : 'null');
                
                // create multi query string
                $q .= "UPDATE students SET Status = $stdStatus, Reason = $reason, Notes = $notes WHERE ID = $stdId; ";
                $queryCount++;
                
            }
            
            // if 20 queries reached, execute
            if ($queryCount == 20){
                
                // execute multi queries and clear stored result
                if ($db->multi_query($q)) clearStoredResults($db);
                else die($db->error); // execution error
                
                // reset vars
                $queryCount = 0;
                $q = "";
                
            }
            
        }
        
        // execute any remanining queries
        if ($queryCount > 0){
            
            // execute multi queries and clear stored result
            if ($db->multi_query($q)) clearStoredResults($db);
            else die($db->error); // execution error
            
        }
        
    }
    
    closeDbAndGoTo($db, "campbyuser.php?uid=" . $uid);
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Alterar Status</title>
    
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
            
            var sels = document.getElementsByClassName('selStatus');
            
            for (var i = 0; i < sels.length; i++){
                styleSelect(sels[i]);
                sels[i].style.borderWidth = '1px';
            }
            
        };
        
        function styleSelect(sel){
            
            var si = sel.selectedIndex;
            
            if (si === 0) sel.style.backgroundColor = 'yellow';
            else if (si === 1) sel.style.backgroundColor = 'orange';
            else if (si === 2) sel.style.backgroundColor = 'red';
            else sel.style.backgroundColor = 'green';
            
            //element(sel.id.replace('selStatus', 'selReason')).disabled = (sel.selectedIndex != 2);
            element(sel.id.replace('selStatus', 'selReason')).style.visibility = (sel.selectedIndex == 2 ? 'visible' : 'hidden');
            
        }
                     
    </script>
    
</head>
<body>
    
    <div class="top" id="divTop"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
        
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
            
            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action="modstd.php<?php if ($isValid && $isAdmin) echo '?uid=' . $uid; ?>">
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
        <br/>
<?php 
if ($isValid){
?>        
        <div class="panel">
            
            <span style="font-weight: bold;">Alterar Status dos Alunos</span>
            <hr/>
            
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $cInfo['Name']; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Colaborador:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($usrName, 0, 'ISO-8859-1'); ?></td>
                </tr>
            </table>

        </div>
        
<?php 

    if (!$isAdmin && !$cInfo['Open']){
        echo '<br/><span style="font-style: italic; color: red;">Esta campanha foi encerrada e não pode ser modificada.</span>' . PHP_EOL;
    }
    else {
        displayStudentTable($uid, $cInfo['ID'], $isAdmin);
    }

} 
else echo '<span style="font-style: italic; color: red;">ID do aluno é inválida.</span>' . PHP_EOL; 

?>
    </div>
    
</body>
</html>
<?php

$db->close();

//--------------------------------------------------------

function displayStudentTable($uid, $cid, $isAdmin){
    
    global $db;
    
    // fetch reasons
    // $reasons[ID] = Description
    $reasons = array();
    
    $result = $db->query("CALL sp_reasons()");
    
    while ($row = $result->fetch_assoc()){
        $reasons[$row['ID']] = $row['Description'];
    }
    
    $result->close();
    
    // clear stored results after stored procedure call
    clearStoredResults($db);

    echo '<form id="form1" action="modstd.php' . ($isAdmin ? '?uid=' . $uid : '') . '" method="post">' . PHP_EOL;
    
    // retrieve students and add to arrays accordint to situation
    $q = "SELECT students.ID, students.Name, students.Situation, students.Status, students.Notes, students.Flagged, students.Reason, classes.Name AS ClassName, "
            . "(SELECT Name FROM schools WHERE ID = classes.School) AS School FROM students LEFT JOIN classes "
            . "ON students.Class = classes.ID WHERE classes.User = $uid AND classes.Campaign = $cid " . ($isAdmin ? '' : 'AND  students.Situation <= 1 ') 
            . "ORDER BY School, ClassName, Name";
    
    $activeStds = array();
    $dropoutStds = array();
    $cancelledStds = array();
    $finishedStds = array();
    
    $result = $db->query($q);
    
    while ($row = $result->fetch_assoc()){
        
        if ($row['Situation'] == 0) $activeStds[] = $row;
        elseif ($row['Situation'] == 1) $dropoutStds[] = $row;
        elseif ($row['Situation'] == 2) $cancelledStds[] = $row;
        elseif ($row['Situation'] == 3) $finishedStds[] = $row;
    }
    
    $result->close();
    
    if (!count($activeStds) && !count($dropoutStds) && !count($cancelledStds) && !count($finishedStds)){
        // no students retrieved
        echo '<br/><span style="font-style: italic; color: red;">Este colaborador não participou desta campanha.</span>' . PHP_EOL;
    }
    else {
        
        // display dropouts
        if (count($dropoutStds)){
            studentTable(1, $dropoutStds, $reasons);
        }

        // display cancelled
        if (count($cancelledStds)){
            studentTable(2, $cancelledStds, $reasons);
        }

        // display finished
        if (count($finishedStds)){
            studentTable(3, $finishedStds, $reasons);
        }

        // display active
        if (count($activeStds)){
            studentTable(0, $activeStds, $reasons);
        }



        echo '<input type="hidden" name="flag" value="1"/></form>' . PHP_EOL . 
                '<br/><img src="' . IMAGE_DIR . 'disk.png" title="Gravar" style="vertical-align: bottom; cursor: pointer;" onclick="element(\'form1\').submit();"/>' . PHP_EOL . 
                '&nbsp;&nbsp;&nbsp;<img src="' . IMAGE_DIR . 'cancel.png" title="Cancelar" style="vertical-align: bottom; cursor: pointer;" onclick="window.location = \'' . 
                ($isAdmin ? 'campbyuser.php?uid='. $uid : '.') . '\';"/>' . PHP_EOL . '<p>&nbsp;</p>';
    
    }
    
}

//--------------------------------------------------------

function studentTable($situation, $rows, $reasons){
    
    switch ($situation) {
        case 0:
            $sitName = 'Aluno';
            break;
        case 1:
            $sitName = 'Evadido';
            break;
        case 2:
            $sitName = 'Cancelado';
            break;
        case 3:
            $sitName = 'Concluinte';
            break;
    }
    
?>
            <br/>
            <table style="width: 100%; box-shadow: 3px 3px 3px #808080; border: #61269e solid 1px;">
                <tr style="background-color: #61269e; color: #ffffff;">
                    <td style="width: 25%;"><?php echo $sitName; ?></td>
                    <td style="width: 15%;">Turma</td>
                    <td style="width: 7%;">Unidade</td>
                    <td style="width: 10%; text-align: center;">Status</td>
                    <td style="width: 15%;">Motivo</td>
                    <td style="width: 28%;">Observações</td>
                </tr>
<?php

        $bgcolor = null;
        
        foreach ($rows as $row){
            
            $bgcolor = ($bgcolor == '#c6c6c6') ? '#ffffff' : '#c6c6c6';
            
            echo '<tr style="font-size: 14px; background-color: ' . $bgcolor . ';">' . PHP_EOL .
                    '<td>' . htmlentities($row['Name'], 0, 'ISO-8859-1') . (!!$row['Flagged'] ? ' <img src="' . IMAGE_DIR . 'flag.png"/>' : '') . '</td>' . PHP_EOL . 
                    '<td>' . htmlentities($row['ClassName'], 0, 'ISO-8859-1') . '</td>' . PHP_EOL .
                    '<td>' . htmlentities($row['School'], 0, 'ISO-8859-1') . '</td>' . PHP_EOL .
                    '<td><select class="selStatus" id="selStatus' . $row['ID'] . '" name="st_status[' . $row['ID'] . ']" style="font-size: 12px;" onkeyup="styleSelect(this);" onchange="styleSelect(this);"><option value="0" style="background-color: yellow;"' . ($row['Status'] == '0' ? ' selected="selected"' : '') . '>Não Contatado</option><option value="1" style="background-color: orange;"' . ($row['Status'] == '1' ? ' selected="selected"' : '') . '>Contatado</option><option value="2" style="background-color: red;"' . ($row['Status'] == '2' ? ' selected="selected"' : '') . '>Não Volta</option><option value="3" style="background-color: green;"' . ($row['Status'] == '3' ? ' selected="selected"' : '') . '>Rematriculado</option></select></td>' . PHP_EOL .
                    '<td><select id="selReason' . $row['ID'] . '" name="st_reason[' . $row['ID'] . ']" style="width: 95%;">' . PHP_EOL;
            
            foreach ($reasons as $rid => $desc) {
                echo '<option value="' . $rid . '"' . ($row['Reason'] == $rid ? ' selected="selected"' : '') . '>' . htmlentities($desc, 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
            }
            
            echo '</select></td>' . PHP_EOL .
                    '<td><textarea id="txtNotes' . $row['ID'] . '" class="txtNotes" name="st_notes[' . $row['ID'] . ']" maxlength="2000" style="width: 98%; height: 50px; resize: none; font-size: 12px;">' . htmlentities($row['Notes'], 0, 'ISO-8859-1') . '</textarea></td>' . PHP_EOL .
                    '</tr>';
            
        }
?>
            </table>
<?php
    
}

?>