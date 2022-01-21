<?php

function displayStudents(&$db, $cid, $campName, $uid, $userName){
    
?>
        <br/>
        <div class="panel" style="width: 1000px; left: 0; right: 0; margin: auto;">
            <span style="font-weight: bold;">Gerenciamento de Contratos Anuais <img src="<?php echo IMAGE_DIR; ?>question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox();"/></span>
            <hr/>
            
            <table>
                <tr>
                    <td style="text-align: right;">Colaborador:</td>
                    <td style="width: 100%; font-weight: bold;">
                        <?php echo htmlentities($userName, 0, 'ISO-8859-1'); ?> &nbsp; 
                        <img src="<?php echo IMAGE_DIR; ?>person.png" title="Modificar Professor" style="cursor: pointer;" onclick="window.location = 'manageyrcont.php';"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $campName; ?></td>
                </tr>
            </table>
<?php

    $q = "SELECT `students`.`ID`, `students`.`Name`, students.`YearlyContract`, " .
         "CASE WHEN `students`.`Situation` = 0 THEN 'Ativo' WHEN `students`.`Situation` = 1 THEN 'Evadido' " .
         "WHEN `students`.`Situation` = 2 THEN 'Cancelado' WHEN `students`.`Situation` = 3 THEN 'Concluinte' " .
         "ELSE NULL END AS Situation, `students`.`Status`, classes.`Name` AS Class, " .
         "`schools`.`Name` AS School FROM students JOIN `classes` ON " .
         "`students`.`Class` = `classes`.`ID` JOIN `schools` ON `classes`.`School` = `schools`.`ID` " .
         "WHERE `classes`.`User` = $uid AND `classes`.`Campaign` = $cid ORDER BY `Name`";
    
    $result = $db->query($q);
    
    if ($result->num_rows){
?>
            <form method="post" action="manageyrcont.php">
            <table class="tbl">
                <tr style="background-color: #61269e; color: white;">
                    <td><img src="<?php echo IMAGE_DIR; ?>cal1.png" style="width: 16px; height: 16px;" title="Contrato Anual"/></td>
                    <td style="width: 35%;">Aluno</td>
                    <td style="width: 29%;">Turma</td>
                    <td style="width: 12%;">Unidade</td>
                    <td style="width: 11%;">Situação</td>
                    <td style="width: 13%; text-align: center;">Status</td>
                </tr>
<?php
    
        
        $bgcolor = null;

        while ($row = $result->fetch_assoc()){
            
            $bgcolor = ($bgcolor == '#e1e1e1') ? '#ffffff' : '#e1e1e1';
            
            switch ((int)$row['Status']) {
                case 0:
                    $st = 'Não Contatado';
                    $stBg = 'yellow';
                    break;
                case 1:
                    $st = 'Contatado';
                    $stBg = 'orange';
                    break;
                case 2:
                    $st = 'Não Volta';
                    $stBg = 'red';
                    break;
                default:
                    $st = 'Rematriculado';
                    $stBg = 'green';
            }
            
            echo '<tr style="background-color: ' . $bgcolor . ';">' . PHP_EOL .
                    '<td><input type="checkbox" class="chkYrContr" id="chkYrContr' . $row['ID'] . '" name="yrcont[' . $row['ID'] . ']" value="1"' . (!!$row['YearlyContract'] ? ' checked="checked"' : '') . '/></td>' . PHP_EOL .
                    '<td>' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</td>' . PHP_EOL .
                    '<td>' . htmlentities($row['Class'], 0, 'ISO-8859-1') . '</td>' . PHP_EOL .
                    '<td>' . htmlentities($row['School'], 0, 'ISO-8859-1') . '</td>' . PHP_EOL .
                    '<td>' . $row['Situation'] . '</td>' . PHP_EOL .
                    '<td><div style="background-color: ' . $stBg . '; text-align: center;">' . $st . '</div><input type="hidden" id="hidSt' . $row['ID'] . '" value="' . $row['Status'] . '"/></td>' . PHP_EOL .
                '</tr>' . PHP_EOL;
            
        }
?>
            </table>
                
            <div style="padding: 10px;">
                <button type="submit" name="save" value="1" onclick="return validateInput();"><img src="<?php echo IMAGE_DIR; ?>disk2.png"/> Salvar</button>
                <button type="button" onclick="reloadPage(<?php echo $uid; ?>);"><img src="<?php echo IMAGE_DIR; ?>refresh.png"/> Restaurar</button>
            </div>
                
            <input type="hidden" name="uid" value="<?php echo $uid; ?>"/>    
            </form>
<?php
    }
    else {
        echo '&nbsp; <span style="color: red; font-style: italic;">Este professor não possui aluno matriculado nesta campanha.</span>' . PHP_EOL;
    }
    
    $result->close();
    
?>
        </div>
<?php
    
}

?>