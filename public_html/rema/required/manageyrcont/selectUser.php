<?php

function selectUser(&$db, $campName){
    
?>
        <br/>
        <div class="panel" style="width: 500px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Gerenciamento de Contratos Anuais</span>
            <hr/>
            
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $campName; ?></td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Selecione o colaborador:</td>
                    <td style="width: 100%;">
                        <select id="selUser" name="uid" style="width: 250px;" onchange="this.styleOption(); if (this.selectedIndex > 0) reloadPage(this.selectedValue());" onkeyup="this.styleOption(); if (this.selectedIndex > 0) reloadPage(this.selectedValue());">
                            <option value="0" style="font-style: italic;">- Todos -</option>
<?php

    // only students who are active and who are teachers
    $result = $db->query("SELECT users.ID, users.Name FROM users WHERE users.Blocked = 0 AND Status < 2 GROUP BY ID ORDER BY Name");
    
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