<?php

function displayClasses(&$db, $uid, $userName, $cid, $campName, $campIsOpen, $isTeacher){
    
?>
        <div class="panel" style="width: 900px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Gerenciamento de Turmas <img src="<?php echo IMAGE_DIR; ?>question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox();"/></span>
            <hr/>
            
            <table>
                <tr>
                    <td style="text-align: right;">Colaborador:</td>
                    <td style="width: 100%; font-weight: bold;">
                        <?php echo htmlentities($userName, 0, 'ISO-8859-1'); ?> &nbsp; 
                        <img src="<?php echo IMAGE_DIR; ?>person.png" title="Modificar Professor" style="cursor: pointer;" onclick="window.location = 'manageclasses.php';"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $campName; ?></td>
                </tr>
            </table>
<?php

    $q = "SELECT classes.ID, classes.Name, schools.ID AS SchoolId, schools.Name AS SchoolName, StudentCount(classes.ID, 0) AS NumStds, " .
            "levels.ID AS LevelID, levels.Name AS Level FROM classes JOIN schools ON classes.School = schools.ID " .
            "JOIN levels ON classes.Level = levels.ID WHERE classes.User = $uid AND classes.Campaign = $cid ORDER BY SchoolName, Name";

    $result = $db->query($q);

    if ($result->num_rows){
        
?>
            <table class="tbl">
                <tr><td style="background-color: #61269e; color: white; width: 40%;">Turma</td>
                <td style="background-color: #61269e; color: white; width: 32%;">Estágio</td>
                <td style="background-color: #61269e; color: white; width: 18%;">Unidade</td>
                <td style="background-color: #61269e; color: white; text-align: center; width: 10%;">Alunos</td>
                <td style="background-color: #61269e; color: white; white-space: nowrap;"><img src="<?php echo IMAGE_DIR; ?>trans.png" style="width: 16px; height: 16px;"/> &nbsp; <img src="<?php echo IMAGE_DIR; ?>trans.png" style="width: 16px; height: 16px;"/></td></tr>
<?php

        $bgcolor = null;
        
        while ($row = $result->fetch_assoc()){
            
            $bgcolor = ($bgcolor == '#e1e1e1') ? '#ffffff' : '#e1e1e1';
?>
            <tr style="background-color: <?php echo $bgcolor; ?>;">
                <td><a href="class.php?clsid=<?php echo $row['ID']; ?>"><span id="spClsName<?php echo $row['ID']; ?>"><?php echo htmlentities($row['Name'], 0, 'ISO-8859-1'); ?></span></a></td>
                <td><?php echo htmlentities($row['Level'], 0, 'ISO-8859-1'); ?></td>
                <td><?php echo htmlentities($row['SchoolName'], 0, 'ISO-8859-1'); ?></td>
                <td style="text-align: center;"><?php echo $row['NumStds']; ?></td>
                <td>
                    <img id="img1_<?php echo $row['ID']; ?>" src="<?php echo IMAGE_DIR; ?>pencil1.png" title="Modificar" style="vertical-align: middle; width: 16px; height: 16px; cursor: pointer;" onclick="showEditBox(<?php echo $uid . ', ' . $row['ID'] . ', ' . $row['SchoolId'] . ', ' . $row['LevelID']; ?>);"/> &nbsp;
<?php

            if (!$row['NumStds']){
                echo '<img id="img2_' . $row['ID'] . '" src="' . IMAGE_DIR . 'recycle2.png" title="Remover" style="vertical-align: middle; width: 16px; height: 16px; cursor: pointer;" onclick="removeClass('. $row['ID'] . ');"/>' . PHP_EOL;
            }
            else {
                echo '<img src="' . IMAGE_DIR . 'recycle.png" title="Indisponível" style="vertical-align: middle; width: 16px; height: 16px; opacity: 0.5;  cursor: not-allowed;"/>' . PHP_EOL;
            }

?>
                </td>
            </tr>
<?php } ?>
            </table>
<?php
    }
    else{
        echo '<div style="font-style: italic; color: red; padding: 5px;">Este colaborador não possue turmas na campanha selecionada.</div>' . PHP_EOL;
    }
    
    $result->close();
    
    if ($isTeacher && $campIsOpen){
        echo '<div style="padding: 10px 5px 5px 5px;"><button type="button" onclick="showAddClassBox();"><img src="' . IMAGE_DIR . 'plus.png" /> Adicionar Turma</button></div>';
    }

?>
        </div>
<?php
    
}

?>