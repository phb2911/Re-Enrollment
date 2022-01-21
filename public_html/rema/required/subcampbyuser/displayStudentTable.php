<?php

function displayStudentTable($situation, &$stdArr){
    
    if ($situation == 0) $title = 'Alunos';
    elseif ($situation == 1) $title = 'Evadidos';

?>
            <br/>
            <table class="list">
                <tr style="background-color: #61269e; color: #ffffff;">
                    <td style="width: 25%;"><?php echo $title; ?></td>
                    <td style="width: 15%;">Turma</td>
                    <td style="width: 9%;">Unidade</td>
                    <td style="width: 12%; text-align: center;">Status</td>
                    <td style="width: 15%;">Motivo</td>
                    <td style="width: 24%;">Observações</td>
                    <td><img src="<?php echo IMAGE_DIR; ?>flag.png"></td>
                </tr>
<?php

            $bgcolor = null;

            foreach ($stdArr as $stdInfo){

                switch (intval($stdInfo['Status'], 10)) {
                        case 0:
                            $st = 'Não Contatado';
                            $stBg = 'yellow';
                            break;
                        case 1:
                            $st = 'Contatado';
                            $stBg = 'orange';
                            break;
                        case 2:
                            $st = 'Não Volta';
                            $stBg = 'red';
                            break;
                        default:
                            $st = 'Rematriculado';
                            $stBg = 'green';
                    }

                    $bgcolor = ($bgcolor == '#c6c6c6') ? '#ffffff' : '#c6c6c6';

                    echo '<tr style="background-color: ' . $bgcolor . ';">' . PHP_EOL .
                            '<td><a href="student.php?sid=' . $stdInfo['ID'] . '">' . htmlentities($stdInfo['Name'], 0, 'ISO-8859-1') . '</a></td>' . PHP_EOL .
                            '<td><a href="class.php?clsid=' . $stdInfo['ClassID'] . '">' . htmlentities($stdInfo['ClassName'], 0, 'ISO-8859-1') . '</a></td>' . PHP_EOL .
                            '<td>' . htmlentities($stdInfo['School'], 0, 'ISO-8859-1') . '</td>' . PHP_EOL .
                            '<td style="text-align: center;"><div style="background-color: ' . $stBg . ';">' . $st . '</div></td>' . PHP_EOL .
                            '<td>' . htmlentities($stdInfo['Reason'], 0, 'ISO-8859-1') . '</td>' . PHP_EOL .
                            '<td>' . htmlentities($stdInfo['Notes'], 0, 'ISO-8859-1') . '</td>' . PHP_EOL .
                            '<td>' . (!!$stdInfo['Flagged'] ? '<a href="flagstd.php?sid=' . $stdInfo['ID'] . '"><img src="' . IMAGE_DIR . 'flag.png"/></a>' : '') . '</td>' . PHP_EOL .
                            '</tr>' . PHP_EOL;

            }
        
?>
            </table>    
<?php
    
}

?>