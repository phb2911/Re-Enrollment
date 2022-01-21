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
    closeDbAndGoTo($db, "searchcamp.php" . (isset($_SERVER['REQUEST_URI']) ? '?redir=' . urlencode($_SERVER['REQUEST_URI']) : ''));
}

$subCampID = getPost('scampid');
$isValid = false;

// retrieve subcampaing info
if (isNum($subCampID) && 
        $row = $db->query("SELECT subcamps.*, campaignName(campaigns.ID) AS CampName, campaigns.Open AS CampOpen FROM subcamps LEFT JOIN campaigns ON subcamps.Parent = campaigns.ID WHERE subcamps.ID = $subCampID")->fetch_assoc()){
    
    // check if selected subcampaign belongs to current campaign
    if ($cInfo['ID'] == $row['Parent']){
    
        $campName = $row['CampName'];
        $campIsOpen = !!$row['CampOpen'];
        $subCampName = $row['Name'];
        $subCampIsOpen = !!$row['Open'];
        $inclDropOuts = !!$row['IncludeDropouts'];

        $isValid = true;
        
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
        
        table.outterTable {
            border-collapse: collapse;
        }
        
        table.outterTable td {
            padding: 0;
        }
        
        table.innerTable td {
            padding: 5px;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
            element('divTop').style.height = element('divHeadder').offsetHeight + 'px';
            
            if (element('selSubCamp')) styleSelectBox(element('selSubCamp'));
            
            element('imgUpArrow').style.visibility = (window.pageYOffset < 15 ? 'hidden' : 'visible');
            
        };
        
        window.onscroll = function(){
            element('imgUpArrow').style.visibility = (window.pageYOffset < 15 ? 'hidden' : 'visible');
        };
        
        function printableVersion(SubCampID){
            window.open('printrept.php?t=6&scid=' + SubCampID, '_blank', 'toolbar=no,scrollbars=yes,resizable=yes,width=840,height=600');
        }
                       
    </script>
    
</head>
<body>
    
    <div class="top" id="divTop"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
        
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
            
            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action="subcamprpt.php">
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
        <div class="panel" style="width: 800px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Subcampanha</span>
            <hr/>
            
            <table class="outterTable">
                <tr>
                    <td style="width: 100%;">
                        <table class="innerTable">
                            <tr>
                                <td style="text-align: right;">Campanha:</td>
                                <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($campName, 0, 'ISO-8859-1'); ?></td>
                            </tr>
                            <tr>
                                <td style="text-align: right; white-space: nowrap;">Subcampanha:</td>
                                <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($subCampName, 0, 'ISO-8859-1'); ?></td>
                            </tr>
            
<?php
    
    if (!$subCampIsOpen) {
        echo '<tr><td style="font-style: italic; color: red;" colspan="2">Esta subcampanha está encerrada.</span></td></tr>';
    }
    elseif (!$campIsOpen) {
        echo '<tr><td style="font-style: italic; color: red; padding-top: 5px;" colspan="2">A campanha ' . htmlentities($campName, 0, 'ISO-8859-1') . ' está encerrada. Esta subcampanha não pode ser editada.</td></tr>';
    }
    
?>
                        </table>
                    </td>
                    <td style="vertical-align: bottom;">
                        <img src="<?php echo IMAGE_DIR; ?>print.png" title="Imprimir Resultados" style="vertical-align: bottom; cursor: pointer;" onclick="printableVersion(<?php echo $subCampID; ?>);"/>
                    </td>
                </tr>
            </table>

        </div>
<?php

    if ($subCampIsOpen){
        
        $do = ($inclDropOuts ? '1' : '0');
        
        $q = "SELECT users.Name, TotalFromOpenSubCamp($subCampID, users.ID, $do) AS Total, "
                . "EnrolledFromOpenSubCamp($subCampID, users.ID, $do) AS Enrolled, "
                . "PercentFromOpenSubCamp($subCampID, users.ID, $do) AS Percentage FROM users HAVING Total > 0 ORDER BY Percentage DESC, Total DESC";
      
    }
    else {
        
        $q = "SELECT users.Name, subcamp_results.Student_Count AS Total, subcamp_results.Enrolled_Count AS Enrolled, "
                . "(CASE subcamp_results.Student_Count WHEN 0 THEN 0 ELSE (subcamp_results.Enrolled_Count * 100 / subcamp_results.Student_Count) END) AS Percentage "
                . "FROM subcamp_results JOIN users ON subcamp_results.User = users.ID WHERE subcamp_results.SubCamp = $subCampID ORDER BY Percentage DESC, Total DESC";
        
    }
    
    $result = $db->query($q);

    if ($result->num_rows) {
            
?>
        <br/>
        <div class="panel" style="width: 800px; left: 0; right: 0; margin: auto;">
            
                <span style="font-weight: bold;">Resultados</span>
                <hr/>

            <table style="border-collapse: collapse; border: #61269e solid 1px; width: 100%;">
                <tr style="background-color: #61269e; color: white;">
                    <td style="width: 50%;">Professor</td>
                    <td style="width: 20%; text-align: center;">Total de Alunos</td>
                    <td style="width: 20%; text-align: center;">Rematriculados</td>
                    <td style="width: 10%; text-align: center;">%</td>
                </tr>
<?php
        $bgcolor = null;
        
        while ($row = $result->fetch_assoc()) {

            $bgcolor = ($bgcolor == '#c6c6c6') ? '#ffffff' : '#c6c6c6';

?>
                <tr style="background-color: <?php echo $bgcolor; ?>;">
                    <td><?php echo htmlentities($row['Name'], 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><?php echo $row['Total']; ?></td>
                    <td style="text-align: center;"><?php echo $row['Enrolled']; ?></td>
                    <td style="text-align: center;"><?php echo +number_format($row['Percentage'], 2); ?>%</td>
                </tr>
<?php
                
        }
            
?>
            </table>
        </div>
<?php
            
    }
    else {
        echo '<div style="width: 800px; left: 0; right: 0; margin: auto; font-style: italic; color: red; padding: 10px;">Não há resultados associados à esta subcampanha.</div>';
    }

    $result->close();
    
}
else {
    //echo '<span style="font-style: italic; color: red;">Parametros inválidos.</span>' . PHP_EOL;
    selectSubCamp($cInfo);
}
?> 
    </div>
    
    <p>&nbsp;</p>
    
    <img id="imgUpArrow" src="<?php echo IMAGE_DIR; ?>arrow_up.png" style="position: fixed; right: 20px; bottom: 20px; cursor: pointer; visibility: hidden;" onclick="document.body.scrollTop = document.documentElement.scrollTop = 0;" title="Topo da página"/>
</body>
</html>
<?php

$db->close();

//----------------------------------------------

function selectSubCamp($cInfo){
    
    global $db;
    
    // fetch subcampaigns
    $result = $db->query("SELECT ID, Name FROM subcamps WHERE Parent = " . $cInfo['ID'] . " ORDER BY Name");
    
?>
        <br/>
        <div class="panel" style="width: 600px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Selecione a Subcampanha</span>
            <hr/>
            <form id="frmSelSubCamp" method="post" action="subcamprpt.php">
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $cInfo['Name']; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Subcampanha:</td>
                    <td style="width: 100%;">
                        <select id="selSubCamp" name="scampid" style="width: 250px;"<?php if (!$result->num_rows) echo ' disabled="disabled"'; ?> onchange="styleSelectBox(this); if (this.selectedIndex) element('frmSelSubCamp').submit();" onkeyup="styleSelectBox(this); if (this.selectedIndex) element('frmSelSubCamp').submit();">
                            <option value="0" style="font-style: italic;">- Selecione -</option>
<?php
        while ($row = $result->fetch_assoc()){
            echo '<option value="' . $row['ID'] . '" style="font-style: normal;">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>';
        }
?>
                        </select>
                        <img id="imgSubCampCir" src="<?php echo IMAGE_DIR; ?>circle_loader.gif" style="vertical-align: middle; visibility: hidden;"/>
                    </td>
                </tr>
            </table>
            </form>
<?php
if (!$result->num_rows) echo '<span style="color: red; font-style: italic">* Esta campanha não contém subcampanhas.</span>';
?>
        </div>
<?php

    $result->close();
    
}

?>