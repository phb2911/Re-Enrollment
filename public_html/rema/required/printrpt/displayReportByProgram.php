<?php

function displayReportByProgram(&$db, $cid, $campName, $units){
    
    // check if argument contain units
    $containUnits = (isset($units) && is_array($units) && count($units));
    
    // fetch unit names to display on headders
    $result = $db->query("SELECT schools.ID, schools.Name FROM classes, schools WHERE classes.School = schools.ID AND classes.Campaign = $cid GROUP BY classes.School ORDER BY schools.Name");
    
    // units string variable
    $sUnits = '';
    $flag = false;
    
    // $myUnits(ID) = Name
    $myUnits = array();
    
    while ($row = $result->fetch_assoc()){
        
        // if no units in $units, all units are displayed
        // this also validates submitted unit ids
        if (!$containUnits || in_array($row['ID'], $units)){
            
            $myUnits[$row['ID']] = $row['Name'];
            
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
    
?>
        <br/>
        <br/>
        <table class="headder" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 100%;">
                    <span style="font-weight: bold; font-size: 21px;">Relatório de Rematrícula por Programa</span>
                    <br/>Campanha: <span style="font-weight: bold;"><?php echo $campName; ?></span>
                    <br/>Unidade(s): <span style="font-weight: bold;"><?php echo htmlentities($sUnits, 0, 'ISO-8859-1'); ?></span>
                    <br/><span style="font-style: italic; font-size: 13px;">Relatório gerado em: <?php echo date("d/m/Y H:i:s"); ?></span>
                </td>
                <td class="noprint" style="vertical-align: bottom;"><img src="<?php echo IMAGE_DIR; ?>printer.png" style="vertical-align: bottom; cursor: pointer;" title="Imprimir" onclick="window.print();"/></td>
            </tr>
        </table>
<?php

    // display data
    if (count($myUnits)){
        
        foreach ($myUnits as $uid => $uName){
            
            // display header
?>
        <br/>
        <table class="main" style="border: black solid 1px;">
            <tr style="background-color: #d1d1d1; font-weight: bold;">
                <td colspan="4"><?php echo $uName; ?></td>
            </tr>
            <tr style="background-color: #d1d1d1; font-weight: bold;">
                <td style="width: 55%;">Programa</td>
                <td style="width: 15%; text-align: center;">Alunos</td>
                <td style="width: 15%; text-align: center;">Rematriculado</td>
                <td style="width: 15%; text-align: center;">%</td>
            </tr>
<?php
            
            // fetch data from unit
            $result = $db->query("SELECT Name, studentCountByProg(ID,$cid,$uid,0) AS Total, studentCountByProg(ID,$cid,$uid,1) AS Enrolled FROM programs ORDER BY Name");
            
            $total = 0;
            $enrolled = 0;
            
            while ($row = $result->fetch_assoc()){
                
                if ($row['Total'] > 0){
                    
                    $total += $row['Total'];
                    $enrolled += $row['Enrolled'];
                    
?>
            <tr style="font-size: 14px; page-break-inside: avoid;">
                <td><?php echo htmlentities($row['Name'], 0, 'ISO-8859-1'); ?></td>
                <td style="text-align: center;"><?php echo $row['Total']; ?></td>
                <td style="text-align: center;"><?php echo $row['Enrolled']; ?></td>
                <td style="text-align: center;"><?php echo round((intval($row['Enrolled'], 10) * 100) / intval($row['Total'], 10), 2); ?>%</td>
            </tr>
<?php
                    
                }
                
            }
            
            $result->close();
            
            
            if ($total){
?>
            <tr style="background-color: #d1d1d1; font-weight: bold;">
                <td>Total</td>
                <td style="text-align: center;"><?php echo $total; ?></td>
                <td style="text-align: center;"><?php echo $enrolled; ?></td>
                <td style="text-align: center;"><?php echo round(($enrolled * 100) / $total, 2); ?>%</td>
            </tr>
<?php
            }
            else {
                // no programs with students found
                echo '<tr><td style="color: red; font-style: italic;" colspan="4">Não há alunos matriculados nesta unidade.</td></tr>';
            }
            
?>
        </table>
        <br/>
<?php
            
        }
        
    }
    else {
        echo '<br/><span style="color: red; font-style: italic;">Não há alunos nesta campanha.</span>';
    }
    
}

?>