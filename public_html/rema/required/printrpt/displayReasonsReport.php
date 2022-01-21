<?php

function displayReasonsReport(&$db, $cid, $campName, $units){
    
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
                    <span style="font-weight: bold; font-size: 21px;">Relatório - Motivos de Não Rematrícula</span>
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
                <td colspan="3"><?php echo $uName; ?></td>
            </tr>
            <tr style="background-color: #d1d1d1; font-weight: bold;">
                <td style="width: 60%;">Descrição</td>
                <td style="width: 20%; text-align: center;">Quantidade</td>
                <td style="width: 20%; text-align: center;">%</td>
            </tr>
<?php
            
            // fetch data from unit
            $q = "SELECT reasons.Description, COUNT(*) AS Count FROM students JOIN classes ON students.Class = classes.ID " .
                     "JOIN reasons ON students.Reason = reasons.ID WHERE classes.Campaign = $cid AND students.Status = 2 " .
                     "AND classes.School = $uid GROUP BY students.Reason ORDER BY Count DESC, Description";

            $result = $db->query($q);

            $rows = array();
            $totalReasons = 0;

            while ($row = $result->fetch_assoc()){

                $rows[] = $row;
                $totalReasons += intval($row['Count'], 10);

            }

            $result->close();
            
            foreach ($rows as $row){
                    
?>
            <tr style="font-size: 14px; page-break-inside: avoid;">
                <td><?php echo htmlentities($row['Description'], 0, 'ISO-8859-1'); ?></td>
                <td style="text-align: center;"><?php echo $row['Count']; ?></td>
                <td style="text-align: center;"><?php echo round(($row['Count'] * 100) / $totalReasons, 2) . '%'; ?></td>
            </tr>
<?php
                
            }
            
            if ($totalReasons){
?>
            <tr style="background-color: #d1d1d1; font-weight: bold;">
                <td>Total</td>
                <td style="text-align: center;"><?php echo $totalReasons; ?></td>
                <td style="text-align: center;">100%</td>
            </tr>
    <?php
            }
            else {
                // this unit has empty classes only
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