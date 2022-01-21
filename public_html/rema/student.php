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

$msg = null;
$isValid = false;

$sid = getGet('sid');

if (isNum($sid)){
    
    $q = "SELECT students.*, classes.Name AS ClassName, users.Name AS Teacher, users.ID AS UserID, reasons.Description AS Reason, "
        . "campaignName(campaigns.ID) AS Campaign, campaigns.Open AS CampOpen, "
        . "(SELECT Name FROM schools WHERE ID = classes.School) AS School FROM students LEFT JOIN classes ON students.Class = classes.ID "
        . "LEFT JOIN users ON classes.User = users.ID LEFT JOIN reasons ON students.Reason = reasons.ID "
        . "LEFT JOIN campaigns ON classes.Campaign = campaigns.ID WHERE students.ID = $sid";
    
    if ($row = $db->query($q)->fetch_assoc()){
        
        $stdName = $row['Name'];
        $email = $row['Email'];
        $clsID = $row['Class'];
        $notes = $row['Notes'];
        $isFlagged = !!$row['Flagged'];
        $flagNotes = $row['FlagNotes'];
        $className = $row['ClassName'];
        $unit = $row['School'];
        $teacher = $row['Teacher'];
        $campName = $row['Campaign'];
        $campIsOpen = !!$row['CampOpen'];
        $uid = $row['UserID'];
        $reason = $row['Reason'];
        $yrCont = (!!$row['YearlyContract'] ? 'Sim' : 'Não');
        
        switch ($row['Situation']) {
            case '0':
                $situation = 'Ativo';
                break;
            case '1':
                $situation = 'Evadido';
                break;
            case '2':
                $situation = 'Cancelado';
                break;
            case '3':
                $situation = 'Concluinte';
                break;
            default:
                $situation = 'Desconhecida';
        }
        
        switch ($row['Status']) {
            case '0':
                $st = 'Não Contatado';
                $stBg = 'yellow';
                break;
            case '1':
                $st = 'Contatado';
                $stBg = 'orange';
                break;
            case '2':
                $st = 'Não Volta';
                $stBg = 'red';
                break;
            default:
                $st = 'Rematriculado';
                $stBg = 'green';
        }
        
        // non admin trying to access class from other user
        $isValid = ($isAdmin || $uid == $loginObj->userId);
        
    }
    
}

// student modified successfully
if (isset($_GET['mod'])){
    $msg = '<span style="font-style: italic; color: blue;">Status modificado com sucesso.</span>';
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Aluno</title>
    
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
                <form id="frmChangeCamp" method="post" action="student.php<?php if ($isValid) echo '?sid=' . $sid; ?>">
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
<?php if ($isValid){ ?>
        <div id="divMsg" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>;">
            <div style="position: relative; width: 550px; left: 0; right: 0; margin: auto; border-radius: 5px; box-shadow: 3px 3px 3px #808080; background-color: #c6c6c6; padding: 10px;">
                <div class="closeImg" onclick="element('divMsg').style.display = 'none';"></div>
                <?php echo $msg; ?>
            </div>
            <br/>
        </div>
        <div class="panel" style="width: 550px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Detalhes do Aluno</span>
            <hr/>
            
            <table style="width: 100%;">
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Nome:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($stdName, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">E-mail:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo (is_null($email) ? '<span style="font-style: italic;">--</span>' : htmlentities($email, 0, 'ISO-8859-1')); ?></td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $campName; ?></td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Professor:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo ($isAdmin ? '<a href="user.php?uid=' . $uid . '">' . htmlentities($teacher, 0, 'ISO-8859-1') . '</a>' : htmlentities($teacher, 0, 'ISO-8859-1')); ?></td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Turma:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo '<a href="class.php?clsid=' . $clsID . '">' . htmlentities($className, 0, 'ISO-8859-1') . '</a>'; ?></td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Unidade:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($unit, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Contrato Anual:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $yrCont; ?></td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Situação:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $situation; ?></td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Status:</td>
                    <td style="width: 100%; font-weight: bold;"><span style="background-color: <?php echo $stBg; ?>; padding: 0 3px 0 3px;"><?php echo $st; ?></span></td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Motivo:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo (is_null($reason) ? '<span style="font-style: italic;">--</span>' : htmlentities($reason, 0, 'ISO-8859-1')); ?></td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right; vertical-align: top;">Observações:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo (is_null($notes) ? '<span style="font-style: italic;">--</span>' : nl2br(htmlentities($notes, 0, 'ISO-8859-1'))); ?></td>
                </tr>
<?php

    if ($isFlagged){
        echo '<tr><td colspan="2"><img src="' . IMAGE_DIR . 'flag.png"/> <span style="font-style: italic;">Este aluno está marcado.</span></td></tr>'. PHP_EOL . 
                '<tr><td style="white-space: nowrap; text-align: right;">Motivo:</td><td style="width: 100%;">' . htmlentities($flagNotes, 0, 'ISO-8859-1') . '</td></tr>';
    }

?>
            </table>
<?php

    if ($isAdmin){
        echo '<div style="padding-top: 5px;"><button onclick="window.location = \'editstudent.php?sid=' . $sid . '\'"><img src="' . IMAGE_DIR . 'pencil1.png" style="vertical-align: bottom;"/> Editar Aluno</button></div>' . PHP_EOL;
    }
    elseif ($campIsOpen) {
        echo '<div style="padding-top: 5px;"><button onclick="window.location = \'modind.php?sid=' . $sid . '\'"><img src="' . IMAGE_DIR . 'pencil1.png" style="vertical-align: bottom;"/> Modificar Status</button></div>' . PHP_EOL;
    }
?>
        </div>
<?php
}
else echo '<span style="font-style: italic; color: red;">Parametros inválidos.</span>' . PHP_EOL;
?>
        <p>&nbsp;</p>
    </div>
    
</body>
</html>
<?php

$db->close();

?>