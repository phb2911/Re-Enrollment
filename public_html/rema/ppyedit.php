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
    closeDbAndGoTo($db, "searchcamp.php" . (isset($_SERVER['REQUEST_URI']) ? '?redir=' . urlencode($_SERVER['REQUEST_URI']) : ''));
}

// set user id
$uid = getPost('uid');

// initialize flags
$isValid = false;
$isPostBack = getPost('postback') == 1;

// validate user id and fetch user name
if (isNum($uid) && $userName = $db->query("SELECT `Name` FROM users WHERE ID = $uid")->fetch_row()[0]){

    // fetch class count for current teacher
    $clsCount = intval($db->query("SELECT COUNT(*) FROM classes WHERE User = $uid AND Campaign = " . $cInfo['ID'])->fetch_row()[0], 10);

    // fetch student count - exclude finished and cancelled students
    $stdCount = intval($db->query("SELECT COUNT(*) FROM students JOIN classes ON students.Class = classes.ID WHERE classes.User = $uid AND classes.Campaign = " . $cInfo['ID'] . " AND students.Situation < 2")->fetch_row()[0], 10);

    // calculate the average of students per class
    $avStd = ($clsCount > 0 ? $stdCount / $clsCount : 0);

    // fetch student rema and convert to numeric value (+)
    $rema = +$db->query("SELECT PercentEnrolled($uid, " . $cInfo['ID'] . ")")->fetch_row()[0];

    // we have a vilid user and valid campaign
    $isValid = true;

    // display message if campaign is closed
    if (!!$cInfo['Open']){
        $msg = 'Atenção: A campnha \'' . htmlentities($cInfo['Name'], 0, 'ISO-8859-1') . '\' está aberta. É aconselhável que o PPY só seja calculado após o fechamento da campanha para que não haja divergências no valor final.';
    }
    
}

// retrieve calculus index
if ($isValid && !$calcIndex = $db->query("SELECT CalcIndex FROM ppy_calc_index WHERE Campaign = " . $cInfo['ID'])->fetch_row()[0]){
    $msg = 'O índice de cálculo da campanha ' . $cInfo['Name'] . ' não foi atribuído. Clique <a href="ppycalcindex.php">aqui</a> para atribuí-lo.';
}

// delete record
if ($isValid && isset($calcIndex) && getPost('del') == $uid){
    if ($db->query("DELETE FROM ppy WHERE User = $uid AND Campaign = " . $cInfo['ID'])){
        $msg = 'Dados removidos com sucesso.';
        $isValid = false;
    }
    else {
        $msg = 'Error: ' . $db->error;
    }
}

// declare variables
$axis1Only = null;
$grad = null;
$gradNotes = null;
$intExp = null;
$intExpNotes = null;
$prof = null;
$profNotes = null;
$numClsNotes = null;
$missMeet = null;
$missMeetNotes = null;
$partMeet = null;
$partMeetNotes = null;
$lateRoll = null;
$lateRollNotes = null;
$lateRpt = null;
$lateRptNotes = null;
$msgFail = null;
$msgFailNotes = null;
$missClass = null;
$missClassNotes = null;
$yilts = 0;
$yiltsNotes = '';
$citizenCamp = 0;
$citizenCampNotes = '';
$cultEvents = 0;
$cultEventsNotes = '';
$inovPedPrac = 0;
$inovPedPracNotes = '';
$relSug = '';
$relSugNotes = '';
$refStd = 0;
$refStdNotes = '';
$pedContr = '';
$pedContrNotes = '';
$otherAct = '';
$otherActNotes = '';
$remaForm = 0;
$remaNotes = '';
$avStdForm = 0;
$avStdNotes = '';

// form submitted
if ($isValid && isset($calcIndex) && $isPostBack){
    
    // all comments will be trucated to 200 characters
    
    // asign parameters to variables
    $numCls = sTrim(getPost('numcls'));
    $numClsNotes = trucateStr(sTrim(getPost('numcls_notes')));
    $axis1Only = getPost('axis1only') == 1 ? 1 : 0;
    $grad = getPost('grad');
    $gradNotes = trucateStr(sTrim(getPost('grad_notes')));
    $intExp = getPost('intexp');
    $intExpNotes = trucateStr(sTrim(getPost('intext_notes')));
    $prof = getPost('prof');
    $profNotes = trucateStr(sTrim(getPost('prof_notes')));
    
    // check if participat in axis 1 only
    if (!$axis1Only){
        
        $yilts = getPost('yilts') == 1 ? 1 : 0;
        $yiltsNotes = trucateStr(sTrim(getPost('yilts_notes')));
        $citizenCamp = getPost('citzcamp') == 1 ? 1 : 0;
        $citizenCampNotes = trucateStr(sTrim(getPost('citzcamp_notes')));
        $cultEvents = getPost('cultevents') == 1 ? 1 : 0;
        $cultEventsNotes = trucateStr(sTrim(getPost('cultevents_notes')));
        $inovPedPrac = getPost('inovpedprac');
        $inovPedPracNotes = trucateStr(sTrim(getPost('inovpedprac_notes')));
        $relSug = sTrim(getPost('relsug'));
        $relSugNotes = trucateStr(sTrim(getPost('relsug_notes')));
        $refStd = getPost('refstd');
        $refStdNotes = trucateStr(sTrim(getPost('refstd_notes')));
        $pedContr = sTrim(getPost('pedcont'));
        $pedContrNotes = trucateStr(sTrim(getPost('pedcont_notes')));
        $otherAct = sTrim(getPost('otheract'));
        $otherActNotes = trucateStr(sTrim(getPost('otheract_notes')));
        $remaForm = getPost('rema');
        $remaNotes = trucateStr(sTrim(getPost('rema_notes')));
        $avStdForm = getPost('avstds');
        $avStdNotes = trucateStr(sTrim(getPost('avstds_notes')));
        
    }
    
    $missMeet = sTrim(getPost('missmeet'));
    $missMeetNotes = trucateStr(sTrim(getPost('missmeet_notes')));
    $partMeet = sTrim(getPost('partmeet'));
    $partMeetNotes = trucateStr(sTrim(getPost('partmeet_notes')));
    $lateRoll = sTrim(getPost('lateroll'));
    $lateRollNotes = trucateStr(sTrim(getPost('lateroll_notes')));
    $lateRpt = sTrim(getPost('laterpt'));
    $lateRptNotes = trucateStr(sTrim(getPost('laterpt_notes')));
    $msgFail = sTrim(getPost('msgfail'));
    $msgFailNotes = trucateStr(sTrim(getPost('msgfail_notes')));
    $missClass = sTrim(getPost('missclass'));
    $missClassNotes = trucateStr(sTrim(getPost('missclass_notes')));
    
    // validate input
    $tempMsg = '';
    
    if (!isNum($numCls)){
        $tempMsg .= '<li>O número de classes não é válido.</li>';
    }
 
    if (!isNum($grad) || $grad > 3){
        $tempMsg .= '<li>O curso de graduação não é válido.</li>';
    }
    
    if (!isNum($intExp) || $intExp > 2){
        $tempMsg .= '<li>A experiência internacional não é válida.</li>';
    }
    
    if (!isNum($prof) || $prof > 2){
        $tempMsg .= '<li>O teste de proficiência não é válido.</li>';
    }
    
    if (!$axis1Only && (!isNum($inovPedPrac) || $inovPedPrac > 4)){
        $tempMsg .= '<li>A prática pedagógica inovadora não é válida.</li>';
    }
    
    if (!$axis1Only && strlen($relSug) && (!is_numeric($relSug) || $relSug > 6 || $relSug < 0.5)){
        $tempMsg .= '<li>O valor da sugestão relevante implementada pela escola deve ser entre 0.5 e 6.</li>';
    }
    
    if (!$axis1Only && (!isNum($refStd) || $refStd > 3)){
        $tempMsg .= '<li>O número de alunos indicados não é válido.</li>';
    }
    
    if (!$axis1Only && strlen($pedContr) && (!is_numeric($pedContr) || $pedContr > 5 || $pedContr < 2)){
        $tempMsg .= '<li>O valor da contribuição formal em reuniões pedagógicas deve ser entre 2 e 5.</li>';
    }
    
    if (!$axis1Only && strlen($otherAct) && (!is_numeric($otherAct) || $otherAct > 4 || $otherAct < 0.5)){
        $tempMsg .= '<li>O valor das outras atividades que caracterizam envolvimento deve ser entre 0.5 e 4.</li>';
    }
    
    if (!$axis1Only && (!isNum($remaForm) || $remaForm > 4)){
        $tempMsg .= '<li>O valor do índice geral de manutenção não é válido.</li>';
    }
    
    if (!$axis1Only && (!isNum($avStdForm) || $avStdForm > 3)){
        $tempMsg .= '<li>O valor da média de aluno por turma não é válido.</li>';
    }
    
    if (strlen($missMeet) && !isNum($missMeet)){
        $tempMsg .= '<li>O número de faltas às reuniões pedagógicas não é válido.</li>';
    }
    
    if (strlen($partMeet) && !isNum($partMeet)){
        $tempMsg .= '<li>O número de atrasos/saídas cedo das reuniões pedagógicas não é válido.</li>';
    }
    
    if (strlen($lateRoll) && !isNum($lateRoll)){
        $tempMsg .= '<li>O número de caternetas do SGY preenchidas com atraso não é válido.</li>';
    }
    
    if (strlen($lateRpt) && !isNum($lateRpt)){
        $tempMsg .= '<li>O número de SIRs entregue com atraso não é válido.</li>';
    }
    
    if (strlen($msgFail) && !isNum($msgFail)){
        $tempMsg .= '<li>O número de E-mail (turmas infantis)/Hotlines/Direct Messages não enviados não é válido.</li>';
    }
    
    if (strlen($missClass) && !isNum($missClass)){
        $tempMsg .= '<li>O número de faltas à aula não é válido.</li>';
    }
    
    if (strlen($tempMsg)){
        $msg = 'Os seguintes erros foram encontrados:<ul>' . $tempMsg . '</ul>As informações não foram gravadas.';
    }
    else {
        
        // insert record
        $q = "CALL spPPY(" . $cInfo['ID'] . ", $uid, $axis1Only, $grad, '$gradNotes', $intExp, '$intExpNotes', $prof, '$profNotes', "
                . "$yilts, '$yiltsNotes', $inovPedPrac, '$inovPedPracNotes', " . ($relSug ? $relSug : "0") . ", '$relSugNotes', "
                . "$citizenCamp, '$citizenCampNotes', $cultEvents, '$cultEventsNotes', $numCls, '$numClsNotes', $refStd, '$refStdNotes', " 
                . ($pedContr ? $pedContr : "0") . ", '$pedContrNotes', " . ($otherAct ? $otherAct : "0") . ", '$otherActNotes', "
                . "$remaForm, '$remaNotes', $avStdForm, '$avStdNotes', " . ($missMeet ? $missMeet : "0") . ", '$missMeetNotes', " 
                . ($partMeet ? $partMeet : "0") . ", '$partMeetNotes', " . ($lateRoll ? $lateRoll : "0") . ", '$lateRollNotes', " 
                . ($lateRpt ? $lateRpt : "0") . ", '$lateRptNotes', " . ($msgFail ? $msgFail : "0") . ", '$msgFailNotes', " 
                . ($missClass ? $missClass : "0") . ", '$missClassNotes')";
        
        // execute query
        if ($db->query($q)){
            // redirect to overview page
            closeDbAndGoTo($db, "ppy.php?uid=$uid");
        }
        else {
            $msg = 'Error: ' . $db->error;
        }
        
        // clear db stored results
        clearStoredResults($db);
        
    }
        
}
// check if user already has record
elseif ($isValid && isset($calcIndex) && $row = $db->query("SELECT * FROM ppy WHERE User = $uid AND Campaign = " . $cInfo['ID'])->fetch_assoc()){
    
    // asign fetch data to variables
    $numCls = $row['E2_NumCls'];
    $numClsNotes = $row['E2_NumClsNotes'];
    $axis1Only = $row['Axis1Only'];
    $grad = $row['E1_Graduation'];
    $gradNotes = $row['E1_GradNotes'];
    $intExp = $row['E1_IntExp'];
    $intExpNotes = $row['E1_IntExpNotes'];
    $prof = $row['E1_ProficiencyTest'];
    $profNotes = $row['E1_ProfNotes'];
    
    // check if participat in axis 1 only
    if (!$axis1Only){
        
        $yilts = $row['E2_YILTS'];
        $yiltsNotes = $row['E2_YILTS_Notes'];
        $citizenCamp = $row['E2_CitizenshipCamp'];
        $citizenCampNotes = $row['E2_CitizNotes'];
        $cultEvents = $row['E2_CulturalEvents'];
        $cultEventsNotes = $row['E2_CultNotes'];
        $inovPedPrac = $row['E2_InovPedPrac'];
        $inovPedPracNotes = $row['E2_InovNotes'];
        $relSug = ($row['E2_RelSug'] > 0 ? +$row['E2_RelSug'] : '');
        $relSugNotes = $row['E2_RelSugNotes'];
        $refStd = $row['E2_ReferredStudent'];
        $refStdNotes = $row['E2_RefNotes'];
        $pedContr = ($row['E2_ContribToPedMeeting'] > 0 ? +$row['E2_ContribToPedMeeting'] : '');
        $pedContrNotes = $row['E2_ContribNotes'];
        $otherAct = ($row['E2_OtherEnv'] > 0 ? +$row['E2_OtherEnv'] : '');
        $otherActNotes = $row['E2_OtherEnvNotes'];
        $remaForm = $row['E3_Rema'];
        $remaNotes = $row['E3_RemaNotes'];
        $avStdForm = $row['E3_AvStds'];
        $avStdNotes = $row['E3_AvgStdsNotes'];
        
    }
    
    $missMeet = ($row['MissedMeeting'] ? $row['MissedMeeting'] : '');
    $missMeetNotes = $row['MissedMeetingNotes'];
    $partMeet = ($row['PartialMeeting'] ? $row['PartialMeeting'] : '');
    $partMeetNotes = $row['PartMeetingNotes'];
    $lateRoll = ($row['LateRollCall'] ? $row['LateRollCall'] : '');
    $lateRollNotes = $row['LateRollCallNotes'];
    $lateRpt = ($row['LateReportCards'] ? $row['LateReportCards'] : '');
    $lateRptNotes = $row['LateReportCardNotes'];
    $msgFail = ($row['MsgNotSent'] ? $row['MsgNotSent'] : '');
    $msgFailNotes = $row['MsgNotSentNotes'];
    $missClass = ($row['MissClass'] ? $row['MissClass'] : '');
    $missClassNotes = $row['MissClassNotes'];
    
    // set flag
    $isPostBack = true;
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - PPY Editar</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
    <script type="text/javascript" src="js/ppyedit.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
        div.infoBox {
            font-size: 13px;
            border: #61269e solid 1px;
            padding: 3px;
            position: absolute;
            background-color: white;
            visibility: hidden;
        }
                
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
            element('divTop').style.height = element('divHeadder').offsetHeight + 'px';
            
            if (element('selUser')) element('selUser').styleOption();
            
            if (element('tblAxis1')) calculateAxis1();
            if (element('tblAxis2')) calculateAxis2();
            if (element('tblAxis3')) calculateAxis3();
            if (element('tblNegative')) calculateNegative();
            if (element('chkAxis1only')) checkBoxClicked();
            if (element('tblResult')) calculateGeneral();
            
        };
        
        function calculateGeneral(){
    
            // calculate general score
            var gen = parseFloat(element('txtAxis1Points').value) + parseFloat(element('txtAxis2Points').value) + 
                    parseFloat(element('txtAxis3Points').value) - parseFloat(element('txtNegativePoints').value);

            // get number of classes
            var numCls = element('txtClasses').value

            // display score
            element('txtGeneralPoints').value = gen;

            // verify if number of classes is valid
            if (numCls.length){

                // calculate result
                // if general score is zero or negative, earnings = 0
                if (gen <= 0){
                    element('txtEarnings').value = '$0,00';
                }
                else {
                    element('txtEarnings').value = '$' + (gen * <?php echo (isset($calcIndex) ? $calcIndex : 0); ?> * parseInt(numCls)).toFixed(2).replace('.', ',');
                }

            }
            else element('txtEarnings').value = '';

        }
        
        function displayInfo(img, infoBoxId){
            
            var infoBox = element(infoBoxId);
            var pos = getAbsPosition(img);
            
            infoBox.style.top = (pos[0] + 20) + 'px';
            infoBox.style.left = (pos[1] - 85) + 'px';
            
            infoBox.style.visibility = 'visible';
            
        }
        
    </script>
    
</head>
<body>
    
    <div class="top" id="divTop"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
        
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
            
            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action="ppyedit.php">
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
        <div id="msgBox" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>; width: 1000px; left: 0; right: 0; margin: auto;">
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <div style="color: red; font-style: italic; padding-right: 10px;"><?php if (isset($msg)) echo $msg; ?></div>
            </div>
            <br/>
        </div>
<?php if ($isValid && isset($calcIndex)) { ?>
        <div class="panel" style="width: 1000px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Premio Performance Yázigi (PPY) - Editar</span>
            <hr/>
            
            <form id="form1" action="ppyedit.php" method="post">
            
            <!-- General info -->
            
            <table id="tblGenInfo" style="width: 100%; border: #61269e solid 1px;">
                <tr>
                    <td style="background-color: #61269e; color: white; font-weight: bold;" colspan="2">Informações Gerais</td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Professor:</td>
                    <td style="width: 100%; font-weight: bold;">
                        <?php echo htmlentities($userName, 0, 'ISO-8859-1'); ?> &nbsp; 
                        <img src="<?php echo IMAGE_DIR; ?>pencil1.png" title="Modificar Professor" style="cursor: pointer;" onclick="window.location = 'ppyedit.php';"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Campanha:</td>
                    <td style="font-weight: bold;">
                        <?php echo htmlentities($cInfo['Name'], 0, 'ISO-8859-1'); ?>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Número de turmas:</td>
                    <td>
                        <input type="text" id="txtNumCls" name="numcls" value="<?php echo ($isPostBack ? htmlentities($numCls, 3, 'ISO-8859-1') : $clsCount); ?>" style="width: 30px;" onblur="calculateAxis2(); calculateGeneral();" onkeypress="this.style.backgroundColor = '';" maxlength="2"/>
                        &nbsp; <span style="color: red; font-style: italic; font-size: 13px;"><?php echo $clsCount; ?> turmas de acordo com a rema.</span>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Participa apenas do Eixo 1<span style="color: red;">*</span>:</td>
                    <td>
                        <input type="checkbox" id="chkAxis1only" name="axis1only" value="1" onclick="checkBoxClicked(); calculateGeneral();"<?php if ($axis1Only) echo ' checked="checked"'; ?>/>
                        &nbsp; <span style="color: red; font-size: 13px; font-style: italic;">Manutenção atual de acordo com a rema: <?php echo $rema; ?>%</span>
                    </td>
                </tr>
                <caption style="color:red; font-size: 13px; font-style: italic; text-align: left; caption-side: bottom;">
                    * Professores cursando ou graduado em Letras ou com especialização na área de educação poderão participar 
                    do Eixo 1 se tiverem manutenção menor que 78% e maior ou igual a 74%.
                </caption>
            </table>
            
            <br/>
            
            <!-- Axis 1 -->
            
            <table id="tblAxis1" style="width: 100%; border: #61269e solid 1px;">
                <tr>
                    <td style="background-color: #61269e; color: white; font-weight: bold;" colspan="4">Eixo 1 - Qualificação (máximo de 30 pontos)</td>
                </tr>
                <tr style="background-color: #b4b4b4;">
                    <td colspan="2">Requesitos</td>
                    <td>Observações</td>
                    <td>Pontuação</td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Curso de Graduação:</td>
                    <td style="width: 100%;">
                        <select id="selGrad" name="grad" style="width: 300px;" onchange="calculateAxis1(); calculateGeneral();" onkeyup="calculateAxis1(); calculateGeneral();">
                            <option value="0"></option>
                            <option value="1"<?php if ($grad == 1) echo ' selected="selected"'; ?>>Graduação em Letras (15 pontos)</option>
                            <option value="2"<?php if ($grad == 2) echo ' selected="selected"'; ?>>Cursando Letras (5 pontos)</option>
                            <option value="3"<?php if ($grad == 3) echo ' selected="selected"'; ?>>Especialização na área concluído (15 pontos)</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="grad_notes" value="<?php echo htmlentities($gradNotes, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="200"/>
                    </td>
                    <td style="text-align: center;">
                        <input type="text" id="txtGradPoints" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right; vertical-align: top;">Experiência no exterior/intercâmbio:</td>
                    <td>
                        <select id="selIntExp" name="intexp" style="width: 300px;"  onchange="calculateAxis1(); calculateGeneral();" onkeyup="calculateAxis1(); calculateGeneral();">
                            <option value="0"></option>
                            <option value="1"<?php if ($intExp == 1) echo ' selected="selected"'; ?>>Duração entre 6 e 12 meses (5 pontos)</option>
                            <option value="2"<?php if ($intExp == 2) echo ' selected="selected"'; ?>>Duração de mais de 12 meses (10 pontos)</option>
                        </select>
                        <img src="<?php echo IMAGE_DIR; ?>info.png" style="cursor: help;" onmouseover="displayInfo(this, 'divIntExpInfo');" onmouseout="element('divIntExpInfo').style.visibility = 'hidden';"/>
                        <div class="infoBox" id="divIntExpInfo">Exclui-se viagem a turismo.</div>
                    </td>
                    <td>
                        <input type="text" name="intext_notes" value="<?php echo htmlentities($intExpNotes, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="200"/>
                    </td>
                    <td style="text-align: center;">
                        <input type="text" id="txtIntExpPoints" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Exame de proficiência:</td>
                    <td>
                        <select id="selProf" name="prof" style="width: 300px;" onchange="calculateAxis1(); calculateGeneral();" onkeyup="calculateAxis1(); calculateGeneral();">
                            <option value="0"></option>
                            <option value="1"<?php if ($prof == 1) echo ' selected="selected"'; ?>>Nível menor que máximo (5 pontos)</option>
                            <option value="2"<?php if ($prof == 2) echo ' selected="selected"'; ?>>Nivel máximo (10 pontos)</option>
                        </select>
                        <img src="<?php echo IMAGE_DIR; ?>info.png" style="cursor: help;" onmouseover="displayInfo(this, 'divProfExm');" onmouseout="element('divProfExm').style.visibility = 'hidden';"/>
                        <div class="infoBox" id="divProfExm">Resultado obtido a menos de 3 anos.</div>
                    </td>
                    <td>
                        <input type="text" name="prof_notes" value="<?php echo htmlentities($profNotes, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="200"/>
                    </td>
                    <td style="text-align: center;">
                        <input type="text" id="txtProfPoints" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right;" colspan="3">Sub-total:</td>
                    <td style="text-align: center;">
                        <input type="text" id="txtEx1Total" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
            </table>
            
            <br/>
            
            <!-- Axis 2 -->
            
            <table id="tblAxis2" style="width: 100%; border: #61269e solid 1px;">
                <tr>
                    <td style="background-color: #61269e; color: white; font-weight: bold;" colspan="4">Eixo 2 - Envolvimento/Dedicação (máximo de 30 pontos)</td>
                </tr>
                <tr style="background-color: #b4b4b4;">
                    <td colspan="2">Requesitos</td>
                    <td>Observações</td>
                    <td>Pontuação</td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Participação no YILTS ou equivalente:</td>
                    <td style="width: 100%;">
                        <input type="checkbox" id="chkYilts" name="yilts" value="1" onclick="calculateAxis2(); calculateGeneral();"<?php if ($yilts) echo ' checked="checked"'; ?>/>
                    </td>
                    <td>
                        <input type="text" name="yilts_notes" value="<?php echo htmlentities($yiltsNotes, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="200"/>
                    </td>
                    <td style="text-align: center;">
                        <input type="text" id="txtYiltsPoints" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Campanha de cidadania:</td>
                    <td>
                        <input type="checkbox" id="chkCitzCamp" name="citzcamp" value="1" onclick="calculateAxis2(); calculateGeneral();"<?php if ($citizenCamp) echo ' checked="checked"'; ?>/>
                        <img src="<?php echo IMAGE_DIR; ?>info.png" style="cursor: help;" onmouseover="displayInfo(this, 'divCitzCampInfo');" onmouseout="element('divCitzCampInfo').style.visibility = 'hidden';"/>
                        <div class="infoBox" id="divCitzCampInfo">Participação de 100% das turmas.</div>
                    </td>
                    <td>
                        <input type="text" name="citzcamp_notes" value="<?php echo htmlentities($citizenCampNotes, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="200"/>
                    </td>
                    <td style="text-align: center;">
                        <input type="text" id="txtCitzCampPoints" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Eventos culturais/pedagógicos:</td>
                    <td>
                        <input type="checkbox" id="chkCultEvents" name="cultevents" value="1" onclick="calculateAxis2(); calculateGeneral();"<?php if ($cultEvents) echo ' checked="checked"'; ?>/>
                        <img src="<?php echo IMAGE_DIR; ?>info.png" style="cursor: help;" onmouseover="displayInfo(this, 'divCitzCampInfo');" onmouseout="element('divCitzCampInfo').style.visibility = 'hidden';"/>
                    </td>
                    <td>
                        <input type="text" name="cultevents_notes" value="<?php echo htmlentities($cultEventsNotes, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="200"/>
                    </td>
                    <td style="text-align: center;">
                        <input type="text" id="txtCultEventsPoints" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right; vertical-align: top;">Prática pedagógica inovadora (com plano de aula):</td>
                    <td>
                        <select id="selPedPrac" name="inovpedprac" onchange="calculateAxis2(); calculateGeneral();" onkeyup="calculateAxis2(); calculateGeneral();">
                            <option value="0"></option>
                            <option value="1"<?php if ($inovPedPrac == 1) echo ' selected="selected"'; ?>>1</option>
                            <option value="2"<?php if ($inovPedPrac == 2) echo ' selected="selected"'; ?>>2</option>
                            <option value="3"<?php if ($inovPedPrac == 3) echo ' selected="selected"'; ?>>3</option>
                            <option value="4"<?php if ($inovPedPrac == 4) echo ' selected="selected"'; ?>>4</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="inovpedprac_notes" value="<?php echo htmlentities($inovPedPracNotes, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="200"/>
                    </td>
                    <td style="text-align: center;">
                        <input type="text" id="txtPedPracPoints" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Sugestão relevante implementada pela escola:</td>
                    <td style="width: 100%;">
                        <input type="text" id="txtRelSug" name="relsug" value="<?php echo htmlentities($relSug, 3, 'ISO-8859-1'); ?>" style="width: 30px;" onblur="calculateAxis2(); calculateGeneral();" onkeypress="this.style.backgroundColor = '';" maxlength="3"/>
                        <img src="<?php echo IMAGE_DIR; ?>info.png" style="cursor: help;" onmouseover="displayInfo(this, 'divRelSugInfo');" onmouseout="element('divRelSugInfo').style.visibility = 'hidden';"/>
                        <div class="infoBox" id="divRelSugInfo">Valor mínimo de 0.5 e máximo de 6.</div>
                    </td>
                    <td>
                        <input type="text" name="relsug_notes" value="<?php echo htmlentities($relSugNotes, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="200"/>
                    </td>
                    <td style="text-align: center;">
                        <input type="text" id="txtRelSugPoints" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right; vertical-align: top;">Número de turmas<span style="color:red;">*</span>:</td>
                    <td>
                        <input type="text" id="txtNumCls2" value="" style="width: 30px;" readonly="readonly"/>
                    </td>
                    <td>
                        <input type="text" name="numcls_notes" value="<?php echo htmlentities($numClsNotes, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="200"/>
                    </td>
                    <td style="text-align: center;">
                        <input type="text" id="txtNumClsPoints" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right; vertical-align: top;">Alunos indicados:</td>
                    <td>
                        <select id="selRefStd" name="refstd" onchange="calculateAxis2(); calculateGeneral();" onkeyup="calculateAxis2(); calculateGeneral();">
                            <option value="0"></option>
                            <option value="1"<?php if ($refStd == 1) echo ' selected="selected"'; ?>>1</option>
                            <option value="2"<?php if ($refStd == 2) echo ' selected="selected"'; ?>>2</option>
                            <option value="3"<?php if ($refStd == 3) echo ' selected="selected"'; ?>>3</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="refstd_notes" value="<?php echo htmlentities($refStdNotes, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="200"/>
                    </td>
                    <td style="text-align: center;">
                        <input type="text" id="txtRefStdPoints" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Contribuição formal em reuniões pedagógicas:</td>
                    <td style="width: 100%;">
                        <input type="text" id="txtPedCont" name="pedcont" value="<?php echo htmlentities($pedContr, 3, 'ISO-8859-1'); ?>" style="width: 30px;" onblur="calculateAxis2(); calculateGeneral();" onkeypress="this.style.backgroundColor = '';" maxlength="3"/>
                        <img src="<?php echo IMAGE_DIR; ?>info.png" style="cursor: help;" onmouseover="displayInfo(this, 'divPedContInfo');" onmouseout="element('divPedContInfo').style.visibility = 'hidden';"/>
                        <div class="infoBox" id="divPedContInfo">Valor mínimo de 2 e máximo de 5.</div>
                    </td>
                    <td>
                        <input type="text" name="pedcont_notes" value="<?php echo htmlentities($pedContrNotes, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="200"/>
                    </td>
                    <td style="text-align: center;">
                        <input type="text" id="txtPedContPoints" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Outras atividades que caracterizam envolvimento:</td>
                    <td>
                        <input type="text" id="txtOtherAct" name="otheract" value="<?php echo htmlentities($otherAct, 3, 'ISO-8859-1'); ?>" style="width: 30px;" onblur="calculateAxis2(); calculateGeneral();" onkeypress="this.style.backgroundColor = '';" maxlength="3"/>
                        <img src="<?php echo IMAGE_DIR; ?>info.png" style="cursor: help;" onmouseover="displayInfo(this, 'divOtherActInfo');" onmouseout="element('divOtherActInfo').style.visibility = 'hidden';"/>
                        <div class="infoBox" id="divOtherActInfo">Valor mínimo de 0.5 e máximo de 4.</div>
                    </td>
                    <td>
                        <input type="text" name="otheract_notes" value="<?php echo htmlentities($otherActNotes, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="200"/>
                    </td>
                    <td style="text-align: center;">
                        <input type="text" id="txtOtherActPoints" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right;" colspan="3">Sub-total:</td>
                    <td style="text-align: center;">
                        <input type="text" id="txtEx2Total" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
                <caption style="color:red; font-size: 13px; font-style: italic; text-align: left; caption-side: bottom;">
                    * O número de turmas deverá ser preenchido no campo de informações gerais.
                </caption>
            </table>
            
            <br/>
            
            <!-- Axis 3 -->
            
            <table id="tblAxis3" style="width: 100%; border: #61269e solid 1px;">
                <tr>
                    <td style="background-color: #61269e; color: white; font-weight: bold;" colspan="4">Eixo 3 - Resultados (máximo de 40 pontos)</td>
                </tr>
                <tr style="background-color: #b4b4b4;">
                    <td colspan="2">Requesitos</td>
                    <td>Observações</td>
                    <td>Pontuação</td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right; vertical-align: top;">Índice geral de manutenção:</td>
                    <td style="width: 100%;">
                        <select id="selRema" name="rema" style="width: 115px;" onchange="calculateAxis3(); calculateGeneral();" onkeyup="calculateAxis3(); calculateGeneral();">
                            <option value="0"<?php if (($isPostBack && $remaForm == 0) || (!$isPostBack && $rema <= 80)) echo ' selected="selected"'; ?>></option>
                            <option value="1"<?php if (($isPostBack && $remaForm == 1) || (!$isPostBack && $rema > 80 && $rema <= 83)) echo ' selected="selected"'; ?>>Maior que 80%</option>
                            <option value="2"<?php if (($isPostBack && $remaForm == 2) || (!$isPostBack && $rema > 83 && $rema <= 86)) echo ' selected="selected"'; ?>>Maior que 83%</option>
                            <option value="3"<?php if (($isPostBack && $remaForm == 3) || (!$isPostBack && $rema > 86 && $rema <= 90)) echo ' selected="selected"'; ?>>Maior que 86%</option>
                            <option value="4"<?php if (($isPostBack && $remaForm == 4) || (!$isPostBack && $rema > 90)) echo ' selected="selected"'; ?>>Maior que 90%</option>
                        </select>
                        &nbsp; <span style="color: red; font-style: italic; font-size: 13px;"><?php echo $rema; ?>% de acordo com a rema.</span>
                    </td>
                    <td>
                        <input type="text" name="rema_notes" value="<?php echo htmlentities($remaNotes, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="200"/>
                    </td>
                    <td style="text-align: center;">
                        <input type="text" id="txtRemaPoints" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right; vertical-align: top;">Média de aluno por turma:</td>
                    <td>
                        <select id="selAverageStds" name="avstds" style="width: 115px;" onchange="calculateAxis3(); calculateGeneral();" onkeyup="calculateAxis3(); calculateGeneral();">
                            <option value="0"<?php if (($isPostBack && $avStdForm == 0) || (!$isPostBack && ($rema < 78 || $avStd <= 12))) echo ' selected="selected"'; ?>></option>
                            <option value="1"<?php if (($isPostBack && $avStdForm == 1) || (!$isPostBack && $rema >= 78 && $avStd > 12 && $avStd <= 13)) echo ' selected="selected"'; ?>>Maior que 12</option>
                            <option value="2"<?php if (($isPostBack && $avStdForm == 2) || (!$isPostBack && $rema >= 78 && $avStd > 13 && $avStd <= 14)) echo ' selected="selected"'; ?>>Maior que 13</option>
                            <option value="3"<?php if (($isPostBack && $avStdForm == 3) || (!$isPostBack && $rema >= 78 && $avStd > 14)) echo ' selected="selected"'; ?>>Maior que 14</option>
                        </select>
                        &nbsp; <span style="color: red; font-style: italic; font-size: 13px;"><?php echo +number_format($avStd, 2, '.', ''); ?> de acordo com a rema.</span>
                    </td>
                    <td>
                        <input type="text" name="avstds_notes" value="<?php echo htmlentities($avStdNotes, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="200"/>
                    </td>
                    <td style="text-align: center;">
                        <input type="text" id="txtAverageStdsPoints" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right;" colspan="3">Sub-total:</td>
                    <td style="text-align: center;">
                        <input type="text" id="txtEx3Total" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
            </table>
            
            <br/>
            
            <!-- Negative score -->
            
            <table id="tblNegative" style="width: 100%; border: #61269e solid 1px;">
                <tr>
                    <td style="background-color: #61269e; color: white; font-weight: bold;" colspan="4">Pontuação Negativa</td>
                </tr>
                <tr style="background-color: #b4b4b4;">
                    <td colspan="2">Requesitos</td>
                    <td>Observações</td>
                    <td>Pontuação</td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Número de faltas às reuniões pedagógicas:</td>
                    <td style="width: 100%;">
                        <input type="text" id="txtMissedMeeting" name="missmeet" value="<?php echo htmlentities($missMeet, 3, 'ISO-8859-1'); ?>" style="width: 30px;" onblur="calculateNegative(); calculateGeneral();" onkeypress="this.style.backgroundColor = '';" maxlength="2"/>
                    </td>
                    <td>
                        <input type="text" name="missmeet_notes" value="<?php echo htmlentities($missMeetNotes, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="200"/>
                    </td>
                    <td style="text-align: center;">
                        <input type="text" id="txtMissedMeetingPoints" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Número de atrasos/saídas cedo das reuniões pedagógicas:</td>
                    <td>
                        <input type="text" id="txtPartMeeting" name="partmeet" value="<?php echo htmlentities($partMeet, 3, 'ISO-8859-1'); ?>" style="width: 30px;" onblur="calculateNegative(); calculateGeneral();" onkeypress="this.style.backgroundColor = '';" maxlength="2"/>
                    </td>
                    <td>
                        <input type="text" name="partmeet_notes" value="<?php echo htmlentities($partMeetNotes, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="200"/>
                    </td>
                    <td style="text-align: center;">
                        <input type="text" id="txtPartMeetingPoints" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Número de caternetas do SGY preenchidas com atraso:</td>
                    <td>
                        <input type="text" id="txtLateRollCall" name="lateroll" value="<?php echo htmlentities($lateRoll, 3, 'ISO-8859-1'); ?>" style="width: 30px;" onblur="calculateNegative(); calculateGeneral();" onkeypress="this.style.backgroundColor = '';" maxlength="2"/>
                    </td>
                    <td>
                        <input type="text" name="lateroll_notes" value="<?php echo htmlentities($lateRollNotes, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="200"/>
                    </td>
                    <td style="text-align: center;">
                        <input type="text" id="txtLateRollCallPoints" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Número de SIRs entregue com atraso:</td>
                    <td>
                        <input type="text" id="txtLateRptCard" name="laterpt" value="<?php echo htmlentities($lateRpt, 3, 'ISO-8859-1'); ?>" style="width: 30px;" onblur="calculateNegative(); calculateGeneral();" onkeypress="this.style.backgroundColor = '';" maxlength="2"/>
                    </td>
                    <td>
                        <input type="text" name="laterpt_notes" value="<?php echo htmlentities($lateRptNotes, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="200"/>
                    </td>
                    <td style="text-align: center;">
                        <input type="text" id="txtLateRptCardPoints" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Número de E-mail (turmas infantis)/Hotlines/Direct Messages não enviados:</td>
                    <td>
                        <input type="text" id="txtMsgFail" name="msgfail" value="<?php echo htmlentities($msgFail, 3, 'ISO-8859-1'); ?>" style="width: 30px;" onblur="calculateNegative(); calculateGeneral();" onkeypress="this.style.backgroundColor = '';" maxlength="2"/>
                    </td>
                    <td>
                        <input type="text" name="msgfail_notes" value="<?php echo htmlentities($msgFailNotes, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="200"/>
                    </td>
                    <td style="text-align: center;">
                        <input type="text" id="txtMsgFailPoints" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Número de faltas à aula (exceto quando justificado):</td>
                    <td>
                        <input type="text" id="txtMissClass" name="missclass" value="<?php echo htmlentities($missClass, 3, 'ISO-8859-1'); ?>" style="width: 30px;" onblur="calculateNegative(); calculateGeneral();" onkeypress="this.style.backgroundColor = '';" maxlength="2"/>
                    </td>
                    <td>
                        <input type="text" name="missclass_notes" value="<?php echo htmlentities($missClassNotes, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="200"/>
                    </td>
                    <td style="text-align: center;">
                        <input type="text" id="txtMissClassPoints" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right;" colspan="3">Sub-total:</td>
                    <td style="text-align: center;">
                        <input type="text" id="txtNegativeTotal" style="width: 50px; text-align: right;" value="0" readonly="readonly"/>
                    </td>
                </tr>
            </table>
            
            <input type="hidden" name="postback" value="1"/>
            <input type="hidden" name="uid" value="<?php echo $uid; ?>"/>
            <input type="hidden" name="cid" value="<?php echo $cInfo['ID']; ?>"/>
            </form>
            
            <br/>
            
            <!-- Final Results -->
            
            <table id="tblResult" style="border: #61269e solid 1px;">
                <tr>
                    <td style="background-color: #61269e; color: white; font-weight: bold;" colspan="7">Resultado Geral <?php echo $cInfo['Name']; ?></td>
                </tr>
                <tr style="background-color: #b4b4b4; text-align: center;">
                    <td>Eixo 1</td>
                    <td>Eixo 2</td>
                    <td>Eixo 3</td>
                    <td>Negativo</td>
                    <td>Geral</td>
                    <td>Turmas<span style="color: red;">*</span></td>
                    <td>Total a receber</td>
                </tr>
                <tr style="text-align: center;">
                    <td><input type="text" id="txtAxis1Points" style="width: 70px; text-align: right;" readonly="readonly"/></td>
                    <td><input type="text" id="txtAxis2Points" style="width: 70px; text-align: right;" readonly="readonly"/></td>
                    <td><input type="text" id="txtAxis3Points" style="width: 70px; text-align: right;" readonly="readonly"/></td>
                    <td><input type="text" id="txtNegativePoints" style="width: 70px; text-align: right;" readonly="readonly"/></td>
                    <td><input type="text" id="txtGeneralPoints" style="width: 70px; text-align: right;" readonly="readonly"/></td>
                    <td><input type="text" id="txtClasses" style="width: 70px; text-align: right;" readonly="readonly"/></td>
                    <td><input type="text" id="txtEarnings" style="width: 70px; text-align: right;" readonly="readonly"/></td>
                </tr>
                <caption style="color:red; font-size: 13px; font-style: italic; text-align: left; caption-side: bottom;">
                    * O calculo do total a receber é baseado no número de turmas preenchido no campo de informações gerais.
                    Verifique se o valor foi atribuído corretamente.
                </caption>
            </table>
            <div style="padding: 15px;">
                <button style="width: 95px;" onclick="if (validateInput()) element('form1').submit();"><img src="<?php echo IMAGE_DIR; ?>disk2.png"/> Gravar</button>
<?php
    
        if (!!$db->query("SELECT ID FROM ppy WHERE User = $uid AND Campaign = " . $cInfo['ID'])->fetch_row()[0]){
            echo '<button style="width: 95px;" onclick="removeRecord(' . $uid . ',' . $cInfo['ID'] . ');"><img src="' . IMAGE_DIR . 'cancel2.png"/> Remover</button>';
        }

?>
            </div>
            
        </div>
        
<?php
}
else {
    selectUser($cInfo);
}

?>
        
        <p>&nbsp;</p>
    </div>
    
</body>
</html>
<?php

$db->close();

//----------------------------------------------------

function trucateStr($str){
    return substr($str,0,200);
}

//----------------------------------------------------

function selectUser($cInfo){
    
    global $db;
    
    // check if the index of calculation is set
    $hasCalcIndex = !!$db->query("SELECT 1 FROM ppy_calc_index WHERE Campaign = " . $cInfo['ID'])->fetch_row()[0];
    
?>
        <div class="panel" style="width: 1000px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Premio Performance Yázigi (PPY) - Editar</span>
            <hr/>
            
            <table>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $cInfo['Name']; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Selecione o Professor:</td>
                    <td>
                        <select id="selUser" name="uid" style="width: 300px;"<?php if (!$hasCalcIndex) echo ' disabled="disabled"' ?> onchange="this.styleOption(); userSelected(this, <?php echo $cInfo['ID']; ?>);" onkeyup="this.styleOption(); userSelected(this, <?php echo $cInfo['ID']; ?>);">
                            <option value="0" style="font-style: italic;">- Selecione -</option>
<?php

    // check if index of calculus is set
    if ($hasCalcIndex) {
        
        $result = $db->query("SELECT ID, Name FROM users WHERE Status < 2 AND Blocked = 0 ORDER BY Name");

        while ($row = $result->fetch_assoc()){
            echo '<option value="' . $row['ID'] . '" style="font-style: normal;">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>';
        }

        $result->close();
        
    }
    
?>
                        </select>
                    </td>
                </tr>
<?php if (!$hasCalcIndex) { ?>
                <tr>
                    <td style="color: red; font-style: italic;" colspan="2">O índice de cálculo da campanha <?php echo $cInfo['Name']; ?> não foi atribuído. Clique <a href="ppycalcindex.php">aqui</a> para atribuí-lo.</td>
                </tr>
<?php } ?>
            </table>
            
        </div>
<?php
    
}

?>