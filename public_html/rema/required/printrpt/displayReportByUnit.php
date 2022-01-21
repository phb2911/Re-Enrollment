<?php

function displayReportByUnit(&$db, $cid, $campName){
    
    // retrieve units
    $result = $db->query("SELECT schools.ID, schools.Name FROM classes JOIN schools ON classes.School = schools.ID WHERE classes.Campaign = $cid GROUP BY classes.School");
    
    // $units[ID] = Name
    $units = array();
    
    while ($row = $result->fetch_assoc()){
        $units[$row['ID']] = $row['Name'];
    }
    
    $result->close();
    
    if (count($units)){
    
?>
        <br/>
        <br/>
        <table class="headder" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 100%;">
                    <span style="font-weight: bold; font-size: 21px;">Relatório Por Unidade</span>
                    <br/>Campanha: <span style="font-weight: bold;"><?php echo $campName; ?></span>
                    <br/><span style="font-style: italic; font-size: 13px;">Relatório gerado em: <?php echo date("d/m/Y H:i:s"); ?></span>
                </td>
                <td class="noprint" style="vertical-align: bottom;"><img src="<?php echo IMAGE_DIR; ?>printer.png" style="vertical-align: bottom; cursor: pointer;" title="Imprimir" onclick="window.print();"/></td>
            </tr>
        </table>
        <br/>
        <table class="main">
            <tr style="background-color: #d1d1d1; font-weight: bold;">
                <td style="width: 55%; border-left: #404040 solid 1px;">Unidade</td>
                <td style="width: 15%; text-align: center;">Alunos</td>
                <td style="width: 15%; text-align: center;">Rematriculados</td>
                <td style="width: 15%; text-align: center; border-right: #404040 solid 1px;">%</td>
            </tr>
<?php

        $genTotal = 0;
        $genEnrolled = 0;

        foreach ($units as $uid => $unitName) {
            
            $row = $db->query("SELECT (SELECT COUNT(*) FROM classes JOIN students ON students.Class = classes.ID WHERE classes.Campaign = $cid AND classes.School = $uid) AS Total, (SELECT COUNT(*) FROM classes JOIN students ON students.Class = classes.ID WHERE classes.Campaign = $cid AND classes.School = $uid AND students.Status = 3) AS Enrolled")->fetch_assoc();
        
            $total = intval($row['Total'], 10);
            $enrolled = intval($row['Enrolled'], 10);
            
            $genTotal += $total;
            $genEnrolled += $enrolled;
            
            // if total = 0, percentage = 0. 
            // if total > 0, percentage = (enrolled * 100) / total
            $percentage = !!$total ? +number_format(($enrolled * 100) / $total, 2) : 0;
            
?>
            <tr>
                <td style="border-left: #404040 solid 1px;"><?php echo htmlentities($unitName, 0, 'ISO-8859-1'); ?></td>
                <td style="text-align: center;"><?php echo $total; ?></td>
                <td style="text-align: center;"><?php echo $enrolled; ?></td>
                <td style="text-align: center; border-right: #404040 solid 1px;"><?php echo $percentage; ?>%</td>
            </tr>
<?php 
        } 

        $genPercentage = !!$genTotal ? +number_format(($genEnrolled * 100) / $genTotal, 2) : 0;
?>
            <tr style="background-color: #d1d1d1; font-weight: bold;">
                <td style="border-left: #404040 solid 1px;">Total</td>
                <td style="text-align: center;"><?php echo $genTotal; ?></td>
                <td style="text-align: center;"><?php echo $genEnrolled; ?></td>
                <td style="text-align: center; border-right: #404040 solid 1px;"><?php echo $genPercentage; ?>%</td>
            </tr>
        </table>
        <div style="font-size: 13px; font-style: italic; padding-top: 5px;">* Nos calculos acima, todos os alunos são considerados, inclusive os cancelados e concluintes.</div>
<?php
    }
    else {
        echo '<br/><br/><span style="font-style: italic; color: red;">Não há turmas nessa campanha.</span>';
    }
    
}

?>