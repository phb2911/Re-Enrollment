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

if ($isAdmin){
    $uid = getGet('uid');
}
else {
    // non-admin can only see their own page
    $uid = $loginObj->userId;
}

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

// initialize variables
$isValid = false;
$dataFlag = false;
$gradTitle = null;
$gradPoints = null;
$intExpTitle = null;
$intExpPoints = null;
$yiltsPoints = null;
$citizenCampPoints = null;
$cultEventsPoints = null;
$inovPedPracPoints = null;
$yilts = null;
$citizenCamp = null;
$cultEvents = null;
$inovPedPrac = null;
$relSug = null;
$numClsPoints = null;
$refStd = null;
$pedContr = null;
$otherAct = null;
$remaTitle = null;
$remaPoints = null;
$avgStdTitle = null;
$avgStdPoints = null;

// validate parameters and fetch data
if (isNum($uid) && $userName = $db->query("SELECT Name FROM users WHERE ID = $uid")->fetch_row()[0]){
    
    $isValid = true;
        
    // retrieve calculus index
    if (!$calcIndex = $db->query("SELECT CalcIndex FROM ppy_calc_index WHERE Campaign = " . $cInfo['ID'])->fetch_row()[0]){
        // calculus index not found, display message only for admin
        // if calculus index not found and user not admin,
        // display data not entered message
        if ($isAdmin) $msg = 'O índice de cálculo da campanha ' . $cInfo['Name'] . ' não foi atribuído. Clique <a href="ppycalcindex.php">aqui</a> para atribuí-lo.';
    }
    elseif ($row = $db->query("SELECT * FROM ppy WHERE User = $uid AND Campaign = " . $cInfo['ID'])->fetch_assoc()){

        $dataFlag = true;
        $profTitle = null;
        $profPoints = null;

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
            $citizenCamp = $row['E2_CitizenshipCamp'];
            $cultEvents = $row['E2_CulturalEvents'];
            $inovPedPrac = $row['E2_InovPedPrac'];
            $relSug = $row['E2_RelSug'];
            $refStd = $row['E2_ReferredStudent'];
            $pedContr = $row['E2_ContribToPedMeeting'];
            $otherAct = $row['E2_OtherEnv'];
            $remaForm = $row['E3_Rema'];
            $avStdForm = $row['E3_AvStds'];

        }

        // comments from axis 2 and 3 are shown by all means
        $yiltsNotes = $row['E2_YILTS_Notes'];
        $citizenCampNotes = $row['E2_CitizNotes'];
        $cultEventsNotes = $row['E2_CultNotes'];
        $inovPedPracNotes = $row['E2_InovNotes'];
        $relSugNotes = $row['E2_RelSugNotes'];
        $refStdNotes = $row['E2_RefNotes'];
        $pedContrNotes = $row['E2_ContribNotes'];
        $otherActNotes = $row['E2_OtherEnvNotes'];
        $remaNotes = $row['E3_RemaNotes'];
        $avStdNotes = $row['E3_AvgStdsNotes'];

        $missMeet = $row['MissedMeeting'];
        $missMeetNotes = $row['MissedMeetingNotes'];
        $partMeet = $row['PartialMeeting'];
        $partMeetNotes = $row['PartMeetingNotes'];
        $lateRoll = $row['LateRollCall'];
        $lateRollNotes = $row['LateRollCallNotes'];
        $lateRpt = $row['LateReportCards'];
        $lateRptNotes = $row['LateReportCardNotes'];
        $msgFail = $row['MsgNotSent'];
        $msgFailNotes = $row['MsgNotSentNotes'];
        $missClass = $row['MissClass'];
        $missClassNotes = $row['MissClassNotes'];

        // values to display
        $ax1 = 0;
        $ax2 = 0;
        $ax3 = 0;

        // axis 1
        if ($grad == 1){
            $gradTitle = 'Graduação em Letras';
            $gradPoints = 15;
            $ax1 += 15;
        }
        elseif ($grad == 2){
            $gradTitle = 'Cursando Letras';
            $gradPoints = 5;
            $ax1 += 5;
        }
        elseif ($grad == 3){
            $gradTitle = 'Especialização na área concluído';
            $gradPoints = 15;
            $ax1 += 15;
        }

        if ($intExp == 1){
            $intExpTitle = 'Duração entre 6 e 12 meses';
            $intExpPoints = 5;
            $ax1 += 5;
        }
        elseif ($intExp == 2){
            $intExpTitle = 'Duração de mais de 12 meses';
            $intExpPoints = 10;
            $ax1 += 10;
        }

        if ($prof == 1){
            $profTitle = 'Nível menor que o máximo';
            $profPoints = 5;
            $ax1 += 5;
        }
        elseif ($prof == 2){
            $profTitle = 'Nível máximo';
            $profPoints = 10;
            $ax1 += 10;
        }

        if ($ax1 > 30) $ax1 = 30; // axis 1 maximum 30 points

        if (!$axis1Only){

            //axis 2
            if ($yilts){
                $yiltsPoints = 10;
                $ax2 += 10;
            }

            if ($citizenCamp){
                $citizenCampPoints += 5;
                $ax2 += 5;
            }

            if ($cultEvents){
                $cultEventsPoints = 5;
                $ax2 += 5;
            }

            if ($inovPedPrac){
                $inovPedPracPoints = $inovPedPrac * 2;
                $ax2 += $inovPedPracPoints;
            }

            $ax2 += $relSug;

            if ($numCls == 5){
                $numClsPoints = 6;
                $ax2 += 6;
            }
            elseif ($numCls == 6 || $numCls == 7){
                $numClsPoints = 10;
                $ax2 += 10;
            }
            elseif ($numCls >= 8){
                $numClsPoints = 15;
                $ax2 += 15;
            }

            $ax2 += $refStd * 3;
            $ax2 += $pedContr;
            $ax2 += $otherAct;

            if ($ax2 > 30) $ax2 = 30; // axis 2 maximum 30 points

            // axis 3

            if ($remaForm == 1){
                $remaTitle = 'Maior que 80%';
                $remaPoints = 5;
                $ax3 += 5;
            }
            elseif ($remaForm == 2){
                $remaTitle = 'Maior que 83%';
                $remaPoints = 10;
                $ax3 += 10;
            }
            elseif ($remaForm == 3){
                $remaTitle = 'Maior que 86%';
                $remaPoints = 20;
                $ax3 += 20;
            }
            elseif ($remaForm == 4){
                $remaTitle = 'Maior que 90%';
                $remaPoints = 30;
                $ax3 += 30;
            }

            if ($avStdForm == 1){
                $avgStdTitle = 'Maior que 12';
                $avgStdPoints = 5;
                $ax3 += 5;
            }
            elseif ($avStdForm == 2){
                $avgStdTitle = 'Maior que 13';
                $avgStdPoints = 15;
                $ax3 += 15;
            }
            elseif ($avStdForm == 3){
                $avgStdTitle = 'Maior que 14';
                $avgStdPoints = 20;
                $ax3 += 20;
            }

            if ($ax3 > 40) $ax3 = 40; // axis 3 maximum 40 points
        }

        // negative score
        $neg = ($missMeet * 2) + ($partMeet * 0.5) + ($lateRoll * 0.5) + ($lateRpt * 0.5) + ($msgFail * 0.5) + ($missClass * 2);
        $gen = $ax1 + $ax2 + $ax3 - $neg;

    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - PPY</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
        span.link {
            color: blue;
            cursor: pointer;
        }
        
        span.link:hover{
            text-decoration: underline;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
            element('divTop').style.height = element('divHeadder').offsetHeight + 'px';
            
            if (element('selUser')) styleSelectBox(element('selUser'));
            if (element('selCamp')) styleSelectBox(element('selCamp'));
            
        };
        
        document.documentElement.onkeydown = function(e) {

            if ((e == null && event.keyCode == 27) || (e != null && e.which == 27))
                hideBoxes();

        };
        
        function redir(sel){
            
            var val = selectedValue(sel);
            
            if (val > 0){
                window.location = 'ppy.php?cid=' + val;
            }
            
        }
        
        function hideBoxes(){
            element('infoBox').style.opacity = '0';
            element('overlay').style.opacity = '0';
            element('overlay').style.visibility = 'hidden';
            element('infoBox').style.visibility = 'hidden';
        }
        
        function showInfo(){
            element('overlay').style.visibility = 'visible';
            element('infoBox').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('infoBox').style.opacity = '1';
        }
        
        function editPPY(uid, cid){
            
            var frm = document.createElement('form');
            frm.method = 'post';
            frm.action = 'ppyedit.php';
            
            document.body.appendChild(frm);
            
            var hid = document.createElement('input');
            hid.type = 'hidden';
            hid.name = 'uid';
            hid.value = uid;
            
            frm.appendChild(hid);
            
            var hid2 = document.createElement('input');
            hid2.type = 'hidden';
            hid2.name = 'cid';
            hid2.value = cid;
            
            frm.appendChild(hid2);
            frm.submit();
            
        }
        
    </script>
    
</head>
<body>
    
    <div class="top" id="divTop"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
        
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
            
            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action="ppy.php">
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
if ($isValid){
?>
        <br/>
        <div id="msgBox" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>; width: 1000px; left: 0; right: 0; margin: auto;">
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <div style="color: red; font-style: italic; padding-right: 10px;"><?php if (isset($msg)) echo $msg; ?></div>
            </div>
            <br/>
        </div>
<?php
    // if calculus index not found and user
    // is not admin, data not entered message
    // will be displayed because $dataFlag will
    // set to false. If user is admin, hide
    // block of code bellow and display error message
    // above.
    if (!$isAdmin || isset($calcIndex)) { 
?>
        <div class="panel" style="width: 1000px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Premio Performance Yázigi (PPY)</span>
            <hr/>
            
            <table>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Professor:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($userName, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Campanha:</td>
                    <td style="font-weight: bold;"><?php echo htmlentities($cInfo['Name'], 0, 'ISO-8859-1'); ?></td>
                </tr>
            </table>
<?php

    if ($dataFlag){
?>
            <table style="width: 100%;">
<?php
        if ($axis1Only){
            echo '<caption style="text-align: left; color: red; font-style: italic; font-size: 13px; padding-left: 5px;">* Este professor participou apenas do Eixo 1. Clique <span class="link" onclick="showInfo();">aqui</span> para maiores informações.</caption>' . PHP_EOL;
        }
?>
                <tr>
                    <td style="background-color: #61269e; color: white; font-weight: bold;" colspan="4">Eixo 1 - Qualificação (máximo de 30 pontos)</td>
                </tr>
                <tr style="background-color: #b4b4b4;">
                    <td style="width: 34%;">Requesito</td>
                    <td style="width: 33%;">Valor Atribuído</td>
                    <td style="width: 33%;">Observações</td>
                    <td>Pontuação</td>
                </tr>
                <tr style="background-color: #f1f1f1;">
                    <td>Curso de Graduação</td>
                    <td><?php echo $gradTitle; ?></td>
                    <td><?php echo htmlentities($gradNotes, 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><?php echo $gradPoints; ?></td>
                </tr>
                <tr style="background-color: #f1f1f1;">
                    <td>Experiência no exterior/intercâmbio</td>
                    <td><?php echo $intExpTitle; ?></td>
                    <td><?php echo htmlentities($intExpNotes, 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><?php echo $intExpPoints; ?></td>
                </tr>
                <tr style="background-color: #f1f1f1;">
                    <td>Exame de proficiência</td>
                    <td><?php echo $profTitle; ?></td>
                    <td><?php echo htmlentities($profNotes, 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><?php echo $profPoints; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; border: 0;" colspan="3">Sub-total:</td>
                    <td style="text-align: center; background-color: #b4b4b4;"><?php echo $ax1; ?></td>
                </tr>
            </table>
           
            <br/>
            
            <table style="width: 100%;">
                <tr>
                    <td style="background-color: #61269e; color: white; font-weight: bold;" colspan="4">Eixo 2 - Envolvimento/Dedicação (máximo de 30 pontos)</td>
                </tr>
                <tr style="background-color: #b4b4b4;">
                    <td style="width: 34%;">Requesito</td>
                    <td style="width: 33%;">Valor Atribuído</td>
                    <td style="width: 33%;">Observações</td>
                    <td>Pontuação</td>
                </tr>
                <tr style="background-color: #f1f1f1;">
                    <td>Participação no YILTS ou equivalente</td>
                    <td><?php echo ($yilts ? 'Sim' : 'Não'); ?></td>
                    <td><?php echo htmlentities($yiltsNotes, 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><?php echo $yiltsPoints; ?></td>
                </tr>
                <tr style="background-color: #f1f1f1;">
                    <td>Campanha de cidadania (participação de 100% das turmas)</td>
                    <td><?php echo ($citizenCamp ? 'Sim' : 'Não'); ?></td>
                    <td><?php echo htmlentities($citizenCampNotes, 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><?php echo $citizenCampPoints; ?></td>
                </tr>
                <tr style="background-color: #f1f1f1;">
                    <td>Eventos culturais/pedagógicos (participação de 100% das turmas)</td>
                    <td><?php echo ($cultEvents ? 'Sim' : 'Não'); ?></td>
                    <td><?php echo htmlentities($cultEventsNotes, 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><?php echo $cultEventsPoints; ?></td>
                </tr>
                <tr style="background-color: #f1f1f1;">
                    <td>Prática pedagógica inovadora</td>
                    <td><?php if ($inovPedPrac) echo $inovPedPrac; ?></td>
                    <td><?php echo htmlentities($inovPedPracNotes, 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><?php echo $inovPedPracPoints; ?></td>
                </tr>
                <tr style="background-color: #f1f1f1;">
                    <td>Sugestão relevante implementada pela escola</td>
                    <td><?php if ($relSug > 0) echo +$relSug; ?></td>
                    <td><?php echo htmlentities($relSugNotes, 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><?php if ($relSug > 0) echo +$relSug; ?></td>
                </tr>
                <tr style="background-color: #f1f1f1;">
                    <td>Número de turmas</td>
                    <td><?php if (!$axis1Only) echo $numCls; ?></td>
                    <td><?php echo htmlentities($numClsNotes, 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><?php echo $numClsPoints; ?></td>
                </tr>
                <tr style="background-color: #f1f1f1;">
                    <td>Alunos indicados</td>
                    <td><?php if ($refStd) echo $refStd; ?></td>
                    <td><?php echo htmlentities($refStdNotes, 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><?php if ($refStd) echo ($refStd * 3); ?></td>
                </tr>
                <tr style="background-color: #f1f1f1;">
                    <td>Contribuição formal em reuniões pedagógicas</td>
                    <td><?php if ($pedContr > 0) echo +$pedContr; ?></td>
                    <td><?php echo htmlentities($pedContrNotes, 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><?php if ($pedContr > 0) echo +$pedContr; ?></td>
                </tr>
                <tr style="background-color: #f1f1f1;">
                    <td>Outras atividades que caracterizam envolvimento</td>
                    <td><?php if ($otherAct > 0) echo +$otherAct; ?></td>
                    <td><?php echo htmlentities($otherActNotes, 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><?php if ($otherAct > 0) echo +$otherAct; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; border: 0;" colspan="3">Sub-total:</td>
                    <td style="text-align: center; background-color: #b4b4b4;"><?php echo $ax2; ?></td>
                </tr>
            </table>
            
            <br/>
            
            <table style="width: 100%;">
                <tr>
                    <td style="background-color: #61269e; color: white; font-weight: bold;" colspan="4">Eixo 3 - Resultados (máximo de 40 pontos)</td>
                </tr>
                <tr style="background-color: #b4b4b4;">
                    <td style="width: 34%;">Requesito</td>
                    <td style="width: 33%;">Valor Atribuído</td>
                    <td style="width: 33%;">Observações</td>
                    <td>Pontuação</td>
                </tr>
                <tr style="background-color: #f1f1f1;">
                    <td>Índice geral de manutenção</td>
                    <td><?php echo $remaTitle; ?></td>
                    <td><?php echo htmlentities($remaNotes, 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><?php echo $remaPoints; ?></td>
                </tr>
                <tr style="background-color: #f1f1f1;">
                    <td>Média de aluno por turma</td>
                    <td><?php echo $avgStdTitle; ?></td>
                    <td><?php echo htmlentities($avStdNotes, 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><?php echo $avgStdPoints; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; border: 0;" colspan="3">Sub-total:</td>
                    <td style="text-align: center; background-color: #b4b4b4;"><?php echo $ax3; ?></td>
                </tr>
            </table>
            
            <br/>
            
            <table style="width: 100%;">
                <tr>
                    <td style="background-color: #61269e; color: white; font-weight: bold;" colspan="4">Pontuação Negativa</td>
                </tr>
                <tr style="background-color: #b4b4b4;">
                    <td style="width: 34%;">Requesito</td>
                    <td style="width: 33%;">Valor Atribuído</td>
                    <td style="width: 33%;">Observações</td>
                    <td>Pontuação</td>
                </tr>
                <tr style="background-color: #f1f1f1;">
                    <td>Número de faltas às reuniões pedagógicas</td>
                    <td><?php if ($missMeet > 0) echo $missMeet; ?></td>
                    <td><?php echo htmlentities($missMeetNotes, 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><?php if ($missMeet > 0) echo ($missMeet * 2); ?></td>
                </tr>
                <tr style="background-color: #f1f1f1;">
                    <td>Número de atrasos/saídas cedo das reuniões pedagógicas</td>
                    <td><?php if ($partMeet > 0) echo $partMeet; ?></td>
                    <td><?php echo htmlentities($partMeetNotes, 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><?php if ($partMeet > 0) echo ($partMeet * 0.5); ?></td>
                </tr>
                <tr style="background-color: #f1f1f1;">
                    <td>Número de caternetas do SGY preenchidas com atraso</td>
                    <td><?php if ($lateRoll > 0) echo $lateRoll; ?></td>
                    <td><?php echo htmlentities($lateRollNotes, 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><?php if ($lateRoll > 0) echo ($lateRoll * 0.5); ?></td>
                </tr>
                <tr style="background-color: #f1f1f1;">
                    <td>Número de SIRs entregue com atraso</td>
                    <td><?php if ($lateRpt > 0) echo $lateRpt; ?></td>
                    <td><?php echo htmlentities($lateRptNotes, 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><?php if ($lateRpt > 0) echo ($lateRpt * 0.5); ?></td>
                </tr>
                <tr style="background-color: #f1f1f1;">
                    <td>Número de E-mail (turmas infantis)/Hotlines/Direct Messages não enviados</td>
                    <td><?php if ($msgFail > 0) echo $msgFail; ?></td>
                    <td><?php echo htmlentities($msgFailNotes, 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><?php if ($msgFail > 0) echo ($msgFail * 0.5); ?></td>
                </tr>
                <tr style="background-color: #f1f1f1;">
                    <td>Número de faltas à aula (exceto quando justificado)</td>
                    <td><?php if ($missClass > 0) echo $missClass; ?></td>
                    <td><?php echo htmlentities($missClassNotes, 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><?php if ($missClass > 0) echo ($missClass * 2); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; border: 0;" colspan="3">Sub-total:</td>
                    <td style="text-align: center; background-color: #b4b4b4;"><?php echo $neg; ?></td>
                </tr>
            </table>
            
            <br/>
            
            <table>
                <tr>
                    <td style="background-color: #61269e; color: white; font-weight: bold;" colspan="7">Resultado Geral <?php echo htmlentities($cInfo['Name'], 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr style="background-color: #b4b4b4; text-align: center;">
                    <td style="width: 80px;">Eixo 1</td>
                    <td style="width: 80px;">Eixo 2</td>
                    <td style="width: 80px;">Eixo 3</td>
                    <td style="width: 80px;">Negativo</td>
                    <td style="width: 80px;">Geral</td>
                    <td style="width: 80px;">Turmas</td>
                    <td style="width: 100px;">Total a receber</td>
                </tr>
                <tr style="text-align: center; background-color: #f1f1f1;">
                    <td><?php echo $ax1; ?></td>
                    <td><?php echo $ax2; ?></td>
                    <td><?php echo $ax3; ?></td>
                    <td><?php echo $neg; ?></td>
                    <td><?php echo $gen; ?></td>
                    <td><?php echo $numCls; ?></td>
                    <td style="font-weight: bold;"><?php echo '$' . ($gen > 0 ? number_format($gen * $calcIndex * $numCls, 2, ',', '') : '0,00'); ?></td>
                </tr>
            </table>
            <br/>
<?php

        if ($isAdmin){
            // edit button
            echo '<div style="padding: 0 0 10px 10px;"><button onclick="editPPY(' . $uid . ',' . $cInfo['ID'] . ');"><img src="' . IMAGE_DIR . 'pencil1.png"/> Editar Planilha</button></div>' . PHP_EOL;
        }

    }
    else {
        echo '<div style="color: red; font-style: italic; padding: 5px;">O PPY ' . htmlentities($cInfo['Name'], 0, 'ISO-8859-1') . ' deste professor não foi calculado.</div>' . PHP_EOL;
    }

?>
        </div>
        
        <div class="overlay" id="overlay" onclick="hideBoxes();"></div>
        <div class="helpBox" id="infoBox" style="width: 420px; height: 160px;">
            <div class="closeImg" onclick="hideBoxes();"></div>
            <span style="font-weight: bold;">PPY - Participação apenas no Eixo 1</span>
            <hr/>
            <div style="padding: 10px;">
                O professor que está cursando ou é graduados em Letras ou possui especialização na área de educação, poderá participar do PPY
                mesmo tendo sua manutenção inferior a 78% e igual ou superior a 74%. No entanto, para este será considerado apenas o Eixo 1.
            </div>
        </div>
<?php
    }
}
elseif ($isAdmin) {
    selectUser($cInfo);
}
else {
    echo '<span style="color: red; font-style: italic;">Parametros inválidos.</span>';
}

?>
        <p>&nbsp;</p>
    </div>
    
</body>
</html>
<?php

$db->close();

// -----------------------------------------------

function selectUser($cInfo){
    
    global $db;
    
    // check if the index of calculation is set
    $hasCalcIndex = !!$db->query("SELECT 1 FROM ppy_calc_index WHERE Campaign = " . $cInfo['ID'])->fetch_row()[0];
    
?>
        <br/>
        <div class="panel" style="width: 1000px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Premio Performance Yázigi (PPY)</span>
            <hr/>
            
            <form id="frmSelTeacher" method="get" action="ppy.php">
            <table>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $cInfo['Name']; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Selecione o Professor:</td>
                    <td>
                        <select id="selUser" name="uid" style="width: 300px;"<?php if (!$hasCalcIndex) echo ' disabled="disabled"' ?> onchange="this.styleOption(); if (this.selectedIndex) window.location = 'ppy.php?uid=' + this.selectedValue();" onkeyup="this.styleOption(); if (this.selectedIndex) window.location = 'ppy.php?uid=' + this.selectedValue();">
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
            </form>
            
        </div>
<?php
    
}

?>