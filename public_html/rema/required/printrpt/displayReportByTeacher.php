<?php

function displayReportByTeacher(&$db, $cid, $campName){
    
    // $units[ID] = Name
    $units = array();
    // $teachers[ID] = Name;
    $teachers = array();
    
    // retrieve units
    // only the units that contain classes in the specified unit are retrieved
    $result = $db->query("SELECT schools.ID, schools.Name FROM classes JOIN schools ON classes.School = schools.ID WHERE classes.Campaign = $cid GROUP BY classes.School");
    
    while ($row = $result->fetch_assoc()){
        $units[$row['ID']] = $row['Name'];
    }
    
    $result->close();
    
    if (count($units)){
        
        // fetch teachers
        $result = $db->query("SELECT users.ID, users.Name FROM classes JOIN users ON classes.User = users.ID WHERE classes.Campaign = $cid GROUP BY ID ORDER BY Name");

        while ($row = $result->fetch_assoc()){
            $teachers[$row['ID']] = $row['Name'];
        }

        $result->close();
                
    }
    else {
        echo '<br/><br/><span style="font-style: italic; color: red;">Não há unidades ativas.</span>' . PHP_EOL;
    }
    
    if (count($teachers)){
        
?>
        <br/>
        <br/>
        <table class="headder" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 100%;">
                    <span style="font-weight: bold; font-size: 21px;">Relatório Por Professor</span>
                    <br/>Campanha: <span style="font-weight: bold;"><?php echo $campName; ?></span>
                    <br/><span style="font-style: italic; font-size: 13px;">Relatório gerado em: <?php echo date("d/m/Y H:i:s"); ?></span>
                </td>
                <td class="noprint" style="vertical-align: bottom;"><img src="<?php echo IMAGE_DIR; ?>printer.png" style="vertical-align: bottom; cursor: pointer;" title="Imprimir" onclick="window.print();"/></td>
            </tr>
        </table>
        <div style="font-size: 13px; font-style: italic; padding-top: 10px;">* Nos calculos a baixo, apenas os alunos ativos e evadidos são considerados.</div>
<?php
        
        $brFlag = false;

        foreach ($teachers as $teacherId => $teacherName){
            
            if ($brFlag) echo '<br/>' . PHP_EOL;
            
            $brFlag = true;
            
?>
        <table class="main" style="page-break-inside: avoid;">
            <tr style="background-color: #d1d1d1; font-weight: bold;">
                <td style="width: 40%; border-left: #404040 solid 1px;"><?php echo htmlentities($teacherName, 0, 'ISO-8859-1'); ?></td>
                <td style="width: 10%; text-align: center;">Alunos</td>
                <td style="width: 10%; text-align: center;">Rema</td>
                <td style="width: 10%; text-align: center;">%</td>
                <td style="width: 10%; text-align: center;">Semestrais</td>
                <td style="width: 10%; text-align: center;">Rema (sem.)</td>
                <td style="width: 10%; text-align: center; border-right: #404040 solid 1px;">% (sem.)</td>
            </tr>
<?php

            $genTotal = 0;
            $genEnrolled = 0;
            $totalYrCont = 0;
            
            foreach ($units as $uid => $unitName) {
                
                $row = $db->query("CALL spGetTeacherNumbers($teacherId, $cid, $uid)")->fetch_assoc();
                
                // get numbers and conver to integers
                $total = intval($row['Total'], 10);
                $enrolled = intval($row['Enrolled'], 10);
                $yrCont = intval($row['YearlyContract'], 10);
                
                // clear $db's stored results
                clearStoredResults($db);
                
                // calculate semestral contracts
                $semCont = $total - $yrCont;
                $enrSemCont = $enrolled - $yrCont;

                // calculate percerntage
                $percentage = $total > 0 ? +number_format(($enrolled * 100) / $total, 2) : 0;
                $percYrCont = $semCont > 0 ? +number_format(($enrSemCont * 100) / $semCont, 2) : 0;

                // add to totals
                $genTotal += $total;
                $genEnrolled += $enrolled;
                $totalYrCont += $yrCont;
                
?>
            <tr>
                <td style="border-left: #404040 solid 1px;"><?php echo htmlentities($unitName, 0, 'ISO-8859-1'); ?></td>
                <td style="text-align: center;"><?php echo $total; ?></td>
                <td style="text-align: center;"><?php echo $enrolled; ?></td>
                <td style="text-align: center;"><?php echo $percentage; ?>%</td>
                <td style="text-align: center;"><?php echo $semCont; ?></td>
                <td style="text-align: center;"><?php echo $enrSemCont; ?></td>
                <td style="text-align: center; border-right: #404040 solid 1px;"><?php echo $percYrCont; ?>%</td>
            </tr>
<?php
            }
            
            // caculate the general percentage
            $genPercentage = $genTotal > 0 ? +number_format(($genEnrolled * 100) / $genTotal, 2) : 0;
            $percYrContTotal = ($genTotal - $totalYrCont) > 0 ? +number_format((($genEnrolled - $totalYrCont) * 100) / ($genTotal - $totalYrCont), 2) : 0;
            
?>
            <tr style="background-color: #d1d1d1; font-weight: bold;">
                <td style="border-left: #404040 solid 1px;">Total</td>
                <td style="text-align: center;"><?php echo $genTotal; ?></td>
                <td style="text-align: center;"><?php echo $genEnrolled; ?></td>
                <td style="text-align: center;"><?php echo $genPercentage; ?>%</td>
                <td style="text-align: center;"><?php echo ($genTotal - $totalYrCont); ?></td>
                <td style="text-align: center;"><?php echo ($genEnrolled - $totalYrCont); ?></td>
                <td style="text-align: center; border-right: #404040 solid 1px;"><?php echo $percYrContTotal; ?>%</td>
            </tr>
        </table>
<?php
        }
?>
        <div class="noprint" style="text-align: right;">
            <br/>
            <img src="<?php echo IMAGE_DIR; ?>printer.png" style="cursor: pointer;" title="Imprimir" onclick="window.print();"/>
        </div>
        
        <p class="noprint">&nbsp;</p>    
<?php
    }
    else {
        echo '<br/><br/><span style="font-style: italic; color: red;">Não há professores participando da campanha ' . $campName . '.</span>' . PHP_EOL;
    }
    
}

?>