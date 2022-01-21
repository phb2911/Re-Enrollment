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

$usrId = null;
$campId = null;
$searchQuery = null;
$orFlag = null;

if (isset($_POST['stdname'])){
    $searchQuery = getPost('stdname');
    $campId = getPost('camp');
    $usrId = getPost('user');
}
else {
    $campId = $cInfo['ID'];
}

// set search type flag
$searchByWord = (getPost('searchType') == 'word');

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Rema - Buscar Aluno</title>
    
    <link rel="icon" href="<?php echo IMAGE_DIR; ?>favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_DIR; ?>js/general.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
        span.spLink {
            color: blue;
            cursor: pointer;
        }
        
        span.spLink:hover {
            text-decoration: underline;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
            
            element('divTop').style.height = element('divHeadder').offsetHeight + 'px';
            
        };
        
        function validateQuery(){
            
            if (!element('txtStdName').value.trim().length){
                alert('Por favor digite o nome do aluno.');
                return false;
            }
            
            return true;
            
        }
        
<?php if ($isAdmin) { ?>
        function campRedir(cid, uid){
            
            var frm = document.createElement('form');
            frm.method = 'post';
            frm.action = 'campbyuser.php?uid=' + uid;
            
            document.body.appendChild(frm);
            
            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'cid';
            inp.value = cid;
            
            frm.appendChild(inp);
            frm.submit();
            
        }
<?php } else { ?>
        function campRedir(cid){
            
            var frm = document.createElement('form');
            frm.method = 'post';
            frm.action = '.';
            
            document.body.appendChild(frm);
            
            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'cid';
            inp.value = cid;
            
            frm.appendChild(inp);
            frm.submit();
            
        }
<?php } ?>
                        
    </script>
    
</head>
<body>
    
    <div class="top" id="divTop"></div>
    
    <div class="main">
        
        <div id="divHeadder" style="background-color: #61269e;">
        
            <a href="."><img style="display: block; width: 800px; height: 110px;" src="<?php echo IMAGE_DIR . 'banner' . ($isAdmin ? 'admin' : '') . '.jpg'; ?>"/></a>
        
            <div style="color: white; padding: 5px 0 5px 10px;">
                <form id="frmChangeCamp" method="post" action="searchstudent.php">
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
        
        <div class="panel">
            
            <span style="font-weight: bold;">Buscar Aluno</span>
            <hr/>
            <form id="form1" action="searchstudent.php" method="post" onsubmit="return validateQuery();">
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right;">Campanha:</td>
                    <td style="width: 100%;">
                        <select id="selCamp" name="camp" style="width: 250px;">
                            <option value="0">Todas</option>
<?php

$result = $db->query("SELECT ID, campaignName(ID) AS Campaign FROM campaigns ORDER BY Open DESC, Campaign DESC");

while ($row = $result->fetch_assoc()){
    echo '<option value="' . $row['ID'] . '"' . ($campId == $row['ID'] ? ' selected="selected"' : '') . '>' . $row['Campaign'] . '</option>' . PHP_EOL;
}

$result->close();

?>
                        </select>
                    </td>
                </tr>
<?php

if ($isAdmin){
    echo '<tr><td style="text-align: right;">Professor:</td>' . PHP_EOL . '<td style="width: 100%;">' . PHP_EOL .
            '<select id="selUse" name="user" style="width: 250px;">' . PHP_EOL . '<option value="0">Todos</option>' . PHP_EOL;
    
    $q = "SELECT ID, Name FROM users WHERE Blocked = 0 AND (Status < 2";
    
    // get users that are admin but own classes
    $result = $db->query("SELECT classes.User FROM classes LEFT JOIN users ON classes.User = users.ID WHERE users.Status = 2 AND users.Blocked = 0 GROUP BY classes.User");
    
    while ($row = $result->fetch_row()){
        $q .= " OR ID = " . $row[0];
    }
    
    $result->close();
    
    $q .= ") ORDER BY Name";
    
    $result = $db->query($q);
    
    while ($row = $result->fetch_assoc()){
        echo '<option value="' . $row['ID'] . '"' . ($usrId == $row['ID'] ? ' selected="selected"' : '') . '>' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
    }
    
    $result->close();
    
    echo '</select>' . PHP_EOL . '</td></tr>' . PHP_EOL;
    
}

?>
                <tr>
                    <td style="text-align: right;">Nome:</td>
                    <td style="width: 100%;">
                        <input type="text" id="txtStdName" name="stdname" value="<?php echo htmlentities($searchQuery, 3, 'ISO-8859-1'); ?>" style="width: 370px;"/>
                        <img src="<?php echo IMAGE_DIR; ?>search.png" style="width: 24px; height: 24px; vertical-align: bottom; cursor: pointer;" onclick="if (validateQuery()) element('form1').submit();"/><br/>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td style="padding-top: 0;">
                        <input type="radio" id="radFull" name="searchType" value="full"<?php if (!$searchByWord) echo ' checked="checked"'; ?>/><label for="radFull"> Pesquisar por nome completo</label><br/>
                        <input type="radio" id="radWord" name="searchType" value="word"<?php if ($searchByWord) echo ' checked="checked"'; ?>/><label for="radWord"> Pesquisar por palavra</label>
                    </td>
                </tr>
            </table>     
            </form>
        </div>
<?php

if (isset($searchQuery)){
    
    // remove excess white spaces
    $searchQuery = sTrim($searchQuery); // from genreq.php
    
    // if search by work, split string by white spaces
    // if search by sentence, don't split.
    if ($searchByWord){
        $pieces = explode(' ', $searchQuery);
    }
    else {
        $pieces = array($searchQuery);
    }
    
    echo '<br/><div class="panel">' . PHP_EOL;
    
    // join students and dropouts tables
    $q = "SELECT students.ID, students.Name, users.Name AS Teacher, users.ID as TeacherID, " .
            "classes.ID AS ClassID, classes.Name AS ClassName, campaigns.ID AS CampID, " .
            "campaignName(campaigns.ID) AS Campaign, (SELECT Name FROM schools WHERE ID = classes.School) AS School " . 
            "FROM students LEFT JOIN classes ON students.Class = classes.ID " .
            "LEFT JOIN users ON classes.User = users.ID " .
            "LEFT JOIN campaigns ON classes.Campaign = campaigns.ID " . 
            "WHERE (";
    
    // add one LIKE statemente for each piece
    foreach ($pieces as $piece) {
        
        // if more than one piece found, add an OR
        if ($orFlag) $q .= " OR ";
        
        $q .= "students.Name LIKE '%" . $db->real_escape_string($piece) . "%'";
        
        $orFlag = true;
        
    }
    
    // close LIKE statements
    $q .= ") ";
    
    // specific campaign
    if (isset($campId) && isNum($campId) && +$campId > 0){
        $q .= "AND campaigns.ID = $campId ";
    }
    
    // specific user
    if (!$isAdmin){
        $q .= "AND users.ID = " . $loginObj->userId . " ";
    }
    elseif (isNum($usrId) && +$usrId > 0){
        $q .= "AND users.ID = $usrId ";
    }
    
    if (count($pieces) === 1) {
        $q .= "ORDER BY LOCATE('" . $db->real_escape_string($searchQuery) . "', students.Name) = 1 DESC, students.Name";
    }
    else {
    
        // order by best match
        $q .= "ORDER BY CASE WHEN students.Name LIKE '" . $db->real_escape_string($searchQuery) . "%' THEN 0";
        $count = 1;

        foreach ($pieces as $piece) {
            $q .= " WHEN students.Name LIKE '" . $db->real_escape_string($piece) . "%' THEN $count";
            $count++;
        }
        
        $q .= " WHEN students.Name LIKE '%" . $db->real_escape_string($searchQuery) . "%' THEN $count";
        $count++;

        foreach ($pieces as $piece) {
            $q .= " WHEN students.Name LIKE '%" . $db->real_escape_string($piece) . "%' THEN $count";
            $count++;
        }

        $q .= " END, students.Name";
    
    }
    
    $result = $db->query($q);
    
    echo '<div style="font-weight: bold; text-align: center; font-size: 12px;">' . $result->num_rows . ' resultado(s) encontrdo(s).</div>' . PHP_EOL;
    
    if ($result->num_rows){
        
        if ($isAdmin){
            echo '<table style="width: 100%; border-collapse: collapse;"><tr><td style="background-color: #61269e; color: white; width: 30%;">Aluno</td>' .
                    '<td style="background-color: #61269e; color: white; width: 30%;">Professor</td>' .
                '<td style="background-color: #61269e; color: white; width: 25%;">Classe</td>' .
                '<td style="background-color: #61269e; color: white; text-align: center; width: 15%;">Campanha</td></tr>' . PHP_EOL;
        }
        else {
            echo '<table style="width: 100%; border-collapse: collapse;"><tr><td style="background-color: #61269e; color: white; width: 45%;">Aluno</td>' .
                '<td style="background-color: #61269e; color: white; width: 40%;">Classe</td>' .
                '<td style="background-color: #61269e; color: white; text-align: center; width: 15%;">Campanha</td></tr>' . PHP_EOL;
        }
        
        $bgcolor = '';
        
        while ($row = $result->fetch_assoc()){

            $bgcolor = ($bgcolor == '#d6d6d6') ? '#ffffff' : '#d6d6d6';

            echo '<tr><td style="background-color: ' . $bgcolor . ';"><a href="student.php?sid=' . $row['ID'] . (is_null($row['ClassID']) ? '&do=1' : '') . '">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</a></td>' . PHP_EOL;
            
            if ($isAdmin){
                echo '<td style="background-color: ' . $bgcolor . ';"><a href="user.php?uid=' . $row['TeacherID'] . '">' . htmlentities($row['Teacher'], 0, 'ISO-8859-1') . '</a></td>' . PHP_EOL;
            }
            
            echo '<td style="background-color: ' . $bgcolor . ';"><a href="' . (is_null($row['ClassID']) ? 'dropouts.php?uid=' . $row['TeacherID'] . '&cid=' . $row['CampID'] : 'class.php?clsid=' . $row['ClassID']) . '">' . htmlentities($row['ClassName'], 0, 'ISO-8859-1') . ' (' . htmlentities($row['School'], 0, 'ISO-8859-1') . ')</a></td>'  . PHP_EOL .
                '<td style="background-color: ' . $bgcolor . '; text-align: center;">';
            
            if ($isAdmin){
                echo '<span class="spLink" onclick="campRedir(' . $row['CampID'] . ', ' . $row['TeacherID'] . ');">' . $row['Campaign'] . '</span>';
            }
            else {
                echo '<span class="spLink" onclick="campRedir(' . $row['CampID'] . ');">' . $row['Campaign'] . '</span>';
            }
            
            echo '</td></tr>' . PHP_EOL;

        }
        
        echo '</table>' . PHP_EOL;
    
    }
    
    //$result->close();
    
    echo '</div>' . PHP_EOL . '<p>&nbsp;</p>' . PHP_EOL;
} 

?>
    </div>
    
</body>
</html>
<?php

$db->close();

?>