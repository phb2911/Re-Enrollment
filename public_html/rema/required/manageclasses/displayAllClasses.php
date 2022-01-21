<?php

function displayAllClasses(&$db, $uid, $cid, $campName, $campIsOpen, $sort){
    
?>
        <div class="panel" style="width: 100%; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Gerenciamento de Turmas <img src="<?php echo IMAGE_DIR; ?>question.png" title="Ajuda" style="cursor: pointer;" onclick="showHelpBox();"/></span>
            <hr/>
            
            <table>
                <tr>
                    <td style="text-align: right;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold;">
                        <?php echo $campName; ?> &nbsp;
                        <img src="<?php echo IMAGE_DIR; ?>person.png" title="Modificar Professor" style="cursor: pointer;" onclick="window.location = 'manageclasses.php';"/>
                    </td>
                    <td style="white-space: nowrap;">
                        <?php
                        
                        if ($campIsOpen){
                            echo '<button type="button" onclick="showAddClassBox();"><img src="' . IMAGE_DIR . 'plus.png" /> Adicionar Turma</button>';
                        }
                        
                        ?>
                    </td>
                </tr>
            </table>
            
<?php

    $titleCls = '<span class="link" onclick="sort(' . $uid . ',1);">Turma</span>';
    $titleLvl = '<span class="link" onclick="sort(' . $uid . ',3);">Estágio</span>';
    $titleTch = '<span class="link" onclick="sort(' . $uid . ',5);">Professor</span>';
    $titleSch = '<span class="link" onclick="sort(' . $uid . ',0);">Unidade</span>';

    // set sort
    if ($sort == 1){
        $titleCls = '<span class="link" onclick="sort(' . $uid . ',2);">Turma</span><img src="' . IMAGE_DIR . 'sort_up.png" style="vertical-align: top;"/>';
        $so = "Name, TeacherName";
    }
    elseif ($sort == 2){
        $titleCls .= '<img src="' . IMAGE_DIR . 'sort_down.png" style="vertical-align: top;"/>';
        $so = "Name DESC, TeacherName DESC";
    }
    elseif ($sort == 3){
        $titleLvl = '<span class="link" onclick="sort(' . $uid . ',4);">Estágio</span><img src="' . IMAGE_DIR . 'sort_up.png" style="vertical-align: top;"/>';
        $so = "Level, Name";
    }
    elseif ($sort == 4) {
        $titleLvl .= '<img src="' . IMAGE_DIR . 'sort_down.png" style="vertical-align: top;"/>';
        $so = "Level DESC, Name DESC";
    }
    elseif ($sort == 5) {
        $titleTch = '<span class="link" onclick="sort(' . $uid . ',6);">Professor</span><img src="' . IMAGE_DIR . 'sort_up.png" style="vertical-align: top;"/>';
        $so = "TeacherName, SchoolName, Name";
    }
    elseif ($sort == 6){
        $titleTch .= '<img src="' . IMAGE_DIR . 'sort_down.png" style="vertical-align: top;"/>';
        $so = "TeacherName DESC, SchoolName, Name";
    }
    elseif ($sort == 7){
        $titleSch .= '<img src="' . IMAGE_DIR . 'sort_down.png" style="vertical-align: top;"/>';
        $so = "SchoolName DESC, TeacherName, Name";
    }
    else {
        $titleSch = '<span class="link" onclick="sort(' . $uid . ',7);">Unidade</span><img src="' . IMAGE_DIR . 'sort_up.png" style="vertical-align: top;"/>';
        $so = "SchoolName, TeacherName, Name";
    }
    
    

    $q = "SELECT classes.ID, classes.Name, schools.ID AS SchoolId, schools.Name AS SchoolName, StudentCount(classes.ID, 0) AS NumStds, "
            . "levels.ID AS LevelID, levels.Name AS Level, users.ID AS TeacherID, users.Name AS TeacherName FROM classes JOIN schools ON "
            . "classes.School = schools.ID JOIN levels ON classes.Level = levels.ID JOIN users ON classes.User = users.ID "
            . "WHERE classes.Campaign = $cid ORDER BY " . $so;
    // DO SORTING IN THE FUTURE
    
    $result = $db->query($q);
    
    if ($result->num_rows){
        
?>
            <table class="tbl">
                <tr>
                    <td style="background-color: #61269e; color: white; width: 30%;"><?php echo $titleCls; ?></td>
                    <td style="background-color: #61269e; color: white; width: 20%;"><?php echo $titleLvl; ?></td>
                    <td style="background-color: #61269e; color: white; width: 15%;"><?php echo $titleSch; ?></td>
                    <td style="background-color: #61269e; color: white; width: 30%;"><?php echo $titleTch; ?></td>
                    <td style="background-color: #61269e; color: white; text-align: center; width: 5%;">Alunos</td>
                    <td style="background-color: #61269e; color: white; white-space: nowrap;"><img src="<?php echo IMAGE_DIR; ?>trans.png" style="width: 16px; height: 16px;"/> &nbsp; <img src="<?php echo IMAGE_DIR; ?>trans.png" style="width: 16px; height: 16px;"/></td>
                </tr>
<?php
    
    $bgcolor = null;
    
    while ($row = $result->fetch_assoc()){
            
        $bgcolor = ($bgcolor == '#e1e1e1') ? '#ffffff' : '#e1e1e1';
        
?>
                <tr style="background-color: <?php echo $bgcolor; ?>;">
                    <td><a href="class.php?clsid=<?php echo $row['ID']; ?>"><span id="spClsName<?php echo $row['ID']; ?>"><?php echo htmlentities($row['Name'], 0, 'ISO-8859-1'); ?></span></a></td>
                    <td><?php echo htmlentities($row['Level'], 0, 'ISO-8859-1'); ?></td>
                    <td><?php echo htmlentities($row['SchoolName'], 0, 'ISO-8859-1'); ?></td>
                    <td><?php echo htmlentities($row['TeacherName'], 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><?php echo $row['NumStds']; ?></td>
                    <td>
                        <img id="img1_<?php echo $row['ID']; ?>" src="<?php echo IMAGE_DIR; ?>pencil1.png" title="Modificar" style="vertical-align: middle; width: 16px; height: 16px; cursor: pointer;" onclick="showEditBox(<?php echo $row['TeacherID'] . ', ' . $row['ID'] . ', ' . $row['SchoolId'] . ', ' . $row['LevelID']; ?>);"/> &nbsp;
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
<?php
    
    }

?>
            </table>
<?php
        
    }
    
    $result->close()

?>
            
        </div>
<?php
    
}

?>