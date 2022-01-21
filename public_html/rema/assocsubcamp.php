<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once '../genreq/genreq.php';
require_once 'dropdown/dropdown.php';
require_once 'required/campaigninfo.php';

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

if (!$loginObj->isLoggedIn()) closeDbAndGoTo($db, LOGIN_PAGE);

$isAdmin = $loginObj->isAdmin();

if (!$isAdmin) closeDbAndGoTo($db, ".");

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

$subCampID = getGet('scampid');
$orderCriteria = getGet('oc');
$isValid = false;
$msg = null;

if (isNum($subCampID) && $row = $db->query("SELECT Name, IncludeDropouts, Parent, campaignName(Parent) AS CampName FROM subcamps WHERE ID = $subCampID AND Open = 1 AND (SELECT Open FROM campaigns WHERE ID = Parent)")->fetch_assoc()){
    
    $subCampName = $row['Name'];
    $inclDropOuts = !!$row['IncludeDropouts'];
    $campName = $row['CampName'];
    $campId = $row['Parent'];
    
    $isValid = true;
    
}
else {
    $msg = '<span style="font-style: italic; color: red;">Subcampanha inválida ou encerrada.</span>';
}

if ($isValid && isset($_POST['incCls']) && $_POST['incCls'] === '1'){
    
    // include dropouts has modified
    if (!$inclDropOuts && $_POST['incldo'] == 1){
        $db->query("UPDATE subcamps SET IncludeDropouts = 1 WHERE ID = $subCampID");
    }
    elseif ($inclDropOuts && $_POST['incldo'] != 1){
        $db->query("UPDATE subcamps SET IncludeDropouts = 0 WHERE ID = $subCampID");
    }
    
    // fetch all class IDs containing in this subcampaign
    $result = $db->query("SELECT ID, Class FROM subcamp_classes WHERE SubCamp = $subCampID");
    
    // current classes in sub campaign table
    // $curCls[ID] = classId
    $curCls = array();
    
    while($row = $result->fetch_assoc()){
        $curCls[$row['ID']] = $row['Class'];
    }
    
    $result->close();
    
    // delete all queries from 
    
    // build insert query
    $ins_q = "INSERT INTO subcamp_classes (SubCamp, Class) VALUES ";
    $flag = false;
    
    // iterate through classes checked, if any
    if (isset($_POST['class']) && is_array($_POST['class'])){
        
        foreach ($_POST['class'] as $clsId) {
            
            if (isNum($clsId)){
                
                // check if class id is in DB
                $recId = array_search($clsId, $curCls);
                
                if ($recId === false){
                    // record not found, add to db
                    if ($flag) $ins_q .= ", ";
                    
                    $ins_q .= "($subCampID, $clsId)";

                    $flag = true;
                }
                else {
                    // record found, remove from array.
                    // the remaining ones in the array will be used
                    // as reference to be deleted
                    unset($curCls[$recId]);
                }
                
            }
            
        }
        
    }
    
    // execute inser query if at least one class added
    if ($flag){
        $db->query($ins_q);
    }
    
    // if there are classes left in array, delete them
    if (count($curCls)){
        
        $del_q = "DELETE FROM subcamp_classes WHERE ";
        $flag = false;
        
        foreach ($curCls as $key => $value) {
            
            if ($flag) $del_q .= "OR ";
            
            $del_q .= "ID = $key ";
            
            $flag = true;
            
        }
        
        // execute query
        $db->query($del_q);
        
    }
    
    // redirect
    closeDbAndGoTo($db, "subcamp.php?scampid=" . $subCampID);
    
}

if ($isValid){
    
    $classes = array();
    
    $result = $db->query("SELECT Class FROM subcamp_classes WHERE SubCamp = $subCampID");
    
    while ($row = $result->fetch_assoc()){
        $classes[] = $row['Class'];
    }
    
    $result->close();
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Associar Turmas à Subcampanha</title>
    
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
            
            checkChanged();
            
        };
        
        function verifyChecked(){
            
            if (element('chkInclDo') && element('chkInclDo').checked) return true;
            
            var chkBoxes = document.getElementsByClassName('chkClass');
            
            for (var i = 0; i < chkBoxes.length; i++){
                if (chkBoxes[i].checked) return true;
            }
            
            alert('Por favor selecione pelo menos uma turma.');
            
            return false;
            
        }
        
        function checkAll(checked){
            
            var chkBoxes = document.getElementsByClassName('chkClass');
            
            for (var i = 0; i < chkBoxes.length; i++){
                chkBoxes[i].checked = checked;
            }
            
        }
        
        function checkChanged(){
            
            var chkBoxes = document.getElementsByClassName('chkClass');
            
            for (var i = 0; i < chkBoxes.length; i++){
                if (!chkBoxes[i].checked){
                    // at least one is not check
                    element('chkCheckAll').checked = false;
                    return;
                }
            }
            
            // all checked
            element('chkCheckAll').checked = true;
            
        }
        
    </script>
    
</head>
<body>
    
    <div class="top" id="divTop"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
        
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
        
            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action="assocsubcamp.php?scampid=<?php echo $subCampID . (isset($orderCriteria) ? '&oc=' . $orderCriteria : ''); ?>">
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
            
            <span style="font-weight: bold;">Associar Turmas à Subcampanha</span>
            <hr/>
            
            <table>
                <tr>
                    <td style="text-align: right;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($campName, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Subcampanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($subCampName, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Ordenar por:</td>
                    <td style="width: 100%;">
                        <select onchange="window.location = 'assocsubcamp.php?scampid=<?php echo $subCampID; ?>' + (this.selectedIndex == 1 ? '&oc=1' : '');">
                            <option<?php if ($orderCriteria != 1) echo ' selected="selected"'; ?>>Turma</option>
                            <option<?php if ($orderCriteria == 1) echo ' selected="selected"'; ?>>Professor</option>
                        </select>
                    </td>
                </tr>
            </table>
            
        </div>
        
<?php

    $result = $db->query("SELECT classes.ID, classes.Name, users.Name AS Teacher, '0' AS Dropout FROM classes LEFT JOIN users ON classes.User = users.ID "
            . "WHERE classes.Campaign = " . $campId . " ORDER BY " . ($orderCriteria == 1 ? 'Teacher, Name' : 'Name, Teacher'));
    
    if ($result->num_rows){
        
        echo '<form action="assocsubcamp.php?scampid=' . $subCampID . '" method="post">' . PHP_EOL .
             '<br/><div class="panel" style="width: 600px; left: 0; right: 0; margin: auto;"><input type="checkbox" id="chkInclDo" name="incldo" value="1" ' . ($inclDropOuts ? 'checked="checked"' : '') . '/><label for="chkInclDo"> Incluir evadidos nesta subcampanha</label></div>' . PHP_EOL;
                
        echo '<br/><div class="panel" style="width: 600px; left: 0; right: 0; margin: auto;"><div style="padding-bottom: 2px;"><input id="chkCheckAll" type="checkbox" onclick="checkAll(this.checked);"/><label for="chkCheckAll"> Selecionar todos</label></div>' . PHP_EOL .
                '<table style="width: 100%; border: #61269e solid 1px; border-collapse: collapse;">' . PHP_EOL . 
                '<tr><td style="background-color: #61269e; color: white;" colspan="2">Turma</td><td style="background-color: #61269e; color: white;">Professor</td></tr>' . PHP_EOL;
        
        $bgcolor = null;
        
        while ($row = $result->fetch_assoc()){
            
            $bgcolor = ($bgcolor == '#f0f0f0') ? '#ffffff' : '#f0f0f0';
            
            // class previously added to sub-campaign
            echo '<tr><td style="background-color: ' . $bgcolor . ';"><input type="checkbox" class="chkClass" id="chkClass' . $row['ID'] . '" name="class[]" value="' . $row['ID'] . '" ' . (in_array($row['ID'], $classes) ? 'checked="checked"' : '') . '/></td><td style="background-color: ' . $bgcolor . '; width: 50%;"><label for="chkClass' . $row['ID'] . '">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</label></td><td style="background-color: ' . $bgcolor . '; width: 50%;">' . htmlentities($row['Teacher'], 0, 'ISO-8859-1') . '</td></tr>' . PHP_EOL;
                        
        }
        
        echo '</table>' . PHP_EOL . '<br/><button type="submit" name="incCls" value="1" style="width: 100px;"><img src="' . IMAGE_DIR . 'disk2.png"/> Salvar</button> <button type="button" style="width: 100px;" onclick="window.location = \'subcamp.php?scampid=' . $subCampID . '\';"><img src="' . IMAGE_DIR . 'cancel2.png"/> Cancelar</button></div>' . PHP_EOL . 
                '</form>' . PHP_EOL;
        
    }
    else {
        echo '<br/><div style="width: 600px; left: 0; right: 0; margin: auto; font-style: italic; color: red;">A campanha ' . $campName . ' não contém turmas.<br/>Por favor insira turmas à campanha para que estas possam ser incluidas à subcampanha.</div>' . PHP_EOL;
    }
    
    $result->close();

}

?>
        
    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

?>