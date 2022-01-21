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

$subCampName = getPost('subcamp');
$msg = null;

if ($cInfo['Open'] && isset($subCampName)){
    
    $subCampName = trim($subCampName);
    
    if (!strlen($subCampName)){
        $msg = '<span style="font-style: italic; color: red;">O nome da subcampanha não é válido.</span>';
    }
    elseif (strlen($subCampName) > 55){
        $msg = '<span style="font-style: italic; color: red;">O nome da subcampanha não pode conter mais de 55 caracteres.</span>';
    }
    elseif (!!$db->query("SELECT 1 FROM subcamps WHERE Parent = " . $cInfo['ID'] . " AND Name = '" . $db->real_escape_string($subCampName) . "'")->fetch_row()[0]){
        $msg = '<span style="font-style: italic; color: red;">A campanha ' . $cInfo['Name'] . ' já possui uma subcampanha com o nome \'' . htmlentities($subCampName, 0, 'ISO-8859-1') . '\'. Por favor escolha um nome diferente.</span>';
    }
    elseif ($db->query("INSERT INTO subcamps (Name, Parent) VALUES ('" . $db->real_escape_string($subCampName) . "', " . $cInfo['ID'] . ")")){
        
        $insId = $db->insert_id;
        
        closeDbAndGoTo($db, "assocsubcamp.php?scampid=$insId");
                
    }
    else {
        $msg = '<span style="font-style: italic; color: red;">' . $db->error . '</span>';
    }
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Nova Subcampanha</title>
    
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
        
        function validateInput(){
            
            var input = element('txtNewSubCamp').value.trim();
            
            if (!input.length){
                alert('Por favor insira o nome da subcampanha.');
                element('txtNewSubCamp').focus();
                return false;
            }
            
            if (input.length > 55){
                alert('O nome da subcampanha não pode conter mais de 55 caracteres.');
                element('txtNewSubCamp').focus();
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
                <form id="frmChangeCamp" method="post" action="newsubcamp.php">
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
if (!$cInfo['Open']){
?>
        <div class="panel" style="width: 500px; left: 0; right: 0; margin: auto;">
            <span style="font-style: italic; color: red;">A campanha <?php echo $cInfo['Name']; ?> não está aberta, portanto, não é possível criar subcampanhas.</span>
        </div>
<?php
}
else {
?>
        <div class="panel" style="width: 600px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Nova Subcampanha</span>
            <hr/>
            
            <form action="newsubcamp.php" method="post">
            <table>
                <tr>
                    <td style="text-align: right;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $cInfo['Name']; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Subcampanha:</td>
                    <td style="width: 100%;"><input type="text" id="txtNewSubCamp" name="subcamp" value="<?php echo htmlentities($subCampName, 3, 'ISO-8859-1'); ?>" style="width: 80%;" maxlength="55"/></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">&nbsp;</td>
                    <td style="width: 100%;"><input type="submit" value="  Criar  " onclick="return validateInput();"/></td>
                </tr>
            </table>
            </form>
            
        </div>
            
<?php } ?>        
    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

?>