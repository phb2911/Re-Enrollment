<?php

require_once __DIR__ . '/../group/fetchTeachersData.php';

function displaySingleGroupReport(&$db, $cid, $campName, $gid){
    
    if (isNum($gid) && $gname = $db->query("SELECT `Name` FROM `groups` WHERE `ID` = $gid AND `Campaign` = $cid")->fetch_row()[0]){
?>
        <br/>
        <br/>
        <table class="headder" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 100%;">
                    <span style="font-weight: bold; font-size: 21px;">Relatório - Detalhes do Grupo</span>
                    <br/>Campanha: <span style="font-weight: bold;"><?php echo $campName; ?></span>
                    <br/>Grupo: <span style="font-weight: bold;"><?php echo htmlentities($gname, 0, 'ISO-8859-1'); ?></span>
                    <br/><span style="font-style: italic; font-size: 13px;">Relatório gerado em: <?php echo date("d/m/Y H:i:s"); ?></span>
                </td>
                <td class="noprint" style="vertical-align: bottom;"><img src="<?php echo getImagePath('printer.png'); ?>" style="vertical-align: bottom; cursor: pointer;" title="Imprimir" onclick="window.print();"/></td>
            </tr>
        </table>
        <br/>
<?php

        $teachersData = fetchTeachersData($db, $gid);
        
        if (!empty($teachersData)){
?>
        <table class="main">
            <tr style="background-color: #d1d1d1; font-weight: bold;">
                <td style="width: 30%; border-left: #404040 solid 1px;">Professor</td>
                <td class="numbers">Alunos</td>
                <td class="numbers">Rema</td>
                <td class="numbers">%</td>
                <td class="numbers">Semestral</td>
                <td class="numbers">Rema (sem.)</td>
                <td class="numbers" style="border-right: #404040 solid 1px;">% (sem.)</td>
            </tr>
<?php
            
            $total = 0;
            $enrolled = 0;
            $semCont = 0;
            $semContEnr = 0;

            foreach ($teachersData as $tinfo) {
                
                $total += $tinfo['Total'];
                $enrolled += $tinfo['Enrolled'];
                $semCont += $tinfo['SemesterContracts'];
                $semContEnr += $tinfo['SemContEnrolled'];
                
?>
            <tr>
                <td style="width: 30%; border-left: #404040 solid 1px;"><?php echo htmlentities($tinfo['Name'], 0, 'ISO-8859-1'); ?></td>
                <td class="numbers"><?php echo $tinfo['Total']; ?></td>
                <td class="numbers"><?php echo $tinfo['Enrolled']; ?></td>
                <td class="numbers"><?php echo +$tinfo['Percentage']; ?>%</td>
                <td class="numbers"><?php echo $tinfo['SemesterContracts']; ?></td>
                <td class="numbers"><?php echo $tinfo['SemContEnrolled']; ?></td>
                <td class="numbers" style="border-right: #404040 solid 1px;"><?php echo +$tinfo['SemContEnrPerc']; ?>%</td>
            </tr>
<?php
            }
            
            $percent = ($total ? ($enrolled * 100) / $total : 0);
            $semContPer = ($semCont ? ($semContEnr * 100) / $semCont : 0);
            
?>
            <tr style="background-color: #d1d1d1; font-weight: bold;">
                <td style="width: 30%; border-left: #404040 solid 1px;">Total</td>
                <td class="numbers"><?php echo $total; ?></td>
                <td class="numbers"><?php echo $enrolled; ?></td>
                <td class="numbers"><?php echo +number_format($percent , 2); ?>%</td>
                <td class="numbers"><?php echo $semCont; ?></td>
                <td class="numbers"><?php echo $semContEnr; ?></td>
                <td class="numbers" style="border-right: #404040 solid 1px;"><?php echo +number_format($semContPer , 2); ?>%</td>
            </tr>
        </table>
<?php
        }
        else {
            echo '<div style="padding: 5px; color: red; font-style: italic;">O grupo não possui professores.</div>' . PHP_EOL;
        }

    }
    else {
        echo '<br/>&nbsp;<div style="padding: 5px; color: red; font-style: italic;">Parametros inválidos.</div>' . PHP_EOL;
    }
    
}

?>