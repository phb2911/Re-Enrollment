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
    // invalid campaign
    closeDbAndGoTo($db, "searchcamp.php" . (isset($_SERVER['REQUEST_URI']) ? '?redir=' . urlencode($_SERVER['REQUEST_URI']) : ''));
}

$sid = getGet('sid');
$isValid = false;
$msg = null;

if (isset($sid) && isNum($sid)){
    
    $q = "SELECT students.Name, students.Situation, students.Status, students.Notes, students.Reason, students.YearlyContract, classes.Name AS ClassName, classes.User, users.Name AS UserName, "
        . "CONCAT(campaigns.`Year`, '.', campaigns.Semester) AS CampName, campaigns.Open AS CampOpen, (SELECT Name FROM schools WHERE ID = classes.School) AS School FROM students LEFT JOIN classes ON "
        . "students.Class = classes.ID LEFT JOIN campaigns ON classes.Campaign = campaigns.ID LEFT JOIN users ON classes.User = users.ID "
        . "WHERE students.ID = $sid";
           
    if ($stdInfo = $db->query($q)->fetch_assoc()){
            
        $campName = $stdInfo['CampName'];
        $campIsOpen = !!$stdInfo['CampOpen'];
        $stdName = $stdInfo['Name'];
        $stdStatus = intval($stdInfo['Status'], 10);
        $usrName = $stdInfo['UserName'];
        $uid = $stdInfo['User'];
        $clsName = $stdInfo['ClassName'];
        $unit = $stdInfo['School'];
        $notes = $stdInfo['Notes'];
        $reason = $stdInfo['Reason'];
        $isYearlyContract = !!$stdInfo['YearlyContract'];
        
        switch ($stdInfo['Situation']) {
            case '1':
                $situation = ' <span style="font-style: italic;">(Evadido)</span>';
                break;
            case '2':
                $situation = ' <span style="font-style: italic;">(Cancelado)</span>';
                break;
            case '3':
                $situation = ' <span style="font-style: italic;">(Concluinte)</span>';
                break;
        }

        $isValid = true;

    }
    
}

// non-admin user trying to acces another user's student
if (!$isAdmin && $isValid && $loginObj->userId != $uid){
    closeDbAndGoTo($db, ".");
}

if ($isValid && getPost('save') === '1'){
    
    $newStatus = getPost('status');
    $newReason = (isNum(getPost('reason')) ? getPost('reason') : 'null');
    $newNotes = trim(getPost('notes'));
    
    if (!isNum($newStatus) || $newStatus > 3 || (!$isAdmin && !$campIsOpen)){
        $msg = '<span style="font-style: italic; color: red;">Parametros inválidos.</span>';
    }
    elseif ($isYearlyContract && $newStatus != 3){
        $msg = '<span style="font-style: italic; color: red;">O aluno marcado com a opção \'Contrato Anual\' deve ter o status \'Rematriculado\' selecionado.</span>';
    }
    elseif (strlen($newNotes) > 2000){
        $msg = '<span style="font-style: italic; color: red;">As observações não pode conter mais de 2000 caracteres.</span>';
    }
    elseif ($db->query("UPDATE students SET Status = $newStatus, Notes = " . (strlen($newNotes) ? "'" . $db->real_escape_string($newNotes) . "'" : "null") . ", Reason = $newReason WHERE ID = $sid")){
        // successfully modified
        closeDbAndGoTo($db, "student.php?mod=1&sid=" . $sid);
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
    }
        
    $stdStatus = $newStatus;
    $reason = $newReason;
    $notes = $newNotes;
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Modificar Aluno</title>
    
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
            
            if (element('selStatus')){
                
                styleSelect(element('selStatus'));
                
            }
            
            if (element('chkFlag')) element('txtFlagNotes').disabled = !element('chkFlag').checked;
            
        };
        
        function styleSelect(sel){
            
            var si = sel.selectedIndex;
            
            if (si === 0) sel.style.backgroundColor = 'yellow';
            else if (si === 1) sel.style.backgroundColor = 'orange';
            else if (si === 2) sel.style.backgroundColor = 'red';
            else sel.style.backgroundColor = 'green';
            
            element('selReason').disabled = (element('selStatus').selectedIndex != 2);
            
        }
        
        function validateInput(){
            
            if (element('chkYrCont').checked && element('selStatus').selectedValue() != 3){
                alert('O aluno marcado com a opção \'Contrato Anual\' deve ter o status \'Rematriculado\' selecionado.');
                return false;
            }
            
            if (element('txtNotes').value.trim().length > 2000){
                alert('As observações não pode conter mais de 2000 caracteres.');
                return false;
            }
            
            return true;
            
        }
                               
    </script>
    
</head>
<body>
    
    <div class="top" id="divTop"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
        
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
            
            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action="modind.php<?php if ($isValid) echo '?sid=' . $sid; ?>">
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

if (!$isValid){
    echo '<span style="font-style: italic; color: red;">Identidade do aluno não é válida.</sapn>';
}
else {
?>
        <div id="divMsg" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>;">
            <div style="position: relative; width: 550px; left: 0; right: 0; margin: auto; border-radius: 5px; box-shadow: 3px 3px 3px #808080; background-color: #c6c6c6; padding: 10px;">
                <div class="closeImg" onclick="element('divMsg').style.display = 'none';"></div>
                <?php echo $msg; ?>
            </div>
            <br/>
        </div>
        <div class="panel" style="width: 550px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Modificar Status do Aluno</span>
            <hr/>
<?php if ($isAdmin || $campIsOpen) echo '<form id="form1" method="post" action="modind.php?sid=' . $sid . '">'; ?>
            
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $campName; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Colaborador:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($usrName, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Aluno:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($stdName, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Turma:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($clsName, 0, 'ISO-8859-1') . $situation; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Unidade:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($unit, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Contrato Anual:</td>
                    <td style="width: 100%; font-weight: bold;"><input type="checkbox" id="chkYrCont" onclick="return false;"<?php if ($isYearlyContract) echo ' checked="checked"'; ?>/></td>
                </tr>
<?php

    if (!$isAdmin && !$campIsOpen){
        echo '<tr><td style="font-style: italic; color: red;" colspan="2">Esta campanha foi encerrada, este aluno não pode ser modificado.</td></tr>';
    }
    else {
?>
                <tr>
                    <td style="text-align: right;">Status:</td>
                    <td style="width: 100%;">
                        <select id="selStatus" name="status" style="border-width: 1px;" onkeyup="styleSelect(this);" onchange="styleSelect(this);">
                            <option value="0" style="background-color: yellow;"<?php if ($stdStatus == 0) echo ' selected="selected"'; ?>>Não Contatado</option>
                            <option value="1" style="background-color: orange;"<?php if ($stdStatus == 1) echo ' selected="selected"'; ?>>Contatado</option>
                            <option value="2" style="background-color: red;"<?php if ($stdStatus == 2) echo ' selected="selected"'; ?>>Não Volta</option>
                            <option value="3" style="background-color: green;"<?php if ($stdStatus == 3) echo ' selected="selected"'; ?>>Rematriculado</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right;">Motivo:</td>
                    <td style="width: 100%;">
                        <select id="selReason" name="reason" style="width: 200px;">
<?php

        $result = $db->query("CALL sp_reasons()");
        
        while ($row = $result->fetch_assoc()){
            
            echo '<option value="' . $row['ID'] . '"' . ($row['ID'] == $reason ? ' selected="selected"' : '') . '>' . htmlentities($row['Description'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
            
        }
        
        $result->close();
        
        // clear stored results after stored procedure call
        clearStoredResults($db);

?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; vertical-align: top;">Observações:</td>
                    <td style="width: 100%;">
                        <textarea id="txtNotes" name="notes" maxlength="2000" style="width: 95%; height: 100px; resize: none;"><?php echo htmlentities($notes, 0, 'ISO-8859-1'); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right;">&nbsp;</td>
                    <td style="width: 100%;">
                        <button type="submit" name="save" value="1" style="width: 90px;" onclick="return validateInput();"><img src="<?php echo IMAGE_DIR; ?>disk2.png"/> Salvar</button>
                        <button type="button" style="width: 90px;" onclick="window.location = 'student.php?sid=<?php echo $sid; ?>';"><img src="<?php echo IMAGE_DIR; ?>cancel2.png"/> Cancelar</button>
                    </td>
                </tr>
<?php } ?>
            </table>
<?php if ($isAdmin || $campIsOpen) echo '</form>'; ?>
            
        </div>
        
<?php } ?>
    
        <p>&nbsp;</p>
    
    </div>
    
</body>
</html>
<?php

$db->close();

?>