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

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Time Bank - Detalhes da Turma</title>
    
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
        
    </script>
    
</head>
<body>
    
    <div class="top"></div>
    
    <div class="main">
        
        <a href="."><img style="display: block;" src="../images/banner3<?php echo ($isAdmin ? 'admin' : ''); ?>.jpg"/></a>
        
<?php

renderDropDown($db, $isAdmin);

$cid = getGet('cid');

// check if class id is valid
if (isNum($cid) && $clsInfo = $db->query("SELECT tb_classes.*, users.Name AS Teacher, tb_banks.Year, tb_banks.StartDate AS TB_Start, tb_banks.EndDate AS TB_End FROM tb_classes LEFT JOIN users ON tb_classes.User = users.ID LEFT JOIN tb_banks ON tb_classes.Bank = tb_banks.ID WHERE tb_classes.ID = $cid")->fetch_assoc()){
    
?>
        <br/>
        <div class="panel" style="width: 700px; left: 0; right: 0; margin: auto;">
    
            <span style="font-weight: bold;">Detalhes da Turma</span>
            <hr/>
            
            <table class="tbl" style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Banco de Horas:</td>
                    <td style="width: 100%;">
                    <?php
                        echo '<span style="font-weight: bold;">' . $clsInfo['Year'] . '</span> <span style="font-style: italic;">(' . formatLiteralDate($clsInfo['TB_Start']) . ' a ' . formatLiteralDate($clsInfo['TB_End']) . ')</span>';
                    ?>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Professor:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo '<a href=".?tbid=' . $clsInfo['Bank'] . '&uid=' . $clsInfo['User'] . '">' . htmlentities($clsInfo['Teacher'], 0, 'ISO-8859-1') . '</a>'; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Nome:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($clsInfo['Name'], 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Semestre:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $clsInfo['Year'] . '.' . $clsInfo['Semester']; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Período Pago:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo formatLiteralDate($clsInfo['StartDate']) . ' <span style="font-weight: normal;">a</span> ' . formatLiteralDate($clsInfo['EndDate']); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Período de Aula:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo formatLiteralDate($clsInfo['StartClass']) . ' <span style="font-weight: normal;">a</span> ' . formatLiteralDate($clsInfo['EndClass']); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Dias da Semana:</td>
                    <td style="width: 100%; font-weight: bold;">
                    <?php
                        
                        $days = intval($clsInfo['Days'], 10);
                        $numDays = 0;
                        $sl = false;
                        
                        $daysArr = array(1 => 'Sab', 2 => 'Sex', 4 => 'Qui', 8 => 'Qua', 16 => 'Ter', 32 => 'Seg', 64 => 'Dom');
                        
                        for ($i = 64; $i >= 1; $i /= 2){
                            
                            if ($days >= $i){
                                
                                if ($sl) echo '/';
                                echo $daysArr[$i];
                                
                                $days -= $i;
                                $sl = true;
                                $numDays++;
                                
                            }
                            
                        }
                        
                    ?>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Duração da Aula:</td>
                    <td style="width: 100%; font-weight: bold;"><?php 
                    
                        $dur = intval($clsInfo['Duration'], 10);
                        
                        echo numToTime($dur);
                        
                        if ($numDays > 1){
                            $dur *= $numDays;
                            echo ' <span style="font-weight: normal; font-style: italic;">(' . numToTime($dur) . ' semanal)</span>';
                        }
                    
                    ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Crédito por Aula:</td>
                    <td style="width: 100%; font-weight: bold;"><?php 
                    
                        $exc = intval($clsInfo['ExcessMinutes'], 10);
                    
                        echo $exc . ($exc > 0 ? ' min' : '');

                        if ($numDays > 1 && $exc > 0){
                            $exc *= $numDays;
                            echo ' <span style="font-weight: normal; font-style: italic;">(' . $exc . ' min semanal)</span>';
                        }
                    
                    ?></td>
                </tr>
                <tr>
                    <td style="width: 100%; font-weight: bold;" colspan="2">
                        <button type="button" onclick="window.location = 'editclass.php?cid=<?php echo $cid; ?>';"><img src="../images/pencil1.png"/> Editar Turma</button>
                    </td>
                </tr>
            </table>
            
        </div>
<?php
    
}
else {
    // class id not valid
    echo '<br/><div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative; color: red; font-style: italic;">ID inválida.</div>';
}

?>

    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

?>