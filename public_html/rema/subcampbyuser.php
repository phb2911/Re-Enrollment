<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once 'dropdown/dropdown.php';
require_once '../genreq/genreq.php';
require_once 'required/campaigninfo.php';

// specific to this script
require_once 'required/subcampbyuser/displayStudentTable.php';
require_once 'required/subcampbyuser/displaySubCampaign.php';
require_once 'required/subcampbyuser/selectSubCampUsr.php';

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

$subCampID = getPost('scampid');
$uid = ($isAdmin ? getPost('uid') : $loginObj->userId);
$isValid = false;
$msg = null;

if (isset($subCampID) && isset($uid)){
    if (isNum($subCampID) && isNum($uid) && $subCampInfo = $db->query("SELECT subcamps.*, campaignName(Parent) AS Campaign, (SELECT Name FROM users WHERE ID = $uid) AS Teacher FROM subcamps WHERE ID = $subCampID")->fetch_assoc()){
        
        if (is_null($subCampInfo['Teacher'])){
            $msg = '<span style="font-style: italic; color: red;">O colaborador não é válido.</span>';
        }
        else {
            $isValid = true;
        }
        
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">Parametros inválidos.</span>';
    }
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Subcampanha por Colaborador</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
       
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
            
            element('divTop').style.height = element('divHeadder').offsetHeight + 'px';
            
            if (element('selCamp')){
                
                element('selCamp').selectedIndex = 0;
                element('selCamp').style.fontStyle = 'italic';
                
            }
            
            if (element('selSubCamp')) {
                element('selSubCamp').selectedIndex = 0;
                element('selSubCamp').style.fontStyle = 'italic';
            }
            
            if (element('selUser')) {
                element('selUser').selectedIndex = 0;
                element('selUser').style.fontStyle = 'italic';
            }
            
        };
        
        function validateInput(){
            
            if (element('selSubCamp').selectedIndex === 0){
                alert('Por favor selecione a subcampanha.');
                return false;
            }
            
            if (element('selUser') && element('selUser').selectedIndex === 0){
                alert('Por favor selecione o colaborador.');
                return;
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
                <form id="frmChangeCamp" method="post" action="subcampbyuser.php">
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
        <div id="msgBox" style="display: <?php echo (isset($msg) ? 'block' : 'none'); ?>;">
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <?php echo $msg; ?>
            </div>
            <br/>
        </div>
<?php 

if ($isValid){
    displaySubCampaign($db, $isAdmin, $uid, $subCampInfo);
}
else {
    selectSubCampUsr($db, $isAdmin, $cInfo);
}

?>
        <p>&nbsp;</p>

    </div>
    
</body>
</html>
<?php

$db->close();

?>