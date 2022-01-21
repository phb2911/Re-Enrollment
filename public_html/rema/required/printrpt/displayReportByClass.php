<?php

function displayReportByClass(&$db ,$cid, $campName, $units){
    
    // check if argument contain units
    $containUnits = (isset($units) && is_array($units) && count($units));
    
    // fetch active unit names to display on headder
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
    $q = "SELECT classes.Name, users.Name AS Teacher, schools.Name AS School, StudentCount(classes.ID, 0) AS NumStds, "
            . "StudentCount(classes.ID, 1) AS NumStdsEnr FROM classes JOIN users ON classes.User = users.ID JOIN schools "
            . "ON classes.School = schools.ID WHERE classes.Campaign = $cid";
 
    if (isset($units) && is_array($units)){
        
        // check which units will be included
        $qFlag = false;

        foreach ($units as $unit){

            // check school id format (numbers only)
            if (preg_match('/^([0-9])+$/', $unit)){

                if (!$qFlag) {
                    $q .= " AND (";
                }
                else {
                    $q .= " OR ";
                }

                $q .= "schools.ID = " . $unit;

                $qFlag = true;

            }

        }

        if ($qFlag) $q .= ")";
    
    }

    $q .= " ORDER BY School, Name, Teacher";
    
    $result = $db->query($q);
    
    $numRows = $result->num_rows;
    
?>
        <br/>
        <br/>
        <table class="headder" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 100%;">
                    <span style="font-weight: bold; font-size: 21px;">Relatório de Rematrícula por Turma</span>
                    <br/>Campanha: <span style="font-weight: bold;"><?php echo $campName; ?></span>
                    <br/>Unidade(s): <span style="font-weight: bold;"><?php echo htmlentities($sUnits, 0, 'ISO-8859-1'); ?></span>
                    <br/>Número de Turmas: <span style="font-weight: bold;"><?php echo $numRows; ?></span>
                    <br/><span style="font-style: italic; font-size: 13px;">Relatório gerado em: <?php echo date("d/m/Y H:i:s"); ?></span>
                </td>
                <td class="noprint" style="vertical-align: bottom;"><img src="<?php echo IMAGE_DIR; ?>printer.png" style="vertical-align: bottom; cursor: pointer;" title="Imprimir" onclick="window.print();"/></td>
            </tr>
        </table>
<?php
    if ($numRows){
?>
        <br/>
        <table class="main" style="border: black solid 1px;">
            <tr style="background-color: #d1d1d1; font-weight: bold;">
                <td style="width: 25%;">Turma</td>
                <td style="width: 15%;">Unidade</td>
                <td style="width: 36%;">Professor</td>
                <td style="width: 8%; text-align: center;">Alunos</td>
                <td style="width: 8%; text-align: center;">Rema</td>
                <td style="width: 8%; text-align: center;">%</td>
            </tr>
<?php
        
        while ($row = $result->fetch_assoc()){
        
            $numStd = intval($row['NumStds'], 10);
            $numStdsEnr = intval($row['NumStdsEnr'], 10);
            
            // prevent division by zero exception
            $percent = ($numStd ? ($numStdsEnr * 100) / $numStd : 0);
            
?>
            <tr style="font-size: 14px; page-break-inside: avoid;">
                <td><?php echo htmlentities($row['Name'], 0, 'ISO-8859-1'); ?></td>
                <td><?php echo htmlentities($row['School'], 0, 'ISO-8859-1'); ?></td>
                <td><?php echo htmlentities($row['Teacher'], 0, 'ISO-8859-1'); ?></td>
                <td style="text-align: center;"><?php echo $numStd; ?></td>
                <td style="text-align: center;"><?php echo $numStdsEnr; ?></td>
                <td style="text-align: center;"><?php echo round($percent, 2); ?>%</td>
            </tr>
<?php
        }
?>
        </table>
        
        <p class="noprint">&nbsp;</p>
<?php        
    }
    else {
        echo '<div style="color: red; font-style: italic; padding-top: 20px;">Não foi encontrado nenhuma turma nesta campanha.</div>';
    }
        
    $result->close();
    
}

?>