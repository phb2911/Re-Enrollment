<?php

function selectUser(&$db, $campName){
    
?>
        <br/>
        <div class="panel" style="width: 500px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Gerenciamento de Turmas</span>
            <hr/>
            
            <form id="frmSelectUser" method="post" action="manageclasses.php">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $campName; ?></td>
                </tr>
                <tr>
                    <td style="white-space: nowrap; text-align: right;">Selecione o colaborador:</td>
                    <td style="width: 100%;">
                        <select id="selUser" name="uid" style="width: 250px;">
                            <option value="0">- Todos -</option>
<?php

    // only students who are active and who are teachers
    $result = $db->query("SELECT users.ID, users.Name FROM users WHERE users.Blocked = 0 AND Status < 2 GROUP BY ID ORDER BY Name");
    
    while ($row = $result->fetch_assoc()){
        echo '<option value="' . $row['ID'] . '">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
    }
    
    $result->close();

?>
                        </select>
                        <img src="<?php echo IMAGE_DIR; ?>ok.png" style="cursor: pointer; vertical-align: middle;" onclick="element('frmSelectUser').submit();"/>
                    </td>
                </tr>
            </table>
            </form>
            
        </div>
            
<?php

}

?>