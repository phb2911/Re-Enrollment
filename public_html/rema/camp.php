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

// not logged in
if (!$loginObj->isLoggedIn()) closeDbAndGoTo($db, LOGIN_PAGE);

$isAdmin = $loginObj->isAdmin();

// not admin
if (!$isAdmin) closeDbAndGoTo($db, '.');

// fetch all campaigns and store into array
$allCamp = allCampaigns($db);

$cid = getPost('cid');
$sort = getGet('s');
$uid = $loginObj->userId;

// check if current campaign id submitted by select element
if (isNum($cid) && isset($allCamp[intval($cid, 10)])){
    $cInfo = $allCamp[intval($cid, 10)];
    // save current campaign id into cookie
    setcookie('curCampId', $cInfo['ID'], 0, '/', COOKIE_DOMAIN);
}
elseif ($cInfo = getCampaignInfo($db, $allCamp)){ // get current campaign info

    $cid = $cInfo['ID'];
    $act = getGet('act');
    
    // open/close campaing
    if (isset($act)){

        if ($act == 'c' && !!$cInfo['Open']){
            
            $db->query("CALL `spCloseCampaign`($cid, $uid, '" . date('Y-m-d H:i:s') . "')");
            
            closeDbAndGoTo($db, 'camp.php' . (isset($sort) ? '?s=' . $sort : ''));
            
        }
        elseif ($act == 'r' && !$cInfo['Open'] && !$db->query("SELECT COUNT(*) FROM campaigns WHERE Open = 1")->fetch_row()[0]){
            
            $db->query("UPDATE campaigns SET Open = 1 WHERE ID = $cid");
            
            closeDbAndGoTo($db, 'camp.php' . (isset($sort) ? '?s=' . $sort : ''));
            
        }

    }

}
else {
    // current campaing not valid
    closeDbAndGoTo($db, 'searchcamp.php');
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Campanhas</title>
    
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
            
            if (element('selCamp')) styleSelectBox(element('selCamp'));
            
        };
        
    </script>
    
</head>
<body>
    
    <div class="top" id="divTop"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
            
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>

            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action="camp.php">
                Campanha: &nbsp;
                <select name="cid" style="width: 100px; border-radius: 5px;" onchange="element('imgCampLoader').style.visibility = 'visible'; element('frmChangeCamp').submit();">
<?php

// create option
foreach ($allCamp as $cmp){
    echo '<option value="' . $cmp['ID'] . '"' . ($cmp['ID'] == $cid ? ' selected="selected"' : '') . ($allCamp[intval($cmp['ID'], 10)]['Open'] ? ' style="font-weight: bold;"' : '') . '>' . $cmp['Name'] . '</option>' . PHP_EOL;
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

showCampaign($cInfo, $sort);

?>
        
    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

//---------------------------

function showCampaign($campInfo, $sort){
    
    global $db;
    
    $cid = $campInfo['ID'];
    $campName = $campInfo['Name'];
    $isOpen = !!$campInfo['Open'];
    
?>

<br/>
<div class="panel">
    <span style="font-weight: bold;">Campanha de Rematrícula</span>
    <hr/>
    <table style="width: 100%;">
        <tr>
            <td style="white-space: nowrap; text-align: right;">Ano/Semester:</td>
            <td style="width: 1000px;"><?php echo $campName; ?></td>
        </tr>
        <tr>
            <td style="white-space: nowrap; text-align: right;">Situação:</td>
            <td style="width: 1000px;"><?php echo ($isOpen ? 'Aberta' : '<span style="color: red;">Encerrada</span>'); ?></td>
        </tr>
        <tr>
            <td style="white-space: nowrap;" colspan="2">
                <button onclick="window.location = 'editcamp.php';"><img src="<?php echo IMAGE_DIR; ?>pencil1.png"/> Editar Campanha</button>
<?php

    if ($isOpen){
        echo '<button onclick="if (confirm(\'A campanha ' . $campName . ' será encerrada. Tem certeza?\')) window.location = \'camp.php?act=c' . (isset($sort) ? '&s=' . $sort : '') . '\'"><img src="' . IMAGE_DIR . 'folder.png"/> Encerrar Campanha</button>' . PHP_EOL;
    }
    elseif (!!$db->query("SELECT 1 FROM campaigns WHERE Open = 1 LIMIT 1")->fetch_row()[0]){
        echo '<button disabled="disabled"><img src="' . IMAGE_DIR . 'folder3.png"/> Reabrir Campanha</button>' . PHP_EOL .
                '<div style="font-style: italic; color: red; padding-top: 3px;">* Esta campanha não pode ser reaberta pois há outra que está aberta.</div>';
    }
    else {
        echo '<button onclick="window.location = \'camp.php?act=r' . (isset($sort) ? '&s=' . $sort : '') . '\'"><img src="' . IMAGE_DIR . 'folder2.png"/> Reabrir Campanha</button>';
    }

?>
            </td>
        </tr>
    </table>
</div>
<?php
    
    $result = $db->query("SELECT ID, Name FROM subcamps WHERE Parent = $cid");
    
    if ($result->num_rows){
        
        echo '<br/><div class="panel">' . PHP_EOL . '<span style="font-weight: bold;">Subcampanhas</span><hr/>' . PHP_EOL . '<ul>' . PHP_EOL;
        
        while ($row = $result->fetch_assoc()){
            echo '<li><a href="subcamp.php?scampid=' . $row['ID'] . '">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</a></li>' . PHP_EOL;
        }
        
        echo '</ul>' . PHP_EOL . '</div>' . PHP_EOL;
    
    }
    
    $result->close();
    
    $result = $db->query("SELECT students.ID, students.Name, classes.User AS UserID, users.Name AS UserName "
            . "FROM classes JOIN students ON students.Class = classes.ID JOIN users ON classes.User = users.ID WHERE students.Flagged = 1 "
            . "AND classes.Campaign = $cid ORDER BY UserName, Name");
    
    if ($result->num_rows){
        
        echo '<br/><div class="panel">' . PHP_EOL . '<img src="' . IMAGE_DIR . 'flag.png"/> <span style="font-weight: bold;">Alunos Marcados</span><hr/>' . PHP_EOL . 
                '<table style="width: 100%; border: #61269e solid 1px; border-collapse: collapse;">' . PHP_EOL . 
                '<tr><td style="background-color: #61269e; color: white; width: 50%;">Aluno</td><td style="background-color: #61269e; color: white; width: 50%;">Professor</td></tr>' . PHP_EOL;
        
        $bgcolor = '';
        
        while ($row = $result->fetch_assoc()){
            $bgcolor = ($bgcolor == '#c6c6c6') ? '#ffffff' : '#c6c6c6';
            echo '<tr><td style="background-color: ' . $bgcolor . ';"><a href="editstudent.php?sid=' . $row['ID'] . '">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</a></td><td style="background-color: ' . $bgcolor . ';"><a href="user.php?uid=' . $row['UserID'] . '">' . htmlentities($row['UserName'], 0, 'ISO-8859-1') . '</a></td></tr>' . PHP_EOL;
        }
        
        echo '</table>' . PHP_EOL . '</div>' . PHP_EOL;
        
    }
    
    
    
    $result->close();
    
    // build query string
    $q = "SELECT classes.User AS ID, users.Name, EnrolledStudents(users.ID, $cid) AS Enrolled, "
            . "TotalStudents(users.ID, $cid) AS Total, PercentEnrolled(users.ID, $cid) AS Percentage, "
            . "SemesterContractCount(users.ID, $cid, 0) AS SemesterContracts, SemesterContractCount(users.ID, $cid, 1) AS SemContEnrolled, "
            . "SemContEnrPercent(users.ID, $cid) AS SemContEnrPerc FROM classes "
            . "JOIN users ON classes.User = users.ID WHERE classes.Campaign = $cid GROUP BY ID";
    
    // sort order
    if (isNum($sort) && $sort >= 1 && $sort <= 13){
        if ($sort == 1){
            $q .= " ORDER BY Name DESC, Total DESC";
        }
        elseif ($sort == 2){
            $q .= " ORDER BY Total DESC, Name";
        }
        elseif ($sort == 3){
            $q .= " ORDER BY Total, Name";
        }
        elseif ($sort == 4){
            $q .= " ORDER BY Enrolled DESC, Total DESC";
        }
        elseif ($sort == 5){
            $q .= " ORDER BY Enrolled, Total";
        }
        elseif ($sort == 6){
            $q .= " ORDER BY Percentage DESC, Total DESC";
        }
        elseif ($sort == 7){
            $q .= " ORDER BY Percentage, Total";
        }
        elseif ($sort == 8){
            $q .= " ORDER BY SemesterContracts DESC, Name";
        }
        elseif ($sort == 9){
            $q .= " ORDER BY SemesterContracts, Name";
        }
        elseif ($sort == 10){
            $q .= " ORDER BY SemContEnrolled DESC, SemesterContracts DESC";
        }
        elseif ($sort == 11){
            $q .= " ORDER BY SemContEnrolled, SemesterContracts";
        }
        elseif ($sort == 12){
            $q .= " ORDER BY SemContEnrPerc DESC, SemesterContracts DESC";
        }
        elseif ($sort == 13){
            $q .= " ORDER BY SemContEnrPerc, SemesterContracts";
        }
        
    }
    else {
        $q .= " ORDER BY Name, Total DESC";
        $sort = 0;
    }
    
    $result = $db->query($q);
    
    if ($result->num_rows){
?>
            <br/>
            <div class="panel">
                
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="font-weight: bold; width: 100%; padding: 0;">Resultados</td>
                        <td style="padding: 0;"><img src="<?php echo IMAGE_DIR; ?>print.png" style="vertical-align: middle; cursor: pointer;" onclick="window.open('printrept.php?t=1&cid=<?php echo $cid . '&s=' . $sort; ?>', '_blank', 'toolbar=no,scrollbars=yes,resizable=yes,width=840,height=600');"/></td>
                    </tr>
                </table>
                
                <hr/> 
                <table style="width: 100%; border: #61269e solid 1px; border-collapse: collapse;"> 
                    <tr style="background-color: #61269e; color: white; ">
                        <td style="width: 40%;"><?php createHeadderLink(0, $sort, 'Professor'); ?></td>
                        <td style="width: 10%; text-align: right; padding-right: 20px;"><?php createHeadderLink(1, $sort, 'Alunos'); ?></td>
                        <td style="width: 10%; text-align: right; padding-right: 20px;"><?php createHeadderLink(2, $sort, 'Rema'); ?></td>
                        <td style="width: 10%; text-align: right; padding-right: 20px;"><?php createHeadderLink(3, $sort, '%'); ?></td>
                        <td style="width: 10%; text-align: right; padding-right: 20px;"><?php createHeadderLink(4, $sort, 'Semestrais'); ?></td>
                        <td style="width: 10%; text-align: right; padding-right: 20px;"><?php createHeadderLink(5, $sort, 'Rema (sem.)'); ?></td>
                        <td style="width: 10%; text-align: right; padding-right: 20px;"><?php createHeadderLink(6, $sort, '% (sem.)'); ?></td>
                    </tr>
<?php

        $bgcolor = '';

        while ($row = $result->fetch_assoc()) {
            
            $bgcolor = ($bgcolor == '#c6c6c6') ? '#ffffff' : '#c6c6c6';
            
            echo '<tr style="background-color: ' . $bgcolor . ';"><td><a href="campbyuser.php?uid=' . $row['ID'] . '">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</a></td>' . PHP_EOL .
                '<td style="text-align: right; padding-right: 20px;">' . $row['Total'] . '</td>' . PHP_EOL .
                '<td style="text-align: right; padding-right: 20px;">' . $row['Enrolled'] . '</td>' . PHP_EOL .
                '<td style="text-align: right; padding-right: 20px;">' . +$row['Percentage'] . '%</td>' . PHP_EOL .
                '<td style="text-align: right; padding-right: 20px;">' . $row['SemesterContracts'] . '</td>' . PHP_EOL .
                '<td style="text-align: right; padding-right: 20px;">' . $row['SemContEnrolled'] . '</td>' . PHP_EOL .
                '<td style="text-align: right; padding-right: 20px;">' . +$row['SemContEnrPerc'] . '%</td></tr>' . PHP_EOL;
            
        }
?>
                </table>
            </div>
<?php        
        
    }
    
    $result->close();

}

//------------------------------------------------

function createHeadderLink($index, $sort, $text){
    
    $index *= 2;
    
    echo '<a href="camp.php?s=' . ($sort == $index ? strval($index + 1) : strval($index)) . '" style="color: white;">' . $text;
    
    if ($sort == $index || $sort == ($index + 1)) echo '<img src="' . IMAGE_DIR . 'sort_' . ($sort == $index ? 'up' : 'down') . '.png" style="vertical-align: top;"/>';
    
    echo '</a>';
    
}

?>