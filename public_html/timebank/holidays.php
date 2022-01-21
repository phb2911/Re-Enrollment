<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once 'dropdown/dropdown.php';
require_once '../genreq/genreq.php';

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

if (!$loginObj->isLoggedIn()){
    $db->close();
    header("Location: " . LOGIN_PAGE);
    die();
}

$isAdmin = $loginObj->isAdmin();

$year = getGet('yr');

$isValid = (isNum($year) && $year >= 2000 && $year <= 2099);

// fetch years that contain holliday
$result = $db->query("SELECT YEAR(`tb_holidays`.`Date`) AS Year FROM `tb_holidays` GROUP BY Year");

$yearArr = array();

while ($row = $result->fetch_row()){
    $yearArr[] = $row[0];
}

$result->close();

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>YCG Time Bank - Lista de Feriados</title>
    
    <link rel="icon" href="../images/favicon.ico" type="image/x-icon"/>
    <link href="dropdown/dropdown.css" rel="stylesheet" type="text/css" />
    <link href="css/gen.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="dropdown/dropdown.js"></script>
    <script type="text/javascript" src="../js/general.js"></script>
       
    <style type="text/css">
        
        td {
            padding: 5px;
        }
        
    </style>
    
    <script type="text/javascript">
    
        window.onload = function(){
            Dropdown.initialise();
        };
        
    </script>
    
</head>
<body>
    
    <div class="top"></div>
    
    <div class="main">
        
        <a href="."><img style="display: block;" src="../images/banner3<?php echo ($isAdmin ? 'admin' : ''); ?>.jpg"/></a>
        
<?php

renderDropDown($db, $isAdmin);

if ($isValid){
    showList($yearArr, $year);
}
else {
    selectYear($yearArr);
}

?>

    </div>
    
    <p>&nbsp;</p>
    
</body>
</html>
<?php

$db->close();

//----------------------------------------------------

function showList($yearArr, $year){
    
    global $db;
    
?>
        <br/>
        <div class="panel" style="width: 700px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Lista de Feriados</span>
            <hr/>
            
            <div style="padding: 10px;">
                Ano: &nbsp;
                <select id="selYear" onchange="window.location = 'holidays.php?yr=' + this.selectedValue();" onkeyup="window.location = 'holidays.php?yr=' + this.selectedValue();">
                    <?php

                    for ($i = 2000; $i <= 2099; $i++) {
                    
                        echo '<option value="' . $i . '"' . ($year == $i ? ' selected="selected"' : '') . (in_array($i, $yearArr) ? ' style="font-weight: bold;"' : '') .'>' . $i . '</option>' . PHP_EOL;
                                            
                    }

                    ?>
                </select>
            </div>
            
<?php

    $result = $db->query("SELECT * FROM `tb_holidays` WHERE DATE_FORMAT(`Date`, '%Y') = '$year' ORDER BY `Date`");
    
    if ($result->num_rows){
        
        $months = array('Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro');
        $curMon = 0;
        
        while ($row = $result->fetch_assoc()){
            
            $hid = $row['ID'];
            $desc = $row['Description'];
            $mon = date('n', strtotime($row['Date']));
            $day = date('d', strtotime($row['Date']));
            $official = !!$row['Official'];
            
            if ($curMon != $mon){
                
                // close old month if necessary
                if ($curMon != 0 ) echo '</table><br/>' . PHP_EOL;
                
                // start new month
?>
            <table style="width: 100%; border: black solid 1px;">
                <tr style="background-color: #1b9a87;">
                    <td colspan="4">
                        <a href="addholiday.php?yr=<?php echo $year . '&mon=' . ($mon - 1); ?>" style="font-weight: bold; color: white;"><?php echo $months[$mon - 1]; ?></a>
                    </td>
                </tr>
                <tr style="background-color: #1b9a87; color: white;">
                    <td style="width: 5%; text-align: center;">Dia</td>
                    <td style="width: 75%;">Descrição</td>
                    <td style="width: 10%; text-align: center;">Oficial</td>
                    <td style="width: 10%; text-align: center;">Folga</td>
                </tr>
<?php

                $curMon = $mon;
                $bgcolor = null;

            }
            
            $bgcolor = ($bgcolor == '#c6c6c6') ? '#e6e6e6' : '#c6c6c6';
            
?>
                <tr style="background-color: <?php echo $bgcolor; ?>;">
                    <td style="text-align: center;"><?php echo $day; ?></td>
                    <td><?php echo htmlentities($desc, 0, 'ISO-8859-1'); ?></td>
                    <td style="text-align: center;"><img src="../images/<?php echo ($official ? 'check3.png' : 'trans.png'); ?>" style="vertical-align: middle; width: 16px; height: 16px;"/></td>
                    <td style="text-align: center;"><img src="../images/<?php echo (!$official ? 'check3.png' : 'trans.png'); ?>" style="vertical-align: middle; width: 16px; height: 16px;"/></td>
                </tr>
<?php
            
        }
        
        echo '</table>' . PHP_EOL;
        
    }
    else {
        echo '<div style="padding: 10px; color: red; font-style: italic;">Não foram encontrados feriados em ' . $year . '.</div>' . PHP_EOL;
    }
    
    $result->close();

?>
            
        </div>
<?php
    
}

//----------------------------------------------------

function selectYear($yearArr){
?>
        <br/>
        <div class="panel" style="width: 700px; left: 0; right: 0; margin: auto;">
            
            <span style="font-weight: bold;">Lista de Feriados</span>
            <hr/>
            
            <table class="tbl" style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Selecione o ano:</td>
                    <td style="width: 100%;">
                        <select id="selYear">
                            <?php
                            
                            for ($i = 2000; $i <= 2099; $i++) {
                                echo '<option value="' . $i . '"' . (date('Y', time()) == $i ? ' selected="selected"' : '') . (in_array($i, $yearArr) ? ' style="font-weight: bold;"' : '') . '>' . $i . '</option>' . PHP_EOL;
                            }
                            
                            ?>
                        </select>
                        <input type="button" value="OK" onclick="window.location = 'holidays.php?yr=' + element('selYear').selectedValue();"/>
                    </td>
                </tr>
            </table>
            
        </div>
<?php
}

?>