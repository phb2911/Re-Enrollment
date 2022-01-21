<?php

function selectCamp(&$db){
    
?>
        <br/>
        <div class="panel" style="width: 100%; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Campanha por Colaborador</span>
            <hr/>
            <form id="frmChangeUsr" action="campbyuser.php" method="get">
            <table>
                <tr>
                    <td style="text-align: right;">Colaborador:</td>
                    <td style="width: 100%;">
                        <select id="selUsr" name="uid" style="width: 250px;" onchange="this.styleOption(); if (this.selectedIndex > 0) element('frmChangeUsr').submit();" onkeyup="this.styleOption();">
                            <option value="0" style="font-style: italic;">- Selecione -</option>
<?php

$result = $db->query("SELECT ID, Name FROM users WHERE Blocked = 0 ORDER BY Name");

while ($row = $result->fetch_assoc()){
    
    echo '<option value="' . $row['ID'] . '" style="font-style: normal;">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
    
}

$result->close();

?>
                        </select>
                    </td>
                </tr>
            </table>
            </form>
            
        </div>
<?php
    
}

?>