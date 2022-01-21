<?php

function displayGroupReport(&$db, $cid, $campName){
    
?>
        <br/>
        <br/>
        <table class="headder" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 100%;">
                    <span style="font-weight: bold; font-size: 21px;">Relatório por Grupo</span>
                    <br/>Campanha: <span style="font-weight: bold;"><?php echo $campName; ?></span>
                    <br/><span style="font-style: italic; font-size: 13px;">Relatório gerado em: <?php echo date("d/m/Y H:i:s"); ?></span>
                </td>
                <td class="noprint" style="vertical-align: bottom;"><img src="<?php echo getImagePath('printer.png'); ?>" style="vertical-align: bottom; cursor: pointer;" title="Imprimir" onclick="window.print();"/></td>
            </tr>
        </table>
        <br/>
<?php

    $groupsInfo = fetchGroupsInfo($db, $cid);
    
    if (!empty($groupsInfo)){
?>
        <table class="main">
            <tr style="background-color: #d1d1d1; font-weight: bold;">
                <td style="width: 30%; border-left: #404040 solid 1px;">Grupo</td>
                <td class="numbers">Profs.</td>
                <td class="numbers">Alunos</td>
                <td class="numbers">Rema</td>
                <td class="numbers">%</td>
                <td class="numbers">Semestral</td>
                <td class="numbers">Rema (sem.)</td>
                <td class="numbers" style="border-right: #404040 solid 1px;">% (sem.)</td>
            </tr>
<?php

    foreach ($groupsInfo as $grInfo){
        
        $percent = ($grInfo['Total'] ? ($grInfo['Enrolled'] * 100) / $grInfo['Total'] : 0);
        $semContPer = ($grInfo['SemesterContracts'] ? ($grInfo['SemContEnrolled'] * 100) / $grInfo['SemesterContracts'] : 0);
        
?>
                    <tr>
                        <td style="width: 30%; border-left: #404040 solid 1px;"><?php echo htmlentities($grInfo['Name'], 0, 'ISO-8859-1'); ?></td>
                        <td class="numbers"><?php echo $grInfo['TeacherCount']; ?></td>
                        <td class="numbers"><?php echo $grInfo['Total']; ?></td>
                        <td class="numbers"><?php echo $grInfo['Enrolled']; ?></td>
                        <td class="numbers"><?php echo +number_format($percent , 2); ?>%</td>
                        <td class="numbers"><?php echo $grInfo['SemesterContracts']; ?></td>
                        <td class="numbers"><?php echo $grInfo['SemContEnrolled']; ?></td>
                        <td class="numbers" style="border-right: #404040 solid 1px;"><?php echo +number_format($semContPer , 2); ?>%</td>
                    </tr>
<?php } ?>
        </table>
<?php
    }
    else {
        echo '<div style="padding: 5px; color: red; font-style: italic;">Não há grupos nesta campanha.</div>' . PHP_EOL;
    }
}

//-----------------------------------------------------

function fetchGroupsInfo(&$db, $cid){
    
    $grInfo = array();
    
    $result = $db->query("CALL spFetchGroupsNumbers($cid);");
    
    while ($row = $result->fetch_assoc()){
        $grInfo[] = $row;
    }
    
    $result->close();
    
    // clear db stored results
    clearStoredResults($db);
    
    return $grInfo;
    
}

?>