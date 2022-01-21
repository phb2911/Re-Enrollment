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

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Time Bank - Lista de Bancos de Horas</title>
    
    <link rel="icon" href="../images/favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="../js/general.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 5px;
            text-align: center;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
        };
        
    </script>
    
</head>
<body>
    
    <div class="top"></div>
    
    <div class="main">
        
        <a href="."><img style="display: block;" src="../images/banner3<?php echo ($isAdmin ? 'admin' : ''); ?>.jpg"/></a>
        
<?php

renderDropDown($db, $isAdmin);

$result = $db->query("SELECT * FROM tb_banks ORDER BY Year DESC");

if ($result->num_rows){
?>
        <br/>
        <div class="panel" style="width: 700px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Lista de Bancos de Horas</span>
            <hr/>
        
            <table style="width: 100%; border: black solid 1px; border-collapse: collapse;">
                <tr style="background-color: #1b9a87; color: white">
                    <td style="width: 25%;">Banco de Horas:</td>
                    <td style="width: 25%;">Início</td>
                    <td style="width: 25%;">Término</td>
                    <td style="width: 25%;">Situação</td>
                    <?php if ($isAdmin) echo '<td></td>'; ?>
                </tr>
<?php

    $bgcolor  = null;

    while ($row = $result->fetch_assoc()){
        
        $bgcolor = ($bgcolor == '#e1e1e1') ? '#ffffff' : '#e1e1e1';
?>
                <tr style="background-color: <?php echo $bgcolor; ?>;">
                    <td><?php echo '<a href=".?tbid=' . $row['ID'] . '">' . $row['Year'] . '</a>'; ?></td>
                    <td><?php echo formatDate($row['StartDate']); ?></td>
                    <td><?php echo formatDate($row['EndDate']); ?></td>
                    <td><?php echo (!!$row['Active'] ? 'Ativo' : 'Encerrado'); ?></td>
                    <?php if ($isAdmin) echo '<td><img src="../images/pencil1.png" style="cursor: pointer;" title="Editar" onclick="window.location = \'editbank.php?tbid=' . $row['ID'] . '\';"/></td>'; ?>
                </tr>
<?php
    }
?>
            </table>
            <span style="font-style: italic; color: red; font-size: 13px;">*Click no Banco de Horas desejado para visualizar os detalhes.</span>
        </div>
<?php
}
else {
?>
        <br/>
        <div style="width: 500px; left: 0; right: 0; margin: auto; background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
            <span style="color: red; font-style: italic;">Nenhum Banco de Horas foi encontrado.<?php if ($isAdmin) echo ' Clique <a href="newbank.php">aqui</a> para criar um.'; ?></span>
        </div>
<?php
}

$result->close();

?>

    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

?>