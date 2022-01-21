<?php

require_once __DIR__ . '/fetchTeachersData.php';

function displayGroupInfo(&$db, $gid, $gname, $cid, $cName){
        
?>
        <div class="panel" style="width: 100%; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Detalhes do Grupo</span>
            <hr/>
            
            <table style="border-collapse: collapse; width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Campanha:</td>
                    <td style="font-weight: bold; font-style: italic; width: 100%;"><?php echo htmlentities($cName, 0, 'ISO-8859-1'); ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Grupo:</td>
                    <td style="font-weight: bold; font-style: italic; width: 100%;"><?php echo htmlentities($gname, 0, 'ISO-8859-1'); ?></td>
                    <td style="padding-right: 10px;"><img src="<?php echo getImagePath('print.png'); ?>" title="Versão para impressão" style="vertical-align: bottom; cursor: pointer;" onclick="window.open('printrept.php?t=12&cid=<?php echo $cid . '&gid=' . $gid; ?>', '_blank', 'toolbar=no,scrollbars=yes,resizable=yes,width=840,height=600');"/></td>
                </tr>
            </table>
            
            <div style="padding: 5px;">
                <table style="border-collapse: collapse; width: 100%; border: solid 1px #61269e;">
                    <tr style="background-color: #61269e; color: white;">
                        <td style="width: 40%;">Professor</td>
                        <td class="numbers">Alunos</td>
                        <td class="numbers">Rema</td>
                        <td class="numbers">%</td>
                        <td class="numbers">Semestrais</td>
                        <td class="numbers">Rema (sem.)</td>
                        <td class="numbers">% (sem.)</td>
                    </tr>
<?php
    
    $teachersData = fetchTeachersData($db, $gid);
    
    if (!empty($teachersData)){

        $bgcolor = null;
        $total = 0;
        $enrolled = 0;
        $semCont = 0;
        $semContEnr = 0;

        foreach ($teachersData as $tinfo) {

            $total += $tinfo['Total'];
            $enrolled += $tinfo['Enrolled'];
            $semCont += $tinfo['SemesterContracts'];
            $semContEnr += $tinfo['SemContEnrolled'];

            $bgcolor = ($bgcolor == "#c6c6c6" ? '#ffffff' : '#c6c6c6');
                
?>
                    <tr style="background-color: <?php echo $bgcolor; ?>;">
                        <td><?php echo htmlentities($tinfo['Name'], 0, 'ISO-8859-1'); ?></td>
                        <td class="numbers"><?php echo $tinfo['Total']; ?></td>
                        <td class="numbers"><?php echo $tinfo['Enrolled']; ?></td>
                        <td class="numbers"><?php echo +$tinfo['Percentage']; ?>%</td>
                        <td class="numbers"><?php echo $tinfo['SemesterContracts']; ?></td>
                        <td class="numbers"><?php echo $tinfo['SemContEnrolled']; ?></td>
                        <td class="numbers"><?php echo +$tinfo['SemContEnrPerc']; ?>%</td>
                    </tr>
<?php
            
        }

        $percent = ($total ? ($enrolled * 100) / $total : 0);
        $semContPer = ($semCont ? ($semContEnr * 100) / $semCont : 0);
            
?>
                    <tr style="background-color: #61269e; color: white;">
                        <td>Total</td>
                        <td class="numbers"><?php echo $total; ?></td>
                        <td class="numbers"><?php echo $enrolled; ?></td>
                        <td class="numbers"><?php echo +number_format($percent , 2); ?>%</td>
                        <td class="numbers"><?php echo $semCont; ?></td>
                        <td class="numbers"><?php echo $semContEnr; ?></td>
                        <td class="numbers"><?php echo +number_format($semContPer , 2); ?>%</td>
                    </tr>
<?php
    }
    else {
        echo '<tr><td style="color: red; font-style: italic;" colspan="7">Este grupo não possui professores.</td></tr>' . PHP_EOL;
    }
?>
                </table>
            </div>
            
        </div>
<?php
    
}

?>