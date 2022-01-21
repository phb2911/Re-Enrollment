<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once '../genreq/genreq.php';
require_once 'dropdown/dropdown.php';
require_once 'dropdown/dropdownMenu.php';
require_once 'required/campaigninfo.php';

// specific to this script
require_once 'editboxes/editboxes.php';

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

// not logged in
if (!$loginObj->isLoggedIn()) closeDbAndGoTo($db, LOGIN_PAGE);

$isAdmin = $loginObj->isAdmin();

// assign input to variables
$cid = getPost('cid');

// fetch all campaigns and store into array
$allCamp = allCampaigns($db);

// check if current campaign id submitted by select element
if (isset($cid) && isNum($cid) && isset($allCamp[intval($cid, 10)])){
    $cInfo = $allCamp[intval($cid, 10)];
    // save current campaign id into cookie
    setcookie('curCampId', $cInfo['ID'], 0, '/', COOKIE_DOMAIN);
}
elseif (!$cInfo = getCampaignInfo($db, $allCamp)){ // get current campaign info
    // invalid campaign
    closeDbAndGoTo($db, "searchcamp.php" . (isset($_SERVER['REQUEST_URI']) ? "?redir=" . urlencode($_SERVER['REQUEST_URI']) : ''));
}

// will be reused later
$cid = null;

$clsid = getGet('clsid');

$isValid = false;

if (isset($clsid) && isNum($clsid)){
    
    $q = "SELECT classes.Name, classes.User, users.Name AS UserName, campaigns.ID AS CampId, campaignName(campaigns.ID) AS Campaign, "
            . "campaigns.Open, (SELECT Name FROM schools WHERE ID = classes.School) AS School FROM classes LEFT JOIN users "
            . "ON classes.User = users.ID LEFT JOIN campaigns ON classes.Campaign = campaigns.ID WHERE classes.ID = $clsid";
    
    if ($row = $db->query($q)->fetch_assoc()){
        $campName = $row['Campaign'];
        $campIsOpen = !!$row['Open'];
        $userName = $row['UserName'];
        $clsName = $row['Name'];
        $unit = $row['School'];
        $uid = $row['User'];
        $cid = $row['CampId'];
        $isValid = true;
    }
    
}

// non admin trying to access class from other user
if ($isValid && !$isAdmin && $uid != $loginObj->userId){
    $isValid = false;
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Turma</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
    
    <link href="dropdown/dropdownMenu.css" rel="stylesheet" type="text/css" />
    <script type="text/javascript" src="dropdown/dropdownMenu.js"></script>
    <script type="text/javascript" src="editboxes/editboxes.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
        table.tblDisplay td {
            font-size: 14px;
        }
        
        table.tblDisplay {
            width: 100%;
            border: #61269e solid 1px;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            DropdownMenu.initialise();
            
            element('divTop').style.height = element('divHeadder').offsetHeight + 'px';
            
            if (element('selEditReason')) element('selEditReason').styleOption();
            if (element('selEditStatus')) styleStatusSelect();
            
        };
        
        function styleStatusSelect(){
            
            var sel = element('selEditStatus');
            var si = sel.selectedIndex;
            
            if (si === 0) sel.style.backgroundColor = 'yellow';
            else if (si === 1) sel.style.backgroundColor = 'orange';
            else if (si === 2) sel.style.backgroundColor = 'red';
            else sel.style.backgroundColor = 'green';
            
            element('selEditReason').disabled = (sel.selectedIndex != 2);
            
        }
        
        function menuClicked(menu, sid){
            
            switch (menu){
                case 1:
                    //window.location = 'modind.php?sid=' + sid;
                    showStatusBox(sid);
                    break;
                case 2:
                    window.location = 'student.php?sid=' + sid;
                    break;
                case 3:
                    window.location = 'editstudent.php?sid=' + sid;
                    break;
                case 4:
                    //window.location = 'flagstd.php?sid=' + sid;
                    showFlagBox(sid);
                    break;
                default:
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
                <form id="frmChangeCamp" method="post" action="class.php?clsid=<?php echo $clsid; ?>">
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
        
<?php if ($isValid){ ?>
        <br/>
        
        <!-- NAVIGATOR
        <div style="padding: 10px; font-size: 14px; font-weight: bold;">
            <a href="."><?php echo $campName; ?></a>
            <span style="font-size: 12px;">&#8594;</span>
            <a href="campbyuser.php?uid=<?php echo $uid; ?>"><?php echo htmlentities($userName, 0, 'ISO-8859-1'); ?></a>
            <span style="font-size: 12px;">&#8594;</span>
            <a href="class.php?clsid=<?php echo $clsid; ?>"><?php echo htmlentities($clsName, 0, 'ISO-8859-1'); ?></a>
        </div>
        -->
        
        <div class="panel">
            
            <span style="font-weight: bold;">Turma - Detalhes</span>
            <hr/>
            
            <table>
                <tr>
                    <td style="text-align: right;">Turma:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($clsName, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Unidade:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($unit, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $campName; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Colaborador:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo '<a href="campbyuser.php?uid=' . $uid . '">' . htmlentities($userName, 0, 'ISO-8859-1') . '</a>'; ?></td>
                </tr>
            </table>
            
        </div>
<?php

    $stdArr = array();
    $doArr = array();
    $canArr = array();
    $finArr = array();

    $result = $db->query("SELECT students.ID, students.Name, students.Situation, students.Status, students.Flagged, students.Notes, students.YearlyContract, reasons.Description AS Reason FROM students LEFT JOIN reasons ON students.Reason = reasons.ID WHERE Class = $clsid ORDER BY Name");
    
    $stdNum = $result->num_rows;
    
    while ($row = $result->fetch_assoc()){
        
        if ($row['Situation'] == 1){
            $doArr[] = $row;
        }
        elseif ($row['Situation'] == 2){
            $canArr[] = $row;
        }
        elseif ($row['Situation'] == 3){
            $finArr[] = $row;
        }
        else {
            $stdArr[] = $row;
        }
        
    }
    
    unset($row);
    
    $result->close();
    
    if ($stdNum){
        
        if (count($doArr)){
            displayStudentTable($doArr, 1, $campIsOpen, $isAdmin);
        }
        
        if (count($canArr)){
            displayStudentTable($canArr, 2, $campIsOpen, $isAdmin);
        }
        
        if (count($finArr)){
            displayStudentTable($finArr, 3, $campIsOpen, $isAdmin);
        }
        
        if (count($stdArr)){
            displayStudentTable($stdArr, 0, $campIsOpen, $isAdmin);
        }
        
        displayEditBoxes($db);
        
    }
    else echo '<div style="font-style: italic; color: red; padding: 10px;">Esta turma não contém alunos.</div>' . PHP_EOL;
    
}
else echo '<br/><span style="font-style: italic; color: red;">Parametros inválidos.</span>' . PHP_EOL;
?>
        
    </div>
    <p>&nbsp;</p>
    <p>&nbsp;</p>
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

function displayStudentTable($stdArr, $situation, $campIsOpen, $isAdmin){
 
    switch ($situation) {
        case 1:
            $desc = 'Evadidos';
            break;
        case 2:
            $desc = 'Cancelados';
            break;
        case 3:
            $desc = 'Concluintes';
            break;
        default:
            $desc = 'Alunos';
    }
    
?>
            <br/>
            <table class="tblDisplay">
                <tr style="background-color: #61269e; color: #ffffff;">
                    <td><img src="<?php echo IMAGE_DIR; ?>trans.png" style="width: 16px; height: 16px;"/></td>
                    <td style="width: 40%;"><?php echo $desc . ' (' . count($stdArr) . ')'; ?></td>
                    <td style="width: 15%; text-align: center;">Status</td>
                    <td style="width: 15%;">Motivo</td>
                    <td style="width: 30%;">Observações</td>
                    <td><img src="<?php echo IMAGE_DIR; ?>cal1.png" style="vertical-align: bottom;" title="Contrato Anual"/></td>
                    <td><img src="<?php echo IMAGE_DIR; ?>flag.png" style="vertical-align: bottom;" title="Marcado"/></td>
                </tr>
<?php
            $bgcolor = '';
            
            foreach ($stdArr as $stdInfo){

                switch ($stdInfo['Status']) {
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
                
                $bgcolor = ($bgcolor == '#c6c6c6') ? '#ffffff' : '#c6c6c6';

                echo '<tr style="background-color: ' . $bgcolor . ';">' . PHP_EOL . '<td>' . PHP_EOL;
                
                // set flag and display menu
                // if not admin, do not display menu if campaign is colsed
                if ($isAdmin || $campIsOpen){
                    
                    printMenu($stdInfo['ID'], $isAdmin);
                    
                    // if campaign is open, add flag no matter what
                    // change visibility according to Flagged status
                    $flagImg = '<img id="imgFlag' . $stdInfo['ID'] . '" src="' . IMAGE_DIR . 'flag.png" style="cursor: pointer; visibility: ' . (!!$stdInfo['Flagged'] ? 'visible' : 'hidden') . ';" title="Detalhes" onclick="showFlagBox(' . $stdInfo['ID'] . ');"/>';
                }
                else {
                    // campaign is closed. display flag only if student is flagged
                    $flagImg = (!!$stdInfo['Flagged'] ? '<img src="' . IMAGE_DIR . 'flag.png" style="vertical-align: bottom;"/>' : '');
                }
                
                echo '</td>' . PHP_EOL . '<td>' . htmlentities($stdInfo['Name'], 0, 'ISO-8859-1') . '</td>' . PHP_EOL .
                        '<td style="text-align: center;"><div id="divStatus' . $stdInfo['ID'] . '" style="background-color: ' . $stBg . ';">' . $st . '</div></td>' . PHP_EOL .
                        '<td><span id="spReason' . $stdInfo['ID'] . '">' . htmlentities($stdInfo['Reason'], 0, 'ISO-8859-1') . '</span></td>' . PHP_EOL .
                        '<td><span id="spNotes' . $stdInfo['ID'] . '">' . nl2br(htmlentities($stdInfo['Notes'], 0, 'ISO-8859-1')) . '</span></td>' . PHP_EOL .
                        '<td>' . (!!$stdInfo['YearlyContract'] ? '<img src="' . IMAGE_DIR . 'check3.png"/>' : '') . '</td><td>' . $flagImg . '</td></tr>' . PHP_EOL;

            }

?>
            </table>
    
<?php
    
}

?>