<?php

function selectSubCampUsr(&$db, $isAdmin, $cInfo){
    
    // select subcampaigns
    $result = $db->query("SELECT ID, Name FROM  subcamps WHERE Parent = " . $cInfo['ID']);
    
    // $subCamps['ID'] = Name
    $subCamps = array();
    
    while ($row = $result->fetch_assoc()){
        $subCamps[$row['ID']] = $row['Name'];
    }
    
    $result->close();
    
?>
    
        <div class="panel">
            
            <span style="font-weight: bold;">Subcampanha por Colaborador</span>
            <hr/>
            <form method="post" action="subcampbyuser.php">
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right;">Campanha:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo $cInfo['Name']; ?></td>
                </tr>
<?php if (count($subCamps)){ ?>
                <tr>
                    <td style="text-align: right;">Subcampanha:</td>
                    <td style="width: 100%;">
                        <select id="selSubCamp" name="scampid" style="width: 250px;" onchange="styleSelectBox(this);" onkeyup="styleSelectBox(this);">
                            <option value="0" style="font-style: italic;">- Selecione -</option>
                            <?php
                            
                                foreach ($subCamps as $sID => $sName) {
                                    echo '<option value="' . $sID . '" style="font-style: normal;">' . htmlentities($sName, 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
                                }
                            
                            ?>
                        </select>
                        <img id="imgSubCampCir" src="<?php echo IMAGE_DIR; ?>circle_loader.gif" style="vertical-align: middle; visibility: hidden;"/>
                    </td>
                </tr>
<?php if ($isAdmin){ ?>
                <tr>
                    <td style="text-align: right;">Colaborador:</td>
                    <td style="width: 100%;">
                        <select id="selUser" name="uid" style="width: 250px;" onchange="styleSelectBox(this);" onkeyup="styleSelectBox(this);">
                            <option value="0" style="font-style: italic;">- Selecione -</option>
<?php

        $result = $db->query("SELECT ID, Name FROM users WHERE Blocked = 0 ORDER BY Name");
        
        while ($row = $result->fetch_assoc()){
            echo '<option value="' . $row['ID'] . '" style="font-style: normal;">' . htmlentities($row['Name'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
        }
        
        $result->close();

?>
                        </select>
                        <img id="imgSubCampCir" src="<?php echo IMAGE_DIR; ?>circle_loader.gif" style="vertical-align: middle; visibility: hidden;"/>
                    </td>
                </tr>
<?php } ?>
                <tr>
                    <td style="text-align: right;">&nbsp;</td>
                    <td style="width: 100%;"><input type="submit" value="Visualizar" onclick="return validateInput();"/></td>
                </tr>
<?php 

    }
    else {
        echo '<tr><td style="color: red; font-style: italic;" colspan="2">Não há subcampanhas nesta campanha.</td></tr>' . PHP_EOL;
    }

?>
            </table>
            </form>
        </div>
    
<?php
    
}

?>