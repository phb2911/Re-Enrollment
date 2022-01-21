<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once '../genreq/genreq.php';
require_once 'required/campaigninfo.php';
require_once 'dropdown/dropdown.php';

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
elseif (!$cInfo = getCampaignInfo($db, $allCamp)){ // fetch camp info
    // invalid campaign
    closeDbAndGoTo($db, "searchcamp.php" . (isset($_SERVER['REQUEST_URI']) ? '?redir=' . urlencode($_SERVER['REQUEST_URI']) : ''));
}

// split year and semester
$cName = explode('.', $cInfo['Name']);

// get submitted data
$year = getPost('year');
$sem = getPost('sem');
$errMsg = null;

// check if updating
if (isset($year) || isset($sem)){

    // validate input
    if (!isNum($year) || +$year < 2000 || +$year > 2050){
        $errMsg = 'O ano não é válido.';
    }
    elseif ($sem != 1 && $sem != 2){
        $errMsg = 'O semestre não é válido.';
    }
    elseif (!!$db->query("SELECT 1 FROM campaigns WHERE `Year` = $year AND Semester = $sem")->fetch_row()[0]){
        $errMsg = 'A campanha de rematrícula ' . $year . '.' . $sem . ' já existe.';
    }
    else {

        // update
        if ($db->query("UPDATE campaigns SET `Year` = $year, Semester = $sem WHERE ID = " . $cInfo['ID'])){
            closeDbAndGoTo($db, "camp.php");
        }
        else {
            $errMsg = $db->error;
        }

    }

}
else {
    // not updating, get camp name from current camp
    $year = $cName[0];
    $sem = $cName[1];
}
    
?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Editar Campanha</title>
    
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
        
        function validateFields(y, s){
            
            if (element('selYear').selectedValue() == y && element('selSem').selectedValue() == s){
                element('divErrMsg').innerHTML = 'O valor não foi modificado.';
                return false;
            }
            
            return true;
            
        }
                        
    </script>
    
</head>
<body>
    
    <div id="divTop" class="top"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
        
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
            
            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action="editcamp.php">
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
        <div class="panel" style="width: 400px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Editar Campanha:</span>
            <hr/>

            <form id="form1" method="post" action="editcamp.php">
            
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Valor Atual:</td>
                    <td style="width: 100%;"><?php echo $cInfo['Name']; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Novo Valor:</td>
                    <td style="width: 100%;">
                        <select id="selYear" name="year" onchange="element('divErrMsg').innerHTML = '';">
<?php

if (!isset($year)) $year = date("Y");

for ($i = 2099; $i >= 2000; $i--){
    echo '<option value="' . $i . '"' . (+$year == $i ? ' selected="selected"' : '') . '>' . $i . '</option>' . PHP_EOL;
}

?>
                        </select>
                        .
                        <select id="selSem" name="sem" onchange="element('divErrMsg').innerHTML = '';">
                            <option value="1"<?php if ($sem == 1) echo ' selected="selected"'; ?>>1</option>
                            <option value="2"<?php if ($sem == 2) echo ' selected="selected"'; ?>>2</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td><input type="submit" value="Salvar" onclick="return validateFields(<?php echo $cName[0] . ', ' . $cName[1]; ?>);"/></td>
                </tr>
            </table>
            
            </form>
            
        </div>
        <br/>
        <div id="divErrMsg" style="color: red; width: 400px; left: 0; right: 0; margin: auto; font-style: italic;"><?php echo $errMsg; ?></div>

    </div>
    
</body>
</html>
<?php

$db->close();

?>