<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once 'dropdown/dropdown.php';
require_once '../genreq/genreq.php';
require_once 'required/campaigninfo.php';

// specific to this script
require_once 'required/subcamp/selectSubCamp.php';

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
    // invalid campaign
    closeDbAndGoTo($db, "searchcamp.php" . (isset($_SERVER['REQUEST_URI']) ? '?redir=' . urlencode($_SERVER['REQUEST_URI']) : ''));
}

$subCampID = getGet('scampid');
$isValid = false;
$msg = null;

if (isNum($subCampID) && 
        $row = $db->query("SELECT subcamps.*, campaignName(campaigns.ID) AS CampName, campaigns.Open AS CampOpen FROM subcamps LEFT JOIN campaigns ON subcamps.Parent = campaigns.ID WHERE subcamps.ID = $subCampID")->fetch_assoc()){
    
    $campName = $row['CampName'];
    $campIsOpen = !!$row['CampOpen'];
    $subCampName = $row['Name'];
    $subCampIsOpen = !!$row['Open'];
    $inclDropOuts = !!$row['IncludeDropouts'];
    
    $isValid = true;
    
    $closeSubcamp = getPost('closesubcamp');
    $delSubcamp = getPost('del');
    
    if (isNum($closeSubcamp) && $closeSubcamp == $subCampID){
        
        // close subcampaing
        $q = "SELECT users.ID, (SELECT COUNT(*) FROM subcamp_classes JOIN classes ON subcamp_classes.Class = classes.ID JOIN students "
                . "ON students.Class = classes.ID WHERE classes.User = users.ID AND subcamp_classes.SubCamp = $subCampID AND students.Situation "
                . ($inclDropOuts ? '<= 1' : '= 0') . ") AS Total, (SELECT COUNT(*) FROM subcamp_classes JOIN classes ON subcamp_classes.Class = classes.ID JOIN students "
                . "ON students.Class = classes.ID WHERE classes.User = users.ID AND subcamp_classes.SubCamp = $subCampID AND Status = 3 AND students.Situation "
                . ($inclDropOuts ? '<= 1' : '= 0') . ") AS Enrolled FROM users JOIN classes ON classes.User = users.ID JOIN subcamp_classes ON subcamp_classes.Class = classes.ID "
                . "WHERE subcamp_classes.SubCamp = $subCampID GROUP BY users.ID";
        
        $result = $db->query($q);
        
        if ($result->num_rows){
            
            $flag = false;
            
            $q = "INSERT INTO subcamp_results (SubCamp, User, Student_Count, Enrolled_Count) VALUES ";
            
            while ($row = $result->fetch_assoc()){
                
                // save only teacher with students
                if ($row['Total'] > 0){
                
                    if ($flag) $q .= ", ";

                    $q .= "($subCampID, " . $row['ID'] . ", " . $row['Total'] . ", " . $row['Enrolled'] . ")";

                    $flag = true;
                
                }
                
            }
            
        }
        
        $result->close();
        
        if ($flag){
            if (!$db->query($q)){
                $msg = '<span style="font-style: italic; color: red;">' . $db->error . '</span>';
                $db->query("DELETE FROM subcamp_results WHERE SubCamp = $subCampID");
            }
        }
        else {
            $msg = '<span style="font-style: italic; color: red;">Esta subcampanha não possui alunos e não pode ser encerrada.</span>';
        }
        
        if (!isset($msg)){
            
            if ($db->query("UPDATE subcamps SET Open = 0 WHERE ID = $subCampID")){
                
                $db->query("DELETE FROM subcamp_classes WHERE SubCamp = $subCampID");
                
                closeDbAndGoTo($db, "subcamp.php?scampid=$subCampID");
                
            }
            else {
                $msg = '<span style="font-style: italic; color: red;">' . $db->error . '</span>';
                $db->query("DELETE FROM subcamp_results WHERE SubCamp = $subCampID");
            }
            
        }
        
    }
    elseif (isset($closeSubcamp)){
        $msg = '<span style="font-style: italic; color: red;">Erro inesperado. A subcampanha não foi fechada.</span>';
    }
    elseif (isNum($delSubcamp) && $delSubcamp == $subCampID){
        
        // remove subcampaign (everything is set to cascade in DB)
        if ($db->query("DELETE FROM subcamps WHERE ID = $subCampID")){
            $isValid = false;
            $msg = '<span style="font-style: italic; color: blue;">A subcampanha \'' . $subCampName . '\' foi removida com sucesso.</span>';
        }
        else {
            $msg = '<span style="font-style: italic; color: red;">Error: ' . $db->error . '</span>';
        }
        
    }
    elseif (isset($delSubcamp)){
        $msg = '<span style="font-style: italic; color: red;">Erro inesperado. A subcampanha não foi removida.</span>';
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Subcampanha</title>
    
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
            
            if (element('selSubCamp')) element('selSubCamp').styleOption();
            
            element('imgUpArrow').style.visibility = (window.pageYOffset < 15 ? 'hidden' : 'visible');
            
        };
        
        window.onscroll = function(){
            element('imgUpArrow').style.visibility = (window.pageYOffset < 15 ? 'hidden' : 'visible');
        };
        
        function showClasses(img, show){
            
            var panel = element('divClassesPanel');
            
            if (show){
                
                img.src = '<?php echo IMAGE_DIR; ?>hide.png';
                img.title = 'Ocultar';
                img.onclick = function(){showClasses(this, false);};
                
                panel.style.display = 'block';
                
            }
            else {
                
                img.src = '<?php echo IMAGE_DIR; ?>show.png';
                img.title = 'Visualizar';
                img.onclick = function(){showClasses(this, true);};
                
                panel.style.display = 'none';
                
            }
            
        }
        
        function printableVersion(SubCampID){
            window.open('printrept.php?t=6&scid=' + SubCampID, '_blank', 'toolbar=no,scrollbars=yes,resizable=yes,width=840,height=600');
        }
        
        function showSubCamp(sel){
            if (sel.selectedIndex > 0) window.location = 'subcamp.php?scampid=' + sel.selectedValue();
        }
        
        function submitDelete(scid){
            
            var frm = document.createElement('form');
            frm.method = 'post';
            frm.action = 'subcamp.php?scampid=' + scid;
            
            document.body.appendChild(frm);
            
            var hid = document.createElement('input');
            hid.type = 'hidden';
            hid.name = 'del';
            hid.value = scid;
            
            frm.appendChild(hid);
            frm.submit();
            
        }
        
        function showBox(){
            element('chkConfDel').checked = false;
            element('btnDelete').disabled = true;
            element('overlay').style.visibility = 'visible';
            element('deleteBox').style.visibility = 'visible';
            element('overlay').style.opacity = '0.6';
            element('deleteBox').style.opacity = '1';
        }
        
        function hideBox(){
            element('deleteBox').style.opacity = '0';
            element('overlay').style.opacity = '0';
            element('overlay').style.visibility = 'hidden';
            element('deleteBox').style.visibility = 'hidden';
        }
        
        function redir(scid, uid){
            
            var frm = document.createElement('form');
            frm.method = 'post';
            frm.action = 'subcampbyuser.php';
            
            document.body.appendChild(frm);
            
            var hid = document.createElement('input');
            hid.type = 'hidden';
            hid.name = 'scampid';
            hid.value = scid;
            
            var hid2 = document.createElement('input');
            hid2.type = 'hidden';
            hid2.name = 'uid';
            hid2.value = uid;
            
            frm.appendChild(hid);
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
                <form id="frmChangeCamp" method="post" action="subcamp.php<?php if ($isValid) echo '?scampid=' . $subCampID; ?>">
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
        <div id="msgBox" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>; width: 600px; left: 0; right: 0; margin: auto;">
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <?php echo $msg; ?>
            </div>
            <br/>
        </div>
<?php
if ($isValid){
?>
        <div class="panel" style="width: 600px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Subcampanha</span>
            <hr/>
            
            <table>
                <tr>
                    <td style="text-align: right;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($campName, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Subcampanha:</td>
                    <td style="width: 100%; font-weight: bold;">
                        <?php echo htmlentities($subCampName, 0, 'ISO-8859-1'); ?> &nbsp;
                        <a href="subcamp.php"><img src="<?php echo IMAGE_DIR; ?>pencil1.png" title="Modificar Subcampanha"/></a>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                    <form method="post" action="subcamp.php?scampid=<?php echo $subCampID; ?>">
<?php
    if ($subCampIsOpen){
        if ($campIsOpen) {
?>
                        <button type="button" onclick="window.location = 'assocsubcamp.php?scampid=<?php echo $subCampID; ?>';">
                            <img src="<?php echo IMAGE_DIR; ?>link.png"> Associar Turmas
                        </button>
<?php } ?>
                        
                        <button type="submit" name="closesubcamp" value="<?php echo $subCampID; ?>" onclick="return confirm('ATENÇÃO: Apos encerrada, a subcampanha não pode ser reaberta. Tem certeza que deseja continuar?');">
                            <img src="<?php echo IMAGE_DIR; ?>folder.png"> Encerrar Subcampanha
                        </button>
                        
<?php } ?>
                        <button type="button" onclick="showBox();">
                            <img src="<?php echo IMAGE_DIR; ?>recycle2.png"> Remover Subcampanha
                        </button>
<?php

    if ($subCampIsOpen && !$campIsOpen) {
        echo '<div style="font-style: italic; color: red; padding-top: 5px;">A campanha ' . htmlentities($campName, 0, 'ISO-8859-1') . ' está encerrada. Esta subcampanha não pode ser modificada.</div>';
    }
    elseif (!$subCampIsOpen){
        echo '<div style="font-style: italic; color: red; padding-top: 5px;">Esta subcampanha está encerrada.</div>';
    }

?>
                    </form>
                    </td>
                </tr>
            </table>
            
        </div>
        <br/>
<?php if ($subCampIsOpen){  ?>
        <div class="panel" style="width: 600px; left: 0; right: 0; margin: auto;">
            
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="font-weight: bold; white-space: nowrap; padding: 0;">Turmas Associadas</td>
                    <td style="width: 100%; text-align: right; padding: 0;">
                        <img src="<?php echo IMAGE_DIR; ?>hide.png" title="Ocultar" style="vertical-align: middle; cursor: pointer;" onclick="showClasses(this, false);"/>
                    </td>
                </tr>
            </table>
            <div id="divClassesPanel">
            <hr/>
<?php

    $result = $db->query("SELECT subcamp_classes.ID, subcamp_classes.Class AS ClassID, classes.Name, classes.User AS TeacherID, users.Name AS Teacher FROM subcamp_classes JOIN classes ON subcamp_classes.Class = classes.ID JOIN users ON classes.User = users.ID WHERE subcamp_classes.SubCamp = $subCampID ORDER BY Teacher, Name");
    
    if ($result->num_rows || $inclDropOuts){
        
        echo '            <table style="border-collapse: collapse; width: 100%; border: #61269e solid 1px;">
                <tr>
                    <td style="background-color: #61269e; width: 50%; color: white;">Turma</td>
                    <td style="background-color: #61269e; width: 50%; color: white;">Professor</td>
                </tr>';
        
        if ($inclDropOuts){
            $bgcolor = '#f0f0f0'
?>
                <tr>
                    <td style="background-color: <?php echo $bgcolor; ?>; width: 50%;">Evadidos</td>
                    <td style="background-color: <?php echo $bgcolor; ?>; width: 50%;">Todos</td>
                </tr>
<?php
        }
        
        $teachers = array();
        
        while ($row = $result->fetch_assoc()){
            
            $teachers[intval($row['TeacherID'], 10)] = $row['Teacher'];
            
            $bgcolor = ($bgcolor == '#f0f0f0') ? '#ffffff' : '#f0f0f0';
?>
                <tr>
                    <td style="background-color: <?php echo $bgcolor; ?>; width: 50%;"><?php echo htmlentities($row['Name'], 0, 'ISO-8859-1'); ?></td>
                    <td style="background-color: <?php echo $bgcolor; ?>; width: 50%;"><?php echo htmlentities($row['Teacher'], 0, 'ISO-8859-1'); ?></td>
                </tr>
<?php
        }
        
        echo '            </table>';
    
    }
    else {
        echo '<div style="font-style: italic; color: red; padding: 10px;">Não há turmas assiciadas à esta subcampanha.</div>';
    }
    
    $result->close();

?>
            </div>
        </div>
<?php

        if (isset($teachers)) {

            // $teacher[ID] = array("Name", "Total", "Enrolled")
            $teacherInfo = array();

            foreach ($teachers as $teacherID => $teacheName) {

                $q = "SELECT TotalFromOpenSubCamp($subCampID, $teacherID, " . ($inclDropOuts ? '1' : '0') . ") AS Total, "
                        . "EnrolledFromOpenSubCamp($subCampID, $teacherID, " . ($inclDropOuts ? '1' : '0') . ") AS Enrolled";
                
                $row = $db->query($q)->fetch_assoc();

                $teacherInfo[$teacherID] = array("Name" => $teacheName, "Total" => intval($row["Total"], 10), "Enrolled" => intval($row['Enrolled'], 10));
                
            }
    
?>

        <br/>
        <div class="panel" style="width: 600px; left: 0; right: 0; margin: auto;">
            
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="font-weight: bold; white-space: nowrap; padding: 0;">Resultados</td>
                    <td style="width: 100%; text-align: right; padding: 0;">
                        <img src="<?php echo IMAGE_DIR; ?>print.png" title="Imprimir Resultados" style="vertical-align: middle; cursor: pointer;" onclick="printableVersion(<?php echo $subCampID; ?>);"/>
                    </td>
                </tr>
            </table>
            <hr/>
<?php

            if (count($teacherInfo)){
                
                echo '<div style="font-style: italic; color: red; padding-bottom: 2px;">* Para visualizar detalhes, click no nome do professor.</div>';
            
                $flag = false;

                foreach ($teacherInfo as $teacherID => $info){

                    if ($flag) echo '<br/>';

                    echo '<table style="border-collapse: collapse; width: 100%;"><tr><td style="background-color: #61269e; border: #61269e solid 1px;" colspan="2"><a href="#" style="color: white;" onclick="redir(' . $subCampID . ',' . $teacherID . '); return false;">' . htmlentities($info["Name"], 0, 'ISO-8859-1') . '</a></td></tr>' . PHP_EOL;

                    if ($info['Total']){
                        echo '<tr><td style="background-color: #f0f0f0; width: 80%; border: #61269e solid 1px; border-right: 0;">Total de Alunos</td><td style="background-color: #f0f0f0; width: 20%; text-align: right; padding-right: 15px; border: #61269e solid 1px; border-left: 0;">' . $info['Total'] . '</td></tr>' . PHP_EOL . 
                                '<tr><td style="background-color: #f0f0f0; width: 80%; border: #61269e solid 1px; border-right: 0;">Rematriculados</td><td style="background-color: #f0f0f0; width: 20%; text-align: right; padding-right: 15px; border: #61269e solid 1px; border-left: 0;">' . $info['Enrolled'] . '</td></tr>' . PHP_EOL .
                                '<tr><td style="background-color: #ffffff; width: 80%; text-align: right;">Rema!</td><td style="background-color: #c1c1c1; width: 20%; text-align: right; padding-right: 15px; border: #61269e solid 1px;">' . +number_format((($info['Enrolled'] * 100) / $info['Total']), 2) . '%</td></tr>' . PHP_EOL;
                    }
                    else {
                        echo '<tr><td style="background-color: #f0f0f0; color: red; font-style: italic; border: #61269e solid 1px;" colspan="2">** Este professor não possue alunos associados com esta subcampanha.</td></tr>';
                    }

                    echo '</table>';

                    $flag = true;
                    
                }
                
            }
            else {
                echo '<div style="font-style: italic; color: red; padding: 5px;">Não há participantes nesta campanha.</div>';
            }

?>
        </div>
    
<?php
        }
    }
    else {
?>
        <div class="panel" style="width: 600px; left: 0; right: 0; margin: auto;">
            
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="font-weight: bold; white-space: nowrap; padding: 0;">Resultados</td>
                    <td style="width: 100%; text-align: right; padding: 0;">
                        <img src="<?php echo IMAGE_DIR; ?>print.png" title="Imprimir Resultados" style="vertical-align: middle; cursor: pointer;" onclick="printableVersion(<?php echo $subCampID; ?>);"/>
                    </td>
                </tr>
            </table>
            <hr/>
<?php
    
        $result = $db->query("SELECT users.Name, subcamp_results.Student_Count AS Total, subcamp_results.Enrolled_Count AS Enrolled FROM subcamp_results JOIN users ON subcamp_results.User = users.ID WHERE subcamp_results.SubCamp = $subCampID ORDER BY Name");
        
        if ($result->num_rows){
            
            $flag = false;
            
            while ($row = $result->fetch_assoc()){
                
                if ($flag) echo '<br/>';

                echo '<table style="border-collapse: collapse; width: 100%;"><tr><td style="background-color: #61269e; color: white; border: #61269e solid 1px;" colspan="2">' . htmlentities($row["Name"], 0, 'ISO-8859-1') . '</td></tr>' . PHP_EOL;

                if ($row['Total']){
                    echo '<tr><td style="background-color: #f0f0f0; width: 80%; border: #61269e solid 1px; border-right: 0;">Total de Alunos</td><td style="background-color: #f0f0f0; width: 20%; text-align: right; padding-right: 15px; border: #61269e solid 1px; border-left: 0;">' . $row['Total'] . '</td></tr>' . PHP_EOL . 
                            '<tr><td style="background-color: #f0f0f0; width: 80%; border: #61269e solid 1px; border-right: 0;">Rematriculados</td><td style="background-color: #f0f0f0; width: 20%; text-align: right; padding-right: 15px; border: #61269e solid 1px; border-left: 0;">' . $row['Enrolled'] . '</td></tr>' . PHP_EOL .
                            '<tr><td style="background-color: #ffffff; width: 80%; text-align: right;">Rema!</td><td style="background-color: #c1c1c1; width: 20%; text-align: right; padding-right: 15px; border: #61269e solid 1px;">' . +number_format(((intval($row['Enrolled'], 10) * 100) / intval($row['Total'], 10)), 2) . '%</td></tr>' . PHP_EOL;
                }
                else {
                    echo '<tr><td style="background-color: #f0f0f0; color: red; font-style: italic; border: #61269e solid 1px;" colspan="2">** Este professor não possue alunos associados com esta subcampanha.</td></tr>';
                }

                echo '</table>';

                $flag = true;
                
            }
            
        }
        else {
            echo '<div style="font-style: italic; color: red; padding: 10px;">Não há resultados associados à esta subcampanha.</div>';
        }
        
        $result->close();

?>
        </div>
<?php } ?>
        <div class="overlay" id="overlay"></div>
        <div class="helpBox" id="deleteBox" style="width: 450px; height: 160px;">
            <span style="font-weight: bold;">Remover Subcampanha</span>
            <hr>
                <div style="padding: 5px;">
                    Todos os dados relativos à esta subcampanha serão removidos. Para continuar, selecione a opção abaixo e clique o botão.
                </div>
                <div style="padding: 5px;">
                    <input id="chkConfDel" type="checkbox" onclick="element('btnDelete').disabled = !this.checked;"/> Confirmar a remoção
                </div>
                <div style="padding: 15px 0 0 5px;">
                    <input id="btnDelete" type="button" value="Remover" disabled="disabled" onclick="submitDelete(<?php echo $subCampID; ?>);"/> <input id="btnCancel" type="button" value="Cancelar" onclick="hideBox();"/>
                </div>
        </div>
<?php
}
else {
    //echo '<span style="font-style: italic; color: red;">Parametros inválidos.</span>' . PHP_EOL;
    selectSubCamp($db, $cInfo);
}
?>
    </div>
    
    <p>&nbsp;</p>
    
    <img id="imgUpArrow" src="<?php echo IMAGE_DIR; ?>arrow_up.png" style="position: fixed; right: 20px; bottom: 20px; cursor: pointer; visibility: hidden;" onclick="document.body.scrollTop = document.documentElement.scrollTop = 0;" title="Topo da página"/>
</body>
</html>
<?php

$db->close();

?>