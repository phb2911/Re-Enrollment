<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once 'dropdown/dropdown.php';
require_once 'dropdown/dropdownMenu.php';
require_once '../genreq/genreq.php';
require_once 'required/campaigninfo.php';

// specific to this script
require_once 'editboxes/editboxes.php';

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

if (!$loginObj->isLoggedIn()) closeDbAndGoTo($db, LOGIN_PAGE);

$isAdmin = $loginObj->isAdmin();

if ($isAdmin){
    closeDbAndGoTo($db, "camp.php");
}

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
    closeDbAndGoTo($db, "searchcamp.php");
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema</title>
    
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
        
        table.list {
            width: 100%; 
            box-shadow: 3px 3px 3px #808080; 
            border: #61269e solid 1px;
        }
        
        table.list td {
            font-size: 14px;
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
                case 4:
                    //window.location = 'flagstd.php?sid=' + sid;
                    showFlagBox(sid);
                    break;
                default:
            }
            
        }
        
        function subCampRedir(scid){
            
            var frm = document.createElement('form');
            frm.method = 'post';
            frm.action = 'subcampbyuser.php';
            
            document.body.appendChild(frm);
            
            var hid = document.createElement('input');
            hid.type = 'hidden';
            hid.name = 'scampid';
            hid.value = scid;
            
            frm.appendChild(hid);
            frm.submit();
            
        }
        
        function updateValues(){
            
            var xmlhttp = xmlhttpobj();

            xmlhttp.onreadystatechange = function() {

                // request ready
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {

                    var obj = JSON.parse(xmlhttp.responseText);

                    if (!obj.Error){ // skip on error
                        
                        var Active = parseInt(obj.Active, 10);
                        var ActiveEnrolled = parseInt(obj.ActiveEnrolled, 10);
                        var DropOuts = parseInt(obj.DropOut, 10);
                        var DropOutEnrolled = parseInt(obj.DropOutEnrolled, 10);
                        var NotContacted = parseInt(obj.NotContacted, 10);
                        var Contacted = parseInt(obj.Contacted, 10);
                        var NotComingBack = parseInt(obj.NotComingBack, 10);
                        var Enrolled = parseInt(obj.Enrolled, 10);
                        var YearlyContract = parseInt(obj.YearlyContract, 10);
                        var Total = NotContacted + Contacted + NotComingBack + Enrolled;
                                                
                        // total students
                        element("tdTotalStds").innerHTML = Total;
                        
                        // active
                        element('tdStds').innerHTML = Active;
                        element('tdStdsPrc').innerHTML = percent(Total, Active);
                                                
                        // dropouts
                        element('tdDos').innerHTML = DropOuts;
                        element('tdDosPrc').innerHTML = percent(Total, DropOuts);
                                                
                        // semester contracts
                        element('tdSem').innerHTML = (Total - YearlyContract);
                        element('tdSemPrc').innerHTML = percent(Total, (Total - YearlyContract));
                                                
                        // contacted + enrolled
                        element('tdCont').innerHTML = Contacted;
                        element('tdContPrc').innerHTML = percent(Total, Contacted);
                                                
                        // not contacted
                        element('tdNotCont').innerHTML = NotContacted;
                        element('tdNotContPrc').innerHTML = percent(Total, NotContacted);
                                                
                        // not coming back
                        element('tdWontBeBack').innerHTML = NotComingBack;
                        element('tdWontBeBackPrc').innerHTML = percent(Total, NotComingBack);
                                                
                        element('tdTotalSemEnr').innerHTML = (ActiveEnrolled + DropOutEnrolled - YearlyContract);
                        element('tdTotalSemEnrPrc').innerHTML = percent((Total - YearlyContract), (ActiveEnrolled + DropOutEnrolled - YearlyContract));
                        
                        // enrolled
                        element('tdTotalEnr').innerHTML = (ActiveEnrolled + DropOutEnrolled);
                        element('tdTotalEnrPrc').innerHTML = percent(Total, (ActiveEnrolled + DropOutEnrolled));
                        
                    }

                }

            };

            // THIS WILL NOT STOP PAGE
            xmlhttp.open("POST", "aj_getnumbers.php?" + Math.random(), true);
            xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xmlhttp.send(<?php echo '"uid=' . $loginObj->userId . '&cid=' . $cInfo['ID'] . '&f=1"'; ?>);
            
        }
        
        // calculate the percentage of val2 over val1
        // ex: percent(40, 20) = 50 (20 is 50% of 40)
        // note: if val1 = 0, 0 is returned
        // note 2: the return value will have a maximum of 2 decimal places
        function percent(val1, val2){
            
            if (val1 == 0) return '0%';
            
            return +(((val2 * 100) / val1).toFixed(2)) + '%';
            
        }
        
    </script>
    
</head>
<body>
    
    <div class="top" id="divTop"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
        
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
            
            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action=".">
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

displayRegularUserPage($loginObj->userId, $cInfo, getGet('s'));

if ($cInfo['Open']) {
    displayEditBoxes($db);
} 

?>
        
    </div>
    
    <p>&nbsp;</p>
    <p>&nbsp;</p>
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

//-----------------------------------------

function sortLing($flag, $sortIndex, $queryString){
    
    $result = '';
    
    if ($flag = 0){
        // student
        if ($sortIndex == 1){
            $result = ' &nbsp;<a href="' . (isset($queryString) ? $queryString . '&s=2' : '?s=2') . '"><img src="' . IMAGE_DIR . 'sort_up.png" style="vertical-align: top;"/></a>';
        }
    }
    
}

//-----------------------------------------

function displayRegularUserPage($uid, $cInfo, $sortIndex){
    
    global $db;
    
    // sorting
    $si = intval($sortIndex, 10);
        
    // start building query string
    $qs = isset($_GET['cid']) ? '?cid=' . $_GET['cid'] : null;
    
    switch ($si) {
        case 1:
            // student asc
            $sortString = 'students.Name, ClassName';
            break;
        case 2:
            // student desc
            $sortString = 'students.Name DESC, ClassName';
            break;
        case 3:
            // class asc
            $sortString = 'ClassName, School, students.Name';
            break;
        case 4:
            $sortString = 'ClassName DESC, School, students.Name';
            // class desc
            break;
        case 5:
            // status asc
            $sortString = 'students.Status, School, ClassName, students.Name';
            break;
        case 6:
            // status asc
            $sortString = 'students.Status DESC, School, ClassName, students.Name';
            break;
        case 7:
            // reason asc
            $sortString = 'ISNULL(Reason), School, ClassName, students.Name';
            break;
        case 8:
            // reason asc
            $sortString = 'ISNULL(Reason), Reason DESC, School, ClassName, students.Name';
            break;
        case 9:
            // school dsc
            $sortString = 'School DESC, ClassName, students.Name';
            break;
        default:
            // school asc
            $sortString = 'School, ClassName, students.Name';
    }
    
    $userName = $db->query("SELECT Name FROM users WHERE ID = $uid")->fetch_row()[0];
    $cid = $cInfo['ID'];
    $campName = $cInfo['Name'];
    $campIsOpen = !!$cInfo['Open'];
    
    /* declare two-dimensional count array
     * [0][0] - active - not contacted
     * [0][1] - active - contacted
     * [0][2] - active - not coming back
     * [0][3] - active - enrolled
     * [1][0] - dropout - not contacted
     * [1][1] - dropout - contacted
     * [1][2] - dropout - not coming back
     * [1][3] - dropout - enrolled 
     */
    $countArr = array(array(0,0,0,0), array(0,0,0,0));
    
    // student array
    // [0] - Active
    // [1] - Dropout
    // [2] - Cancelled
    // [3] - finished
    $stdArr = array(array(), array());
    
    // yearly contract count
    $yrContCount = 0;
    
    $result = $db->query("SELECT students.ID, students.Name, students.Situation, students.Status, students.Notes, students.Flagged, students.YearlyContract, classes.Name AS ClassName, " .
        "classes.ID as ClassID, (SELECT Name FROM schools WHERE ID = classes.School) AS School, reasons.Description AS Reason FROM students " .
        "LEFT JOIN classes ON students.Class = classes.ID LEFT JOIN reasons ON students.Reason = reasons.ID " .
        "WHERE classes.User = $uid AND classes.Campaign = $cid AND students.Situation <= 1 ORDER BY $sortString");

    while ($row = $result->fetch_assoc()){
        
        $sit = intval($row['Situation'], 10);
        $sta = intval($row['Status'], 10);
        
        // increment count array
        $countArr[$sit][$sta]++;
        
        // populate student arrays according to situation
        $stdArr[$sit][] = $row;
        
        // increment yearly contract
        if (!!$row['YearlyContract']) $yrContCount++;
        
    }

    $result->close();
    
    $stdNum = count($stdArr[0]);   // student count
    $doNum = count($stdArr[1]);     // dropout count

    $stdTotal = $stdNum + $doNum; // total of students
    
    $contacted = $countArr[0][1] + $countArr[1][1]; // contacted
    $notCont = $countArr[0][0] + $countArr[1][0]; // not contacted
    $notCmBk = $countArr[0][2] + $countArr[1][2]; // not comming back
    $totalErn = $countArr[0][3] + $countArr[1][3]; // enrolled
    
?>
        <br/>
        <div class="panel">
            
            <span style="font-weight: bold;">Campanha por Colaborador</span>
            <hr/>
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $campName; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Colaborador:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($userName, 0, 'ISO-8859-1'); ?></td>
                </tr>
<?php
    if (!$campIsOpen) echo '<tr><td style="font-style: italic; color: red;" colspan="2">Esta campanha foi encerrada e não pode ser alterada.</td></tr>' . PHP_EOL;
    elseif ($stdTotal > 0) echo '<tr><td colspan="2"><button onclick="window.location = \'modstd.php\';"><img src="' . IMAGE_DIR . 'people.png" style="vertical-align: bottom;"/> Alterar Status dos Alunos</button></td></tr>' . PHP_EOL;
?>
            </table>
        </div>
    
<?php

    $result = $db->query("SELECT ID, Name FROM subcamps WHERE Parent = $cid");
    
    if ($result->num_rows){
?>
        <br/>
        <div class="panel">
            
            <span style="font-weight: bold;">Subcampanhas</span>
            <hr/>
            <ul style="line-height: 150%; margin: 5px;">
<?php
        
        while ($row = $result->fetch_assoc()){
            echo '<li style="color: blue;"><a href="#" onclick="subCampRedir(' . $row['ID'] . '); return false;">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</a></li>' . PHP_EOL;
        }

?>
            </ul>
        </div>
<?php
    }
    
    $result->close();

    if ($stdTotal > 0) {
?>
        <br/>
        <table style="border-collapse: collapse; width: 100%; box-shadow: 3px 3px 3px #808080;">
            <tr style="background-color: #c6c6c6;">
                <td style="width: 70%; padding-left: 10px;">Total de Alunos</td>
                <td id="tdTotalStds" style="text-align: right; padding-right: 20px; width: 15%;"><?php echo $stdTotal; ?></td>
                <td style="text-align: right; padding-right: 20px; width: 15%;">100%</td>
            </tr>
            <tr style="background-color: #ffffff;">
                <td style="padding-left: 10px;">Alunos Ativos</td>
                <td id="tdStds" style="text-align: right; padding-right: 20px;"><?php echo $stdNum; ?></td>
                <td id="tdStdsPrc" style="text-align: right; padding-right: 20px;"><?php echo +number_format((($stdNum * 100) / $stdTotal), 2) . '%'; ?></td>
            </tr>
            <tr style="background-color: #c6c6c6;">
                <td style="padding-left: 10px;">Evadidos</td>
                <td id="tdDos" style="text-align: right; padding-right: 20px;"><?php echo $doNum; ?></td>
                <td id="tdDosPrc" style="text-align: right; padding-right: 20px;"><?php echo +number_format((($doNum * 100) / $stdTotal), 2) . '%'; ?></td>
            </tr>
            <tr style="background-color: #ffffff;">
                <td style="padding-left: 10px;">Alunos Semestrais</td>
                <td id="tdSem" style="text-align: right; padding-right: 20px;"><?php echo ($stdTotal - $yrContCount); ?></td>
                <td id="tdSemPrc" style="text-align: right; padding-right: 20px;"><?php echo +number_format((($stdTotal - $yrContCount) * 100) / $stdTotal, 2) . '%'; ?></td>
            </tr>
            <tr style="background-color: #c6c6c6;">
                <td style="padding-left: 10px;">Contatados</td>
                <td id="tdCont" style="text-align: right; padding-right: 20px;"><?php echo $contacted; ?></td>
                <td id="tdContPrc" style="text-align: right; padding-right: 20px;"><?php echo +number_format(($contacted * 100) / $stdTotal, 2) . '%'; ?></td>
            </tr>
            <tr style="background-color: #ffffff;">
                <td style="padding-left: 10px;">Não Contatados</td>
                <td id="tdNotCont" style="text-align: right; padding-right: 20px;"><?php echo $notCont; ?></td>
                <td id="tdNotContPrc" style="text-align: right; padding-right: 20px;"><?php echo +number_format(($notCont * 100) / $stdTotal, 2) . '%'; ?></td>
            </tr>
            <tr style="background-color: #c6c6c6;">
                <td style="padding-left: 10px;">Não Voltam</td>
                <td id="tdWontBeBack" style="text-align: right; padding-right: 20px;"><?php echo $notCmBk; ?></td>
                <td id="tdWontBeBackPrc" style="text-align: right; padding-right: 20px;"><?php echo +number_format(($notCmBk * 100) / $stdTotal, 2) . '%'; ?></td>
            </tr>
            <tr style="background-color: #ffffff; color: red;">
                <td style="padding-left: 10px;">Total de Semestrais Rematriculados<sup style="font-size: 10px;">1</sup></td>
                <td id="tdTotalSemEnr" style="text-align: right; padding-right: 20px;"><?php echo ($totalErn - $yrContCount); ?></td>
                <td id="tdTotalSemEnrPrc" style="text-align: right; padding-right: 20px;"><?php echo +number_format(($stdTotal == $yrContCount ? 0 : (($totalErn - $yrContCount) * 100) / ($stdTotal - $yrContCount)), 2) . '%'; ?></td>
            </tr>
            <tr style="background-color: #c6c6c6; color: red;">
                <td style="padding-left: 10px;">Total de Rematriculados</td>
                <td id="tdTotalEnr" style="text-align: right; padding-right: 20px;"><?php echo $totalErn; ?></td>
                <td id="tdTotalEnrPrc" style="text-align: right; padding-right: 20px;"><?php echo +number_format(($totalErn * 100) / $stdTotal, 2) . '%'; ?></td>
            </tr>
        </table>
        <div style="font-style: italic; color: red; padding: 10px; font-size: 13px;">
            1. A porcentagem de alunos semestrais é calculada a partir do número de alunos semestrais.<br/>
            &#10013; As demais porcentagens são calculadas a partir do número total de alunos.
        </div>
<?php if ($campIsOpen) { ?>
            <div class="panel">
                <img src="<?php echo IMAGE_DIR; ?>info.png" /> Para fazer modificações, clique no ícone <img src="<?php echo IMAGE_DIR; ?>list.png" style=" width: 12px; height: 12px;"/> para exibir o menu do aluno.
            </div>
            <br/>
<?php 

        }
                
        if ($doNum){
            // display droupouts table
            displayStudentTable($stdArr, $campIsOpen, $qs, $si, true);
            echo '<br/>' . PHP_EOL;
        }
        
        if ($stdNum){
            // display students table
            displayStudentTable($stdArr, $campIsOpen, $qs, $si, false);
        }

    }
    else {
        echo '<br/><span style="font-style: italic; color: red;">Este colaborador não participou desta campanha.</sapn>' . PHP_EOL;
    }

}

//-----------------------------------------

function displayStudentTable(&$stdArr, $campIsOpen, $qs, $si, $isDropout){
?>
            <table class="list">
                <tr style="background-color: #61269e; color: #ffffff;">
                    <td><img src="<?php echo IMAGE_DIR; ?>trans.png" style="width: 16px; height: 16px;"/></td>
                    <td style="width: 26%;"><a href="<?php echo (isset($qs) ? $qs . '&' : '?') . 's=' . ($si == 1 ? '2' : '1'); ?>" style="color: white;"><?php echo ($isDropout ? 'Evadidos' : 'Alunos'); ?></a><?php if ($si == 1 || $si == 2) echo '  &nbsp;<img src="' . IMAGE_DIR . 'sort_' . ($si == 1 ? 'up' : 'down') . '.png" style="vertical-align: top;"/>'; ?></td>
                    <td style="width: 18%;"><a href="<?php echo (isset($qs) ? $qs . '&' : '?') . 's=' . ($si == 3 ? '4' : '3'); ?>" style="color: white;">Turma<a/><?php if ($si == 3 || $si == 4) echo '  &nbsp;<img src="' . IMAGE_DIR . 'sort_' . ($si == 3 ? 'up' : 'down') . '.png" style="vertical-align: top;"/>'; ?></td>
                    <td style="width: 8%;"><a href="<?php echo (isset($qs) ? $qs . '&' : '?') . 's=' . ($si == 0 ? '9' : '0'); ?>" style="color: white;">Unidade</a><?php if ($si >= 9 || $si == 0) echo '  &nbsp;<img src="' . IMAGE_DIR . 'sort_' . ($si != 9 ? 'up' : 'down') . '.png" style="vertical-align: top;"/>'; ?></td>
                    <td style="width: 13%; text-align: center;"><a href="<?php echo (isset($qs) ? $qs . '&' : '?') . 's=' . ($si == 5 ? '6' : '5'); ?>" style="color: white;">Status</a><?php if ($si == 5 || $si == 6) echo '  &nbsp;<img src="' . IMAGE_DIR . 'sort_' . ($si == 5 ? 'up' : 'down') . '.png" style="vertical-align: top;"/>'; ?></td>
                    <td style="width: 12%;"><a href="<?php echo (isset($qs) ? $qs . '&' : '?') . 's=' . ($si == 7 ? '8' : '7'); ?>" style="color: white;">Motivo</a><?php if ($si == 7 || $si == 8) echo '  &nbsp;<img src="' . IMAGE_DIR . 'sort_' . ($si == 7 ? 'up' : 'down') . '.png" style="vertical-align: top;"/>'; ?></td>
                    <td style="width: 23%;">Observações</td>
                    <td><img src="<?php echo IMAGE_DIR; ?>cal1.png" title="Contrato Anual"/></td>
                    <td><img src="<?php echo IMAGE_DIR; ?>flag.png"/></td>
                </tr>
<?php

            $arr = ($isDropout ? $stdArr[1] : $stdArr[0]);
            $bgcolor = null;

            foreach ($arr as $stdInfo){
                
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
                
                // set flag image
                if ($campIsOpen){
                    // if campaign is open, add flag no matter what
                    // change visibility according to Flagged status
                    $flagImg = '<img id="imgFlag' . $stdInfo['ID'] . '" src="' . IMAGE_DIR . 'flag.png" style="cursor: pointer; visibility: ' . (!!$stdInfo['Flagged'] ? 'visible' : 'hidden') . ';" title="Detalhes" onclick="showFlagBox(' . $stdInfo['ID'] . ');"/>';
                }
                else {
                    // campaign is closed. display flag only if student is flagged
                    $flagImg = (!!$stdInfo['Flagged'] ? '<img src="' . IMAGE_DIR . 'flag.png" style="vertical-align: bottom;"/>' : '');
                }
                
                $bgcolor = ($bgcolor == '#c6c6c6') ? '#ffffff' : '#c6c6c6';

                echo '<tr style="background-color: ' . $bgcolor . ';"><td>' . PHP_EOL;
                        
                if ($campIsOpen) printMenu($stdInfo['ID'], false); // show menu only when campaign is open
                
                        //($campIsOpen ? '<img id="imgList' . $stdInfo['ID'] . '" src="' . IMAGE_DIR . 'list.png" title="Menu" style="cursor: pointer;" onclick="showMenu(this, ' . $stdInfo['ID'] . ');"/>' : '') . 
                echo '</td>' . PHP_EOL . '<td>' . htmlentities($stdInfo['Name'], 0, 'ISO-8859-1') . '</td>' . PHP_EOL .
                        '<td><a href="class.php?clsid=' . $stdInfo['ClassID'] . '">' . htmlentities($stdInfo['ClassName'], 0, 'ISO-8859-1') . '</a></td>' . PHP_EOL .
                        '<td>' . htmlentities($stdInfo['School'], 0, 'ISO-8859-1') . '</td>' . PHP_EOL .
                        '<td style="text-align: center;"><div id="divStatus' . $stdInfo['ID'] . '" style="background-color: ' . $stBg . ';">' . $st . '</div></td>' . PHP_EOL .
                        '<td><span id="spReason' . $stdInfo['ID'] . '">' . (isset($stdInfo['Reason']) ? htmlentities($stdInfo['Reason'], 0, 'ISO-8859-1') : '&nbsp;') . '</span></td>' . PHP_EOL . 
                        '<td><span id="spNotes' . $stdInfo['ID'] . '">' . nl2br(htmlentities($stdInfo['Notes'], 0, 'ISO-8859-1')) . '</span></td>' . PHP_EOL .
                        '<td>' . (!!$stdInfo['YearlyContract'] ? '<img src="' . IMAGE_DIR . 'check3.png"/>' : '') . '</td>' . PHP_EOL .
                        '<td>' . $flagImg . '</td></tr>' . PHP_EOL;


                
            }
            
?>
            </table>
            <br/>
<?php
}

?>