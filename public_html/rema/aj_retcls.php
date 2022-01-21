<?php

require_once '../genreq/ajax.php';
require_once '../genreq/genreq.php';

header('Content-type: text/html; charset=iso-8859-1');

$db = mysqliConnObj('latin1');

if ($db->connect_errno > 0) die();

$cid = getPost('cid');
$uid = getPost('uid');

echo '<option value="0" style="font-style: italic;">- Selecione -</option>';

if (isNum($cid) && isNum($uid)){
    
    $result = $db->query("SELECT classes.ID, classes.Name, schools.Name AS School FROM classes JOIN schools ON classes.School = schools.ID WHERE classes.User = $uid AND classes.Campaign = $cid ORDER BY Name");
    
    while ($row = $result->fetch_assoc()){
        echo '<option value="' . $row['ID'] . '" style="font-style: normal;">' . htmlentities($row['Name'], 3, 'ISO-8859-1') . ' (' . htmlentities($row['School'], 3, 'ISO-8859-1') . ')</option>';
    }

    $result->close();
    
}

$db->close();

?>