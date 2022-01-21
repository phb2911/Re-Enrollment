<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once 'dropdown/dropdown.php';
require_once '../genreq/genreq.php';

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

if (!$loginObj->isLoggedIn()){
    $db->close();
    header("Location: " . LOGIN_PAGE);
    die();
}

$isAdmin = $loginObj->isAdmin();

if (!$isAdmin){
    $db->close();
    header("Location: .");
    die();
}

$cls = trim(getPost('class'));
$uid = getPost('uid');
$tbid = getPost('bank');

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Time Bank - Buscar Turma</title>
    
    <link rel="icon" href="../images/favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="../js/general.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
        };
        
        function validateInput(){
            
            if (!element('txtClass').value.trim().length){
                alert('Por favor insira o nome da turma.');
                return false;
            }
            
            return true;
            
        }
        
    </script>
    
</head>
<body>
    
    <div class="top"></div>
    
    <div class="main">
        
        <a href="."><img style="display: block;" src="../images/banner3<?php echo ($isAdmin ? 'admin' : ''); ?>.jpg"/></a>
        
<?php

renderDropDown($db, $isAdmin);

?>
        <br/>
        <div class="panel">
            
            <span style="font-weight: bold;">Buscar Turma</span>
            <hr/>
            
            <form action="classsearch.php" method="post">
            <table class="tbl" style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Banco de Horas:</td>
                    <td style="width: 100%;">
                        <select id="selTB" name="bank" style="width: 250px;">
                            <option value="0">Todos</option>
                    <?php
                        
                    $result = $db->query("SELECT ID, `Year` FROM tb_banks ORDER BY `Year` DESC");
                            
                    while ($row = $result->fetch_assoc()){
                        echo '<option value="' . $row['ID'] . '"' . ($tbid == $row['ID'] ? ' selected="selected"' : '') . '>' . $row['Year'] . '</option>' . PHP_EOL;
                    }

                    $result->close();
                    
                    ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Professor:</td>
                    <td style="width: 100%;">
                        <select id="selTeacher" name="uid" style="width: 250px;">
                            <option value="0">Todos</option>
                    <?php
                        
                    $result = $db->query("SELECT ID, Name FROM users WHERE Status <= 1 AND Blocked = 0 ORDER BY Name");
                            
                    while ($row = $result->fetch_assoc()){
                        echo '<option value="' . $row['ID'] . '"' . ($uid == $row['ID'] ? ' selected="selected"' : '') . '>' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
                    }

                    $result->close();
                    
                    ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Nome da Turma:</td>
                    <td style="width: 100%;">
                        <input type="text" id="txtClass" name="class" value="<?php echo htmlentities($cls, 3, 'ISO-8859-1'); ?>" style="width: 350px;" />
                        <input type="submit" value="Buscar" onclick="return validateInput();"/>
                    </td>
                </tr>
            </table>
            </form>
            
        </div>
<?php

if (strlen($cls)){
    
    $q = "SELECT tb_classes.ID, tb_classes.Name, tb_classes.Semester, tb_banks.Year, tb_classes.User, users.Name AS Teacher FROM " .
            "tb_classes JOIN users ON tb_classes.User = users.ID JOIN tb_banks ON tb_classes.Bank = tb_banks.ID WHERE tb_classes.Name LIKE '%" . 
            $db->real_escape_string($cls) . "%'";
    
    if (preg_match('/^([0-9])+$/', $tbid) && $tbid > 0){
        $q .= " AND tb_classes.Bank = $tbid";
    }
    
    if (preg_match('/^([0-9])+$/', $uid) && $uid > 0){
        $q .= " AND tb_classes.User = $uid";
    }
    
    echo '<br/><div class="panel">';
    
    $result = $db->query($q);
    
    $numRows = $result->num_rows;
    
    echo '<div style="font-size: 12px; font-weight: bold; text-align: center;">' . $numRows . ' resultado(s) encontrado(s).</div>';
    
    if ($numRows){
        
?>
            <table style="width: 100%; border: black solid 1px;">
                <tr style="background-color: #1b9a87; color: white;">
                    <td style="width: 45%;">Turma</td>
                    <td style="width: 45%;">Professor</td>
                    <td style="width: 10%; text-align: center;">Semestre</td>
                </tr>
<?php
            $bgcolor = null;
            
            while ($row = $result->fetch_assoc()){
                
                $bgcolor = ($bgcolor == '#c1c1c1') ? '#f1f1f1' : '#c1c1c1';
            
?>
                <tr style="background-color: <?php echo $bgcolor; ?>;">
                    <td style="width: 45%;"><?php echo '<a href="classdetails.php?cid=' . $row['ID'] . '">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</a>'; ?></td>
                    <td style="width: 45%;"><?php echo '<a href=".?tbid=' . $tbid . '&uid=' . $row['User'] . '">' . htmlentities($row['Teacher'], 0, 'ISO-8859-1') . '</a>'; ?></td>
                    <td style="width: 10%; text-align: center;"><?php echo $row['Year'] . '.' . $row['Semester']; ?></td>
                </tr>
<?php

            }
            
?>
            </table>
<?php
        
    }
    
    $result->close();
    
    echo '</div>';
    
}

?>

    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

?>