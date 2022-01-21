<?php

require_once __DIR__ . '/../../../genreq/genreq.php';

function displayCampaign(&$db, $uid, $usrName, $campInfo){
    
    $cid = $campInfo['ID'];
    $campName = $campInfo['Name'];
    $campIsOpen = !!$campInfo['Open'];
    
    /* declare two-dimensional count array
     * [0][0] - active - not contacted
     * [0][1] - active - contacted
     * [0][2] - active - not coming back
     * [0][3] - active - enrolled
     * [1][0] - dropout - not contacted
     * [1][1] - dropout - contacted
     * [1][2] - dropout - not coming back
     * [1][3] - dropout - enrolled
     * [2][0] - cancelled - not contacted
     * [2][1] - cancelled - contacted
     * [2][2] - cancelled - not coming back
     * [2][3] - cancelled - enrolled
     * [3][0] - finished - not contacted
     * [3][1] - finished - contacted
     * [3][2] - finished - not coming back
     * [3][3] - finished - enrolled 
     */
    $countArr = array();
    
    // student array
    // [0] - Active
    // [1] - Dropout
    // [2] - Cancelled
    // [3] - finished
    $stdArr = array();
    
    for ($i = 0; $i <= 3; $i++){
        $countArr[$i] = array(0,0,0,0);
        $stdArr[$i] = array();
    }
    
    // this array will be used to keep track
    // of the number of classes per teacher
    $classIDs = array();
    
    // record the number of yearly contracts
    $yrCont = 0;
    
    // retrieve data
    $result = $db->query("SELECT students.ID, students.Name, students.Situation, students.Status, students.Notes, students.Flagged, students.YearlyContract, "
            . "classes.Name AS ClassName, classes.ID as ClassID, (SELECT Name FROM schools WHERE ID = classes.School) AS School, "
            . "(SELECT reasons.Description FROM reasons WHERE reasons.ID = students.Reason) AS ReasonDescription FROM students "
            . "LEFT JOIN classes ON students.Class = classes.ID WHERE classes.User = $uid AND classes.Campaign = $cid ORDER BY School, ClassName, students.Name");

    while ($row = $result->fetch_assoc()){
        
        $sit = intval($row['Situation'], 10);
        $sta = intval($row['Status'], 10);
        
        // increment count array
        $countArr[$sit][$sta]++;
        
        // populate student arrays according to situation
        $stdArr[$sit][] = $row;
        
        // store class id if not in array
        if (!in_array($row['ClassID'], $classIDs)){
            $classIDs[] = $row['ClassID'];
        }
        
        if (!!$row['YearlyContract']) $yrCont++;
        
    }

    $result->close();
    
    $stdNum = count($stdArr[0]); // student count
    $doNum = count($stdArr[1]); // dropout count
    $cancNum = count($stdArr[2]); // cancelled count
    $finNum = count($stdArr[3]); // finished count
    $clsCount = count($classIDs); // class count

    $stdEnrTotal = $stdNum + $doNum; // total of reenrollable students students
    
    $enrSemCont = $stdEnrTotal - $yrCont; // total of reenrollable semstral contracts
    
    $stdTotal = $stdEnrTotal + $cancNum + $finNum; // total of students
    
    $contacted = 0; // total of contacted an enrolled
    $notCont = 0; // total of students not contacted
    $notComeBack = 0; // total of student not coming back
    
    for ($i = 0; $i <= 3; $i++){
        $contacted += $countArr[$i][1];
        $notCont += $countArr[$i][0];
        $notComeBack += $countArr[$i][2];
    }
    
?>
        <br/>
        
        <div class="panel">
            
            <span style="font-weight: bold;">Campanha por Colaborador</span>
            <hr/>
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $campName; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Colaborador:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo htmlentities($usrName, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <button onclick="window.location = 'campbyuser.php';"><img src="<?php echo IMAGE_DIR; ?>back.png" style="width: 16px; height: 16px; vertical-align: bottom;"/> Modificar Colaborador</button>
<?php
    if ($stdTotal) {
?>
                        <button onclick="window.location = 'modstd.php?uid=<?php echo $uid; ?>';"><img src="<?php echo IMAGE_DIR; ?>people.png" style="vertical-align: bottom;"/> Alterar Status dos Alunos</button>
<?php 
    }
    
    if (!$campIsOpen){
        echo '<div style="font-style: italic; color: red; padding-top: 5px;">* Esta campanha está encerrada.</div>' . PHP_EOL;
    }
    
?>
                    </td>
                </tr>
            </table>
        </div>
<?php
    if ($stdTotal) {
?>
            <br/>
            <table style="border-collapse: collapse; width: 100%; box-shadow: 3px 3px 3px #808080;">
                <tr style="background-color: #ffffff;">
                    <td style="width: 60%; padding-left: 10px;">Número de Turmas</td>
                    <td id="tdTotalCls" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo $clsCount; ?></td>
                    <td style="text-align: right; padding-right: 20px; width: 20%;"></td>
                </tr>
                <tr style="background-color: #c6c6c6;">
                    <td style="width: 60%; padding-left: 10px;">Total de Alunos</td>
                    <td id="tdTotalStds" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo $stdTotal; ?></td>
                    <td style="text-align: right; padding-right: 20px; width: 20%;">100%</td>
                </tr>
                <tr style="background-color: #ffffff;">
                    <td style="width: 60%; padding-left: 10px;">Total de Alunos Rematriculáveis<sup style="font-style: italic; color: red; font-size: 10px;">1</sup></td>
                    <td id="tdEnrl" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo $stdEnrTotal; ?></td>
                    <td id="tdEnrlPrc" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo ($stdTotal > 0 ? +number_format((($stdEnrTotal * 100) / $stdTotal), 2) : 0) . '%'; ?></td>
                </tr>
                <tr style="background-color: #c6c6c6;">
                    <td style="width: 60%; padding-left: 10px;">Alunos Ativos</td>
                    <td id="tdStds" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo $stdNum; ?></td>
                    <td id="tdStdsPrc" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo ($stdTotal > 0 ? +number_format((($stdNum * 100) / $stdTotal), 2) : 0) . '%'; ?></td>
                </tr>
                <tr style="background-color: #ffffff;">
                    <td style="width: 60%; padding-left: 10px;">Evadidos</td>
                    <td id="tdDos" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo $doNum; ?></td>
                    <td id="tdDosPrc" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo ($stdTotal > 0 ? +number_format((($doNum * 100) / $stdTotal), 2) : 0) . '%'; ?></td>
                </tr>
                <tr style="background-color: #c6c6c6;">
                    <td style="width: 60%; padding-left: 10px;">Alunos Semestrais Rematriculáveis</td>
                    <td id="tdYrCont" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo $enrSemCont; ?></td>
                    <td id="tdYrContPrc" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo ($stdTotal > 0 ? +number_format((($enrSemCont * 100) / $stdTotal), 2) : 0) . '%'; ?></td>
                </tr>
                <tr style="background-color: #ffffff;">
                    <td style="width: 60%; padding-left: 10px;">Contatados</td>
                    <td id="tdCont" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo $contacted; ?></td>
                    <td id="tdContPrc" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo ($stdTotal > 0 ? +number_format((($contacted * 100) / $stdTotal), 2) : 0) . '%'; ?></td>
                </tr>
                <tr style="background-color: #c6c6c6;">
                    <td style="width: 60%; padding-left: 10px;">Não Contatados</td>
                    <td id="tdNotCont" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo $notCont; ?></td>
                    <td id="tdNotContPrc" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo ($stdTotal > 0 ? +number_format((($notCont * 100) / $stdTotal), 2) : 0) . '%'; ?></td>
                </tr>
                <tr style="background-color: #ffffff;">
                    <td style="width: 60%; padding-left: 10px;">Não Voltam</td>
                    <td id="tdWontBeBack" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo $notComeBack; ?></td>
                    <td id="tdWontBeBackPrc" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo ($stdTotal > 0 ? +number_format((($notComeBack * 100) / $stdTotal), 2) : 0) . '%'; ?></td>
                </tr>
                <tr style="background-color: #c6c6c6;">
                    <td style="width: 60%; padding-left: 10px;">Alunos Ativos Rematriculados<sup style="font-style: italic; color: red; font-size: 10px;">2</sup></td>
                    <td id="tdEnr" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo ($stdNum > 0 ? $countArr[0][3] : 0); ?></td>
                    <td id="tdEnrPrc" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo ($stdNum > 0 ? +number_format((($countArr[0][3] * 100) / $stdNum), 2) : 0) . '%'; ?></td>
                </tr>
                <tr style="background-color: #ffffff;">
                    <td style="width: 60%; padding-left: 10px;">Evadidos Rematriculados<sup style="font-style: italic; color: red; font-size: 10px;">3</sup></td>
                    <td id="tdDoEnr" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo ($doNum > 0 ? $countArr[1][3] : 0); ?></td>
                    <td id="tdDoEnrPrc" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo ($doNum > 0 ? +number_format((($countArr[1][3] * 100) / $doNum), 2) : 0) . '%'; ?></td>
                </tr>
                <tr style="background-color: #c6c6c6; color: red;">
                    <td style="width: 60%; padding-left: 10px;">Total de Semestrais Rematriculados<sup style="font-style: italic; color: red; font-size: 10px;">4</sup></td>
                    <td id="tdTotalSemEnr" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo ($countArr[0][3] + $countArr[1][3] - $yrCont); ?></td>
                    <td id="tdTotalEnrSemPrc" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo ($enrSemCont > 0 ? +number_format((($countArr[0][3] + $countArr[1][3] - $yrCont) * 100) / $enrSemCont, 2) : 0) . '%'; ?></td>
                </tr>
                <tr style="background-color: #ffffff; color: red;">
                    <td style="width: 60%; padding-left: 10px;">Total de Rematriculados<sup style="font-style: italic; color: red; font-size: 10px;">5</sup></td>
                    <td id="tdTotalEnr" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo ($countArr[0][3] + $countArr[1][3]); ?></td>
                    <td id="tdTotalEnrPrc" style="text-align: right; padding-right: 20px; width: 20%;"><?php echo ($stdEnrTotal > 0 ? +number_format((($countArr[0][3] + $countArr[1][3]) * 100) / $stdEnrTotal, 2) : 0) . '%'; ?></td>
                </tr>
            </table>
            <div style="font-style: italic; color: red; padding: 10px; font-size: 13px;">
                1. Alunos rematriculáveis corresponde ao número total de alunos menos os concluintes e cancelados.<br/>
                2. A porcentagem de alunos ativos rematriculados é calculada com base no número de alunos ativos.<br/>
                3. A porcentagem de evadidos rematriculados é calculada com base no número de alunos  evadidos.<br/>
                4. A porcentagem do total de alunos semestrais rematriculados é calculada com base no número de alunos semestrais rematriculáveis.<br/>
                5. O total de rematriculados é calculado com base no número de alunos rematriculáveis.<br/>
                &#10013; As demais porcentagens são calculadas com base no número total de alunos.
            </div>
            <div class="panel">
                <img src="<?php echo IMAGE_DIR; ?>info.png" /> Para fazer modificações, clique no ícone <img src="<?php echo IMAGE_DIR; ?>list.png" style=" width: 12px; height: 12px;"/> para exibir o menu do aluno.
            </div>
<?php 
        if ($doNum){
            displayStudentTable($stdArr, 1);
        }
        
        if ($cancNum){
            displayStudentTable($stdArr, 2);
        }
        
        if ($finNum){
            displayStudentTable($stdArr, 3);
        }
        
        if ($stdNum){
            displayStudentTable($stdArr, 0);
        }
        
    }
    else {
        echo '<br/><span style="font-style: italic; color: red;">Este colaborador não participou desta campanha.</sapn>' . PHP_EOL;
    }
    
}

?>