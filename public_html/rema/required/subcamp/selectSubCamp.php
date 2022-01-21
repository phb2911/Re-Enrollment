<?php

function selectSubCamp(&$db, $cInfo){
    
    // select subcampains
    $result = $db->query("SELECT ID, Name FROM subcamps WHERE Parent = " . $cInfo['ID']);
    
    // $subCamps[ID] = Name
    $subCamps = array();
    
    while ($row = $result->fetch_assoc()){
        $subCamps[$row['ID']] = $row['Name'];
    }
    
    $result->close();
    
    $noSubcamps = !count($subCamps);
    
?>
        <div class="panel" style="width: 600px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Selecione a Subcampanha</span>
            <hr/>
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $cInfo['Name']; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Subcampanha:</td>
                    <td style="width: 100%;">
                        <select id="selSubCamp" style="width: 250px;"<?php if ($noSubcamps) echo ' disabled="disabled"'; ?> onchange="this.styleOption(); showSubCamp(this);" onkeyup="this.styleOption(); showSubCamp(this);">
                            <option value="0" style="font-style: italic;">- Selecione -</option>
<?php

    foreach ($subCamps as $scId => $scName) {
        echo '<option value="' . $scId . '" style="font-style: normal;">' . htmlentities($scName, 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
    }

?>
                        </select>
                    </td>
                </tr>
<?php if ($noSubcamps){ ?>
                <tr>
                    <td style="color: red; font-style: italic;" colspan="2">* Esta campanha não possue subcampanhas.</td>
                </tr>
<?php } ?>
            </table>
        </div>
<?php
    
}

?>