<?php

function displayUnenrolledReport(&$db ,$cid, $campName, $units){
    
    // check if argument contain units
    $containUnits = (isset($units) && is_array($units) && count($units));
    
    // fetch unit names to display on headder
    $result = $db->query("SELECT schools.ID, schools.Name FROM classes, schools WHERE classes.School = schools.ID AND classes.Campaign = $cid GROUP BY classes.School ORDER BY schools.Name");
    
    // units string variable
    $sUnits = '';
    $flag = false;
    
    while ($row = $result->fetch_assoc()){
        
        if (!$containUnits || in_array($row['ID'], $units)){
            
            if ($flag) $sUnits .= ' / ';
            $sUnits .= $row['Name'];
            $flag = true;
        }
        
    }
    
    $result->close();
    
    // no unit associated with the ones found in the $units array
    if (!$flag) {
        echo '<div style="color: red; font-style: italic; padding-top: 20px;">ID das unidades inválidos.</div>';
        return;
    }
    
    // build query string
    $q = "SELECT students.ID, students.Name, students.Status, students.Notes, users.Name AS TeacherName, classes.Name AS ClassName, schools.Name AS "
         . "UnitName, reasons.Description AS Reason FROM students JOIN classes ON students.Class = classes.ID JOIN users ON classes.User = users.ID JOIN schools ON classes.School = "
         . "schools.ID LEFT JOIN reasons ON students.Reason = reasons.ID WHERE students.Status < 3 AND students.Situation < 3 AND classes.Campaign = $cid";
    
    if (isset($units) && is_array($units)){
        // check which units will be included
        $qFlag = false;
        
        foreach ($units as $sid){

            // check school id format (numbers only)
            if (preg_match('/^([0-9])+$/', $sid)){

                if (!$qFlag) {
                    $q .= " AND (";
                }
                else {
                    $q .= " OR ";
                }

                $q .= "schools.ID = " . $sid;
                
                $qFlag = true;
            }

        }

        if ($qFlag) $q .= ")";
        
    }
    
    // get sort order
    $sortOrder = getGet('so');
    
    if (!isNum($sortOrder) || $sortOrder == 0 || $sortOrder > 11){
        $q .= " ORDER BY UnitName, TeacherName, ClassName, Name";
    }
    elseif ($sortOrder == 1){
        $q .= " ORDER BY UnitName DESC, TeacherName, ClassName, Name";
    }
    elseif ($sortOrder == 2){
        $q .= " ORDER BY Name, UnitName, TeacherName, ClassName";
    }
    elseif ($sortOrder == 3){
        $q .= " ORDER BY Name DESC, UnitName, TeacherName, ClassName";
    }
    elseif ($sortOrder == 4){
        $q .= " ORDER BY TeacherName, UnitName, ClassName, Name";
    }
    elseif ($sortOrder == 5){
        $q .= " ORDER BY TeacherName DESC, UnitName, ClassName, Name";
    }
    elseif ($sortOrder == 6){
        $q .= " ORDER BY ClassName, TeacherName, UnitName, Name";
    }
    elseif ($sortOrder == 7){
        $q .= " ORDER BY ClassName DESC, TeacherName, UnitName, Name";
    }
    elseif ($sortOrder == 8){
        $q .= " ORDER BY Status, UnitName, TeacherName, ClassName, Name";
    }
    elseif ($sortOrder == 9){
        $q .= " ORDER BY Status DESC, UnitName, TeacherName, ClassName, Name";
    }
    elseif ($sortOrder == 10){
        $q .= " ORDER BY IF(ISNULL(Reason), 1, 0), Reason, UnitName, TeacherName, ClassName, Name";
    }
    elseif ($sortOrder == 11){
        $q .= " ORDER BY Reason DESC, UnitName, TeacherName, ClassName, Name";
    }
    
    // display report
    $result = $db->query($q);
    
    $numRows = $result->num_rows;
    
    if ($numRows){
?>
        <br/>
        <br/>
        <table class="headder" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 100%;">
                    <span style="font-weight: bold; font-size: 21px;">Relatório de Alunos Não Rematriculados</span>
                    <br/>Campanha: <span style="font-weight: bold;"><?php echo $campName; ?></span>
                    <br/>Unidade(s): <span style="font-weight: bold;"><?php echo htmlentities($sUnits, 0, 'ISO-8859-1'); ?></span>
                    <br/>Número de Alunos: <span style="font-weight: bold;"><?php echo $numRows; ?></span>
                    <br/><span style="font-style: italic; font-size: 13px;">Relatório gerado em: <?php echo date("d/m/Y H:i:s"); ?></span>
                </td>
                <td class="noprint" style="vertical-align: bottom;"><img src="<?php echo IMAGE_DIR; ?>printer.png" style="vertical-align: bottom; cursor: pointer;" title="Imprimir" onclick="window.print();"/></td>
            </tr>
        </table>
        <br/>
        <table class="main" style="border: black solid 1px;">
            <tr style="background-color: #d1d1d1; font-weight: bold;">
                <td style="width: 18%;">Aluno</td>
                <td style="width: 18%;">Professor</td>
                <td style="width: 23%;">Turma</td>
                <td style="width: 10%; text-align: center;">Status</td>
                <td style="width: 8%;">Motivo</td>
                <td style="width: 23%;">Observações</td>
            </tr>
<?php

        // set status array
        $statusArr = array('Não Contatado', 'Contatado', 'Não Volta');

        while ($row = $result->fetch_assoc()){
?>
            <tr style="font-size: 14px; page-break-inside: avoid;">
                <td><?php echo htmlentities($row['Name'], 0, 'ISO-8859-1'); ?></td>
                <td><?php echo htmlentities($row['TeacherName'], 0, 'ISO-8859-1'); ?></td>
                <td><?php echo htmlentities($row['ClassName'] . ' (' . $row['UnitName'] . ')', 0, 'ISO-8859-1'); ?></td>
                <td style="text-align: center; white-space: nowrap;"><?php echo $statusArr[intval($row['Status'], 10)]; ?></td>
                <td><?php echo htmlentities($row['Reason'], 0, 'ISO-8859-1'); ?></td>
                <td><?php echo htmlentities($row['Notes'], 0, 'ISO-8859-1'); ?></td>
            </tr>
<?php
        }

?>
        </table>
        
        <p class="noprint">&nbsp;</p>
<?php
    }
    else {
        echo '<div style="color: red; font-style: italic; padding-top: 20px;">Não foi encontrado nenhum aluno não matriculado nesta campanha.</div>';
    }
    
    $result->close();
    
}

?>