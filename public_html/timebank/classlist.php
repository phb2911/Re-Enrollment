<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once 'dropdown/dropdown.php';
require_once '../genreq/date_functions.php';
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

$tbid = getGet('tbid');
$isValid = false;

if (isNum($tbid) && $tbinfo = $db->query("SELECT `Year`, StartDate, EndDate FROM tb_banks WHERE ID = $tbid")->fetch_assoc()){
    
    $tbYear = $tbinfo['Year'];
    
    // convert dates to dd/mm/yyyy format
    $tbStartDate = formatDate($tbinfo['StartDate']);
    $tbEndDate = formatDate($tbinfo['EndDate']);
    
    // set flag
    $isValid = true;
    
}

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Time Bank - Lista de Turmas</title>
    
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
            
            if (element('selTB')) styleSelectBox(element('selTB'));
            
        };
        
        function redir(val){
            
            if (val > 0) window.location = 'classlist.php?tbid=' + val;
            
        }
        
    </script>
    
</head>
<body>
    
    <div class="top"></div>
    
    <div class="main">
        
        <a href="."><img style="display: block;" src="../images/banner3<?php echo ($isAdmin ? 'admin' : ''); ?>.jpg"/></a>
        
<?php

renderDropDown($db, $isAdmin);

if ($isValid){
    displayList($tbid, $tbYear, $tbStartDate, $tbEndDate);
}
else {
    selectTB();
}

?>

    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

//------------------------------------------

function displayList($tbid, $tbYear, $tbStartDate, $tbEndDate){
    
    global $db;
    
    $sb = getGet('sb');
    
?>
        <br/>
        <div class="panel">
            
            <span style="font-weight: bold;">Lista de Turmas</span>
            <hr/>
            
            <table class="tbl" style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Banco de Horas:</td>
                    <td style="width: 100%;"><?php echo '<span style="font-weight: bold;">' . $tbYear . '</span> <span style="font-style: italic;">(' . $tbStartDate . ' a ' . $tbEndDate . ')</span>'; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Ordenar por:</td>
                    <td style="width: 100%;">
                        <select onchange="window.location = 'classlist.php?tbid=<?php echo $tbid; ?>&sb=' + this.selectedValue();" onkeyup="window.location = 'classlist.php?tbid=<?php echo $tbid; ?>&sb=' + this.selectedValue();">
                            <option value="0"<?php if ($sb != 1) echo ' selected="selected"'; ?>>Turma</option>
                            <option value="1"<?php if ($sb == 1) echo ' selected="selected"'; ?>>Professor</option>
                        </select>
                    </td>
                </tr>
            </table>
            
<?php
    
    // build query string

    $q = "SELECT tb_classes.ID, tb_classes.Name, tb_classes.Semester, tb_classes.User, users.Name AS Teacher FROM tb_classes JOIN users ON tb_classes.User = users.ID WHERE tb_classes.Bank = $tbid ORDER BY ";

    if ($sb == 1){
        $q .= "Teacher, Semester, Name";
    }
    else {
        $q .= "Name, Semester";
    }

    $result = $db->query($q);
    
    if ($result->num_rows){
    
?>
            <table style="width: 100%; border: black solid 1px;">
                <tr style="background-color: #1b9a87; color: white;">
                    <td style="width: 45%;">Turma</td>
                    <td style="width: 45%;">Professor</td>
                    <td style="width: 10%; text-align: center;">Semestre</td>
                    <td>&nbsp;</td>
                </tr>
<?php
        
        $bgcolor = null;
        
        while ($row = $result->fetch_assoc()){

            $bgcolor = ($bgcolor == '#c1c1c1') ? '#f1f1f1' : '#c1c1c1';
            
?>
                <tr style="background-color: <?php echo $bgcolor; ?>;">
                    <td style="width: 45%;"><?php echo '<a href="classdetails.php?cid=' . $row['ID'] . '">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</a>'; ?></td>
                    <td style="width: 45%;"><?php echo '<a href=".?tbid=' . $tbid . '&uid=' . $row['User'] . '">' . htmlentities($row['Teacher'], 0, 'ISO-8859-1') . '</a>'; ?></td>
                    <td style="width: 10%; text-align: center;"><?php echo $tbYear . '.' . $row['Semester']; ?></td>
                    <td><a href="editclass.php?cid=<?php echo $row['ID']; ?>"><img src="../images/pencil1.png" title="Editar Turma" style="vertical-align: middle;"/></a></td>
                </tr>
<?php

        }
        
?>
            </table>
<?php
    
    }
    else {
        echo '<div style="font-style: italic; color: red; padding: 10px;">*Este Banco de Horas não possui turmas.</div>';
    }
    
    $result->close();
    
?>
            </table>
            
        </div>
<?php
    
}

//------------------------------------------

function selectTB(){
    
    global $db;
    
?>
        <br/>
        <div class="panel" style="width: 700px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Lista de Turmas</span>
            <hr/>
            
            <table class="tbl" style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Selecione o Banco de Horas:</td>
                    <td style="width: 100%;">
                        <select id="selTB" onchange="styleSelectBox(this); redir(this.selectedValue());" onkeyup="styleSelectBox(this); redir(this.selectedValue());">
                            <option value="0" style="font-style: italic;">- Selecione -</option>
                            <?php
                            
                            $result = $db->query("SELECT ID, `Year` FROM tb_banks ORDER BY `Year` DESC");
                            
                            while ($row = $result->fetch_assoc()){
                                echo '<option value="' . $row['ID'] . '" style="font-style: normal;">' . $row['Year'] . '</option>' . PHP_EOL;
                            }
                            
                            $result->close();
                            
                            ?>
                        </select>
                    </td>
                </tr>
            </table>
            
        </div>
<?php
    
}

?>