<?php

function displaySubCampaign(&$db, $isAdmin, $uid, $subCampInfo){
    
    $campName = $subCampInfo['Campaign'];
    $teacher = $subCampInfo['Teacher'];
    $subCampID = $subCampInfo['ID'];
    $subCampName = $subCampInfo['Name'];
    $inclDropOuts = !!$subCampInfo['IncludeDropouts'];
    $subCampIsOpen = !!$subCampInfo['Open'];
    
?>
        <div class="panel">
            
            <span style="font-weight: bold;">Subcampanha por Colaborador</span>
            <hr/>
        
            <table>
                <tr>
                    <td style="text-align: right;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($campName, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Subcampanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo ($isAdmin ? '<a href="subcamp.php?scampid=' . $subCampID . '">' : '') . htmlentities($subCampName, 0, 'ISO-8859-1') . ($isAdmin ? '</a>' : ''); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Colaborador:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($teacher, 0, 'ISO-8859-1'); ?></td>
                </tr>
<?php if (!$subCampIsOpen){ ?>
                <tr>
                    <td style="font-style: italic; color: red;" colspan="2">Esta subcampanha está encerrada.</td>
                </tr>
<?php } ?>
            </table>
        </div>
        <br/>
        <div class="panel">
            
            <div style="font-weight: bold;">Resultados</div>
            <hr/>
<?php

    // fetch results
    if ($subCampIsOpen){
        
        $q = "SELECT (SELECT COUNT(*) FROM subcamp_classes JOIN classes ON subcamp_classes.Class = classes.ID JOIN students "
                . "ON students.Class = classes.ID WHERE classes.User = users.ID AND subcamp_classes.SubCamp = $subCampID AND students.Situation "
                . ($inclDropOuts ? '<= 1' : '= 0') . ") AS Total, (SELECT COUNT(*) FROM subcamp_classes JOIN classes ON subcamp_classes.Class = classes.ID JOIN students "
                . "ON students.Class = classes.ID WHERE classes.User = users.ID AND subcamp_classes.SubCamp = $subCampID AND Status = 3 AND students.Situation "
                . ($inclDropOuts ? '<= 1' : '= 0') . ") AS Enrolled FROM users JOIN classes ON classes.User = users.ID JOIN subcamp_classes ON subcamp_classes.Class = classes.ID "
                . "WHERE users.ID = $uid LIMIT 1";
        
    }
    else {
        $q = "SELECT Student_Count AS Total, Enrolled_Count AS Enrolled FROM subcamp_results WHERE SubCamp = $subCampID AND User = $uid";
    }

    $resFlag = ($row = $db->query($q)->fetch_assoc());
    
    if ($resFlag){
        
        $resFlag = !!$row['Total'];

        if ($resFlag){
            echo '<div style=" line-height: 50%;"/>&nbsp;</div>' . PHP_EOL .
                    '<table style="border-collapse: collapse; width: 100%;">' . PHP_EOL .
                    '<tr><td style="background-color: #f0f0f0; width: 80%; border: #61269e solid 1px; border-right: 0;">Total de Alunos</td><td style="background-color: #f0f0f0; width: 20%; text-align: right; padding-right: 15px; border: #61269e solid 1px; border-left: 0;">' . $row['Total'] . '</td></tr>' . PHP_EOL . 
                    '<tr><td style="background-color: #f0f0f0; width: 80%; border: #61269e solid 1px; border-right: 0;">Rematriculados</td><td style="background-color: #f0f0f0; width: 20%; text-align: right; padding-right: 15px; border: #61269e solid 1px; border-left: 0;">' . $row['Enrolled'] . '</td></tr>' . PHP_EOL .
                    '<tr><td style="background-color: #ffffff; width: 80%; text-align: right;">Rema!</td><td style="background-color: #c1c1c1; width: 20%; text-align: right; padding-right: 15px; border: #61269e solid 1px;">' . +number_format(((intval($row['Enrolled'], 10) * 100) / intval($row['Total'], 10)), 2) . '%</td></tr>' . PHP_EOL .
                    '</table>';
        }
        else {
            echo '<div style="color: red; font-style: italic; padding: 5px;" colspan="2">** Este professor não possue alunos associados com esta subcampanha.</div>';
        }

    }
    else {
        echo '<div style="font-style: italic; color: red; padding: 10px;">Não há resultados associados à esta subcampanha.</div>';
    }
        
?>
        </div>
<?php

    if ($resFlag && $subCampIsOpen){
        
        $activeStds = array();
        $dropoutStds = array();
        
        $q ="(SELECT students.ID, students.Name, classes.ID AS ClassID, classes.Name AS ClassName, students.Status, reasons.Description AS Reason, "
            . "students.Notes, students.Flagged, students.Situation, (SELECT Name FROM schools WHERE ID = classes.School) AS School FROM subcamp_classes "
            . "JOIN classes ON subcamp_classes.Class = classes.ID JOIN students ON students.Class = classes.ID LEFT JOIN reasons ON students.Reason = reasons.ID "
            . "WHERE subcamp_classes.SubCamp = $subCampID AND classes.User = $uid AND students.Situation " . ($inclDropOuts ? "<= 1" : "=0") . " ORDER BY ClassName, Name)";
        
        $result = $db->query($q);
        
        while ($row = $result->fetch_assoc()){
            
            if ($row['Situation'] == 0) $activeStds[] = $row;
            else $dropoutStds[] = $row;
            
        }
        
        $result->close();
        
        if (count($dropoutStds)) displayStudentTable(1, $dropoutStds);
        if (count($activeStds)) displayStudentTable(0, $activeStds);

    }
    
}

?>