<?php

function displayPpyReport(&$db, $cid, $campName){
    
?>
        <br/>
        <br/>
        <table class="headder" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 100%;">
                    <span style="font-weight: bold; font-size: 21px;">Relatório de Resultados do PPY</span>
                    <br/>Campanha: <span style="font-weight: bold;"><?php echo $campName; ?></span>
                    <br/><span style="font-style: italic; font-size: 13px;">Relatório gerado em: <?php echo date("d/m/Y H:i:s"); ?></span>
                </td>
                <td class="noprint" style="vertical-align: bottom;"><img src="<?php echo IMAGE_DIR; ?>printer.png" style="vertical-align: bottom; cursor: pointer;" title="Imprimir" onclick="window.print();"/></td>
            </tr>
        </table>
<?php
    
    $q = "SELECT users.Name AS UserName, ppy_calc_index.CalcIndex, ppy.ID, ppy.User, ppy.Axis1Only, ppy.E1_Graduation, ppy.E1_IntExp, "
            . "ppy.E1_ProficiencyTest, ppy.E2_YILTS, ppy.E2_InovPedPrac, ppy.E2_RelSug, ppy.E2_CitizenshipCamp, ppy.E2_CulturalEvents, "
            . "ppy.E2_NumCls, ppy.E2_ReferredStudent, ppy.E2_ContribToPedMeeting, ppy.E2_OtherEnv, ppy.E3_Rema, ppy.E3_AvStds, "
            . "ppy.MissedMeeting, ppy.PartialMeeting, ppy.LateRollCall, ppy.LateReportCards, ppy.MsgNotSent, ppy.MissClass FROM ppy "
            . "JOIN users ON ppy.User = users.ID JOIN ppy_calc_index ON ppy.Campaign = ppy_calc_index.Campaign WHERE ppy.Campaign = $cid "
            . "ORDER BY UserName";

    $result = $db->query($q);
    
    if ($result->num_rows){
?>
        <br/>
        <table class="main" style="border: black solid 1px;">
                <tr style="background-color: #d1d1d1; font-weight: bold;">
                    <td style="width: 80%;">Professor</td>
                    <td style="width: 80%; text-align: right; padding-right: 20px;">&nbsp;</td>
                </tr>
<?php

        $grandTotal = 0;

        while ($row = $result->fetch_assoc()){
            
            // calculate value
            $ax1 = 0;
            $ax2 = 0;
            $ax3 = 0;
            
            if ($row['E1_Graduation'] == 1 || $row['E1_Graduation'] == 3) $ax1 += 15;
            elseif ($row['E1_Graduation'] == 2) $ax1 += 5;
            
            $ax1 += ($row['E1_IntExp'] * 5) + ($row['E1_ProficiencyTest'] * 5);
            
            if ($ax1 > 30) $ax1 = 30;
            
            if (!$row['Axis1Only']){
                
                $ax2 = ($row['E2_YILTS'] ? 10 : 0) + ($row['E2_CitizenshipCamp'] ? 5 : 0) + ($row['E2_CulturalEvents'] ? 5 : 0) +
                        ($row['E2_InovPedPrac'] * 2) + $row['E2_RelSug'] + ($row['E2_ReferredStudent'] * 3) + $row['E2_ContribToPedMeeting'] + 
                        $row['E2_OtherEnv'];
                
                if ($row['E2_NumCls'] == 5) $ax2 += 6;
                elseif ($row['E2_NumCls'] == 6 || $row['E2_NumCls'] == 7) $ax2 += 10;
                elseif ($row['E2_NumCls'] >= 8) $ax2 += 15;
                
                if ($ax2 > 30) $ax2 = 30;
                
                if ($row['E3_Rema'] == 1){
                    $ax3 += 5;
                }
                elseif ($row['E3_Rema'] == 2){
                    $ax3 += 10;
                }
                elseif ($row['E3_Rema'] == 3){
                    $ax3 += 20;
                }
                elseif ($row['E3_Rema'] == 4){
                    $ax3 += 30;
                }
                
                if ($row['E3_AvStds'] == 1){
                    $ax3 += 5;
                }
                elseif ($row['E3_AvStds'] == 2){
                    $ax3 += 15;
                }
                elseif ($row['E3_AvStds'] == 3){
                    $ax3 += 20;
                }
                
                if ($ax3 > 40) $ax3 = 40;
                
            }
            
            $neg = ($row['MissedMeeting'] * 2) + ($row['PartialMeeting'] / 2) + ($row['LateRollCall'] / 2) + ($row['LateReportCards'] / 2) + 
                    ($row['MsgNotSent'] / 2) + ($row['MissClass'] * 2);
            
            $gen = $ax1 + $ax2 + $ax3 - $neg;
            
            if ($gen <= 0){
                $total = 0;
            }
            else {
                $total = $gen * $row['CalcIndex'] * $row['E2_NumCls'];
            }
            
            $grandTotal += $total;
            
?>
                <tr>
                    <td><?php echo htmlentities($row['UserName'], 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: right; padding-right: 20px;">$<?php echo number_format($total, 2, ',', ''); ?></td>
                </tr>
        <?php } ?>
                <tr style="background-color: #d1d1d1; font-weight: bold;">
                    <td>Total</td>
                    <td style="text-align: right; padding-right: 20px;">$<?php echo number_format($grandTotal, 2, ',', ''); ?></td>
                </tr>
            </table>
<?php
    }
    else {
        echo '<div style="color: red; font-style: italic; padding: 10px;">Nenhum PPY foi calculado e salvo nesta campanha.</div>';
    }
    
    $result->close();

}

?>