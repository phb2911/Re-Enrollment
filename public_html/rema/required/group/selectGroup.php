<?php

function selectGroup(&$db, $cid, $cName){
    
?>
        <div class="panel" style="width: 600px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Selecione o Grupo</span>
            <hr/>
            
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold; font-style: italic;"><?php echo htmlentities($cName, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Selecione o Grupo:</td>
                    <td>
                        <select id="selSelGrp" style="width: 300px;" onchange="this.styleOption(); redir(this.selectedValue());" onkeyup="this.styleOption(); redir(this.selectedValue());">
                            <option value="0" style="font-style: italic;">- Selecione -</option>
<?php

    $result = $db->query("SELECT `ID`, `Name` FROM `groups` WHERE `Campaign` = $cid ORDER BY `Name`");
    
    while ($row = $result->fetch_assoc()){
        echo '<option value="' . $row['ID'] . '" style="font-style: normal;">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
    }
    
    $result->close();

?>
                        </select>
                    </td>
                </tr>
            </table>
            
        </div>
<?php
    
}

?>