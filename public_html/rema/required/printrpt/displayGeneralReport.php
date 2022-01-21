<?php

function displayGeneralReport(&$db, $cid, $campName){
    
    $q = "SELECT classes.User AS ID, users.Name, EnrolledStudents(users.ID, $cid) AS Enrolled, "
            . "TotalStudents(users.ID, $cid) AS Total, PercentEnrolled(users.ID, $cid) AS Percentage, "
            . "SemesterContractCount(users.ID, $cid, 0) AS SemesterContracts, SemesterContractCount(users.ID, $cid, 1) AS SemContEnrolled, "
            . "SemContEnrPercent(users.ID, $cid) AS SemContEnrPerc FROM classes "
            . "JOIN users ON classes.User = users.ID WHERE classes.Campaign = $cid GROUP BY ID";
    
    $sort = getGet('s');
    
    // sort order
    if (isNum($sort) && $sort >= 1 && $sort <= 13){
        if ($sort == 1){
            $q .= " ORDER BY Name DESC, Total DESC";
        }
        elseif ($sort == 2){
            $q .= " ORDER BY Total DESC, Name";
        }
        elseif ($sort == 3){
            $q .= " ORDER BY Total, Name";
        }
        elseif ($sort == 4){
            $q .= " ORDER BY Enrolled DESC, Total DESC";
        }
        elseif ($sort == 5){
            $q .= " ORDER BY Enrolled, Total";
        }
        elseif ($sort == 6){
            $q .= " ORDER BY Percentage DESC, Total DESC";
        }
        elseif ($sort == 7){
            $q .= " ORDER BY Percentage, Total";
        }
        elseif ($sort == 8){
            $q .= " ORDER BY SemesterContracts DESC, Name";
        }
        elseif ($sort == 9){
            $q .= " ORDER BY SemesterContracts, Name";
        }
        elseif ($sort == 10){
            $q .= " ORDER BY SemContEnrolled DESC, SemesterContracts DESC";
        }
        elseif ($sort == 11){
            $q .= " ORDER BY SemContEnrolled, SemesterContracts";
        }
        elseif ($sort == 12){
            $q .= " ORDER BY SemContEnrPerc DESC, SemesterContracts DESC";
        }
        elseif ($sort == 13){
            $q .= " ORDER BY SemContEnrPerc, SemesterContracts";
        }
        
    }
    else {
        $q .= " ORDER BY Name, Total DESC";
    }
    
    $result = $db->query($q);
    
    if ($result->num_rows){
?>
        <br/>
        <br/>
        <table class="headder" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 100%;">
                    <span style="font-weight: bold; font-size: 21px;">Relatório Geral</span>
                    <br/>Campanha: <span style="font-weight: bold;"><?php echo $campName; ?></span>
                    <br/><span style="font-style: italic; font-size: 13px;">Relatório gerado em: <?php echo date("d/m/Y H:i:s"); ?></span>
                </td>
                <td class="noprint" style="vertical-align: bottom;"><img src="<?php echo IMAGE_DIR; ?>printer.png" style="vertical-align: bottom; cursor: pointer;" title="Imprimir" onclick="window.print();"/></td>
            </tr>
        </table>
        <br/>
        <table class="main">
            <tr style="background-color: #d1d1d1; font-weight: bold;">
                <td style="width: 40%; border-left: #404040 solid 1px;">Professor</td>
                <td style="width: 10%; text-align: right; padding-right: 20px;">Alunos</td>
                <td style="width: 10%; text-align: right; padding-right: 20px;">Rema</td>
                <td style="width: 10%; text-align: right; padding-right: 20px;">%</td>
                <td style="width: 10%; text-align: right; padding-right: 20px;">Semestral</td>
                <td style="width: 10%; text-align: right; padding-right: 20px;">Rema (sem.)</td>
                <td style="width: 10%; text-align: right; padding-right: 20px; border-right: #404040 solid 1px;">% (sem.)</td>
            </tr>
<?php

        $genTotal = 0;
        $genEnrolled = 0;
        $genSemCont = 0;
        $genSemContEnr = 0;

        while ($row = $result->fetch_assoc()) {
            
            $total = intval($row['Total'], 10);
            $enrolled = intval($row['Enrolled'], 10);
            $semCont = intval($row['SemesterContracts'], 10);
            $semContEnr = intval($row['SemContEnrolled'], 10);
            
            echo '<tr><td style="border-left: #404040 solid 1px;">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</td>' . PHP_EOL .
                '<td style="text-align: right; padding-right: 20px;">' . $total . '</td>' . PHP_EOL .
                '<td style="text-align: right; padding-right: 20px;">' . $enrolled . '</td>' . PHP_EOL .
                '<td style="text-align: right; padding-right: 20px;">' . +$row['Percentage'] . '%</td>' . PHP_EOL .
                '<td style="text-align: right; padding-right: 20px;">' . $semCont . '</td>' . PHP_EOL .
                '<td style="text-align: right; padding-right: 20px;">' . $semContEnr . '</td>' . PHP_EOL .
                '<td style="text-align: right; padding-right: 20px; border-right: #404040 solid 1px;">' . +$row['SemContEnrPerc'] . '%</td></tr>' . PHP_EOL;
            
            $genTotal += $total;
            $genEnrolled += $enrolled;
            $genSemCont += $semCont;
            $genSemContEnr += $semContEnr;
            
        }
        
        $res = !!$genTotal ? ($genEnrolled * 100) / $genTotal : 0;
        $res2 = !!$genSemCont ? ($genSemContEnr * 100) / $genSemCont : 0;

?>
            <tr style="background-color: #d1d1d1; font-weight: bold;">
                <td style="border-left: #404040 solid 1px;">Total</td>
                <td style="text-align: right; padding-right: 20px;"><?php echo $genTotal; ?></td>
                <td style="text-align: right; padding-right: 20px;"><?php echo $genEnrolled; ?></td>
                <td style="text-align: right; padding-right: 20px;"><?php echo +number_format($res, 2); ?>%</td>
                <td style="text-align: right; padding-right: 20px;"><?php echo $genSemCont; ?></td>
                <td style="text-align: right; padding-right: 20px;"><?php echo $genSemContEnr; ?></td>
                <td style="text-align: right; padding-right: 20px; border-right: #404040 solid 1px;"><?php echo +number_format($res2, 2); ?>%</td>
            </tr>
        </table>
        
        <div class="noprint" style="text-align: right;">
            <br/>
            <img src="<?php echo IMAGE_DIR; ?>printer.png" style="cursor: pointer;" title="Imprimir" onclick="window.print();"/>
        </div>
        
        <p class="noprint">&nbsp;</p>
        
<?php
    }
    else {
        echo '<br/><br/><span style="font-style: italic; color: red;">A campanha de rematrícula ' . $campName . ' não contém alunos.</span>';
    }
    
    $result->close();
    
}

?>