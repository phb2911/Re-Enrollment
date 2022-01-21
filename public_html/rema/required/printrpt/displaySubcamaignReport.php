<?php

function displaySubcamaignReport(&$db, $subCampInfo){
    
    $subCampID = intval($subCampInfo['ID'], 10);
    $subCampName = $subCampInfo['Name'];
    $campName = $subCampInfo['CampName'];
    $campIsOpen = !!$subCampInfo['CampOpen'];
    $subCampIsOpen = !!$subCampInfo['Open'];
    $inclDropOuts = !!$subCampInfo['IncludeDropouts'];
    
?>
        <br/>
        <br/>
        <table class="headder" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 100%;">
                    <span style="font-weight: bold; font-size: 21px;">Relatório de Subcampanha</span>
                    <br/>Campanha: <span style="font-weight: bold;"><?php echo $campName; ?></span>
                    <br/>Subcampanha: <span style="font-weight: bold;"><?php echo htmlentities($subCampName, 0, 'ISO-8859-1'); ?></span>
                    <br/><span style="font-style: italic; font-size: 13px;">Relatório gerado em: <?php echo date("d/m/Y H:i:s"); ?></span>
                </td>
                <td class="noprint" style="vertical-align: bottom;"><img src="<?php echo IMAGE_DIR; ?>printer.png" style="vertical-align: bottom; cursor: pointer;" title="Imprimir" onclick="window.print();"/></td>
            </tr>
        </table>
        <br/>
<?php

    // build query
    if ($subCampIsOpen){
        
        $do = ($inclDropOuts ? '1' : '0');
        
        $q = "SELECT users.Name, TotalFromOpenSubCamp($subCampID, users.ID, $do) AS Total, "
                . "EnrolledFromOpenSubCamp($subCampID, users.ID, $do) AS Enrolled, "
                . "PercentFromOpenSubCamp($subCampID, users.ID, $do) AS Percentage FROM users HAVING Total > 0 ORDER BY Percentage DESC, Total DESC";
        
    }
    else {
        
        $q = "SELECT users.Name, subcamp_results.Student_Count AS Total, subcamp_results.Enrolled_Count AS Enrolled, "
                . "(CASE subcamp_results.Student_Count WHEN 0 THEN 0 ELSE (subcamp_results.Enrolled_Count * 100 / subcamp_results.Student_Count) END) AS Percentage "
                . "FROM subcamp_results JOIN users ON subcamp_results.User = users.ID WHERE subcamp_results.SubCamp = $subCampID ORDER BY Percentage DESC, Total DESC";
        
    }
    
    $result = $db->query($q);
    
    if ($result->num_rows){
        
        if ($campIsOpen && $subCampIsOpen){
            echo '<span style="font-style: italic; color: red; font-size: 13px;">*Esta subcampanha está aberta. Os valores abaixo ainda podem ser alterados.</span>';
        }
        
?>
        <table class="main" style="border: black solid 1px;">
            <tr style="background-color: #d1d1d1; font-weight: bold;">
                <td style="width: 50%;">Professor</td>
                <td style="width: 20%; text-align: center;">Total de Alunos</td>
                <td style="width: 20%; text-align: center;">Rematriculados</td>
                <td style="width: 10%; text-align: center;">%</td>
            </tr>
<?php

        while ($row = $result->fetch_assoc()){
            
?>
            <tr style="page-break-inside: avoid;">
                <td><?php echo htmlentities($row['Name'], 0, 'ISO-8859-1'); ?></td>
                <td style="text-align: center;"><?php echo $row['Total']; ?></td>
                <td style="text-align: center;"><?php echo $row['Enrolled']; ?></td>
                <td style="text-align: center;"><?php echo +number_format($row['Percentage'], 2); ?>%</td>
            </tr>
<?php
        }

?>
        </table>
<?php
        
    }
    else {
        echo '<span style="font-style: italic; color: red;">Não há participantes nesta subcampanha.</span>';
    }
    
    $result->close();
    
}

?>