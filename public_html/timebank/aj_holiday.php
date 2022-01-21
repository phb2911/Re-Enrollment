<?php

require_once '../genreq/ajax.php';
require_once '../genreq/genreq.php';

header('Content-type: text/html; charset=iso-8859-1');

$db = mysqliConnObj('latin1');

if ($db->connect_errno > 0) die();

$year = getPost('year');
$month = getPost('month');
$day = getPost('day');

$monthLength = array(31, ($year % 400 == 0 || ($year % 100 != 0 && $year % 4 == 0) ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

if (isNum($year) && $year >= 2000 && $year <= 2099 && isNum($month) && $month > 0 && $month <= 12 && isNum($day) && $day > 0 && $day <= $monthLength[$month - 1]){
    
    $editFlag = false;
    $isOfficial = false;
    $desc = null;
    
    if ($holInfo = $db->query("SELECT * FROM tb_holidays WHERE `Date` = '$year-$month-$day'")->fetch_assoc()){
        
        $desc = $holInfo['Description'];
        $isOfficial = !!$holInfo['Official'];
        
        $editFlag = true;
        
    }
    
?>
            <table class="tbl" style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Data:</td>
                    <td style="width: 100%; font-weight: bold;"><?php echo (strlen($day) == 1 ? '0' . $day : $day) . '/' . (strlen($month) == 1 ? '0' . $month : $month) . '/' . $year; ?></td>
                </tr>                
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Descrição:</td>
                    <td style="width: 100%;">
                        <input type="text" id="txtDesc" name="desc" value="<?php echo htmlentities($desc, 3, 'ISO-8859-1'); ?>" style="width: 300px;" maxlength="55" />
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Tipo:</td>
                    <td style="width: 100%;">
                        <select id="selType" name="type">
                            <option value="1"<?php if (!$editFlag || $isOfficial) echo ' selected="selected"'; ?>>Oficial</option>
                            <option value="0"<?php if ($editFlag && !$isOfficial) echo ' selected="selected"'; ?>>Folga</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <button type="submit" name="save" value="1" style="padding: 2px; width: 90px;" onclick="return validateInput();"><img src="../images/disk2.png" style="vertical-align: middle;"/> Salvar</button>
                        <?php
                        
                        if ($editFlag){
                            echo '<button type="submit" name="delete" value="1" style="padding: 2px; width: 90px;" onclick="return confirm(\'Tem certeza que deseja remover o feriado?\');"><img src="../images/recycle2.png" style="vertical-align: middle;"/> Remover</button>';
                        }
                        
                        ?>
                        <button type="button" style="padding: 2px; width: 90px;" onclick="hideBox();"><img src="../images/cancel2.png" style="vertical-align: middle;"/> Cancelar</button>
                    </td>
                </tr>
            </table>
            <input type="hidden" name="date" value="<?php echo "$day/$month/$year"; ?>"/>
<?php
    
}
else echo '<div style="font-style: italic; color: red; text-align: center;">Parametros inválidos.</div>';

$db->close();

?>